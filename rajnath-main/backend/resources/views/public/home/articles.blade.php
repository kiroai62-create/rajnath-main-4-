    {{-- Latest articles --}}
    @php $latestPosts = app(\App\Domain\Blog\Blog::class)->latest(3); @endphp
    @if ($latestPosts->isNotEmpty())
    <section class="container-page reveal pt-16" aria-labelledby="art-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><p class="eyebrow">{{ __('Articles') }}</p><h2 id="art-h" class="mt-2 text-2xl">{{ __('Learn from the blog') }}</h2></div>
            <a href="{{ route('blog') }}" class="btn-outline">{{ __('All articles') }}</a>
        </div>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($latestPosts as $lp)@include('public.blog.card', ['p' => $lp])@endforeach</div>
    </section>
    @endif
