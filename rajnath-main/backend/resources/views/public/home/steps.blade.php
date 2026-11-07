{{-- How a consultation works, in four steps. --}}
<section class="bg-ivory-100 py-16" aria-labelledby="steps-h">
    <div class="container-page reveal">
        <div class="text-center">
            <p class="eyebrow justify-center">{{ __('How it works') }}</p>
            <h2 id="steps-h" class="mt-2 text-2xl sm:text-3xl">{{ __('From your question to clear guidance') }}</h2>
        </div>
        <ol class="relative mt-10 grid gap-6 md:grid-cols-4">
            <span aria-hidden="true" class="absolute left-[12%] right-[12%] top-8 hidden h-px bg-gradient-to-r from-transparent via-gold-500/70 to-transparent md:block"></span>
            @foreach ([
                ['chat', __('Choose a service'), __('Consultation by video, audio or in person, a written answer, a report or a puja.')],
                ['calendar', __('Pick a time and pay'), __('See free times in IST and pay securely online. GST invoice included.')],
                ['scroll', __('Share birth details'), __('Date, exact time and place of birth. Your details stay private.')],
                ['sparkle', __('Receive guidance'), __('Acharya Ji explains your chart in plain words, with simple remedies.')],
            ] as $n => [$ic, $t, $d])
                <li class="group relative text-center">
                    <span class="icon-orb mx-auto size-16"><x-astro-icon :name="$ic" class="size-7" /></span>
                    <span class="absolute left-1/2 top-0 ml-5 grid size-6 place-items-center rounded-full bg-saffron-500 text-xs font-bold text-white">{{ $n + 1 }}</span>
                    <h3 class="mt-4 text-base">{{ $t }}</h3>
                    <p class="mt-1.5 text-sm text-ink-600">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
        <div class="mt-10 text-center"><a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-gold px-6">{{ __('Book a consultation') }}</a></div>
    </div>
</section>
