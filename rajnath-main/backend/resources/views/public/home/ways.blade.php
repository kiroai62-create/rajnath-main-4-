@php
    $st = $profile->stats();
    $rv = app(\App\Domain\Reviews\Reviews::class)->summary();
    $price = fn (string $slug) => \App\Models\Offering::public()->with('variants')->where('slug', $slug)->first()?->fromPriceMinor();
    $fmt = fn (?int $m) => $m ? \App\Support\Money::format($m) : null;
    $signs = ['मे', 'वृ', 'मि', 'क', 'सिं', 'क', 'तु', 'वृ', 'ध', 'म', 'कुं', 'मी'];
    $signSlugs = \App\Http\Controllers\Web\ContentController::signSlugs();
@endphp
{{-- Ways to get guidance --}}
<section class="container-page reveal py-16" aria-labelledby="ways-h">
    <p class="eyebrow">{{ __('Real astrologers. Real conversations.') }}</p>
    <h2 id="ways-h" class="mt-2 max-w-2xl text-2xl sm:text-3xl">{{ __('Choose how you want guidance') }}</h2>
    <div class="mt-8 grid gap-5 md:grid-cols-3">
        @foreach ([
            ['family-consultation', __('Talk to Acharya Ji'), __('One-to-one by audio, video or in person in Patna, for career, marriage, health and the decisions that cannot wait.'), route('services.show', 'consultation-with-acharya-ji'), $fmt($price('consultation-with-acharya-ji')), __('Book a call')],
            ['family-ask-a-question', __('Ask a question in writing'), __('Send one, two or three questions with your birth details. A written answer reaches your account in about 48 working hours.'), route('services.show', 'ask-one-question'), $fmt($price('ask-one-question')), __('Ask now')],
            ['family-reports', __('Personal reports'), __('Premium Kundli, career, finance, love, Sade Sati, Varshphal and more — prepared from your chart and delivered as a PDF.'), route('services.index', ['family' => 'reports']), $fmt($price('premium-personalized-kundli')), __('See reports')],
        ] as [$img, $t, $d, $href, $from, $cta])
            <article class="card card-hover group relative flex flex-col overflow-hidden">
                <div class="overflow-hidden"><x-photo :src="'images/art/'.$img.'.jpg'" alt="" class="aspect-[16/9] w-full object-cover transition duration-700 group-hover:scale-110 group-hover:rotate-1" /></div>
                <div class="flex flex-1 flex-col p-6">
                    <h3 class="text-lg"><a href="{{ $href }}" class="no-underline after:absolute after:inset-0">{{ $t }}</a></h3>
                    <p class="mt-2 flex-1 text-sm text-ink-600">{{ $d }}</p>
                    <p class="mt-5 flex items-center justify-between text-sm"><span class="font-semibold">{{ $from ? __('From :price', ['price' => $from]) : '' }}</span><span class="inline-flex items-center gap-1 font-semibold text-gold-700">{{ $cta }}<svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/></svg></span></p>
                </div>
            </article>
        @endforeach
    </div>
</section>
