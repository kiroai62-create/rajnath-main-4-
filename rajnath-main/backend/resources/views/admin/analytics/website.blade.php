@php
    $C = \App\Http\Controllers\Admin\AnalyticsController::class;
    $time = fn (int $s) => $s >= 60 ? intdiv($s, 60).'m '.($s % 60).'s' : $s.'s';
    $flagged = fn ($k) => $k ? $C::countryName($k).' ('.$k.')' : 'Unknown';
@endphp
<x-layouts.admin title="Website analytics">
    @include('admin.analytics._range')

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8">
        @foreach ([
            ['Visitors', number_format($totals['visitors']), number_format($totals['new_visitors']).' new'],
            ['Visits (sessions)', number_format($totals['sessions']), $totals['pages_per_session'].' pages per visit'],
            ['Page views', number_format($totals['pageviews']), null],
            ['Avg. time per visit', $time($totals['avg_seconds']), 'time the page was on screen'],
            ['Bounce rate', $totals['bounce_rate'].'%', 'left after one page'],
            ['Clicks', number_format($totals['clicks']), 'links and buttons'],
            ['Signed-in customers', number_format($totals['signed_in']), null],
            ['On the site now', number_format($totals['live']), 'last 5 minutes'],
        ] as [$label, $value, $hint])
            <div class="card p-4"><p class="text-xs text-ink-600">{{ $label }}</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $value }}</p>@if ($hint)<p class="text-xs text-ink-600">{{ $hint }}</p>@endif</div>
        @endforeach
    </div>

    <div class="mt-6">@include('admin.analytics._bars', ['title' => 'Page views per day', 'a' => 'page views', 'b' => 'visitors'])</div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @include('admin.analytics._list', ['rows' => $pages, 'title' => 'Most viewed pages', 'label' => 'Page'])
        @include('admin.analytics._list', ['rows' => $entries, 'title' => 'Where visits start', 'label' => 'First page', 'nLabel' => 'Visits'])
    </div>

    <section class="card mt-6 min-w-0 p-5" aria-labelledby="clicks-h">
        <h2 id="clicks-h" class="text-base">What people click</h2>
        @if (empty($clicks))<p class="mt-3 text-sm text-ink-600">No clicks recorded in this period yet.</p>@else
        <div class="table-wrap mt-3" role="region" aria-label="Clicks" tabindex="0"><table class="table">
            <thead><tr><th scope="col">Link or button</th><th scope="col">Goes to</th><th scope="col" class="text-right">Clicks</th><th scope="col" class="text-right">People</th></tr></thead>
            <tbody>@foreach ($clicks as $c)<tr><td>{{ $c->label ?: '—' }}</td><td class="max-w-xs truncate text-xs text-ink-600">{{ $c->target ?: '—' }}</td><td class="text-right tabular-nums">{{ number_format($c->n) }}</td><td class="text-right tabular-nums">{{ number_format($c->v) }}</td></tr>@endforeach</tbody>
        </table></div>@endif
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
        @include('admin.analytics._list', ['rows' => $countries, 'title' => 'Countries', 'label' => 'Country', 'format' => $flagged])
        @include('admin.analytics._list', ['rows' => $devices, 'title' => 'Devices', 'label' => 'Device', 'format' => fn ($k) => ucfirst($k ?? 'unknown')])
        @include('admin.analytics._list', ['rows' => $os, 'title' => 'Operating systems', 'label' => 'System'])
        @include('admin.analytics._list', ['rows' => $browsers, 'title' => 'Browsers', 'label' => 'Browser'])
        @include('admin.analytics._list', ['rows' => $locales, 'title' => 'Website language chosen', 'label' => 'Language', 'format' => fn ($k) => config('locales.available')[$k] ?? ($k ?? 'Unknown')])
        @include('admin.analytics._list', ['rows' => $referrers, 'title' => 'Coming from other websites', 'label' => 'Website'])
        @include('admin.analytics._list', ['rows' => $campaigns, 'title' => 'Campaigns (utm_source / medium / campaign)', 'label' => 'Campaign'])
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="card p-5 text-sm text-ink-700">
            <h2 class="text-base">How this is counted</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>A visitor is one browser (a first-party cookie). A visit ends after 30 minutes without a new page.</li>
                <li>Search engines, link previews and other bots are not counted. The control centre is not counted.</li>
                <li>Country comes from the visitor's IP address, looked up on this server; the IP address itself is not saved.
                    @unless ($geoLoaded)<strong>The country list is not loaded yet</strong>, so countries show as Unknown. It loads on the next deploy (or run <code>php artisan arj:geoip-import</code>).@endunless</li>
                <li>IP geolocation by <a href="https://db-ip.com" rel="noopener" target="_blank">DB-IP</a> (CC BY 4.0).</li>
            </ul>
        </section>
        @can('settings.manage')
        <form method="POST" action="{{ route('admin.analytics.settings') }}" class="card space-y-3 p-5">
            @csrf @method('PUT')
            <h2 class="text-base">Analytics settings</h2>
            @foreach (['enabled' => 'Count website and app visitors', 'respect_dnt' => 'Do not count browsers that ask not to be tracked (Do Not Track / Global Privacy Control)', 'tool_history' => 'Save the history of free-calculator use (names and birth details are encrypted)'] as $k => $l)
                <label class="flex min-h-9 items-start gap-2 text-sm"><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" name="{{ $k }}" value="1" @checked($settings[$k]) class="mt-1 size-4 accent-ink-900"> {{ $l }}</label>
            @endforeach
            <x-field name="retention_days" label="Delete analytics and calculator history older than (days)" type="number" min="30" max="3650" :value="$settings['retention_days']" />
            <button class="btn-primary">Save</button>
        </form>
        @endcan
    </div>
</x-layouts.admin>
