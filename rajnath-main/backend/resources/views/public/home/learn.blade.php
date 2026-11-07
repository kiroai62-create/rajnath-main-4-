{{-- Learn Jyotish: topic tiles linking to the guide. --}}
<section class="container-page py-16" aria-labelledby="learn-h">
    <div class="reveal">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Jyotish gyan') }}</p>
                <h2 id="learn-h" class="mt-2 text-2xl sm:text-3xl">{{ __('Understand Vedic astrology') }}</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-600">{{ __('A plain-language guide to the ideas behind every reading: houses, nakshatras, dashas, yogas, doshas, remedies and Vastu.') }}</p>
            </div>
            <a href="{{ route('learn') }}" class="btn-outline">{{ __('Open the guide') }}</a>
        </div>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (\App\Support\JyotishGyan::TOPICS as $slug => [$t, $hi, $ic, $d])
                <li><a href="{{ route('learn') }}#{{ $slug }}" class="card card-hover group flex h-full items-start gap-4 p-5 no-underline">
                    <span class="icon-orb size-12"><x-astro-icon :name="$ic" class="size-6" /></span>
                    <span>
                        <span class="block font-semibold text-ink-900 group-hover:text-maroon-700">{{ __($t) }} <span class="text-sm font-normal text-ink-600" lang="hi">· {{ $hi }}</span></span>
                        <span class="mt-1 block text-sm text-ink-600">{{ __($d) }}</span>
                    </span>
                </a></li>
            @endforeach
        </ul>
    </div>
</section>
