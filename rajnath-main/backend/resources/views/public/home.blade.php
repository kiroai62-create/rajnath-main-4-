@php
    $homeFaqs = [
        [__('How do I book a consultation with Acharya Ji?'), __('Open Consultations, choose audio, video or in person, pick a time and pay online. You can also call or WhatsApp the office.')],
        [__('What do I need for a kundli reading?'), __('Your date, exact time and place of birth. If the time is not known, palmistry and Prashna kundli can still help.')],
        [__('How soon do reports and written answers arrive?'), __('Written answers in about 48 working hours, reports in 3–5 working days, in your account and by email.')],
        [__('Can I consult from outside India?'), __('Yes. Consultations are held by video or audio call, and payment is online.')],
    ];
@endphp
<x-layouts.public :description="__('Vedic astrology consultations, Kundli, Vastu and remedies with Acharya Dr. Rajnath Jha, Ph.D. in Astrology, Director of Jyotirveda Vigyan Sansthan, Patna.')" :jsonld="[\App\Support\Seo\JsonLd::faq($homeFaqs), ['@type' => 'ItemList', 'name' => __('How Acharya Ji can help'), 'itemListElement' => $featured->values()->map(fn ($o, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => route('services.show', $o->slug), 'name' => $o->t()?->title])->all()]]">
    @include('public.partials.home-top')

    @php $siteContent = app(\App\Support\SiteContent::class); @endphp
    @foreach ($siteContent->order() as $sectionKey)
        @if ($siteContent->sectionOn($sectionKey))
            @include('public.home.'.$sectionKey)
        @endif
    @endforeach
</x-layouts.public>
