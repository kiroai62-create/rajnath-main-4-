{{-- Date range picker shared by the analytics pages. --}}
@php $days = (int) request('days', request('from') ? 0 : 30); @endphp
<div class="flex flex-wrap items-end justify-between gap-3">
    <nav aria-label="Analytics sections"><ul class="flex flex-wrap gap-2">
        @foreach (['admin.analytics.website' => 'Website', 'admin.analytics.tools' => 'Free tools', 'admin.analytics.app' => 'Mobile app'] as $route => $label)
            <li><a href="{{ route($route, request()->only('days', 'from', 'to')) }}" @class(['btn px-4', 'bg-ink-900 text-white' => request()->routeIs($route), 'border border-ink-200 bg-white text-ink-800' => ! request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a></li>
        @endforeach
    </ul></nav>
    <form method="GET" class="flex flex-wrap items-end gap-2">
        @foreach (request()->except('days', 'from', 'to', 'page') as $k => $v)@if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
        <div><label for="a-days" class="field-label">Period</label>
            <select id="a-days" name="days" class="input min-h-10">@foreach (\App\Http\Controllers\Admin\AnalyticsController::RANGES as $d => $l)<option value="{{ $d }}" @selected($days === $d)>{{ $l }}</option>@endforeach</select></div>
        <div><label for="a-from" class="field-label">or from</label><input id="a-from" type="date" name="from" value="{{ request('from') }}" class="input min-h-10"></div>
        <div><label for="a-to" class="field-label">to</label><input id="a-to" type="date" name="to" value="{{ request('to') }}" class="input min-h-10"></div>
        <button class="btn-outline min-h-10">Show</button>
    </form>
</div>
<p class="mt-2 text-xs text-ink-600">{{ $r->from->format('d M Y') }} – {{ $r->to->format('d M Y') }} (India time)</p>
