<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analytics\ToolHistory;
use App\Domain\Analytics\ToolPdf;
use App\Domain\Astro\PanchangService;
use App\Domain\Astro\ToolDisplay;
use App\Domain\Astro\Tools;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackAppRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ToolApiController extends Controller
{
    public function compute(Request $request, string $slug, Tools $tools, ToolHistory $history, ToolPdf $pdf): JsonResponse
    {
        if (! isset(Tools::TOOLS[$slug])) {
            return AuthController::error('not_found', 'Unknown tool.', 404);
        }
        $in = $request->validate(Tools::rules(Tools::TOOLS[$slug][1]));
        try {
            $r = $tools->compute($slug, $in);
            $run = $history->record($slug, $in, $r, $request, TrackAppRequest::isApp($request) ? 'app' : 'web', ToolHistory::visitorFor($request));
            $pdfUrl = $run && $pdf->template($slug)['enabled']
                ? URL::temporarySignedRoute('tools.pdf', now()->addDay(), ['slug' => $slug, 'run' => $run->ref, 'via' => 'app']) : null;

            return response()->json(['data' => $r + ['display' => ToolDisplay::for($r), 'pdf_url' => $pdfUrl]]);
        } catch (RuntimeException $e) {
            report($e);

            return AuthController::error('engine_unavailable', 'The calculation service is unavailable. Please try again shortly.', 503);
        }
    }

    public function panchang(Request $request, PanchangService $panchang, ToolHistory $history): JsonResponse
    {
        $d = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1800-01-01', 'before_or_equal:2399-12-31'], 'place_id' => ['required', 'integer', 'exists:places,id']]);
        $p = DB::table('places')->where('id', $d['place_id'])->first();
        try {
            $day = $panchang->day($d['date'], (float) $p->lat, (float) $p->lng, $p->tz_name);
            $history->lookup('panchang', ['date' => $d['date'], 'place' => ToolHistory::placeLabel($p)], 'Panchang '.$d['date'].' · '.$p->name, $request, ToolHistory::channelFor($request), ToolHistory::visitorFor($request));

            return response()->json(['data' => $day]);
        } catch (RuntimeException $e) {
            report($e);

            return AuthController::error('engine_unavailable', 'The calculation service is unavailable. Please try again shortly.', 503);
        }
    }
}
