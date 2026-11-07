{{-- Today's Panchang (Patna) and the free Moon-sign finder. --}}
@php $pc = \App\Support\HomeSky::panchang(); @endphp
<section class="container-page relative z-10 -mt-4 pb-6" aria-labelledby="today-h">
    <div class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
        <div class="card bg-temple relative overflow-hidden p-6 sm:p-7">
            <x-astro-icon name="sun" class="pointer-events-none absolute -right-8 -top-8 size-40 animate-spin-slow text-gold-200/60" />
            <p class="eyebrow">{{ __("Today's Panchang") }} · {{ __('Patna') }}</p>
            <h2 id="today-h" class="mt-2 text-2xl">{{ $pc['date'] ?? now('Asia/Kolkata')->format('l, j F Y') }}</h2>
            @if ($pc)
                <p class="mt-1 text-sm text-ink-600">{{ $pc['vara'] }}@if ($pc['masa']) · {{ __(':m maas', ['m' => $pc['masa']]) }}@endif @if ($pc['moon_sign']) · {{ __('Moon in :s', ['s' => $pc['moon_sign']]) }}@endif</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['moon', __('Tithi'), $pc['tithi'], $pc['tithi_ends'] ? __('till :t', ['t' => $pc['tithi_ends']]) : null],
                        ['star', __('Nakshatra'), $pc['nakshatra'], $pc['nakshatra_ends'] ? __('till :t', ['t' => $pc['nakshatra_ends']]) : null],
                        ['yoga', __('Yoga'), $pc['yoga'], $pc['karana'] ? __('Karana :k', ['k' => $pc['karana']]) : null],
                        ['sun', __('Sunrise · Sunset'), $pc['sunrise'].' · '.$pc['sunset'], null],
                        ['shield', __('Rahu Kaal'), $pc['rahu_kaal'], __('Avoid new starts')],
                        ['sparkle', __('Abhijit muhurat'), $pc['abhijit'], __('Good for important work')],
                    ] as [$ic, $l, $v, $note])
                        @if ($v)
                            <div class="group rounded-2xl border border-gold-200/70 bg-white/80 p-3.5">
                                <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gold-700"><x-astro-icon :name="$ic" class="size-4 transition duration-500 group-hover:scale-125" />{{ $l }}</dt>
                                <dd class="mt-1.5 font-semibold text-ink-900">{{ $v }}</dd>
                                @if ($note)<dd class="text-xs text-ink-600">{{ $note }}</dd>@endif
                            </div>
                        @endif
                    @endforeach
                </dl>
            @else
                <p class="mt-4 text-ink-600">{{ __('Tithi, nakshatra, Rahu Kaal, Choghadiya and Hora for any city.') }}</p>
            @endif
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('panchang') }}" class="btn-primary">{{ __('Full Panchang for your city') }}</a>
                <a href="{{ route('muhurat.index') }}" class="btn-outline">{{ __('Shubh muhurat') }}</a>
                <a href="{{ route('calendar') }}" class="btn-ghost">{{ __('Vrat calendar') }}</a>
            </div>
        </div>

        <form method="POST" action="{{ route('tools.compute', 'moon-sign') }}" class="card bg-cosmic stars relative space-y-3 overflow-hidden p-6 text-white sm:p-7" novalidate aria-labelledby="ms-h">
            @csrf
            <x-zodiac-wheel class="pointer-events-none absolute -bottom-24 -right-24 size-72 animate-spin-slow opacity-30" />
            <p class="eyebrow text-gold-200">{{ __('Free · 10 seconds') }}</p>
            <p id="ms-h" class="text-xl font-semibold text-white">{{ __('Find your Moon sign (Rashi)') }}</p>
            <p class="text-sm text-white/75">{{ __('Your Rashi is the sign the Moon was in at birth. Rashifal is read from it.') }}</p>
            <div class="relative grid gap-3 sm:grid-cols-2 [&_.field-label]:text-white/90">
                <x-field name="birth_date" :label="__('Date of birth')" type="date" min="1800-01-01" max="2399-12-31" required id="hero-dob" />
                <x-field name="birth_time" :label="__('Time of birth')" type="time" required id="hero-tob" />
            </div>
            <div x-data="placePicker" class="relative">
                <label for="place-hero" class="field-label text-white/90">{{ __('Place of birth') }}</label>
                <input id="place-hero" type="text" name="place_label" class="input" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="list-hero" :aria-expanded="expanded"
                       x-model="query" @input="onInput" @keydown="onKey" @blur="close" placeholder="{{ __('Start typing a town or city') }}" aria-describedby="status-hero">
                <input type="hidden" name="place_id" :value="placeId">
                <ul id="list-hero" role="listbox" x-show="open" x-cloak class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-ink-200 bg-white py-1 text-ink-900 shadow-[var(--shadow-card)]">
                    <template x-for="(pl, index) in results" :key="pl.id">
                        <li role="option" :data-index="index" :aria-selected="index === active ? 'true' : 'false'" class="cursor-pointer px-3 py-2.5 text-sm aria-selected:bg-ivory-100 hover:bg-ivory-50" @mousedown.prevent="choose"><span x-text="pl.label"></span></li>
                    </template>
                </ul>
                <p id="status-hero" class="sr-only" role="status" aria-live="polite" x-text="status"></p>
            </div>
            <button class="btn-gold relative w-full">{{ __('Find my Moon sign') }}</button>
        </form>
    </div>
</section>
