@props(['title', 'text' => null])
{{-- Used instead of a bare "nothing here yet" line: says so kindly, then offers useful next steps. --}}
<div {{ $attributes->merge(['class' => 'card mt-6 p-6 text-center sm:p-8']) }}>
    <p class="text-3xl" aria-hidden="true">🪔</p>
    <h2 class="mt-2 text-lg">{{ $title }}</h2>
    @if ($text)<p class="mx-auto mt-2 max-w-xl text-sm text-ink-600">{{ $text }}</p>@endif
    {{ $slot }}
    <p class="mt-5 text-sm font-semibold text-ink-900">{{ __('Meanwhile, you may like') }}</p>
    <ul class="mx-auto mt-3 grid max-w-3xl gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            [route('tools.show', 'kundli'), 'kundli', __('Free Kundli')],
            [route('panchang'), 'panchang', __("Today's Panchang")],
            [route('rashifal'), 'festivals', __('Rashifal')],
            [route('services.index'), 'family-consultation', __('Book a consultation')],
        ] as [$href, $img, $label])
            <li><a href="{{ $href }}" class="card card-hover group flex items-center gap-3 p-2 text-left no-underline"><span class="shrink-0 overflow-hidden rounded-lg ring-1 ring-gold-200"><x-photo :src="'images/art/'.$img.'.jpg'" alt="" class="size-12 object-cover" /></span><span class="text-sm font-semibold text-ink-900">{{ $label }}</span></a></li>
        @endforeach
    </ul>
    <div class="mx-auto mt-5 max-w-xl text-left"><x-ask-ai :q="__('What can you help me with on this website?')" /></div>
</div>
