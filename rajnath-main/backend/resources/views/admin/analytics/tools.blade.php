@php $T = \App\Domain\Analytics\ToolHistory::allTools(); $C = \App\Http\Controllers\Admin\AnalyticsController::class; @endphp
<x-layouts.admin title="Free tools analytics">
    @include('admin.analytics._range')

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        @foreach ([['Calculations made', $sum['runs']], ['Panchang, muhurat & other lookups', $sum['lookups']], ['People', $sum['people']], ['PDFs downloaded', $sum['pdfs']], ['Kundlis made', $sum['kundli']], ['Kundli matchings', $sum['matching']]] as [$l, $v])
            <div class="card p-4"><p class="text-xs text-ink-600">{{ $l }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($v) }}</p></div>
        @endforeach
    </div>

    <div class="mt-6">@include('admin.analytics._bars', ['title' => 'Calculations per day'.(! empty($filters['tool']) ? ' — '.$T[$filters['tool']] : ''), 'a' => 'calculations', 'b' => 'PDFs'])</div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="card min-w-0 p-5" aria-labelledby="per-tool-h">
            <h2 id="per-tool-h" class="text-base">By calculator</h2>
            <div class="table-wrap mt-3" role="region" aria-label="Calculator use" tabindex="0"><table class="table">
                <thead><tr><th scope="col">Calculator</th><th scope="col" class="text-right">Made</th><th scope="col" class="text-right">People</th><th scope="col" class="text-right">PDFs</th><th scope="col" class="text-right">Website</th><th scope="col" class="text-right">App</th><th scope="col" class="text-right">Signed in</th></tr></thead>
                <tbody>
                @forelse ($totals as $t)
                    <tr>
                        <td><a href="{{ route('admin.analytics.tools', request()->only('days', 'from', 'to') + ['tool' => $t->tool]) }}">{{ $T[$t->tool] ?? $t->tool }}</a>@if (in_array($t->tool, $pdfOff, true)) <span class="badge-ink">PDF off</span>@endif</td>
                        <td class="text-right tabular-nums">{{ number_format($t->runs) }}</td><td class="text-right tabular-nums">{{ number_format($t->people) }}</td>
                        <td class="text-right tabular-nums">{{ number_format($t->pdfs) }}</td><td class="text-right tabular-nums">{{ number_format($t->web) }}</td>
                        <td class="text-right tabular-nums">{{ number_format($t->app) }}</td><td class="text-right tabular-nums">{{ number_format($t->signed_in) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-ink-600">No calculations in this period yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
        @include('admin.analytics._list', ['rows' => $countries, 'title' => 'Countries', 'label' => 'Country', 'nLabel' => 'Made', 'format' => fn ($k) => $C::countryName($k)])
    </div>

    <div class="mt-6">@include('admin.analytics._list', ['rows' => $pages, 'title' => 'Free pages viewed (Rashifal, Panchang, Gochar …)', 'label' => 'Page', 'format' => fn ($k) => $C::FREE_PAGES[$k] ?? $k])</div>

    <section class="mt-8" aria-labelledby="hist-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 id="hist-h" class="text-lg">History</h2>
            @if ($history)
            <form method="GET" class="flex flex-wrap items-end gap-2">
                @foreach (request()->only('days', 'from', 'to') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <x-select name="tool" label="Calculator" :value="$filters['tool'] ?? ''" :options="['' => 'All'] + $T" />
                <x-select name="channel" label="From" :value="$filters['channel'] ?? ''" :options="['' => 'Website and app', 'web' => 'Website', 'app' => 'App']" />
                <label class="flex min-h-10 items-center gap-2 text-sm"><input type="checkbox" name="pdf" value="1" @checked(! empty($filters['pdf'])) class="size-4 accent-ink-900"> PDF downloaded</label>
                <button class="btn-outline min-h-10">Filter</button>
                <a href="{{ route('admin.analytics.tools.export', request()->only('days', 'from', 'to')) }}" class="btn-outline min-h-10">Export CSV</a>
            </form>
            @endif
        </div>
        @if (! $history)
            <p class="mt-3 text-sm text-ink-600">Names and birth details are shown only to staff with the "calculator history" permission.</p>
        @else
        <div class="table-wrap mt-4" role="region" aria-label="Calculator history" tabindex="0"><table class="table">
            <thead><tr><th scope="col">When (IST)</th><th scope="col">Calculator</th><th scope="col">Details entered</th><th scope="col">Result</th><th scope="col">From</th><th scope="col" class="text-right">PDFs</th></tr></thead>
            <tbody>
            @forelse ($history as $run)
                <tr>
                    <td class="whitespace-nowrap"><a href="{{ route('admin.analytics.tools.show', $run) }}">{{ $run->created_at->tz('Asia/Kolkata')->format('d M, g:i A') }}</a></td>
                    <td>{{ $T[$run->tool] ?? $run->tool }}</td>
                    <td class="max-w-sm text-sm">{{ \Illuminate\Support\Str::limit($run->personLine(), 120) }}@if ($run->user)<br><a href="{{ route('admin.customers.show', $run->user) }}" class="text-xs">Customer: {{ $run->user->name ?: $run->user->phone_e164 }}</a>@endif</td>
                    <td class="max-w-xs text-xs text-ink-600">{{ $run->summary }}</td>
                    <td class="whitespace-nowrap text-xs">{{ $run->channel === 'app' ? 'App' : 'Website' }} · {{ ucfirst($run->device ?? '') }}<br>{{ $C::countryName($run->country) }}</td>
                    <td class="text-right tabular-nums">{{ $run->pdf_downloads }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-ink-600">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-4">{{ $history->links() }}</div>
        @endif
    </section>
    <p class="mt-4 text-xs text-ink-600">The PDF layout of each calculator is set in <a href="{{ route('admin.pdf-templates.index') }}">PDF templates</a>.</p>
</x-layouts.admin>
