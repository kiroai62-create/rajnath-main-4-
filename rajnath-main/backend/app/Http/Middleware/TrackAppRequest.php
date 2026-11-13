<?php

namespace App\Http\Middleware;

use App\Domain\Analytics\Tracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile app analytics from API traffic (Admin › Analytics › Mobile app). The app loads
 * /api/v1/meta every time it starts, so that request is an "app open"; other feature requests
 * show what people use. Newer app builds send X-App-Install (a random id made on the phone),
 * X-App-Version and their screen views; older builds are grouped by a daily one-way hash.
 */
class TrackAppRequest
{
    /** Polling and housekeeping calls that are not a feature being used. */
    private const QUIET = ['api/v1/me/notifications*', 'api/v1/analytics/*', 'api/v1/me', 'api/v1/devices*', 'api/v1/auth/*'];

    public function __construct(private Tracker $tracker) {}

    public static function isApp(Request $request): bool
    {
        return $request->hasHeader('X-App-Install') || str_starts_with((string) $request->userAgent(), 'Dart/');
    }

    /** Install id from the app, or (older builds) a daily hash that never contains the IP itself. */
    public static function installId(Request $request): string
    {
        $id = (string) $request->header('X-App-Install');
        if (Tracker::validId($id)) {
            return $id;
        }

        return 'h-'.substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString(), (string) config('app.key')), 0, 30);
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! self::isApp($request) || ! $this->tracker->enabled() || $request->is(...self::QUIET) || $response->getStatusCode() >= 400) {
            return;
        }
        $route = $request->route()?->uri();
        rescue(fn () => $this->tracker->record($request->is('api/v1/meta') ? 'app_open' : 'app_call', self::context($request, $this->tracker) + [
            'path' => '/'.ltrim($request->path(), '/'), 'route' => $route ? mb_substr($route, 0, 80) : null,
        ]), null, true);
    }

    /** @return array<string, mixed> */
    public static function context(Request $request, Tracker $tracker): array
    {
        $version = (string) $request->header('X-App-Version');

        return [
            'channel' => 'app', 'visitor' => self::installId($request), 'user_id' => $request->user('sanctum')?->id,
            'country' => $tracker->country($request), 'locale' => app()->getLocale(),
            'device' => 'mobile', 'os' => $request->header('X-App-Platform') === 'ios' ? 'iOS' : 'Android', 'browser' => 'App',
            'app_version' => preg_match('/^\d+(\.\d+){0,3}$/', $version) ? $version : null,
        ];
    }
}
