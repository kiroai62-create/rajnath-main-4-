    {{-- Reviews (Admin › Reviews; featured ones first) --}}
    @php $homeReviews = app(\App\Domain\Reviews\Reviews::class)->featured(3); $rs = app(\App\Domain\Reviews\Reviews::class)->summary(); @endphp
    @if ($homeReviews->isNotEmpty())
    <section class="container-page reveal pt-16" aria-labelledby="rev-h">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Reviews') }}</p>
                <h2 id="rev-h" class="mt-2 text-2xl">{{ __('What clients say') }}</h2>
                <p class="mt-1 flex items-center gap-2 text-sm text-ink-600"><x-stars :rating="$rs['average']" /> {{ number_format($rs['average'], 1) }} / 5 · {{ trans_choice(':count review|:count reviews', $rs['count'], ['count' => $rs['count']]) }}
                    @if ($g = \App\Domain\Reviews\Reviews::google()) · <a href="{{ $g['url'] }}" rel="noopener" target="_blank">{{ __(':avg/5 on Google (:count ratings)', ['avg' => number_format($g['average'], 1), 'count' => $g['count']]) }}</a>@endif</p>
            </div>
            <a href="{{ route('reviews') }}" class="btn-outline">{{ __('All reviews') }}</a>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ($homeReviews as $r)<x-review-card :r="$r" clamp />@endforeach
        </div>
    </section>
    @endif
