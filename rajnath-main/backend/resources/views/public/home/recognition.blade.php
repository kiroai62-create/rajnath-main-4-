    {{-- Recognition (Admin › About & awards) --}}
    @if ($highlights->isNotEmpty())
    <section class="bg-temple bg-ivory-100 py-16" aria-labelledby="rec-h">
        <div @class(['container-page reveal grid items-center gap-10', 'lg:grid-cols-[1.1fr_0.9fr]' => $awardPhotos->isNotEmpty()])>
            <div>
                <p class="eyebrow">{{ __('Recognition') }}</p>
                <h2 id="rec-h" class="mt-2 text-2xl sm:text-3xl">{{ __('Honoured in India and abroad') }}</h2>
                <ul class="mt-6 space-y-3 text-sm">
                    @foreach ($highlights as $e)
                        <li class="card card-hover group flex gap-3 p-4">
                            <span class="icon-orb size-9"><x-astro-icon name="star" class="size-4" /></span>
                            <span><strong>{{ \App\Domain\Showcase\Profile::tr($e, 'title') }}</strong>@if ($d = \App\Domain\Showcase\Profile::tr($e, 'detail')) — {{ $d }}@endif @if ($e->year && ! str_contains((string) $d.$e->title, (string) $e->year))({{ $e->year }})@endif</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('awards') }}" class="btn-primary">{{ __('All awards') }}</a>
                    <a href="{{ route('about') }}" class="btn-outline">{{ __('Full biography & awards') }}</a>
                </div>
            </div>
            @if ($awardPhotos->isNotEmpty())
            <div @class(['grid items-start gap-4', 'grid-cols-2' => $awardPhotos->count() > 1])>
                @foreach ($awardPhotos as $n => $ph)
                    <figure @class(['group', 'mt-10' => $n === 1])>
                        <div class="aspect-[4/5] overflow-hidden rounded-[var(--radius-card)] shadow-[var(--shadow-card)] ring-2 ring-gold-300/70">
                            <x-photo :src="$ph['src']" :alt="$ph['alt']" class="size-full object-cover object-top transition duration-700 group-hover:scale-[1.04]" />
                        </div>
                        <figcaption class="mt-2 line-clamp-2 text-xs text-ink-600">{{ $ph['alt'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
            @endif
        </div>
    </section>
    @endif
