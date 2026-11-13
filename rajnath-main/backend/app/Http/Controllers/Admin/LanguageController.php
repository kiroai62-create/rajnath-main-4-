<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Translation\SiteTranslations;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Jobs\ScanSitePages;
use App\Jobs\TranslatePendingText;
use App\Support\Audit\AuditLogger;
use App\Support\Navigation;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/** Admin › Languages: which languages visitors can pick, AI translation, and a line-by-line editor. */
class LanguageController extends Controller
{
    public function __construct(private SettingsRepository $settings, private SiteTranslations $translations, private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('content.publish');
        $locales = array_diff_key(config('locales.available'), ['en' => true]);
        $locale = (string) $request->query('locale', array_key_first($locales));
        abort_unless(isset($locales[$locale]), 404);
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));
        $lines = DB::table('site_translations')->where('locale', $locale)
            ->when(in_array($status, ['done', 'pending', 'failed'], true), fn ($w) => $w->where('status', $status))
            ->when($status === 'manual', fn ($w) => $w->where('origin', 'manual'))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
                $w->where(fn ($x) => $x->where('source', 'like', $like)->orWhere('text', 'like', $like));
            })
            ->orderByDesc('id')->paginate(40)->withQueryString();

        return view('admin.languages.index', [
            'locales' => $locales, 'locale' => $locale, 'status' => $status, 'q' => $q, 'lines' => $lines,
            'enabled' => SetLocale::enabled(), 'stats' => $this->translations->stats(),
            'auto' => $this->translations->autoEnabled(), 'aiReady' => $this->translations->aiConfigured(),
            'lastError' => $this->settings->get('translate.last_error'), 'sync' => config('queue.default') === 'sync',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');
        $data = $request->validate([
            'locales' => ['array'], 'locales.*' => [Rule::in(array_keys(config('locales.available')))],
            'auto' => ['nullable', 'boolean'],
        ]);
        $on = array_values(array_unique(['en', ...($data['locales'] ?? [])]));
        $this->settings->set('site.locales', array_values(array_intersect(array_keys(config('locales.available')), $on)), false, 'site');
        $this->settings->set('translate.auto', (bool) ($data['auto'] ?? false), false, 'translate');
        Navigation::forget();

        return back()->with('status', 'Language settings saved.');
    }

    /** Translates waiting text now: one batch straight away, the rest in the background. */
    public function translate(Request $request): RedirectResponse
    {
        $this->authorize('content.publish');
        $data = $request->validate(['locale' => ['required', Rule::in(['all', ...array_keys(config('locales.available'))])], 'retry' => ['nullable', 'boolean']]);
        if (! $this->translations->aiConfigured()) {
            return back()->withErrors(['locale' => 'Add an AI key first in Admin › AI assistant (Groq, Gemini, Cloudflare or OpenAI), and switch that provider on.']);
        }
        $locales = $data['locale'] === 'all' ? array_values(array_diff(SetLocale::enabled(), ['en'])) : [$data['locale']];
        if (! empty($data['retry'])) {
            DB::table('site_translations')->whereIn('locale', $locales)->where('status', 'failed')->update(['status' => 'pending', 'attempts' => 0, 'updated_at' => now()]);
        }
        $saved = 0;
        try {
            $saved = $this->translations->translateBatch($locales[0]);
        } catch (Throwable $e) {
            report($e);
        }
        if (config('queue.default') !== 'sync') {
            foreach ($locales as $l) {
                TranslatePendingText::dispatch($l);
            }
        }
        $this->audit->record('translations.run', null, ['locales' => $locales, 'saved_now' => $saved]);

        return back()->with('status', $saved.' lines translated now.'.(config('queue.default') !== 'sync' ? ' The rest continue in the background; refresh this page to see progress.' : ' Press the button again to continue.'));
    }

    /** Opens every page once in each language so all text is queued for translation. */
    public function scan(): RedirectResponse
    {
        $this->authorize('content.publish');
        if (config('queue.default') === 'sync') {
            return back()->withErrors(['scan' => 'Scanning the whole website needs the background queue (QUEUE_CONNECTION=redis or database).']);
        }
        ScanSitePages::dispatch();
        $this->audit->record('translations.scan');

        return back()->with('status', 'Scanning every page in every language. New text is translated automatically; this can take 10–30 minutes.');
    }

    public function saveLine(Request $request, int $id): RedirectResponse
    {
        $this->authorize('content.publish');
        $row = DB::table('site_translations')->where('id', $id)->first();
        abort_unless($row, 404);
        $data = $request->validate(['text' => ['nullable', 'string', 'max:6000']]);
        $text = trim((string) ($data['text'] ?? ''));
        DB::table('site_translations')->where('id', $id)->update($text === ''
            ? ['text' => null, 'status' => 'pending', 'origin' => null, 'attempts' => 0, 'updated_at' => now()]
            : ['text' => $text, 'status' => 'done', 'origin' => 'manual', 'updated_at' => now()]);
        $this->audit->record('translations.line_saved', null, ['id' => $id, 'locale' => $row->locale, 'cleared' => $text === '']);

        return back()->with('status', $text === '' ? 'Line cleared; AI will translate it again.' : 'Translation saved. AI will not change it.');
    }
}
