<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\GeoIp;
use App\Domain\Analytics\Reports;
use App\Domain\Analytics\ToolHistory;
use App\Domain\Analytics\ToolPdf;
use App\Domain\Astro\ToolDisplay;
use App\Domain\Astro\Tools;
use App\Http\Controllers\Controller;
use App\Models\ToolRun;
use App\Support\Audit\AuditLogger;
use App\Support\Pdf\Documents;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin › Analytics: website visitors and clicks, free-calculator use and history, and the mobile app. */
class AnalyticsController extends Controller
{
    /** Free pages that are read rather than calculated (counted from page views). */
    public const FREE_PAGES = [
        'rashifal' => 'Rashifal', 'rashifal.kind' => 'Nakshatra / Chinese / Numerology / Tarot horoscope', 'rashi' => 'Rashi (sign) pages',
        'gochar' => 'Grah gochar', 'gochar.planet' => 'Gochar planet pages', 'navagraha' => 'The nine planets', 'muhurat.index' => 'Shubh muhurat list',
        'muhurat.show' => 'Muhurat by ceremony', 'festivals' => 'Festivals', 'calendar' => 'Vrat calendar', 'panchang' => 'Panchang',
        'tools.index' => 'All calculators', 'tools.show' => 'Calculator pages (opened)', 'learn' => 'Learn Jyotish',
    ];

    public const RANGES = [1 => 'Today', 7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'];

    public function __construct(private AuditLogger $audit, private SettingsRepository $settings) {}

    private function range(Request $request): Reports
    {
        $d = $request->validate([
            'days' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $r = new Reports($d['from'] ?? null, $d['to'] ?? null, (int) ($d['days'] ?? 30));
        if ($r->from->diffInDays($r->to) > 1100) {
            $r->from = $r->to->subDays(1100)->startOfDay();   // at most three years at a time
        }

        return $r;
    }

    public static function countryName(?string $code): string
    {
        if (! $code) {
            return 'Unknown';
        }
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('und_'.$code, 'en') : '';

        return $name !== '' && $name !== $code ? $name : $code;
    }

    public function website(Request $request, GeoIp $geo): View
    {
        $this->authorize('analytics.view');
        $r = $this->range($request);
        $pv = fn () => $r->events('pageview');

        return view('admin.analytics.website', [
            'r' => $r, 'totals' => $r->websiteTotals(), 'series' => $r->daily($pv()),
            'pages' => $r->top($pv(), 'path', 15), 'entries' => $r->entryPages(), 'clicks' => $r->topClicks(),
            'referrers' => $r->top($pv()->whereNotNull('referrer'), 'referrer'),
            'campaigns' => $r->top($pv()->whereNotNull('utm_source'), "concat_ws(' / ', utm_source, utm_medium, utm_campaign)"),
            'countries' => $r->top($pv(), 'country', 15), 'devices' => $r->top($pv(), 'device'), 'os' => $r->top($pv(), 'os'),
            'browsers' => $r->top($pv(), 'browser'), 'locales' => $r->top($pv(), 'locale'),
            'settings' => [
                'enabled' => (bool) $this->settings->get('analytics.enabled', true), 'respect_dnt' => (bool) $this->settings->get('analytics.respect_dnt', true),
                'tool_history' => (bool) $this->settings->get('analytics.tool_history', true), 'retention_days' => (int) $this->settings->get('analytics.retention_days', 395),
            ],
            'geoLoaded' => $geo->loaded(),
        ]);
    }

    public function tools(Request $request, ToolPdf $pdf): View
    {
        $this->authorize('analytics.view');
        $r = $this->range($request);
        $f = $request->validate(['tool' => ['nullable', Rule::in(array_keys(ToolHistory::allTools()))], 'channel' => ['nullable', 'in:web,app'], 'pdf' => ['nullable', 'boolean']]);
        $history = $request->user()->can('tools.history')
            ? ToolRun::query()->with('user')->whereBetween('created_at', [$r->from->utc(), $r->to->utc()])
                ->when($f['tool'] ?? null, fn ($q, $t) => $q->where('tool', $t))->when($f['channel'] ?? null, fn ($q, $c) => $q->where('channel', $c))
                ->when(! empty($f['pdf']), fn ($q) => $q->where('pdf_downloads', '>', 0))
                ->orderByDesc('id')->paginate(30)->withQueryString()
            : null;
        $runs = $r->toolRuns();

        return view('admin.analytics.tools', [
            'r' => $r, 'totals' => $r->toolTotals(), 'filters' => $f, 'history' => $history,
            'series' => $r->daily($r->toolRuns()->when($f['tool'] ?? null, fn ($q, $t) => $q->where('tool', $t)), 'count(*)', 'sum(pdf_downloads)', 'created_at'),
            'sum' => ['runs' => (clone $runs)->whereIn('tool', array_keys(Tools::TOOLS))->count(), 'lookups' => (clone $runs)->whereIn('tool', array_keys(ToolHistory::LOOKUPS))->count(),
                'people' => (clone $runs)->distinct()->count('visitor'), 'pdfs' => $r->events('pdf_download')->count() + $r->events('pdf_download', 'app')->count(),
                'kundli' => (clone $runs)->where('tool', 'kundli')->count(), 'matching' => (clone $runs)->where('tool', 'kundli-matching')->count()],
            'countries' => $r->top(clone $runs, 'country'),
            'pdfOff' => collect(array_keys(Tools::TOOLS))->filter(fn ($s) => ! $pdf->template($s)['enabled'])->values()->all(),
            'pages' => $r->top($r->events('pageview')->whereIn('route', array_keys(self::FREE_PAGES)), 'route', 20),
        ]);
    }

    public function toolRun(ToolRun $run, ToolHistory $history): View
    {
        $this->authorize('tools.history');
        $this->audit->record('analytics.tool_run_viewed', $run);
        $display = null;
        if (isset(Tools::TOOLS[$run->tool])) {
            try {
                $display = ToolDisplay::for($history->result($run));
            } catch (RuntimeException $e) {
                report($e);
            }
        }

        return view('admin.analytics.tool-run', ['run' => $run->load('user'), 'display' => $display, 'events' => $run->visitor
            ? DB::table('analytics_events')->where('visitor', $run->visitor)->whereIn('type', ['pageview', 'tool_run', 'pdf_download', 'app_open'])->orderByDesc('id')->limit(25)->get()
            : collect()]);
    }

    public function toolRunPdf(ToolRun $run, ToolHistory $history, ToolPdf $pdf, Documents $docs): Response
    {
        $this->authorize('tools.history');
        abort_unless(isset(Tools::TOOLS[$run->tool]), 404);
        try {
            $result = $history->result($run);
        } catch (RuntimeException $e) {
            report($e);
            abort(503, 'The calculation service is unavailable right now.');
        }
        $this->audit->record('analytics.tool_run_pdf', $run);

        return $docs->download($pdf->html($run->tool, $result, $run->ref), $run->tool.'-'.substr($run->ref, -8).'.pdf');
    }

    public function exportTools(Request $request): StreamedResponse
    {
        $this->authorize('tools.history');
        $r = $this->range($request);
        $this->audit->record('analytics.tool_runs_exported', null, ['from' => $r->from->toDateString(), 'to' => $r->to->toDateString()]);
        $rows = ToolRun::query()->whereBetween('created_at', [$r->from->utc(), $r->to->utc()])->lazyByIdDesc(500);
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 marker so Excel shows Hindi names correctly
            fputcsv($out, ['Reference', 'When (IST)', 'Calculator', 'Channel', 'Details entered', 'Result', 'Country', 'Device', 'Language', 'PDF downloads', 'Customer account']);
            foreach ($rows as $run) {
                fputcsv($out, array_map($safe, [$run->ref, $run->created_at->tz('Asia/Kolkata')->format('Y-m-d H:i'), ToolHistory::name($run->tool), $run->channel,
                    $run->personLine(), $run->summary, $run->country, $run->device, $run->locale, $run->pdf_downloads, $run->user_id ? 'yes' : '']));
            }
            fclose($out);
        }, 'calculator-history-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    public function app(Request $request): View
    {
        $this->authorize('analytics.view');
        $r = $this->range($request);
        $all = fn () => $r->events(['app_open', 'app_call', 'app_screen'], 'app');
        $features = collect($r->top($r->events('app_call', 'app'), 'route', 200))
            ->groupBy(fn ($row) => Reports::feature($row->k))->map(fn ($g, $name) => (object) ['k' => $name, 'n' => $g->sum('n'), 'v' => $g->sum('v')])
            ->sortByDesc('n')->take(15)->values()->all();

        return view('admin.analytics.app', [
            'r' => $r, 'totals' => $r->appTotals(), 'series' => $r->daily($r->events('app_open', 'app')),
            'features' => $features, 'screens' => $r->top($r->events('app_screen', 'app'), 'label', 15),
            'versions' => $r->top($all()->whereNotNull('app_version'), 'app_version'), 'countries' => $r->top($all(), 'country'),
            'locales' => $r->top($all(), 'locale'), 'os' => $r->top($all(), 'os'),
            'tools' => $r->toolRuns()->where('channel', 'app')->selectRaw('tool, count(*) as n')->groupBy('tool')->orderByDesc('n')->limit(10)->get(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');
        $d = $request->validate([
            'enabled' => ['nullable', 'boolean'], 'respect_dnt' => ['nullable', 'boolean'], 'tool_history' => ['nullable', 'boolean'],
            'retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
        ]);
        foreach (['enabled', 'respect_dnt', 'tool_history'] as $k) {
            $this->settings->set('analytics.'.$k, (bool) ($d[$k] ?? false), false, 'analytics');
        }
        $this->settings->set('analytics.retention_days', (int) $d['retention_days'], false, 'analytics');

        return back()->with('status', 'Analytics settings saved.');
    }
}
