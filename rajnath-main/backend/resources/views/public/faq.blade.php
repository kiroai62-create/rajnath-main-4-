<x-layouts.public :title="__('Questions & answers')" :description="__('Answers to common questions about consultations, birth details, languages, timings, remedies and privacy.')" :jsonld="[\App\Support\Seo\JsonLd::faq($faqs->map(fn ($f) => [$f->question, $f->answer])), \App\Support\Seo\JsonLd::breadcrumbs([[__('Questions & answers'), route('faq')]])]">
    <x-page-hero>
        <x-slot:eyebrow>Help</x-slot:eyebrow>
        <x-slot:title>Questions & answers</x-slot:title>
    </x-page-hero>
    <div class="container-page max-w-3xl py-12">
        <div class="mt-8 space-y-3">
            @forelse ($faqs as $f)
                <details class="card group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium">
                        {{ $f->question }}<span aria-hidden="true" class="text-gold-700 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 leading-7 text-ink-700">{{ $f->answer }}</p>
                </details>
            @empty
                <x-empty-state :title="__('No questions published yet.')" :text="__('Ask our AI assistant — it knows our services, prices and policies.')" />
            @endforelse
        </div>
        <p class="mt-10 text-center text-ink-600">Still have a question? <a href="{{ route('contact') }}">Contact us</a>.</p>
    </div>
</x-layouts.public>
