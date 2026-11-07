<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\WritingHelper;
use App\Http\Controllers\Controller;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/** POST /admin/ai/assist — the "✨ AI" helper under admin text boxes (returns text, saves nothing). */
class AiAssistController extends Controller
{
    public function __invoke(Request $request, WritingHelper $helper, AuditLogger $audit): JsonResponse
    {
        $d = $request->validate([
            'action' => ['required', Rule::in(array_keys(WritingHelper::ACTIONS))],
            'field' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_\-\[\]\.]+$/'],
            'text' => ['nullable', 'string', 'max:20000'],
            'context' => ['nullable', 'string', 'max:6000'],
            'instructions' => ['nullable', 'string', 'max:400'],
        ]);
        try {
            $text = $helper->run($d['action'], $d['field'], (string) ($d['text'] ?? ''), (string) ($d['context'] ?? ''), (string) ($d['instructions'] ?? ''));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => ['code' => 'ai_unavailable', 'message' => mb_substr($e->getMessage(), 0, 300)]], 503);
        }
        $audit->record('ai.writing_help', null, ['action' => $d['action'], 'field' => $d['field']]);

        return response()->json(['text' => $text]);
    }
}
