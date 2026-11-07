    {{-- Gallery (Admin › Gallery, albums marked "home page") --}}
    @if ($albums->isNotEmpty())
    <section class="container-page reveal pt-16" aria-labelledby="gal-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Gallery') }}</p>
                <h2 id="gal-h" class="mt-2 text-2xl">{{ __('Pujas, events and moments') }}</h2>
            </div>
            <a href="{{ route('gallery') }}" class="btn-outline">{{ __('Open the gallery') }}</a>
        </div>
        <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($albums as $a)@include('public.gallery.card', ['a' => $a])@endforeach
        </ul>
    </section>
    @endif
