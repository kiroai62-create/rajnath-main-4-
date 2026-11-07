@php use App\Domain\Showcase\Gallery; use App\Domain\Showcase\Profile; @endphp
<x-layouts.public :title="$category ? __(Gallery::CATEGORIES[$category]).' — '.__('Gallery') : __('Gallery: pujas, events and awards')"
    :description="__('Photos and videos of pujas, anushthans, award ceremonies, seminars and media appearances of :name.', ['name' => $site('brand.practitioner_name')])"
    :image="($c = $albums->first()?->cover) && $c->kind === 'photo' ? $c->path : null"
    :jsonld="[['@type' => 'CollectionPage', 'url' => route('gallery'), 'name' => __('Gallery'), 'about' => ['@id' => \App\Support\Seo\JsonLd::id('founder')],
        'hasPart' => $albums->map(fn ($a) => ['@type' => 'ImageGallery', 'name' => Profile::tr($a, 'title'), 'url' => route('gallery.show', $a->slug), 'thumbnailUrl' => Gallery::coverUrl($a)])->all()],
        \App\Support\Seo\JsonLd::breadcrumbs([[__('Gallery'), route('gallery')]])]">
    <section class="bg-cosmic stars relative overflow-hidden text-white">
        <img src="/images/brand/logo-mark-light.svg" alt="" aria-hidden="true" class="pointer-events-none absolute -right-28 -top-24 size-[30rem] animate-spin-slower opacity-10">
        <div class="container-page relative py-14">
            <p class="eyebrow text-gold-200">{{ __('Gallery') }}</p>
            <h1 class="mt-2 text-3xl text-white sm:text-5xl">{{ __('Pujas, events and') }} <span class="text-gold-grad">{{ __('moments') }}</span></h1>
            <p class="mt-3 max-w-2xl text-white/80">{{ __('Pujas and anushthans performed by Acharya Ji, award ceremonies in India and abroad, seminars, temple darshan, television and newspaper coverage.') }}</p>
            <dl class="mt-8 flex flex-wrap gap-3">
                @foreach ([[__('Albums'), $totals['albums']], [__('Photos'), $totals['photos']], [__('Videos'), $totals['videos']]] as [$l, $n])
                    @if ($n)<div class="rounded-2xl border border-white/15 bg-white/5 px-5 py-3 backdrop-blur"><dt class="text-xs text-white/70">{{ $l }}</dt><dd class="text-2xl font-semibold text-gold-200">{{ $n }}</dd></div>@endif
                @endforeach
            </dl>
        </div>
    </section>

    <section class="container-page py-10" @if ($view === 'photos') x-data="lightbox" @keydown.window="key" @endif>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav aria-label="{{ __('View') }}" class="inline-flex rounded-full border border-ink-200 bg-white p-1 text-sm">
                <a href="{{ route('gallery', array_filter(['category' => $category])) }}" @class(['btn min-h-9 px-4', 'bg-ink-900 text-white' => $view === 'albums', 'text-ink-800' => $view !== 'albums']) @if ($view === 'albums') aria-current="page" @endif>{{ __('Albums') }}</a>
                <a href="{{ route('gallery', array_filter(['category' => $category, 'view' => 'photos'])) }}" @class(['btn min-h-9 px-4', 'bg-ink-900 text-white' => $view === 'photos', 'text-ink-800' => $view !== 'photos']) @if ($view === 'photos') aria-current="page" @endif>{{ __('All photos') }}</a>
            </nav>
            @if ($categories->count() > 1)
                <nav aria-label="{{ __('Filter by category') }}" class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('gallery', array_filter(['view' => $view === 'photos' ? 'photos' : null])) }}" @class(['chip', '!border-gold-500 !bg-gold-50 font-semibold' => ! $category]) @if (! $category) aria-current="page" @endif>{{ __('All') }}</a>
                    @foreach ($categories as $k => $l)<a href="{{ route('gallery', array_filter(['category' => $k, 'view' => $view === 'photos' ? 'photos' : null])) }}" @class(['chip', '!border-gold-500 !bg-gold-50 font-semibold' => $category === $k]) @if ($category === $k) aria-current="page" @endif>{{ __($l) }}</a>@endforeach
                </nav>
            @endif
        </div>

        @if ($view === 'photos')
            @if ($photos->isEmpty())
                <p class="card mt-8 p-8 text-center text-ink-600">{{ __('Photos will appear here soon.') }}</p>
            @else
                <ul class="mt-8 columns-2 gap-4 sm:columns-3 lg:columns-4" aria-label="{{ __('All photos') }}">
                    @foreach ($photos as $n => $i)
                        @php $cap = Profile::tr($i, 'caption') ?: Profile::tr($i, 'title'); @endphp
                        <li class="mb-4 break-inside-avoid">
                            <button type="button" class="group relative block w-full overflow-hidden rounded-xl shadow-[var(--shadow-card)] focus-visible:outline-2" @click="openAt"
                                    data-lb data-index="{{ $n }}" data-kind="photo" data-caption="{{ $cap }}" data-src="/{{ $i->path }}" data-video="" aria-label="{{ __('Open photo') }}: {{ $cap }}">
                                <x-photo :src="$i->thumb_path ?: $i->path" :alt="$cap" class="w-full transition duration-500 group-hover:scale-[1.04]" />
                                <span aria-hidden="true" class="absolute inset-x-0 bottom-0 translate-y-full bg-gradient-to-t from-ink-950/85 to-transparent p-3 pt-8 text-left text-xs font-medium text-white transition group-hover:translate-y-0">{{ $cap }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                @include('public.gallery.lightbox')
            @endif
        @elseif ($albums->isEmpty())
            <p class="card mt-8 p-8 text-center text-ink-600">{{ __('Photos will appear here soon.') }}</p>
        @else
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $a)@include('public.gallery.card', ['a' => $a])@endforeach
            </ul>
        @endif
    </section>
</x-layouts.public>
