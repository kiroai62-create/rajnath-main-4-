@php
    $st = $profile->stats();
    $rv = app(\App\Domain\Reviews\Reviews::class)->summary();
    $price = fn (string $slug) => \App\Models\Offering::public()->with('variants')->where('slug', $slug)->first()?->fromPriceMinor();
    $fmt = fn (?int $m) => $m ? \App\Support\Money::format($m) : null;
    $signs = ['मे', 'वृ', 'मि', 'क', 'सिं', 'क', 'तु', 'वृ', 'ध', 'म', 'कुं', 'मी'];
    $signSlugs = \App\Http\Controllers\Web\ContentController::signSlugs();
@endphp
{{-- Everything in one place --}}
<section class="container-page reveal py-16" aria-labelledby="eco-h">
    <p class="eyebrow">{{ __('Everything for your spiritual journey') }}</p>
    <h2 id="eco-h" class="mt-2 max-w-2xl text-2xl sm:text-3xl">{{ __('One place for guidance, ritual and learning') }}</h2>
    <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['kundli', __('Free Kundli'), __('Birth chart, Navamsa and dasha in seconds.'), route('tools.show', 'kundli')],
            ['kundli-matching', __('Kundli matching'), __('36-guna milan with Manglik check.'), route('tools.show', 'kundli-matching')],
            ['family-puja', __('Online puja'), __('Sankalp in your name, performed on a shubh muhurat.'), route('services.index', ['family' => 'puja'])],
            ['family-chadhava', __('Chadhava'), __('Offerings at temples on your behalf.'), route('services.index', ['family' => 'chadhava'])],
            ['gems', __('Gemstones and samagri'), __('Certified stones, rudraksha, yantra and pooja kits.'), route('store.index')],
            ['panchang', __('Panchang'), __('Tithi, nakshatra, Rahu Kaal, Choghadiya and Hora.'), route('panchang')],
            ['muhurat', __('Shubh muhurat'), __('Dates for vivah, griha pravesh, mundan and more.'), route('muhurat.index')],
            ['gochar', __('Grah gochar'), __('Where the planets are now, and what it means for your sign.'), route('gochar')],
            ['festivals', __('Festivals and vrat'), __('Calendar for the year, by city.'), route('festivals')],
            ['calculators', __('26 free calculators'), __('Numerology, Lo Shu, Sade Sati, Lal Kitab and more.'), route('tools.index')],
            ['blog', __('Articles'), __('Guides to kundli, Vastu, remedies and festivals.'), route('blog')],
            ['courses', __('Courses'), __('Learn Jyotish with Acharya Ji — coming soon.'), route('courses')],
        ] as [$img, $t, $d, $href])
            <li><a href="{{ $href }}" class="card card-hover group flex h-full items-center gap-4 p-3 no-underline">
                <span class="shrink-0 overflow-hidden rounded-xl ring-1 ring-gold-200"><x-photo :src="'images/art/'.$img.'.jpg'" alt="" class="size-20 object-cover transition duration-700 group-hover:rotate-6 group-hover:scale-125" /></span>
                <span><span class="block font-semibold text-ink-900 group-hover:text-maroon-700">{{ $t }}</span><span class="mt-0.5 block text-sm text-ink-600">{{ $d }}</span></span>
            </a></li>
        @endforeach
    </ul>
</section>
