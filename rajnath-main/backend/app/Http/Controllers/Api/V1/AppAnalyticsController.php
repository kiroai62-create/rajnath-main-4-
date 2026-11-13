<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analytics\Tracker;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackAppRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Screen views and taps sent by the app (POST /api/v1/analytics/events), a few at a time. */
class AppAnalyticsController extends Controller
{
    public function store(Request $request, Tracker $tracker): Response
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'max:50'],
            'events.*.type' => ['required', 'in:screen,tap'],
            'events.*.name' => ['required', 'string', 'max:120'],
            'events.*.seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);
        if (! $tracker->enabled()) {
            return response()->noContent();
        }
        $ctx = TrackAppRequest::context($request, $tracker);
        foreach ($data['events'] as $e) {
            $tracker->record($e['type'] === 'screen' ? 'app_screen' : 'app_tap', $ctx + [
                'label' => mb_substr(strip_tags($e['name']), 0, 120), 'value' => $e['seconds'] ?? null,
            ]);
        }

        return response()->noContent();
    }
}
