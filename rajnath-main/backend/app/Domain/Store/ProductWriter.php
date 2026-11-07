<?php

namespace App\Domain\Store;

use App\Domain\Assistant\AiProviders;
use App\Domain\Content\RashifalWriter;
use RuntimeException;

/**
 * "Enhance with AI" in Admin › Gem store: rewrites a product page (summary, description,
 * highlights, benefits, how to wear, cautions, care, FAQs) from the product's own facts.
 * It only returns a draft; nothing is saved until a staff member reviews it and presses Save.
 */
class ProductWriter
{
    public function __construct(private AiProviders $ai) {}

    /**
     * @return array{summary: string, description: string, highlights: list<string>, sections: list<array{title: string, kind: string, items: list<mixed>}>, faqs: list<array{q: string, a: string}>}
     */
    public function enhance(object $p, string $instructions = ''): array
    {
        if (! $this->ai->available()) {
            throw new RuntimeException('AI is not switched on. Add a key in Admin › AI assistant first.');
        }
        $current = ProductGuide::for($p);
        $facts = json_encode([
            'name' => $p->name, 'category' => $current['category_label'], 'summary' => $p->summary, 'description' => $p->description,
            'weight' => $p->weight, 'origin' => $p->origin, 'certificate' => $p->certification, 'price_rupees' => (int) $p->price_minor / 100,
            'current_page' => ['highlights' => $current['highlights'], 'sections' => $current['sections'], 'faqs' => $current['faqs']],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $extra = trim($instructions) !== '' ? "\nOwner's instructions (follow them): ".mb_substr(trim($instructions), 0, 600) : '';
        $prompt = <<<TXT
        Improve this product page for Acharya Dr. Rajnath Jha's Vedic astrology store (astrorajnathjha.com, Patna, India).
        Write like a good Indian e-commerce page: clear, warm, specific, easy to scan. Keep every fact that is given
        (weight, origin, certificate, price). Do not invent certificates, lab names, origins or prices.
        Follow traditional Vedic/Jyotish practice for planet, metal, finger, day and mantra.
        Never promise guaranteed results or medical cures; say "in traditional belief" where needed and keep a
        cautions section with real possible downsides (wrong stone for the chart, stones not to combine, fakes, care).
        {$extra}

        Return ONLY JSON with this shape:
        {"summary": "max 280 characters", "description": "2–4 short paragraphs separated by a blank line",
         "highlights": ["4–6 short points"],
         "sections": [{"title": "Benefits", "kind": "list", "items": ["..."]},
                      {"title": "Who should wear it", "kind": "list", "items": ["..."]},
                      {"title": "How to wear", "kind": "table", "items": [["Planet", "..."], ["Metal", "..."]]},
                      {"title": "Possible side effects and cautions", "kind": "list", "items": ["..."]},
                      {"title": "Care", "kind": "list", "items": ["..."]}],
         "faqs": [{"q": "...", "a": "..."}]}
        Use section titles that fit the item (for a puja kit: "What it is for", "How to use", ...). 5–8 FAQs.

        PRODUCT
        {$facts}
        TXT;
        [, , $text] = $this->ai->complete([
            ['role' => 'system', 'content' => 'You write accurate, honest product pages for a Vedic astrology store and return strict JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ], 4000);
        $j = RashifalWriter::extractJson($text);
        if (! isset($j['sections']) || ! is_array($j['sections'])) {
            throw new RuntimeException('The AI answer could not be read. Please try again.');
        }
        $form = [
            'highlights' => implode("\n", array_map('strval', (array) ($j['highlights'] ?? []))),
            'sections' => array_map(fn ($s) => ['title' => (string) ($s['title'] ?? ''), 'kind' => (string) ($s['kind'] ?? 'list'),
                'items' => implode("\n", array_map(fn ($i) => is_array($i) ? implode(': ', array_map('strval', array_slice($i, 0, 2))) : (string) $i, (array) ($s['items'] ?? [])))], (array) $j['sections']),
            'faqs' => implode("\n\n", array_map(fn ($f) => 'Q: '.($f['q'] ?? '')."\nA: ".($f['a'] ?? ''), (array) ($j['faqs'] ?? []))),
        ];
        $clean = ProductGuide::fromForm($form);

        return [
            'summary' => mb_substr(trim(strip_tags((string) ($j['summary'] ?? $p->summary))), 0, 300),
            'description' => mb_substr(trim(strip_tags((string) ($j['description'] ?? $p->description ?? ''))), 0, 5000),
        ] + $clean;
    }
}
