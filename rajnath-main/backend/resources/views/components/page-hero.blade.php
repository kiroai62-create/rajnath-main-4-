@props(['eyebrow' => null, 'title', 'lang' => null])
{{-- Page header in the home page's style: night sky, stars, a slowly turning zodiac wheel, gold eyebrow. --}}
<section class="bg-cosmic stars relative isolate overflow-hidden text-white" @if ($lang) lang="{{ $lang }}" @endif>
    <x-zodiac-wheel class="pointer-events-none absolute -right-28 top-1/2 -z-10 hidden size-[28rem] -translate-y-1/2 animate-spin-slower opacity-25 sm:block" />
    <div class="container-page relative py-12 sm:py-14">
        @if ($eyebrow)<p class="eyebrow text-gold-200">{{ $eyebrow }}</p>@endif
        <h1 class="mt-2 max-w-3xl text-3xl text-white sm:text-4xl">{{ $title }}</h1>
        @if (trim((string) $slot) !== '')<div class="mt-3 max-w-2xl text-white/80">{{ $slot }}</div>@endif
    </div>
</section>
