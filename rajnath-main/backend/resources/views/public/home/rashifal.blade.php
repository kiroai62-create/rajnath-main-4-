{{-- Today's Rashifal: 12 signs with a one-line preview of today's reading. --}}
@php
    $signs = ['मे', 'वृ', 'मि', 'क', 'सिं', 'क', 'तु', 'वृ', 'ध', 'म', 'कुं', 'मी'];
    $glyphs = ['♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓'];
    $signSlugs = \App\Http\Controllers\Web\ContentController::signSlugs();
    $preview = \App\Support\HomeSky::rashifalPreview();
@endphp
<section class="bg-cosmic relative isolate overflow-hidden py-16 text-white" aria-labelledby="rf-h">
    <div aria-hidden="true" class="stars pointer-events-none absolute inset-0 -z-10 animate-twinkle opacity-60"></div>
    <x-zodiac-wheel class="pointer-events-none absolute -left-40 top-1/2 -z-10 size-[34rem] -translate-y-1/2 animate-spin-slower opacity-25" />
    <div class="container-page reveal">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow text-gold-200">{{ __('Rashifal') }} · {{ now('Asia/Kolkata')->format('j M Y') }}</p>
                <h2 id="rf-h" class="mt-2 text-3xl text-white sm:text-4xl">{{ __('Your sign') }} <span class="text-gold-grad">{{ __('today') }}</span></h2>
                <p class="mt-2 max-w-xl text-sm text-white/70">{{ __('Written from today\'s real planetary positions (Swiss Ephemeris, Lahiri). Choose your Moon sign for the full reading, weekly, monthly and yearly Rashifal.') }}</p>
            </div>
            <a href="{{ route('rashifal') }}" class="btn border border-gold-200/40 bg-white/5 text-white hover:bg-white/10">{{ __('Daily, weekly and yearly Rashifal') }}</a>
        </div>
        <ul class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach (\App\Domain\Astro\Vedic::SIGNS as $i => $name)
                <li><a href="{{ route('rashi', $signSlugs[$i]) }}" class="group flex h-full flex-col items-center rounded-2xl border border-gold-200/15 bg-white/[0.06] p-4 text-center text-white no-underline backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-gold-300/70 hover:bg-white/10 hover:shadow-[0_18px_40px_-18px_rgb(224_165_38/0.6)]">
                    <span aria-hidden="true" class="icon-orb relative size-14 text-xl font-semibold">
                        <span lang="hi">{{ $signs[$i] }}</span>
                    </span>
                    <span class="mt-2.5 text-sm font-semibold">{{ __(strtok($name, ' ')) }}</span>
                    <span class="text-xs text-gold-200/80">{{ trim(strstr($name, ' '), ' ()') }}</span>
                    @if ($t = $preview[$i] ?? null)<span class="mt-2 line-clamp-3 text-xs leading-5 text-white/70">{{ $t }}</span>@endif
                </a></li>
            @endforeach
        </ul>
    </div>
</section>
