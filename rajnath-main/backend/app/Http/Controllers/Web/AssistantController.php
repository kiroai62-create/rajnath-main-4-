<?php

namespace App\Http\Controllers\Web;

use App\Domain\Assistant\Assistant;
use App\Domain\Assistant\AssistantLimitReached;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/** Assistant chat for the website (guests allowed with a small daily limit) and the app (Bearer). */
class AssistantController extends Controller
{
    public function __construct(private Assistant $assistant) {}

    public function page(Request $request): View
    {
        return view('public.assistant', ['enabled' => true, 'live' => $this->assistant->live(), 'signedIn' => (bool) $request->user()]);
    }

    public function chat(Request $request): JsonResponse
    {
        $d = $request->validate(['message' => ['required', 'string', 'max:1000'], 'conversation' => ['nullable', 'string', 'size:26'], 'page' => ['nullable', 'string', 'max:160']]);
        $u = $request->user();
        if (! $u && $request->is('api/*') && $request->bearerToken()) {
            $t = auth('sanctum')->user();
            $u = $t && $t->tokenCan('customer') && $t->isActive() && ! $t->isStaff() ? $t : null;
        }
        if ($u?->isStaff()) {
            $u = null; // staff testing the public chat count as visitors
        }
        try {
            $r = $this->assistant->chat($u, $request->is('api/*') ? 'app' : 'web', $d['message'], $d['conversation'] ?? null, $request->ip(), $d['page'] ?? null);
        } catch (AssistantLimitReached $e) {
            return response()->json(['error' => ['code' => 'daily_limit', 'message' => $e->getMessage()]], 429);
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'assistant_unavailable') {
                report($e);
            }

            return response()->json(['error' => ['code' => 'assistant_unavailable', 'message' => 'The assistant is not available right now. Please contact our team.']], 503);
        }

        return response()->json($r);
    }
}
