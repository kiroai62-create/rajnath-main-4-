@php $T = \App\Domain\Astro\Tools::TOOLS; $C = \App\Http\Controllers\Admin\AnalyticsController::class; $in = $run->inputs ?? []; @endphp
<x-layouts.admin :title="($T[$run->tool][0] ?? $run->tool).' · '.$run->created_at->tz('Asia/Kolkata')->format('d M Y, g:i A')">
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="card min-w-0 p-5 sm:p-6">
            <h2 class="text-lg">Result</h2>
            @if (! $display)
                <p class="mt-3 text-sm text-ink-600">The calculation service is unavailable, so the result cannot be shown right now.</p>
            @else
                @foreach ($display['sections'] as $s)
                    <div class="mt-5">
                        @if (! empty($s['title']))<h3 class="text-sm font-semibold">{{ $s['title'] }}</h3>@endif
                        @if (! empty($s['highlight']))<p class="mt-1 text-lg font-semibold text-maroon-700">{{ $s['highlight'] }}</p>@endif
                        @if (! empty($s['text']))<p class="mt-1 text-sm">{{ $s['text'] }}</p>@endif
                        @if (! empty($s['rows']))<dl class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">@foreach ($s['rows'] as [$k, $v])<div class="flex justify-between gap-3 border-b border-ink-200/50 py-1"><dt class="text-ink-600">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>@endforeach</dl>@endif
                        @if (! empty($s['items']))<ul class="mt-1 list-disc pl-5 text-sm">@foreach ($s['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>@endif
                        @if (! empty($s['table']))
                            <div class="table-wrap mt-2" role="region" aria-label="{{ $s['title'] ?? 'Table' }}" tabindex="0"><table class="table"><thead><tr>@foreach ($s['table']['head'] as $h)<th scope="col">{{ $h }}</th>@endforeach</tr></thead>
                                <tbody>@foreach ($s['table']['rows'] as $row)<tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div>
                        @endif
                    </div>
                @endforeach
            @endif
        </section>
        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-base">Details entered</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    @foreach ($in as $k => $v)
                        @continue(str_ends_with($k, 'place_id') || $v === null || $v === '')
                        <div><dt class="text-ink-600">{{ ucfirst(str_replace(['a_', 'b_', '_'], ['Person 1 ', 'Person 2 ', ' '], $k)) }}</dt><dd class="font-medium break-words">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            </section>
            <section class="card p-5 text-sm">
                <dl class="space-y-2">
                    <div><dt class="text-ink-600">From</dt><dd>{{ $run->channel === 'app' ? 'Mobile app' : 'Website' }} · {{ ucfirst($run->device ?? '—') }} · {{ $C::countryName($run->country) }}</dd></div>
                    <div><dt class="text-ink-600">Language</dt><dd>{{ config('locales.available')[$run->locale] ?? $run->locale }}</dd></div>
                    <div><dt class="text-ink-600">PDF downloads</dt><dd>{{ $run->pdf_downloads }}@if ($run->last_pdf_at) (last {{ $run->last_pdf_at->tz('Asia/Kolkata')->format('d M, g:i A') }})@endif</dd></div>
                    <div><dt class="text-ink-600">Customer account</dt><dd>@if ($run->user)<a href="{{ route('admin.customers.show', $run->user) }}">{{ $run->user->name ?: $run->user->phone_e164 }}</a>@else Guest @endif</dd></div>
                    <div><dt class="text-ink-600">Reference</dt><dd class="font-mono text-xs">{{ $run->ref }}</dd></div>
                </dl>
                <a href="{{ route('admin.analytics.tools.pdf', $run) }}" class="btn-outline mt-4 w-full">Download PDF</a>
            </section>
            @if ($events->isNotEmpty())
            <section class="card p-5">
                <h2 class="text-base">This visitor's recent activity</h2>
                <ol class="mt-3 space-y-1 text-xs">
                    @foreach ($events as $e)<li><span class="text-ink-600">{{ \Illuminate\Support\Carbon::parse($e->occurred_at, 'UTC')->tz('Asia/Kolkata')->format('d M, g:i A') }}</span> · {{ str_replace('_', ' ', $e->type) }} · {{ $e->label ?: $e->path }}</li>@endforeach
                </ol>
            </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
