@php
    $courses = [
        ['book', __('Vedic astrology for beginners'), __('Rashi, bhava, graha and nakshatra; reading a birth chart step by step.')],
        ['numbers', __('Numerology'), __('Mulank, Bhagyank, name numbers and the Lo Shu grid.')],
        ['hand', __('Palmistry (Hastrekha)'), __('Lines, mounts and signs of the hand.')],
        ['house', __('Vastu Shastra'), __('Directions, zones and corrections without demolition.')],
        ['kalash', __('Karmkand and puja vidhi'), __('Sankalp, nitya puja and the main samskaras.')],
        ['chart', __('Advanced predictive astrology'), __('Dasha, transits, divisional charts and muhurat.')],
    ];
@endphp
<x-layouts.public :title="__('Astrology courses — coming soon')" :description="__('Courses in Vedic astrology, numerology, palmistry, Vastu and karmkand by Acharya Dr. Rajnath Jha are being prepared.')" image="images/art/courses.jpg"
    :jsonld="[\App\Support\Seo\JsonLd::breadcrumbs([[__('Courses'), route('courses')]])]">
    <section class="container-page grid items-center gap-10 py-12 lg:grid-cols-2">
        <div>
            <span class="badge-gold">{{ __('Coming soon') }}</span>
            <h1 class="mt-4 text-3xl sm:text-4xl">{{ __('Learn Jyotish with Acharya Ji') }}</h1>
            <p class="mt-4 max-w-xl text-lg text-ink-600">{{ __('Acharya Dr. Rajnath Jha has taught at B.H.U. and K.S.D.S.U. and is founding a Vedic Gurukul in Madhubani. Online courses in Hindi and English are being prepared.') }}</p>
            <p class="mt-4 text-ink-600">{{ __('Leave your number below and we will tell you when admissions open.') }}</p>
        </div>
        <x-photo src="images/art/courses.jpg" :alt="__('Astrology courses')" eager class="w-full rounded-[var(--radius-card)] shadow-[var(--shadow-card)]" />
    </section>

    <section class="container-page pb-6" aria-labelledby="planned-h">
        <h2 id="planned-h" class="text-2xl">{{ __('Courses being prepared') }}</h2>
        <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as [$motif, $title, $text])
                <li class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gold-700">{{ __('Coming soon') }}</p>
                    <h3 class="mt-1 text-base">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink-600">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="bg-cosmic stars relative isolate mt-10 overflow-hidden text-white" aria-labelledby="free-h">
        <x-zodiac-wheel class="pointer-events-none absolute -right-28 top-1/2 -z-10 hidden size-[26rem] -translate-y-1/2 animate-spin-slower opacity-25 sm:block" />
        <div class="container-page py-12">
            <p class="eyebrow text-gold-200">{{ __('Start today, free') }}</p>
            <h2 id="free-h" class="mt-2 text-2xl text-white sm:text-3xl">{{ __('Begin learning while the courses are prepared') }}</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [route('learn'), __('Learn Jyotish guide'), __('Rashi, graha, bhava and nakshatra explained simply.')],
                    [route('blog', ['category' => 'kundli']), __('Astrology articles'), __('Kundli, dasha, Sade Sati and more.')],
                    [route('blog', ['category' => 'vastu']), __('Vastu articles'), __('Directions, zones and simple corrections.')],
                    [route('tools.index'), __('Practise with calculators'), __('Make your own kundli and numerology chart.')],
                ] as [$href, $t, $d])
                    <li><a href="{{ $href }}" class="block h-full rounded-[var(--radius-card)] border border-gold-200/30 bg-white/5 p-4 no-underline transition hover:bg-white/10"><span class="block font-semibold text-gold-200">{{ $t }}</span><span class="mt-1 block text-sm text-white/75">{{ $d }}</span></a></li>
                @endforeach
            </ul>
        </div>
    </section>
    <section class="container-page pt-10">
        <x-ask-ai q="Teach me the basics of Vedic astrology in 5 short lessons, starting with lesson 1." :label="__('Learn with the AI tutor')" :suggestions="[__('What are the 12 rashis?'), __('What is a nakshatra?'), __('How do I read my kundli?')]" />
    </section>

    <section class="container-page py-12">
        <div class="card mx-auto max-w-2xl p-5 sm:p-6">
            <h2 class="text-lg">{{ __('Tell me when admissions open') }}</h2>
            @include('public.partials.enquiry-form', ['offering' => null])
        </div>
    </section>
</x-layouts.public>
