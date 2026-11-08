<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\AiProviders;
use App\Domain\Content\RashifalWriter;
use App\Domain\Notify\Fcm;
use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcast;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Admin › Push messages: write a message (by hand or with AI) and send it to every customer's
 * app inbox, phone (push) and email. Sending needs a fresh step-up; AI only fills the form.
 */
class BroadcastController extends Controller
{
    public const KINDS = ['offer' => 'Offer or promotion (customers who turned off offers do not get it)', 'general' => 'Important announcement (sent to everyone)'];

    public function index(Fcm $fcm): View
    {
        $this->authorize('content.publish');

        return view('admin.broadcast.index', [
            'audience' => User::query()->where('kind', User::KIND_CUSTOMER)->where('status', User::STATUS_ACTIVE)->count(),
            'devices' => DB::table('push_tokens')->count(), 'pushReady' => $fcm->configured(), 'aiReady' => app(AiProviders::class)->available(),
            'recent' => DB::table('user_notifications')->where('ref', 'like', 'bc%')->whereRaw('CHAR_LENGTH(ref) = 26')->groupBy('ref')
                ->selectRaw('ref, MIN(title) as title, MIN(kind) as kind, COUNT(*) as recipients, MIN(created_at) as sent_at')->orderByDesc('sent_at')->limit(15)->get(),
        ]);
    }

    /** "Write with AI": fills the title and message from a topic. Nothing is sent. */
    public function ai(Request $request, AiProviders $ai, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('content.publish');
        $d = $request->validate(['topic' => ['required', 'string', 'min:3', 'max:400'], 'language' => ['nullable', Rule::in(['en', 'hi', 'both'])], 'kind' => ['nullable', Rule::in(array_keys(self::KINDS))], 'link' => ['nullable', 'string', 'max:255']]);
        try {
            if (! $ai->available()) {
                throw new RuntimeException('AI is not switched on. Add a key in Admin › AI assistant first.');
            }
            $lang = ['en' => 'simple English', 'hi' => 'simple Hindi (Devanagari)', 'both' => 'Hindi first, then the same in English'][$d['language'] ?? 'en'];
            [, , $text] = $ai->complete([
                ['role' => 'system', 'content' => 'You write short, warm, honest app notifications for an Indian Vedic astrology practice. Return strict JSON only.'],
                ['role' => 'user', 'content' => "Write a push notification for Acharya Dr. Rajnath Jha's app (astrorajnathjha.com) about: {$d['topic']}\n"
                    ."Language: {$lang}. Title max 60 characters, message max 220 characters, one emoji at most. Do not invent prices, dates or discounts that are not in the topic; never promise results.\n"
                    .'Return ONLY {"title": "...", "body": "..."}'],
            ], 600);
            $j = RashifalWriter::extractJson($text);
            if (empty($j['title']) || empty($j['body'])) {
                throw new RuntimeException('The AI answer could not be read. Please try again.');
            }
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['topic' => mb_substr($e->getMessage(), 0, 300)]);
        }
        $audit->record('broadcast.ai_draft', null, ['topic' => Str::limit($d['topic'], 80)]);

        return back()->withInput(['topic' => $d['topic'], 'language' => $d['language'] ?? 'en', 'kind' => $d['kind'] ?? 'offer', 'link' => $d['link'] ?? null,
            'title' => mb_substr(strip_tags((string) $j['title']), 0, 80), 'body' => mb_substr(strip_tags((string) $j['body']), 0, 300)])
            ->with('status', 'AI draft ready. Read it, change anything, then send.');
    }

    public function send(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('content.publish');
        $d = $request->validate([
            'title' => ['required', 'string', 'max:80'], 'body' => ['required', 'string', 'max:300'],
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'link' => ['nullable', 'string', 'max:255', 'regex:#^(https://|/|app:)#'], 'confirm' => ['accepted'],
        ], ['link.regex' => 'Start the link with https://, / (a page on the website) or app: (a screen in the app).', 'confirm.accepted' => 'Tick the box to confirm sending to all customers.']);
        // 26 characters like other refs: "bc" + 24 random, so sent messages can be listed together.
        $ref = 'bc'.Str::lower(Str::random(24));
        $link = $d['link'] ?? null;
        SendBroadcast::dispatch($ref, $d['kind'], trim(strip_tags($d['title'])), trim(strip_tags($d['body'])), $link !== null && str_starts_with($link, '/') ? url($link) : $link);
        $audit->record('broadcast.sent', null, ['ref' => $ref, 'kind' => $d['kind'], 'title' => $d['title']]);

        return redirect()->route('admin.broadcast.index')->with('status', 'Sending started. Customers get it in the app inbox, as a push and by email within a few minutes.');
    }
}
