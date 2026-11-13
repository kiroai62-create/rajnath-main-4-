@php $T = \App\Domain\Analytics\ToolHistory::allTools(); $C = \App\Http\Controllers\Admin\AnalyticsController::class; @endphp
<x-layouts.admin title="Mobile app analytics">
    @include('admin.analytics._range')

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        @foreach ([['App opens', $totals['opens']], ['Phones using the app', $totals['devices']], ['Opened today', $totals['today']], ['Signed-in customers', $totals['signed_in']], ['Feature requests', $totals['calls']], ['Calculations in the app', $totals['tool_runs']]] as [$l, $v])
            <div class="card p-4"><p class="text-xs text-ink-600">{{ $l }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($v) }}</p></div>
        @endforeach
    </div>

    <div class="mt-6">@include('admin.analytics._bars', ['title' => 'App opens per day', 'a' => 'opens', 'b' => 'phones'])</div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
        @include('admin.analytics._list', ['rows' => $features, 'title' => 'Most used features', 'label' => 'Feature', 'nLabel' => 'Uses', 'vLabel' => 'Phones'])
        @include('admin.analytics._list', ['rows' => $screens, 'title' => 'Screens viewed', 'label' => 'Screen', 'vLabel' => 'Phones'])
        <section class="card p-5" aria-label="Calculators used in the app">
            <h2 class="text-base">Calculators used in the app</h2>
            @if ($tools->isEmpty())<p class="mt-3 text-sm text-ink-600">None in this period.</p>@else
            <ul class="mt-3 space-y-1 text-sm">@foreach ($tools as $t)<li class="flex justify-between border-t border-ink-200/50 py-1.5"><span>{{ $T[$t->tool] ?? $t->tool }}</span><span class="tabular-nums">{{ number_format($t->n) }}</span></li>@endforeach</ul>@endif
        </section>
        @include('admin.analytics._list', ['rows' => $versions, 'title' => 'App versions', 'label' => 'Version', 'nLabel' => 'Requests', 'vLabel' => 'Phones'])
        @include('admin.analytics._list', ['rows' => $countries, 'title' => 'Countries', 'label' => 'Country', 'nLabel' => 'Requests', 'vLabel' => 'Phones', 'format' => fn ($k) => $C::countryName($k)])
        @include('admin.analytics._list', ['rows' => $locales, 'title' => 'App language', 'label' => 'Language', 'nLabel' => 'Requests', 'vLabel' => 'Phones', 'format' => fn ($k) => config('locales.available')[$k] ?? ($k ?? 'Unknown')])
    </div>
    <p class="mt-6 max-w-3xl text-xs text-ink-600">An "app open" is counted each time the app starts and loads its settings. App builds 1.12.1 and older are grouped per phone per day; newer builds send a random install id, their version and the screens viewed, so "Screens viewed" and "App versions" fill in once the new build is published.</p>
</x-layouts.admin>
