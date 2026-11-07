<?php

namespace App\Support;

use App\Domain\Astro\Gochar;
use App\Domain\Astro\PanchangService;
use App\Domain\Astro\Vedic;
use App\Domain\Content\GocharRashifal;
use App\Domain\Content\HoroscopeTypes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Live sky data for the home page and the app: today's Panchang for Patna, where the nine planets
 * are now, and a one-line preview of today's Rashifal for each sign. Cached; when the ephemeris
 * engine is not available the parts are simply null and the sections hide.
 */
class HomeSky
{
    /** @return array<string, mixed>|null */
    public static function panchang(): ?array
    {
        $date = now('Asia/Kolkata')->toDateString();
        try {
            return Cache::remember("home-sky:panchang:$date", 1800, function () use ($date) {
                $d = app(PanchangService::class)->day($date, 25.59408, 85.13563, 'Asia/Kolkata');
                $t = fn (?string $iso) => $iso ? CarbonImmutable::parse($iso)->format('g:i A') : null;
                $a = $d['angas'] ?? [];
                $rk = $d['intervals']['rahu_kaal'] ?? null;
                $abhijit = null;
                if (! empty($d['sunrise']) && ! empty($d['sunset'])) {
                    $rise = CarbonImmutable::parse($d['sunrise']);
                    $mid = $rise->addSeconds((int) (CarbonImmutable::parse($d['sunset'])->diffInSeconds($rise, true) / 2));
                    $abhijit = $mid->subMinutes(24)->format('g:i A').' – '.$mid->addMinutes(24)->format('g:i A');
                }

                return [
                    'date' => CarbonImmutable::parse($date)->format('l, j F Y'), 'vara' => $d['vara'] ?? null,
                    'masa' => $d['masa']['purnimanta'] ?? null,
                    'tithi' => $a['tithi'][0]['name'] ?? null, 'tithi_ends' => $t($a['tithi'][0]['ends'] ?? null),
                    'nakshatra' => $a['nakshatra'][0]['name'] ?? null, 'nakshatra_ends' => $t($a['nakshatra'][0]['ends'] ?? null),
                    'yoga' => $a['yoga'][0]['name'] ?? null, 'karana' => $a['karana'][0]['name'] ?? null,
                    'sunrise' => $t($d['sunrise'] ?? null), 'sunset' => $t($d['sunset'] ?? null),
                    'moonrise' => $t($d['moonrise'] ?? null), 'moon_sign' => $d['moon_sign'] ?? null,
                    'rahu_kaal' => $rk ? $t($rk['start']).' – '.$t($rk['end']) : null, 'abhijit' => $abhijit,
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** @return array<string, array{sign: int, sign_name: string, deg: string, retro: bool, nakshatra: string}>|null */
    public static function planets(): ?array
    {
        try {
            return Cache::remember('home-sky:planets:'.now()->format('YmdH'), 3600, fn () => collect(app(Gochar::class)->now())
                ->map(fn ($x) => $x + ['sign_name' => strtok(Vedic::SIGNS[$x['sign']], ' ')])->all());
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** First sentence of today's general Rashifal for each sign (sign index => text). */
    public static function rashifalPreview(): array
    {
        GocharRashifal::fillToday('rashi', 'daily');
        $today = now('Asia/Kolkata')->toDateString();
        $locale = HoroscopeTypes::contentLocale(app()->getLocale());

        return Cache::remember("home-sky:rashifal:$today:$locale", 600, fn () => DB::table('horoscopes')->where('status', 'published')->where('kind', 'rashi')
            ->where('topic', 'general')->where('period', 'daily')->where('locale', $locale)->where('starts_on', $today)
            ->orderByRaw("case when source = 'engine' then 1 else 0 end")->get(['sign', 'body'])
            ->unique('sign')->mapWithKeys(fn ($r) => [(int) $r->sign => Str::limit(preg_split('/(?<=[.।!?])\s/u', trim($r->body))[0] ?? $r->body, 110)])->all());
    }
}
