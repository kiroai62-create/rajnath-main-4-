<?php

namespace App\Domain\Analytics;

use App\Domain\Astro\Tools;
use App\Domain\Astro\Vedic;
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
    public function __construct(private Tracker $tracker, private SettingsRepository $settings) {}

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
            $device = Tracker::device((string) $request->userAgent());
            $run = ToolRun::create([
                'tool' => $slug, 'channel' => $channel, 'user_id' => $request->user('sanctum')?->id ?? $request->user()?->id,
                'visitor' => $visitor, 'inputs' => $in, 'summary' => mb_substr(self::summary($result), 0, 300),
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
    public static function pdfUrl(ToolRun $run): string
    {
        return URL::temporarySignedRoute('tools.pdf', now()->addDay(), ['slug' => $run->tool, 'run' => $run->ref]);
    }

    public function countPdf(ToolRun $run, Request $request, string $channel): void
    {
        $run->forceFill(['pdf_downloads' => $run->pdf_downloads + 1, 'last_pdf_at' => now()])->save();
        $this->tracker->record('pdf_download', ['channel' => $channel, 'visitor' => $run->visitor, 'user_id' => $run->user_id, 'label' => $run->tool,
            'path' => '/tools/'.$run->tool, 'country' => $this->tracker->country($request)] + Tracker::device((string) $request->userAgent()));
    }

    /** Recalculates a saved run (results are not stored, only the inputs). */
    public function result(ToolRun $run): array
    {
        $in = collect($run->inputs ?? [])->except(['birth_place', 'a_birth_place', 'b_birth_place'])->all();

        return app(Tools::class)->compute($run->tool, $in);
    }
}
