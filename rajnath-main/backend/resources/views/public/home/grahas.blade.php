{{-- Where the nine planets are today, with links to the transit pages. --}}
@php
    $planets = \App\Support\HomeSky::planets();
    $hues = ['sun' => 'from-amber-300 to-orange-600', 'moon' => 'from-slate-100 to-slate-400', 'mars' => 'from-red-400 to-red-700', 'mercury' => 'from-emerald-300 to-emerald-700',
        'jupiter' => 'from-yellow-200 to-amber-500', 'venus' => 'from-pink-100 to-rose-400', 'saturn' => 'from-indigo-400 to-slate-800', 'rahu' => 'from-zinc-400 to-zinc-800', 'ketu' => 'from-orange-300 to-stone-600'];
@endphp
@if ($planets)
<section class="container-page py-16" aria-labelledby="graha-h">
    <div class="reveal">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Navagraha · live') }}</p>
                <h2 id="graha-h" class="mt-2 text-2xl sm:text-3xl">{{ __('Where the nine planets are today') }}</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-600">{{ __('Sidereal positions (Lahiri) updated every hour. Open a planet to see its sign changes, retrograde periods and what its transit means for your Moon sign.') }}</p>
            </div>
            <a href="{{ route('gochar') }}" class="btn-outline">{{ __('Grah gochar') }}</a>
        </div>
        <ul class="mt-8 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-9">
            @foreach ($planets as $p => $x)
                <li><a href="{{ route('gochar.planet', $p) }}" class="card card-hover group flex h-full flex-col items-center p-3 text-center no-underline">
                    <span aria-hidden="true" class="relative grid size-14 place-items-center">
                        <span class="absolute inset-0 rounded-full border border-dashed border-gold-500/50 transition duration-[3s] group-hover:rotate-180"></span>
                        <span class="size-8 rounded-full bg-gradient-to-br {{ $hues[$p] }} shadow-[inset_-4px_-4px_8px_rgb(0_0_0/0.25)] transition duration-500 group-hover:scale-110"></span>
                        @if ($p === 'saturn')<span class="absolute h-2 w-12 -rotate-12 rounded-[50%] border border-indigo-300/80"></span>@endif
                    </span>
                    <span class="mt-2 text-sm font-semibold text-ink-900">{{ __(strtok(\App\Domain\Astro\Vedic::PLANET_NAMES[$p], ' ')) }}</span>
                    <span class="text-xs text-ink-600" lang="hi">{{ \App\Domain\Astro\Gochar::HI[$p] }}</span>
                    <span class="mt-1.5 rounded-full bg-gold-50 px-2 py-0.5 text-xs font-semibold text-gold-700 ring-1 ring-gold-200">{{ __($x['sign_name']) }}</span>
                    <span class="mt-1 text-[0.7rem] text-ink-600">{{ $x['deg'] }}@if ($x['retro'] && ! in_array($p, ['rahu', 'ketu'], true)) · {{ __('Retro') }}@endif</span>
                </a></li>
            @endforeach
        </ul>
    </div>
</section>
@endif
