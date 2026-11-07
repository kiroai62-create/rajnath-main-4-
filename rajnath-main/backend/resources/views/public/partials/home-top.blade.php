{{-- Home hero: night sky, rotating zodiac wheel behind Acharya Ji's portrait, key numbers. --}}
@php
    $st = $profile->stats();
    $rv = app(\App\Domain\Reviews\Reviews::class)->summary();
    $price = fn (string $slug) => \App\Models\Offering::public()->with('variants')->where('slug', $slug)->first()?->fromPriceMinor();
    $fmt = fn (?int $m) => $m ? \App\Support\Money::format($m) : null;
    $name = $site('brand.practitioner_name');
@endphp

<section class="bg-cosmic relative isolate overflow-hidden text-white">
    <div aria-hidden="true" class="stars pointer-events-none absolute inset-0 -z-10 animate-twinkle opacity-70"></div>
    <div aria-hidden="true" class="stars pointer-events-none absolute inset-0 -z-10 opacity-40 [background-position:210px_140px]"></div>
    <div class="container-page grid items-center gap-12 py-14 md:py-20 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="animate-rise">
            <p class="eyebrow text-gold-200">{{ $profile->text('home.eyebrow') }}</p>
            <h1 class="mt-4 text-4xl leading-[1.08] text-white sm:text-5xl xl:text-6xl">
                {{ __('Clear, practical guidance from') }}
                <span class="text-gold-grad block pb-1">{{ $name }}</span>
            </h1>
            <p class="mt-5 max-w-xl text-lg leading-8 text-white/80">{{ $profile->text('home.subtitle') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-gold px-6 text-base">{{ __('Book a consultation') }}</a>
                <a href="{{ route('tools.show', 'kundli') }}" class="btn border border-gold-200/40 bg-white/5 px-6 text-base text-white backdrop-blur hover:bg-white/10">{{ __('Free Kundli') }}</a>
                <a href="{{ route('services.show', 'ask-one-question') }}" class="btn px-4 text-gold-200 underline decoration-gold-200/40 underline-offset-4 hover:text-white">{{ __('Ask a question') }}@if ($p = $fmt($price('ask-one-question'))) · {{ $p }}@endif</a>
            </div>
            <dl class="reveal mt-10 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (array_filter([[__('Years of practice'), $st['years'], 'sun'], [__('Clients guided'), $st['clients'], 'sparkle'], [__('Awards'), $st['awards'], 'star'], $rv['count'] ? [__('Client rating'), number_format($rv['average'], 1).'/5', 'moon'] : null]) as [$l, $n, $ic])
                    @if ($n !== '' && $n !== '0')
                        <div class="group flex flex-col rounded-2xl border border-gold-200/20 bg-white/5 p-4 backdrop-blur transition hover:border-gold-300/60 hover:bg-white/10">
                            <x-astro-icon :name="$ic" class="size-5 text-gold-300 transition duration-700 group-hover:rotate-180" />
                            <dt class="order-last text-xs text-white/70">{{ $l }}</dt>
                            <dd class="mt-2 text-2xl font-semibold text-white" data-count="{{ $n }}">{{ $n }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
        <div class="relative mx-auto w-full max-w-md">
            <x-zodiac-wheel class="pointer-events-none absolute left-1/2 top-1/2 -z-10 w-[135%] max-w-none -translate-x-1/2 -translate-y-1/2 animate-spin-slower opacity-70" />
            <figure class="relative mx-auto w-[78%]">
                <div aria-hidden="true" class="absolute -inset-2 rounded-t-[999px] rounded-b-[2rem] bg-gradient-to-b from-gold-300 via-saffron-500 to-maroon-600 opacity-90"></div>
                <x-photo :src="$profile->photo('profile.photo_home')" :alt="$name.' at his office in Patna'" eager
                         class="relative w-full rounded-t-[999px] rounded-b-[1.75rem] object-cover shadow-2xl" />
                <figcaption class="absolute -bottom-5 left-1/2 w-max -translate-x-1/2 rounded-full border border-gold-300/60 bg-ink-950/90 px-4 py-2 text-xs font-semibold text-gold-200 shadow-lg backdrop-blur">
                    {{ __('Ph.D. in Astrology · Patna and online') }}
                </figcaption>
            </figure>
            <span aria-hidden="true" class="absolute -left-2 top-[18%] hidden animate-float rounded-2xl border border-gold-200/30 bg-ink-950/80 px-3 py-2 text-xs text-white shadow-xl backdrop-blur sm:block">
                <span class="block font-semibold text-gold-300">{{ __('Kundli') }}</span>{{ __('Parashari method') }}
            </span>
            <span aria-hidden="true" class="absolute -right-3 top-[48%] hidden animate-float rounded-2xl border border-gold-200/30 bg-ink-950/80 px-3 py-2 text-xs text-white shadow-xl backdrop-blur [animation-delay:1.5s] sm:block">
                <span class="block font-semibold text-gold-300">{{ __('Vastu & Puja') }}</span>{{ __('Full vidhi, shubh muhurat') }}
            </span>
        </div>
    </div>
    <svg aria-hidden="true" class="block w-full text-ivory-50" viewBox="0 0 1440 48" preserveAspectRatio="none"><path fill="currentColor" d="M0 48h1440V24C1200 0 960 40 720 24S240 0 0 24z"/></svg>
</section>
