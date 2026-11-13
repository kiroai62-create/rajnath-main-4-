<?php

namespace App\Http\Controllers\Web;

use App\Domain\Analytics\Tracker;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Receives clicks and time-on-page from resources/js/app.js (navigator.sendBeacon). */
class AnalyticsBeaconController extends Controller
{
    public function __invoke(Request $request, Tracker $tracker): Response
    {
        if (! $tracker->enabled() || $tracker->ignored($request)) {
            return response()->noContent();
        }
        $data = $request->validate([
            'type' => ['required', 'in:click,engage'],
            'path' => ['required', 'string', 'max:300'],
            'label' => ['nullable', 'string', 'max:200'],
            'target' => ['nullable', 'string', 'max:300'],
            'seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);
        $ids = $request->attributes->get('analytics.ids') ?? $tracker->identify($request);
        if (! Tracker::validId((string) $request->cookie(Tracker::VISITOR_COOKIE))) {
            return response()->noContent();   // no page view was counted for this browser
        }
        $path = Tracker::cleanPath((string) parse_url($data['path'], PHP_URL_PATH));
        $tracker->record($data['type'], $tracker->context($request, $ids) + [
            'path' => mb_substr($path, 0, 300),
            'label' => isset($data['label']) ? mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($data['label']))), 0, 200) : null,
            'target' => self::target($data['target'] ?? null),
            'value' => $data['type'] === 'engage' ? (int) ($data['seconds'] ?? 0) : null,
        ]);

        return response()->noContent();
    }

    /** Link without its query string or fragment (those can hold personal data or tokens). */
    private static function target(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $p = parse_url($url);
        if ($p === false) {
            return null;
        }
        if (isset($p['scheme']) && in_array($p['scheme'], ['tel', 'mailto'], true)) {
            return $p['scheme'].':';
        }
        $own = ! isset($p['host']) || $p['host'] === parse_url((string) config('app.url'), PHP_URL_HOST) || $p['host'] === request()->getHost();

        return mb_substr(($own ? '' : $p['host']).Tracker::cleanPath($p['path'] ?? '/'), 0, 300);
    }
}
