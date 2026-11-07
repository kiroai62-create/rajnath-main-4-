    {{-- In the spotlight: videos from published gallery albums --}}
    @php $videos = \Illuminate\Support\Facades\DB::table('gallery_items as i')->join('gallery_albums as a', 'a.id', '=', 'i.album_id')->where('a.published', true)->where('i.kind', 'video')->orderByDesc('i.id')->limit(3)->get(['i.video_id', 'i.caption', 'a.title', 'a.slug']); @endphp
    @if ($videos->isNotEmpty())
    <section class="container-page reveal pt-16" aria-labelledby="vid-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><p class="eyebrow">{{ __('In the spotlight') }}</p><h2 id="vid-h" class="mt-2 text-2xl">{{ __('Watch Acharya Ji') }}</h2></div>
            <a href="{{ route('gallery') }}" class="btn-outline">{{ __('All videos and photos') }}</a>
        </div>
        <ul class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ($videos as $v)
                <li class="card relative overflow-hidden">
                    <img src="{{ \App\Domain\Showcase\Gallery::videoThumb($v->video_id) }}" alt="" width="480" height="360" loading="lazy" decoding="async" class="aspect-video w-full object-cover">
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 grid aspect-video place-items-center"><span class="grid size-14 place-items-center rounded-full bg-ink-900/75 text-white"><svg class="ml-1 size-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></span>
                    <p class="p-4 text-sm font-semibold"><a href="{{ route('gallery.show', $v->slug) }}" class="no-underline after:absolute after:inset-0">{{ $v->caption ?: $v->title }}</a></p>
                </li>
            @endforeach
        </ul>
    </section>
    @endif
