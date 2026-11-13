<?php

namespace App\Domain\Analytics;

use App\Domain\Astro\Tools;
use App\Domain\Astro\Vedic;
use App\Http\Middleware\TrackAppRequest;
use App\Models\ToolRun;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Saves every free-calculator run (website and app) so the owner can see how many Kundlis,
 * matchings and numerology charts are made, by whom, and how many PDFs are downloaded.
 * The details people enter are encrypted; runs older than the retention period are deleted.
 */
class ToolHistory
{
    /** Free tools that are pages rather than calculators (they have no PDF). */
    public const LOOKUPS = [
        'panchang' => 'Panchang', 'muhurat' => 'Shubh muhurat', 'festivals' => 'Festivals', 'calendar' => 'Vrat calendar', 'gochar' => 'Grah gochar',
    ];

    public function __construct(private Tracker $tracker, private SettingsRepository $settings) {}

    /** Display name of any free tool (calculator or lookup). */
    public static function name(string $tool): string
    {
        return Tools::TOOLS[$tool][0] ?? self::LOOKUPS[$tool] ?? $tool;
    }

    /** @return array<string, string> every free tool, slug => name */
    public static function allTools(): array
    {
        return collect(Tools::TOOLS)->map(fn ($t) => $t[0])->all() + self::LOOKUPS;
    }

    /**
     * Saves the use of a lookup tool (Panchang, muhurat, festivals, calendar, gochar). Bots are
     * skipped, and the same visitor looking up the same thing within 30 minutes counts once.
     *
     * @param  array<string, mixed>  $inputs  what was chosen (date, month, place, event …)
     */
    public function lookup(string $tool, array $inputs, string $summary, Request $request, string $channel = 'web', ?string $visitor = null): ?ToolRun
    {
        if (! $this->enabled() || ! isset(self::LOOKUPS[$tool]) || $this->tracker->ignored($request) && ! $request->hasHeader('X-App-Install')) {
            return null;
        }
        $user = $request->user('sanctum') ?? $request->user();
        $dupe = ToolRun::where('tool', $tool)->where('summary', mb_substr($summary, 0, 300))->where('created_at', '>=', now()->subMinutes(30))
            ->when($visitor, fn ($q) => $q->where('visitor', $visitor), fn ($q) => $user ? $q->where('user_id', $user->id) : $q->whereRaw('1 = 0'))->exists();
        if ($dupe) {
            return null;
        }
        try {
            $device = Tracker::device((string) $request->userAgent());
            $run = ToolRun::create([
                'tool' => $tool, 'channel' => $channel, 'user_id' => $user?->id, 'visitor' => $visitor, 'inputs' => array_filter($inputs, fn ($v) => $v !== null && $v !== ''),
                'summary' => mb_substr($summary, 0, 300), 'country' => $this->tracker->country($request), 'device' => $device['device'], 'locale' => app()->getLocale(),
            ]);
            $this->tracker->record('tool_run', ['channel' => $channel, 'visitor' => $visitor, 'user_id' => $run->user_id, 'label' => $tool,
                'path' => '/'.ltrim($request->path(), '/'), 'country' => $run->country, 'locale' => $run->locale] + $device);

            return $run;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Visitor id for the current request: the website cookie id, or the app install id. */
    public static function visitorFor(Request $request): ?string
    {
        if (TrackAppRequest::isApp($request)) {
            return TrackAppRequest::installId($request);
        }

        return $request->attributes->get('analytics.ids')['visitor']
            ?? 'h-'.substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString(), (string) config('app.key')), 0, 30);
    }

    public static function channelFor(Request $request): string
    {
        return TrackAppRequest::isApp($request) ? 'app' : 'web';
    }

    /** "Patna, Bihar, India" for a place row. */
    public static function placeLabel(?object $place): ?string
    {
        return $place ? implode(', ', array_filter([$place->name ?? null, $place->admin1 ?? null, $place->country ?? null])) : null;
    }

    public function enabled(): bool
    {
        return (bool) $this->settings->get('analytics.tool_history', true);
    }

    /**
     * @param  array<string, mixed>  $in  validated input
     * @param  array<string, mixed>  $result  Tools::compute() output
     */
    public function record(string $slug, array $in, array $result, Request $request, string $channel = 'web', ?string $visitor = null): ?ToolRun
    {
        if (! $this->enabled()) {
            return null;
        }
        try {
            foreach (['', 'a_', 'b_'] as $p) {
                if (isset($in[$p.'place_id'])) {
                    $place = DB::table('places')->where('id', $in[$p.'place_id'])->first(['name', 'admin1', 'country']);
                    $in[$p.'birth_place'] = $place ? implode(', ', array_filter([$place->name, $place->admin1, $place->country])) : null;
                }
            }
            $summary = mb_substr(self::summary($result), 0, 300);
            if ($visitor && ($same = ToolRun::where('tool', $slug)->where('visitor', $visitor)->where('summary', $summary)->where('created_at', '>=', now()->subMinutes(10))->latest('id')->first())) {
                return $same;   // the same calculation sent again (refresh, double tap, the assistant asking twice)
            }
            $device = Tracker::device((string) $request->userAgent());
            $run = ToolRun::create([
                'tool' => $slug, 'channel' => $channel, 'user_id' => $request->user('sanctum')?->id ?? $request->user()?->id,
                'visitor' => $visitor, 'inputs' => $in, 'summary' => $summary,
                'country' => $this->tracker->country($request), 'device' => $device['device'], 'locale' => app()->getLocale(),
            ]);
            $this->tracker->record('tool_run', ['channel' => $channel, 'visitor' => $visitor, 'user_id' => $run->user_id, 'label' => $slug,
                'path' => '/tools/'.$slug, 'country' => $run->country, 'locale' => $run->locale] + $device);

            return $run;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Short result line, e.g. "Lagna Simha · Moon sign Vrishabha · Nakshatra Rohini 2". */
    public static function summary(array $r): string
    {
        $c = $r['chart'] ?? null;
        $bits = [];
        if ($c) {
            $bits[] = 'Lagna '.$c['lagna']['sign_name'];
            $bits[] = 'Moon '.$c['moon_sign']['name'];
            $bits[] = $c['nakshatra']['name'].' '.$c['nakshatra']['pada'];
        }
        if (isset($r['match']['total'])) {
            $bits[] = $r['match']['total'].'/36 gunas';
        }
        foreach (['mulank' => 'Mulank', 'bhagyank' => 'Bhagyank', 'name_number' => 'Name number', 'personal_year' => 'Personal year'] as $k => $l) {
            if (isset($r[$k]) && is_scalar($r[$k])) {
                $bits[] = $l.' '.$r[$k];
            }
        }
        if (isset($r['rudraksha']['mukhi'])) {
            $bits[] = $r['rudraksha']['mukhi'].' mukhi ('.(Vedic::PLANET_NAMES[$r['rudraksha']['lagna_lord']] ?? '').')';
        }
        if (isset($r['flames']) && is_scalar($r['flames'])) {
            $bits[] = (string) $r['flames'];
        }

        return implode(' · ', $bits) ?: ($r['title'] ?? '');
    }

    /** Link to the PDF of a run, valid for 24 hours (signed, so it cannot be guessed). */
    public static function isCalculator(string $tool): bool
    {
        return isset(Tools::TOOLS[$tool]);
    }

    public static function pdfUrl(ToolRun $run): string
    {
        return URL::temporarySignedRoute('tools.pdf', now()->addDay(), ['slug' => $run->tool, 'run' => $run->ref]);
    }

    public function countPdf(ToolRun $run, Request $request, string $channel): void
    {
        ToolRun::whereKey($run->id)->update(['pdf_downloads' => DB::raw('pdf_downloads + 1'), 'last_pdf_at' => now()]);
        $this->tracker->record('pdf_download', ['channel' => $channel, 'visitor' => $run->visitor, 'user_id' => $run->user_id, 'label' => $run->tool,
            'path' => '/tools/'.$run->tool, 'country' => $this->tracker->country($request)] + Tracker::device((string) $request->userAgent()));
    }

    /** Recalculates a saved run (results are not stored, only the inputs). */
    public function result(ToolRun $run): array
    {
        $in = collect($run->inputs ?? [])->except(['birth_place', 'a_birth_place', 'b_birth_place', 'via'])->all();

        return app(Tools::class)->compute($run->tool, $in);
    }
}
