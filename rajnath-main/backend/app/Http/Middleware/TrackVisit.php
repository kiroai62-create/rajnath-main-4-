<?php

namespace App\Http\Middleware;

use App\Domain\Analytics\Tracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts website page views (Admin › Analytics). The visitor and session ids are set here so
 * the calculators can link a run to its visitor; the row is written after the response has been
 * sent (terminate), so tracking never slows a page down. The control centre is not tracked.
 */
class TrackVisit
{
    private const SKIP = ['admin', 'admin/*', 'staff/*', 'api/*', 'analytics/*', 'files/*', 'webhooks/*', 'up', 'ready', 'health*', 'sitemap.xml', 'robots.txt', 'llms*.txt', 'build/*', 'sw.js', 'manifest*', 'puja-photos/*'];

    public function __construct(private Tracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $track = $request->isMethod('GET') && ! $request->is(...self::SKIP) && $this->tracker->enabled() && ! $this->tracker->ignored($request);
        if ($track || ($request->isMethod('POST') && ! $request->is('admin/*', 'staff/*'))) {
            $request->attributes->set('analytics.ids', $this->tracker->identify($request));
        }
        $response = $next($request);
        $ids = $request->attributes->get('analytics.ids');
        if (! $ids || ! $this->tracker->enabled() || $this->tracker->ignored($request)) {
            return $response;
        }
        $secure = (bool) config('session.secure');
        $response->headers->setCookie(new Cookie(Tracker::VISITOR_COOKIE, $ids['visitor'], now()->addYears(2), '/', null, $secure, true, false, Cookie::SAMESITE_LAX));
        $response->headers->setCookie(new Cookie(Tracker::SESSION_COOKIE, $ids['session'], now()->addMinutes(Tracker::SESSION_MINUTES), '/', null, $secure, true, false, Cookie::SAMESITE_LAX));
        if ($track && $response->isSuccessful() && str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            $request->attributes->set('analytics.pageview', true);
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->attributes->get('analytics.pageview')) {
            return;
        }
        $ids = $request->attributes->get('analytics.ids');
        rescue(fn () => $this->tracker->record('pageview', $this->tracker->context($request, $ids) + [
            'is_new' => $ids['new_visitor'], 'path' => Tracker::cleanPath($request->path()), 'route' => $request->route()?->getName(),
            'referrer' => Tracker::referrer($request),
            'utm_source' => self::utm($request, 'utm_source'), 'utm_medium' => self::utm($request, 'utm_medium'), 'utm_campaign' => self::utm($request, 'utm_campaign'),
        ]), null, true);
    }

    private static function utm(Request $request, string $key): ?string
    {
        $v = $request->query($key);

        return is_string($v) && $v !== '' ? mb_substr(strip_tags($v), 0, 100) : null;
    }
}
