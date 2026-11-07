    {{-- Featured services from the live catalogue --}}
    @if ($featured->isNotEmpty())
    <section class="container-page reveal py-16" aria-labelledby="featured-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Services') }}</p>
                <h2 id="featured-h" class="mt-2 text-2xl">{{ __('How Acharya Ji can help') }}</h2>
            </div>
            <a href="{{ route('services.index') }}" class="btn-outline">{{ __('All services') }}</a>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featured as $o)<div class="relative"><x-service-card :o="$o" /></div>@endforeach
        </div>
    </section>
    @endif
