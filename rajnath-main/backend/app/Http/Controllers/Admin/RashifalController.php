<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\GocharRashifal;
use App\Domain\Content\HoroscopeTypes;
use App\Http\Controllers\Controller;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin › Rashifal: exactly what the website shows today for every sign (written by AI, the
 * built-in engine or staff), with an edit box under each one. Text edited here is marked as
 * written by staff, so the AI writer never replaces it; the earlier text is kept in the audit log.
 */
class RashifalController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function live(Request $request): View
    {
        $this->authorize('content.author');
        $d = $request->validate([
            'kind' => ['nullable', Rule::in(HoroscopeTypes::PUBLIC_KINDS)],
            'period' => ['nullable', Rule::in(array_keys(HoroscopeTypes::PERIODS))],
            'topic' => ['nullable', Rule::in(array_keys(HoroscopeTypes::TOPICS))],
            'locale' => ['nullable', Rule::in(['hi', 'en'])],
        ]);
        $kind = $d['kind'] ?? 'rashi';
        $period = $d['period'] ?? ($kind === 'rashi' || $kind === 'nakshatra' ? 'daily' : ($kind === 'numerology' ? 'monthly' : 'yearly'));
        $topic = $kind === 'rashi' ? ($d['topic'] ?? 'general') : 'general';
        $locale = $d['locale'] ?? HoroscopeTypes::contentLocale(null);
        $today = now('Asia/Kolkata')->toDateString();
        rescue(fn () => GocharRashifal::fillToday($kind, $period), null, true);
        $rows = DB::table('horoscopes')->where(compact('kind', 'topic', 'period', 'locale'))->whereIn('status', ['published', 'draft'])
            ->where('starts_on', '<=', $today)->where('ends_on', '>=', $today)->orderByRaw("status = 'published' desc")->get()->groupBy('sign');

        return view('admin.ops.rashifal-live', [
            'kind' => $kind, 'period' => $period, 'topic' => $topic, 'locale' => $locale, 'rows' => $rows,
            'names' => HoroscopeTypes::subjects($kind, 'hi'), 'namesEn' => HoroscopeTypes::subjects($kind),
            'counts' => DB::table('horoscopes')->where('status', 'published')->where('starts_on', '<=', $today)->where('ends_on', '>=', $today)
                ->selectRaw('source, count(*) as n')->groupBy('source')->pluck('n', 'source'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorize('content.publish');
        $h = DB::table('horoscopes')->find($id);
        abort_unless($h && in_array($h->status, ['published', 'draft'], true), 404);
        $d = $request->validate(['body' => ['required', 'string', 'min:40', 'max:5000'], 'version' => ['required', 'integer']]);
        if ((int) $d['version'] !== (int) $h->version) {
            return back()->withErrors(['body' => 'Someone changed this text a moment ago. The page now shows their version; make your change again.']);
        }
        if (trim($d['body']) === trim($h->body)) {
            return back()->with('status', 'No change.');
        }
        DB::table('horoscopes')->where('id', $id)->where('version', $h->version)->update([
            'body' => trim($d['body']), 'source' => 'staff', 'status' => 'published', 'author_id' => $request->user()->id, 'reviewer_id' => $request->user()->id,
            'published_at' => now(), 'version' => $h->version + 1, 'updated_at' => now(),
        ]);
        $this->audit->record('content.horoscope_edited', null, ['id' => $id, 'kind' => $h->kind, 'sign' => $h->sign, 'period' => $h->period,
            'previous_source' => $h->source, 'previous_text' => mb_substr($h->body, 0, 5000)]);

        return back()->with('status', 'Saved. The Rashifal page shows the new text now (the home page within 10 minutes).');
    }
}
