<?php

namespace App\Domain\Translation;

use App\Jobs\TranslatePendingText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Translates a rendered HTML page into the visitor's language, text node by text node, so every
 * page (including database content and calculator results) follows the language button.
 *
 * Order of preference for each piece of text: the reviewed lang/{code}.json strings, then the
 * translation memory (site_translations, filled by AI or by staff). Text with no translation yet
 * stays in English and, when $learn is on, is queued for AI translation.
 *
 * The markup itself is never re-serialised: only text between tags and a few readable attributes
 * (placeholder, title, alt, aria-label, meta description) are replaced, so scripts, Alpine
 * attributes and CSP nonces are untouched. <script>, <style>, <svg>, <code>, <pre>, <textarea>
 * and any element marked translate="no" or class="notranslate" are skipped.
 */
class PageTranslator
{
    private const SKIP_TAGS = ['svg', 'textarea', 'code', 'pre', 'template', 'math', 'noscript'];

    private const VOID_TAGS = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];

    /** Most new strings queued from one page view (keeps a single request cheap). */
    private const MAX_NEW_PER_PAGE = 400;

    public static function hash(string $text): string
    {
        return sha1($text);
    }

    /** Collapses whitespace; returns null when the text is not worth translating. */
    public static function normalise(string $raw): ?string
    {
        $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($t === '' || mb_strlen($t) < 2 || mb_strlen($t) > 2000 || ! preg_match('/[A-Za-z]{2,}/', $t)) {
            return null;
        }
        if (preg_match('#^(https?://|www\.)\S+$#i', $t) || filter_var($t, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $t;
    }

    /**
     * @param  bool  $learn  queue unknown text for AI translation (only for pages without personal data)
     * @param  list<string>  $private  values typed by the visitor; text containing them is never queued
     */
    public function translate(string $html, string $locale, bool $learn = false, array $private = []): string
    {
        $tokens = preg_split('/(<script\b[^>]*>.*?<\/script\s*>|<style\b[^>]*>.*?<\/style\s*>|<!--.*?-->|<[a-zA-Z\/!](?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (! $tokens) {
            return $html;
        }

        // Pass 1: find every translatable piece and where it sits.
        $slots = [];   // token index => ['text' => [lead, core, trail]] or ['attrs' => [[name, raw, core]...]]
        $skip = null;  // [tag, depth] while inside a skipped element
        foreach ($tokens as $i => $tok) {
            if ($tok[0] === '<') {
                // Comments, doctype and whole <script>/<style> blocks are kept exactly as they are.
                if (str_starts_with($tok, '<!') || preg_match('#^<(script|style)\b#i', $tok) || ! preg_match('#^<(/?)([a-zA-Z][a-zA-Z0-9-]*)#', $tok, $m)) {
                    continue;
                }
                $closing = $m[1] === '/';
                $tag = strtolower($m[2]);
                $selfClosing = in_array($tag, self::VOID_TAGS, true) || str_ends_with($tok, '/>');
                if ($skip) {
                    if ($tag === $skip[0] && ! $selfClosing) {
                        $skip[1] += $closing ? -1 : 1;
                        if ($skip[1] === 0) {
                            $skip = null;
                        }
                    }

                    continue;
                }
                if ($closing) {
                    continue;
                }
                $noTranslate = preg_match('/\stranslate\s*=\s*["\']?no\b/i', $tok) || preg_match('/\sclass\s*=\s*"[^"]*\bnotranslate\b/i', $tok);
                if ((in_array($tag, self::SKIP_TAGS, true) || $noTranslate) && ! $selfClosing) {
                    $skip = [$tag, 1];

                    continue;
                }
                if ($noTranslate) {
                    continue;
                }
                $attrs = $this->attributes($tok, $tag);
                if ($attrs) {
                    $slots[$i] = ['attrs' => $attrs];
                }

                continue;
            }
            if ($skip) {
                continue;
            }
            $core = self::normalise($tok);
            if ($core !== null) {
                preg_match('/^(\s*).*?(\s*)$/su', $tok, $ws);
                $slots[$i] = ['text' => [$ws[1] ?? '', $core, $ws[2] ?? '']];
            }
        }
        if (! $slots) {
            return $html;
        }

        // Pass 2: look everything up at once.
        $wanted = [];
        foreach ($slots as $s) {
            if (isset($s['text'])) {
                $wanted[$s['text'][1]] = true;
            }
            foreach ($s['attrs'] ?? [] as $a) {
                $wanted[$a[2]] = true;
            }
        }
        $found = $this->lookup(array_keys($wanted), $locale, $learn, $private);

        // Pass 3: write the translations back.
        foreach ($slots as $i => $s) {
            if (isset($s['text'])) {
                [$lead, $core, $trail] = $s['text'];
                if (isset($found[$core])) {
                    $tokens[$i] = $lead.e($found[$core], false).$trail;
                }

                continue;
            }
            $tok = $tokens[$i];
            foreach ($s['attrs'] as [$name, $match, $core]) {
                if (isset($found[$core])) {
                    $tok = str_replace($match, $name.'="'.e($found[$core], false).'"', $tok);
                }
            }
            $tokens[$i] = $tok;
        }

        return implode('', $tokens);
    }

    /** @return list<array{0: string, 1: string, 2: string}> [attribute, whole name="value" match, normalised text] */
    private function attributes(string $tok, string $tag): array
    {
        $names = ['placeholder', 'title', 'alt', 'aria-label'];
        if ($tag === 'meta' && preg_match('/\s(?:name|property)="(description|og:title|og:description|og:image:alt|twitter:title|twitter:description)"/i', $tok)) {
            $names[] = 'content';
        }
        $out = [];
        if (preg_match_all('/(?<=\s)('.implode('|', array_map('preg_quote', $names)).')="([^"]*)"/i', $tok, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                if (($core = self::normalise($m[2])) !== null) {
                    $out[] = [$m[1], $m[0], $core];
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $texts
     * @param  list<string>  $private
     * @return array<string, string> English => translation
     */
    public function lookup(array $texts, string $locale, bool $learn = false, array $private = []): array
    {
        $translator = app('translator');
        $found = [];
        $rest = [];
        foreach ($texts as $t) {
            $json = $translator->get($t, [], $locale, false);
            if (is_string($json) && $json !== $t) {
                $found[$t] = $json;
            } else {
                $rest[self::hash($t)] = $t;
            }
        }
        if (! $rest) {
            return $found;
        }
        $known = [];
        foreach (array_chunk(array_keys($rest), 500) as $chunk) {
            foreach (DB::table('site_translations')->where('locale', $locale)->whereIn('hash', $chunk)->get(['hash', 'text', 'status']) as $row) {
                $known[$row->hash] = true;
                if ($row->status === 'done' && $row->text !== null && $row->text !== '') {
                    $found[$rest[$row->hash]] = $row->text;
                }
            }
        }
        if ($learn) {
            $this->queue(array_diff_key($rest, $known), $locale, $private);
        }

        return $found;
    }

    /**
     * Adds unknown text to the translation memory as "pending" and starts the AI job.
     *
     * @param  array<string, string>  $new  hash => English text
     * @param  list<string>  $private
     */
    public function queue(array $new, string $locale, array $private = []): int
    {
        $private = array_values(array_filter(array_map(fn ($p) => mb_strtolower(trim((string) $p)), $private), fn ($p) => mb_strlen($p) >= 3));
        $rows = [];
        $now = now();
        foreach ($new as $hash => $text) {
            $lower = mb_strtolower($text);
            foreach ($private as $p) {
                if (str_contains($lower, $p)) {
                    continue 2;
                }
            }
            $rows[] = ['locale' => $locale, 'hash' => $hash, 'source' => $text, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now];
            if (count($rows) >= self::MAX_NEW_PER_PAGE) {
                break;
            }
        }
        if (! $rows) {
            return 0;
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('site_translations')->insertOrIgnore($chunk);
        }
        self::kick($locale);

        return count($rows);
    }

    /**
     * Starts the AI translation job for a language (at most once every 30 seconds). Not on the
     * "sync" queue, where the job would hold up the visitor's page; use Admin › Languages there.
     */
    public static function kick(string $locale): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }
        if (app(SiteTranslations::class)->autoReady() && Cache::add('tr.kick.'.$locale, 1, 30)) {
            TranslatePendingText::dispatch($locale);
        }
    }
}
