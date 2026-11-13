<?php

namespace App\Domain\Analytics;

use App\Support\Settings\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * First-party visitor tracking for the website and the app. A visitor gets a random id in a
 * cookie (website) or sends the app's install id; a session ends after 30 minutes without a
 * page. Country comes from a CDN header or the local DB-IP table, device/OS/browser from the
 * user agent. The IP address itself is never stored. Bots, previews and prefetches are ignored,
 * and so are visitors whose browser sends "Do Not Track" or "Global Privacy Control" when the
 * owner keeps that option on (Admin › Analytics).
 */
class Tracker
{
    public const VISITOR_COOKIE = 'arj_vid';

    public const SESSION_COOKIE = 'arj_sid';

    public const SESSION_MINUTES = 30;

    private const BOTS = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|httpclient|okhttp|go-http|java\/|symfony|guzzle|scrapy|semrush|ahrefs|mj12/i';

    public function __construct(private SettingsRepository $settings, private GeoIp $geo) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('analytics.enabled', true);
    }

    /** Requests that must not be counted at all. */
    public function ignored(Request $request): bool
    {
        $ua = (string) $request->userAgent();
        if ($ua === '' || preg_match(self::BOTS, $ua)) {
            return true;
        }
        if (in_array(strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose'))), ['prefetch', 'prerender', 'prefetch;prerender'], true)) {
            return true;
        }

        return (bool) $this->settings->get('analytics.respect_dnt', true)
            && ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1');
    }

    /** @return array{visitor: string, session: string, new_visitor: bool} */
    public function identify(Request $request): array
    {
        $vid = (string) $request->cookie(self::VISITOR_COOKIE);
        $sid = (string) $request->cookie(self::SESSION_COOKIE);
        $new = ! self::validId($vid);

        return [
            'visitor' => $new ? (string) Str::uuid() : $vid,
            'session' => self::validId($sid) && ! $new ? $sid : (string) Str::uuid(),
            'new_visitor' => $new,
        ];
    }

    /** Page path with order/booking references replaced, so paths never identify a record. */
    public static function cleanPath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        return mb_substr(preg_replace('#/(?:[0-9A-HJKMNP-TV-Z]{26}|[0-9a-f-]{32,36}|\d{4,})(?=/|$)#i', '/{ref}', $path), 0, 300);
    }

    public static function validId(?string $id): bool
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9-]{8,40}$/', $id) === 1;
    }

    /** @return array{device: string, os: string, browser: string} */
    public static function device(string $ua): array
    {
        $device = match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android|windows phone|dart\//i', $ua) => 'mobile',
            default => 'desktop',
        };
        $os = match (true) {
            (bool) preg_match('/windows phone/i', $ua) => 'Windows Phone',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/cros/i', $ua) => 'ChromeOS',
            (bool) preg_match('/mac os x|macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Other',
        };
        $browser = match (true) {
            (bool) preg_match('/dart\//i', $ua) => 'App',
            (bool) preg_match('/edg(a|ios|e)?\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung',
            (bool) preg_match('/ucbrowser/i', $ua) => 'UC Browser',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/chrome|crios/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Other',
        };

        return ['device' => $device, 'os' => $os, 'browser' => $browser];
    }

    public function country(Request $request): ?string
    {
        foreach (config('analytics.trust_cdn_country') ? ['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Country-Code', 'X-AppEngine-Country'] : [] as $h) {
            $c = strtoupper((string) $request->header($h));
            if (preg_match('/^[A-Z]{2}$/', $c) && ! in_array($c, ['XX', 'T1'], true)) {
                return $c;
            }
        }

        return $this->geo->country((string) $request->ip());
    }

    /** Host of an external referrer (own site and empty referrers give null). */
    public static function referrer(Request $request): ?string
    {
        $host = strtolower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        $own = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host === '' || $host === $request->getHost() || $host === $own || preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', $own)) {
            return null;
        }

        return mb_substr(preg_replace('/^www\./', '', $host), 0, 190);
    }

    /**
     * Stores one event. Never throws: analytics must not break a page.
     *
     * @param  array<string, mixed>  $data  columns of analytics_events
     */
    public function record(string $type, array $data): void
    {
        try {
            DB::table('analytics_events')->insert(array_merge([
                'occurred_at' => now(), 'type' => $type, 'channel' => 'web',
            ], array_map(fn ($v) => is_string($v) ? mb_substr($v, 0, 300) : $v, $data)));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Common columns for a website request.
     *
     * @return array<string, mixed>
     */
    public function context(Request $request, array $ids): array
    {
        return self::device((string) $request->userAgent()) + [
            'visitor' => $ids['visitor'], 'session' => $ids['session'], 'user_id' => $request->user()?->id,
            'country' => $this->country($request), 'locale' => app()->getLocale(),
        ];
    }
}
