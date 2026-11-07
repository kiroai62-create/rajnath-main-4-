    {{-- Families --}}
    <section class="container-page reveal py-16" aria-labelledby="fam-h">
        <p class="eyebrow">{{ __('Find what you need') }}</p>
        <h2 id="fam-h" class="mt-2 text-2xl">{{ __('Browse by category') }}</h2>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($families as $f)
                <li class="card card-hover group relative p-5"><span class="icon-orb mb-3 size-10"><x-astro-icon name="sparkle" class="size-5" /></span>
                    <h3 class="text-base"><a href="{{ route('services.index', ['family' => $f->slug]) }}" class="no-underline after:absolute after:inset-0">{{ $f->label() }}</a></h3>
                    <p class="mt-2 text-sm text-ink-600">{{ $f->blurb() }}</p>
                    <p class="mt-3 text-xs font-semibold text-gold-700">{{ trans_choice(':count service|:count services', $f->offerings_count, ['count' => $f->offerings_count]) }}</p>
                </li>
            @endforeach
        </ul>
    </section>
