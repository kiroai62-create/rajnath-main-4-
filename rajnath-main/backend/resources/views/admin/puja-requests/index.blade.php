<x-layouts.admin title="Puja requests">
    <p class="max-w-3xl text-sm text-ink-600">Requests from the website form at <a href="{{ route('puja-request.create') }}" target="_blank" rel="noopener">{{ route('puja-request.create') }}</a>.
        Call the person, agree the puja, date, place and dakshina, then open the request and set it to <strong>Date fixed</strong>. Signed-in customers get a notification with the date.</p>
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Filter by status"><ul class="flex flex-wrap gap-2">
            @foreach (['new' => 'New', 'contacted' => 'Contacted', 'upcoming' => 'Upcoming pujas', 'scheduled' => 'Date fixed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'spam' => 'Spam', 'all' => 'All'] as $k => $l)
                <li><a href="{{ route('admin.puja-requests.index', ['status' => $k]) }}" @class(['btn px-4', 'bg-ink-900 text-white' => $status === $k, 'border border-ink-200 bg-white text-ink-800' => $status !== $k]) @if ($status === $k) aria-current="page" @endif>{{ $l }}@if ($k === 'upcoming') ({{ $upcoming }})@elseif ($k !== 'all') ({{ $counts[$k] ?? 0 }})@endif</a></li>
            @endforeach
        </ul></nav>
        <div class="flex flex-wrap gap-2">
            <form method="GET" class="flex gap-2"><input type="hidden" name="status" value="all"><label for="q" class="sr-only">Search</label><input id="q" name="q" value="{{ $q }}" class="input min-h-10 w-56" placeholder="Name, phone or puja"><button class="btn-outline">Search</button></form>
            @can('bookings.manage')<a href="{{ route('admin.puja-requests.export', ['status' => $status]) }}" class="btn-outline">Export CSV</a>@endcan
        </div>
    </div>
    <div class="table-wrap mt-5" role="region" aria-label="Puja requests" tabindex="0">
        <table class="table">
            <thead><tr><th scope="col">Received (IST)</th><th scope="col">Name</th><th scope="col">Puja</th><th scope="col">Where</th><th scope="col">Wanted</th><th scope="col">Fixed for</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @forelse ($list as $r)
                <tr>
                    <td class="whitespace-nowrap">{{ $r->created_at->tz('Asia/Kolkata')->format('d M, g:i A') }}</td>
                    <td><a href="{{ route('admin.puja-requests.show', $r) }}" class="font-medium">{{ $r->name }}</a><br><span class="text-xs text-ink-600">{{ \App\Support\Logging\Redactor::mask($r->phone_e164) }}</span></td>
                    <td>{{ $r->puja_type }}</td>
                    <td>{{ \Illuminate\Support\Str::before(\App\Models\PujaRequest::MODES[$r->mode] ?? $r->mode, ' (') }}</td>
                    <td class="whitespace-nowrap">{{ $r->preferred_date?->format('d M Y') ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ $r->scheduled_at?->tz('Asia/Kolkata')->format('d M Y, g:i A') ?? '—' }}</td>
                    <td><span @class(['badge-gold' => $r->status === 'new', 'badge-success' => in_array($r->status, ['scheduled', 'completed']), 'badge-ink' => ! in_array($r->status, ['new', 'scheduled', 'completed'])])>{{ \App\Models\PujaRequest::STATUSES[$r->status] ?? $r->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-ink-600">No puja requests here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $list->links() }}</div>

    @can('bookings.manage')
    <form method="POST" action="{{ route('admin.puja-requests.settings') }}" class="card mt-10 max-w-3xl space-y-4 p-5 sm:p-6">
        @csrf @method('PUT')
        <h2 class="text-lg">Form settings</h2>
        <label class="flex min-h-10 items-center gap-2 text-sm"><input type="hidden" name="open" value="0"><input type="checkbox" name="open" value="1" @checked($open) class="size-4 accent-ink-900"> Accept new puja requests on the website</label>
        <div>
            <label for="f-types" class="field-label">Pujas in the drop-down (one per line)</label>
            <textarea id="f-types" name="types" rows="10" class="input font-mono text-sm">{{ old('types', implode("\n", $types)) }}</textarea>
            <p class="field-hint">Visitors can always choose "Other / not sure" and write their own.</p>
        </div>
        <div>
            <label for="f-intro" class="field-label">Text at the top of the form</label>
            <textarea id="f-intro" name="intro" rows="3" maxlength="600" class="input">{{ old('intro', $intro) }}</textarea>
        </div>
        <button class="btn-primary">Save form settings</button>
    </form>
    @endcan
</x-layouts.admin>
