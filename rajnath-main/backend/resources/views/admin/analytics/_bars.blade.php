{{-- Daily bar chart. $series = [[Y-m-d, a, b]], $a / $b = labels of the two values, $title. --}}
@php $max = max(1, collect($series)->max(fn ($s) => $s[1])); $few = count($series) <= 31; @endphp
<section class="card p-5" aria-label="{{ $title }}">
    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-base">{{ $title }}</h2>
        <p class="text-xs text-ink-600"><span class="inline-block size-2.5 rounded-sm bg-gold-500"></span> {{ $a }} @if (! empty($b))· {{ $b }} shown on hover @endif</p></div>
    <ol class="mt-5 flex h-44 items-end gap-px sm:gap-1">
        @foreach ($series as [$d, $v, $w])
            <li class="group relative flex h-full flex-1 flex-col justify-end" title="{{ \Illuminate\Support\Carbon::parse($d)->format('D j M') }}: {{ number_format($v) }} {{ $a }}@if (! empty($b)), {{ number_format($w) }} {{ $b }}@endif">
                <span class="block rounded-t bg-gradient-to-t from-ink-700 to-gold-500 group-hover:opacity-80" style="height: {{ $v ? max(2, round($v * 100 / $max)) : 0 }}%"></span>
                <span class="sr-only">{{ \Illuminate\Support\Carbon::parse($d)->format('j M') }}: {{ $v }} {{ $a }}</span>
            </li>
        @endforeach
    </ol>
    @if ($series)<div class="mt-2 flex justify-between text-xs text-ink-600"><span>{{ \Illuminate\Support\Carbon::parse($series[0][0])->format('j M') }}</span>@if ($few && count($series) > 2)<span>{{ \Illuminate\Support\Carbon::parse($series[intdiv(count($series), 2)][0])->format('j M') }}</span>@endif<span>{{ \Illuminate\Support\Carbon::parse(end($series)[0])->format('j M') }}</span></div>@endif
</section>
