@php
    use App\Support\JyotishGyan as G;
    use App\Domain\Astro\Vedic;
    $planetName = fn (string $p) => Vedic::PLANET_NAMES[$p] ?? ucfirst($p);
    $faq = [
        [__('Is astrology a substitute for a doctor, lawyer or financial adviser?'), __('No. Jyotish is traditional guidance about timing and tendencies. For health, legal or money decisions, always consult a qualified professional.')],
        [__('Why do Vedic and Western sun signs differ?'), __('Vedic astrology uses the sidereal zodiac (fixed to the stars, Lahiri ayanamsha), which today is about 24° behind the tropical zodiac used in the West, so most people\'s signs move back by one.')],
        [__('Is my Rashi the same as my sun sign?'), __('In Vedic astrology your Rashi is your Moon sign, the sign the Moon occupied at birth. Rashifal is read from it.')],
    ];
@endphp
<x-layouts.public :title="__('Learn Jyotish: a guide to Vedic astrology')" :description="__('The 12 houses, 27 nakshatras, Panchang, Vimshottari dasha, yogas, doshas, remedies and Vastu directions, explained plainly by the team of Acharya Dr. Rajnath Jha.')"
    :jsonld="[\App\Support\Seo\JsonLd::faq($faq), \App\Support\Seo\JsonLd::breadcrumbs([[__('Learn Jyotish'), route('learn')]])]">
    <section class="bg-cosmic relative isolate overflow-hidden text-white">
        <div aria-hidden="true" class="stars pointer-events-none absolute inset-0 -z-10 animate-twinkle opacity-60"></div>
        <x-zodiac-wheel class="pointer-events-none absolute -right-32 top-1/2 -z-10 size-[30rem] -translate-y-1/2 animate-spin-slower opacity-40" />
        <div class="container-page py-16">
            <p class="eyebrow text-gold-200">{{ __('Jyotish gyan') }} · ज्योतिष ज्ञान</p>
            <h1 class="mt-3 max-w-3xl text-4xl text-white sm:text-5xl">{{ __('Learn') }} <span class="text-gold-grad">{{ __('Vedic astrology') }}</span></h1>
            <p class="mt-4 max-w-2xl text-lg text-white/80">{{ __('The ideas behind every reading, in plain language: how a Kundli is built, what the houses and nakshatras mean, how dashas time events, and how remedies are chosen.') }}</p>
            <nav aria-label="{{ __('Topics') }}" class="mt-8 flex flex-wrap gap-2">
                @foreach (G::TOPICS as $slug => [$t, $hi, $ic])
                    <a href="#{{ $slug }}" class="group inline-flex min-h-10 items-center gap-2 rounded-full border border-gold-200/30 bg-white/5 px-4 text-sm text-white no-underline backdrop-blur hover:border-gold-300 hover:bg-white/10"><x-astro-icon :name="$ic" class="size-4 text-gold-300 transition group-hover:rotate-45" />{{ __($t) }}</a>
                @endforeach
            </nav>
        </div>
    </section>

    <div class="container-page space-y-20 py-14">
        <section id="kundli-basics" class="reveal scroll-mt-28 grid gap-8 lg:grid-cols-[1fr_1fr]">
            <div>
                <p class="eyebrow">{{ __('Start here') }}</p>
                <h2 class="mt-2 text-3xl">{{ __('Kundli basics') }}</h2>
                <div class="mt-4 space-y-4 leading-7 text-ink-800">
                    <p>{{ __('A Kundli (Janma patrika) is a map of the sky at the exact moment and place of your birth. It shows where the Sun, the Moon, the five visible planets and the two lunar nodes, Rahu and Ketu, stood in the 12 signs.') }}</p>
                    <p>{{ __('The sign rising on the eastern horizon is the Lagna (ascendant) and becomes the 1st house. The other houses follow in order, each ruling one part of life. The sign holding the Moon is your Rashi, and the Moon\'s star is your birth nakshatra.') }}</p>
                    <p>{{ __('An astrologer reads three things together: the promise of the birth chart, the timing given by the dasha running now, and the current transits (gochar). Divisional charts such as the Navamsa (D9) refine marriage and inner strength.') }}</p>
                    <p>{{ __('This is why the exact time of birth matters: the Lagna changes about every two hours, and the Moon changes nakshatra about once a day.') }}</p>
                </div>
                <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('tools.show', 'kundli') }}" class="btn-gold">{{ __('Make your free Kundli') }}</a><a href="{{ route('services.show', 'janma-kundali-reading') }}" class="btn-outline">{{ __('Get it read by Acharya Ji') }}</a></div>
            </div>
            <div class="card bg-cosmic stars relative grid place-items-center overflow-hidden p-8">
                <x-zodiac-wheel class="w-full max-w-sm animate-spin-slow" />
                <p class="absolute bottom-4 text-center text-xs text-white/70">{{ __('The 12 rashis of the zodiac, each 30°') }}</p>
            </div>
        </section>

        <section id="twelve-houses" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Bhava') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('The 12 houses (bhavas)') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('Houses are counted from the Lagna. The 1st, 4th, 7th and 10th are kendras (pillars), the 1st, 5th and 9th are trikonas (fortune), and the 6th, 8th and 12th are dusthanas (houses of difficulty, which also give strength to overcome).') }}</p>
            <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (G::HOUSES as $n => [$name, $sk, $sig, $karaka])
                    <li class="card card-hover group relative overflow-hidden p-5">
                        <span aria-hidden="true" class="absolute -right-3 -top-5 text-7xl font-bold text-gold-50 transition group-hover:text-gold-200/60">{{ $n + 1 }}</span>
                        <p class="relative text-xs font-semibold uppercase tracking-wide text-gold-700">{{ __($name) }}</p>
                        <h3 class="relative mt-1 text-lg">{{ $sk }}</h3>
                        <p class="relative mt-2 text-sm text-ink-700">{{ __($sig) }}</p>
                        <p class="relative mt-3 text-xs text-ink-600">{{ __('Karaka') }}: <strong>{{ __($karaka) }}</strong></p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section id="nakshatras" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Nakshatra') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('The 27 nakshatras') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('The zodiac is also divided into 27 lunar mansions of 13°20\' each, with four padas (quarters) of 3°20\'. The ruling planet of your birth nakshatra starts your Vimshottari dasha; the gana (Deva, Manushya or Rakshasa) is one of the factors checked in Kundli matching.') }}</p>
            <div class="table-wrap mt-8">
                <table class="table">
                    <thead><tr><th>#</th><th>{{ __('Nakshatra') }}</th><th>{{ __('Ruling planet') }}</th><th>{{ __('Deity') }}</th><th>{{ __('Symbol') }}</th><th>{{ __('Gana') }}</th><th>{{ __('In sign') }}</th></tr></thead>
                    <tbody>
                        @foreach (G::NAKSHATRAS as $i => [$n, $lord, $deity, $symbol, $gana])
                            @php $s1 = intdiv($i * 4, 9); $s2 = intdiv($i * 4 + 3, 9); @endphp
                            <tr><td class="text-ink-600">{{ $i + 1 }}</td><td class="font-semibold">{{ $n }} <span class="font-normal text-ink-600" lang="hi">{{ \App\Domain\Content\HoroscopeTypes::NAKSHATRAS_HI[$i] }}</span></td><td>{{ __($lord) }}</td><td>{{ $deity }}</td><td>{{ __($symbol) }}</td>
                                <td><span @class(['badge', 'bg-gold-50 text-gold-700' => $gana === 'Deva', 'bg-ink-200/50 text-ink-800' => $gana === 'Manushya', 'bg-red-50 text-danger-700' => $gana === 'Rakshasa'])>{{ $gana }}</span></td>
                                <td class="text-ink-600">{{ strtok(Vedic::SIGNS[$s1], ' ') }}{{ $s2 !== $s1 ? ' – '.strtok(Vedic::SIGNS[$s2], ' ') : '' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <a href="{{ route('tools.show', 'nakshatra') }}" class="btn-outline mt-5">{{ __('Find your birth nakshatra') }}</a>
        </section>

        <section id="panchang" class="reveal scroll-mt-28 grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="eyebrow">{{ __('Panchang') }}</p>
                <h2 class="mt-2 text-3xl">{{ __('The five limbs of Panchang') }}</h2>
                <p class="mt-3 text-ink-600">{{ __('Panch-anga means "five limbs". Together they describe the quality of a day and are used to choose a muhurat for vivah, griha pravesh, mundan, travel or starting a business.') }}</p>
                <a href="{{ route('panchang') }}" class="btn-gold mt-6">{{ __("See today's Panchang") }}</a>
            </div>
            <ol class="space-y-3">
                @foreach (G::PANCHANG as $n => [$limb, $what, $use])
                    <li class="card card-hover group flex gap-4 p-5">
                        <span class="icon-orb size-11 text-sm font-bold">{{ $n + 1 }}</span>
                        <div><h3 class="text-lg">{{ __($limb) }}</h3><p class="mt-1 text-sm text-ink-700">{{ __($what) }}</p><p class="mt-1.5 text-sm text-gold-700">{{ __($use) }}</p></div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section id="dasha" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Timing') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('Vimshottari dasha') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('Vimshottari is the 120-year cycle of planetary periods taught by Maharishi Parashara. The first dasha at birth is ruled by the lord of the Moon\'s nakshatra, and the balance depends on how far the Moon had travelled through it. Each mahadasha is divided into nine antardashas in the same order.') }}</p>
            @php $total = array_sum(Vedic::DASHA_YEARS); @endphp
            <div class="mt-8 flex h-12 overflow-hidden rounded-2xl ring-1 ring-gold-200" role="img" aria-label="{{ __('Dasha order and years') }}">
                @foreach (Vedic::DASHA_ORDER as $k => $p)
                    <div class="grid place-items-center text-[0.7rem] font-semibold text-white {{ ['bg-stone-600', 'bg-rose-500', 'bg-orange-600', 'bg-slate-500', 'bg-red-700', 'bg-zinc-700', 'bg-amber-500', 'bg-indigo-800', 'bg-emerald-700'][$k] }}" style="width: {{ round(Vedic::DASHA_YEARS[$p] / $total * 100, 2) }}%">{{ Vedic::DASHA_YEARS[$p] }}</div>
                @endforeach
            </div>
            <ul class="mt-4 grid grid-cols-3 gap-2 text-sm sm:grid-cols-5 lg:grid-cols-9">
                @foreach (Vedic::DASHA_ORDER as $k => $p)
                    <li class="card p-3 text-center"><span class="block font-semibold">{{ __(strtok($planetName($p), ' ')) }}</span><span class="text-xs text-ink-600">{{ __(':n years', ['n' => Vedic::DASHA_YEARS[$p]]) }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('tools.show', 'memorable-periods') }}" class="btn-outline mt-5">{{ __('See your own dasha timeline') }}</a>
        </section>

        <section id="yogas" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Yoga') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('Important yogas') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('A yoga is a special combination of planets. Its results show most clearly in the dashas of the planets that form it, and depend on their strength.') }}</p>
            <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach (G::YOGAS as [$n, $how, $gives])
                    <article class="card card-hover group p-5">
                        <div class="flex items-center gap-3"><span class="icon-orb size-10"><x-astro-icon name="yoga" class="size-5" /></span><h3 class="text-lg">{{ __($n) }}</h3></div>
                        <p class="mt-3 text-sm text-ink-700"><strong>{{ __('Forms when') }}:</strong> {{ __($how) }}</p>
                        <p class="mt-2 text-sm text-ink-700"><strong>{{ __('Gives') }}:</strong> {{ __($gives) }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="doshas" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Dosha') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('Doshas explained calmly') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('A dosha is a sensitive point in the chart, not a curse. Most have cancellations, and none should be judged from one rule alone. Acharya Ji always checks the whole chart before suggesting a remedy.') }}</p>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach (G::DOSHAS as [$n, $how, $calm, $rem])
                    <details class="card group p-5 open:ring-1 open:ring-gold-300">
                        <summary class="flex cursor-pointer list-none items-center gap-3 [&::-webkit-details-marker]:hidden"><span class="icon-orb size-10"><x-astro-icon name="shield" class="size-5" /></span><span class="flex-1 text-lg font-semibold">{{ __($n) }}</span><span aria-hidden="true" class="text-gold-700 transition group-open:rotate-45">＋</span></summary>
                        <dl class="mt-4 space-y-2 text-sm text-ink-700">
                            <div><dt class="font-semibold text-ink-900">{{ __('How it is judged') }}</dt><dd>{{ __($how) }}</dd></div>
                            <div><dt class="font-semibold text-ink-900">{{ __('The balanced view') }}</dt><dd>{{ __($calm) }}</dd></div>
                            <div><dt class="font-semibold text-ink-900">{{ __('Traditional remedies') }}</dt><dd>{{ __($rem) }}</dd></div>
                        </dl>
                    </details>
                @endforeach
            </div>
            <div class="mt-5 flex flex-wrap gap-3"><a href="{{ route('tools.show', 'sade-sati') }}" class="btn-outline">{{ __('Check Sade Sati') }}</a><a href="{{ route('tools.show', 'kundli-matching') }}" class="btn-outline">{{ __('Kundli matching') }}</a><a href="{{ route('services.index', ['family' => 'puja']) }}" class="btn-ghost">{{ __('Dosha shanti pujas') }}</a></div>
        </section>

        <section id="remedies" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Upay') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('Remedies (upay)') }}</h2>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (G::REMEDIES as $n => [$r, $what, $when])
                    <li class="card card-hover group p-5">
                        <span class="icon-orb size-11"><x-astro-icon :name="['om', 'sparkle', 'moon', 'lamp', 'gem', 'star', 'hand'][$n] ?? 'star'" class="size-5" /></span>
                        <h3 class="mt-3 text-base">{{ __($r) }}</h3>
                        <p class="mt-1.5 text-sm text-ink-700">{{ __($what) }}</p>
                        <p class="mt-2 text-xs font-medium text-gold-700">{{ __($when) }}</p>
                    </li>
                @endforeach
            </ul>
            <h3 class="mt-10 text-xl">{{ __('Planets, their day, gemstone and colour') }}</h3>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead><tr><th>{{ __('Planet') }}</th><th>{{ __('Signifies') }}</th><th>{{ __('Day') }}</th><th>{{ __('Gemstone') }}</th><th>{{ __('Colour') }}</th><th>{{ __('Dasha') }}</th></tr></thead>
                    <tbody>
                        @foreach (\App\Domain\Astro\Gochar::FACTS as $p => $f)
                            <tr><td class="font-semibold"><a href="{{ route('gochar.planet', $p) }}">{{ $planetName($p) }}</a></td><td>{{ __($f['signifies']) }}</td><td>{{ __($f['day']) }}</td><td>{{ __($f['gem']) }}</td><td>{{ __($f['colour']) }}</td><td>{{ __(':n years', ['n' => Vedic::DASHA_YEARS[$p]]) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-ink-600">{{ __('Wear a gemstone only after your chart has been checked: a stone strengthens its planet whether that planet helps or harms you.') }}</p>
        </section>

        <section id="vastu" class="reveal scroll-mt-28">
            <p class="eyebrow">{{ __('Vastu') }}</p>
            <h2 class="mt-2 text-3xl">{{ __('Vastu directions') }}</h2>
            <p class="mt-3 max-w-3xl text-ink-600">{{ __('Vastu Shastra places each activity of the home in the direction whose deity and planet suit it. Small corrections (placement, colours, use of rooms) usually help without demolition.') }}</p>
            <div class="mt-8 grid gap-6 lg:grid-cols-[22rem_1fr]">
                <div class="card bg-temple mx-auto grid aspect-square w-full max-w-sm grid-cols-3 gap-1.5 p-3" role="img" aria-label="{{ __('Vastu grid of the eight directions') }}">
                    @foreach ([['North-west', 'Vayavya'], ['North', 'Uttar'], ['North-east', 'Ishan'], ['West', 'Paschim'], ['Centre', 'Brahmasthan'], ['East', 'Purva'], ['South-west', 'Nairitya'], ['South', 'Dakshin'], ['South-east', 'Agneya']] as [$dir, $sk])
                        <div @class(['grid place-items-center rounded-xl p-1 text-center text-xs', 'bg-gradient-to-br from-ink-800 to-maroon-700 text-gold-200' => $dir === 'Centre', 'bg-white/90 text-ink-800 ring-1 ring-gold-200' => $dir !== 'Centre'])><span><strong class="block text-[0.8rem]">{{ __($dir) }}</strong>{{ $sk }}</span></div>
                    @endforeach
                </div>
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach (G::VASTU as [$dir, $sk, $deity, $planet, $suits])
                        <li class="card p-4"><p class="font-semibold">{{ __($dir) }} <span class="font-normal text-ink-600">· {{ $sk }}</span></p><p class="mt-0.5 text-xs text-gold-700">{{ $deity }} · {{ __($planet) }}</p><p class="mt-1.5 text-sm text-ink-700">{{ __($suits) }}</p></li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('services.index', ['family' => 'vastu']) }}" class="btn-gold mt-6">{{ __('Vastu consultation') }}</a>
        </section>

        <section class="reveal" aria-labelledby="lfaq-h">
            <h2 id="lfaq-h" class="text-2xl">{{ __('Common questions') }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ($faq as [$q, $a])<details class="card p-4 open:ring-1 open:ring-gold-300"><summary class="cursor-pointer font-semibold marker:text-saffron-500">{{ $q }}</summary><p class="mt-2 text-sm leading-6 text-ink-700">{{ $a }}</p></details>@endforeach
            </div>
        </section>

        <section class="card bg-cosmic stars relative overflow-hidden p-8 text-center text-white sm:p-12">
            <h2 class="text-2xl text-white sm:text-3xl">{{ __('Want to know what your own chart says?') }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-white/80">{{ __('Speak to Acharya Dr. Rajnath Jha by video, audio or in person in Patna, or send a written question.') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3"><a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-gold">{{ __('Book a consultation') }}</a><a href="{{ route('services.show', 'ask-one-question') }}" class="btn border border-white/30 text-white hover:bg-white/10">{{ __('Ask a question') }}</a></div>
        </section>
    </div>
</x-layouts.public>
