<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Puja\PujaRequests;
use App\Http\Controllers\Controller;
use App\Models\PujaRequest;
use App\Support\Audit\AuditLogger;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin › Puja requests: call the visitor back, fix the date, priest and dakshina, and track it to completion. */
class PujaRequestController extends Controller
{
    public function __construct(private AuditLogger $audit, private PujaRequests $requests) {}

    public function index(Request $request): View
    {
        $this->authorize('bookings.view');
        $status = (string) $request->query('status', 'new');
        $q = trim((string) $request->query('q', ''));
        $list = PujaRequest::query()
            ->when($status === 'upcoming', fn ($w) => $w->where('status', 'scheduled')->where('scheduled_at', '>=', now()->subDay())->orderBy('scheduled_at'))
            ->when(! in_array($status, ['all', 'upcoming'], true), fn ($w) => $w->where('status', $status))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
                $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('phone_e164', 'like', $like)->orWhere('puja_type', 'like', $like)->orWhere('ref', 'like', $like));
            })
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.puja-requests.index', [
            'list' => $list, 'status' => $status, 'q' => $q,
            'counts' => DB::table('puja_requests')->select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status'),
            'upcoming' => PujaRequest::where('status', 'scheduled')->where('scheduled_at', '>=', now()->subDay())->count(),
            'types' => $this->requests->types(), 'intro' => $this->requests->intro(), 'open' => $this->requests->open(),
        ]);
    }

    public function show(PujaRequest $pujaRequest): View
    {
        $this->authorize('bookings.view');
        $this->audit->record('puja_request.viewed', $pujaRequest);

        return view('admin.puja-requests.show', ['r' => $pujaRequest->load('handler')]);
    }

    public function update(Request $request, PujaRequest $pujaRequest): RedirectResponse
    {
        $this->authorize('bookings.manage');
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(PujaRequest::STATUSES))],
            'scheduled_date' => ['nullable', 'required_if:status,scheduled', 'date_format:Y-m-d'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'priest' => ['nullable', 'string', 'max:120'],
            'quoted' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'meeting_link' => ['nullable', 'url:https', 'max:300'],
            'staff_note' => ['nullable', 'string', 'max:4000'],
        ], ['scheduled_date.required_if' => 'Choose the puja date to mark it as "Date fixed".']);
        $this->requests->update($pujaRequest, $data, $request->user());

        return redirect()->route('admin.puja-requests.show', $pujaRequest)->with('status', 'Puja request updated.');
    }

    public function settings(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $this->authorize('bookings.manage');
        $data = $request->validate([
            'types' => ['required', 'string', 'max:6000'],
            'intro' => ['nullable', 'string', 'max:600'],
            'open' => ['nullable', 'boolean'],
        ]);
        $types = array_values(array_unique(array_filter(array_map(fn ($l) => mb_substr(trim($l), 0, 150), preg_split('/\R/', $data['types'])))));
        $settings->set('puja_request.types', $types, false, 'puja');
        $settings->set('puja_request.intro', trim((string) ($data['intro'] ?? '')), false, 'puja');
        $settings->set('puja_request.open', (bool) ($data['open'] ?? false), false, 'puja');

        return back()->with('status', 'Puja request form saved.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('bookings.manage');
        $status = (string) $request->query('status', 'all');
        $this->audit->record('puja_request.exported', null, ['status' => $status]);
        $rows = PujaRequest::query()->when(! in_array($status, ['all', 'upcoming'], true), fn ($w) => $w->where('status', $status))->orderByDesc('id')->cursor();
        $csv = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;

        return response()->streamDownload(function () use ($rows, $csv) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Received (IST)', 'Status', 'Name', 'Phone', 'Email', 'Puja', 'Mode', 'Address', 'Preferred date', 'Second date', 'Time', 'Fixed for (IST)', 'Priest', 'Dakshina (INR)', 'Purpose', 'Note']);
            foreach ($rows as $r) {
                fputcsv($out, array_map($csv, [
                    $r->ref, $r->created_at->tz('Asia/Kolkata')->format('Y-m-d H:i'), PujaRequest::STATUSES[$r->status] ?? $r->status, $r->name, $r->phone_e164, $r->email,
                    $r->puja_type, PujaRequest::MODES[$r->mode] ?? $r->mode, $r->address, $r->preferred_date?->toDateString(), $r->alternate_date?->toDateString(),
                    PujaRequest::TIMES[$r->preferred_time] ?? $r->preferred_time, $r->scheduled_at?->tz('Asia/Kolkata')->format('Y-m-d H:i'), $r->priest,
                    $r->quoted_minor !== null ? number_format($r->quoted_minor / 100, 2, '.', '') : '', $r->purpose, $r->staff_note,
                ]));
            }
            fclose($out);
        }, 'puja-requests-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
