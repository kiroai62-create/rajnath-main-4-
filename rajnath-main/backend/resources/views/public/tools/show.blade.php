@php [$title, $input, $kind, $desc] = $tool; $r = $result; @endphp
<x-layouts.public :title="$title" :description="$desc.' '.__('Free, instant, by Acharya Dr. Rajnath Jha.')" :jsonld="[\App\Support\Seo\JsonLd::webApp($title, $desc, route('tools.show', $slug)), \App\Support\Seo\JsonLd::breadcrumbs([[__('Free calculators'), route('tools.index')], [$title, route('tools.show', $slug)]])]">
    <section class="bg-cosmic stars relative isolate overflow-hidden text-white">
        <x-zodiac-wheel class="pointer-events-none absolute -right-28 top-1/2 -z-10 hidden size-[26rem] -translate-y-1/2 animate-spin-slower opacity-25 sm:block" />
        <div class="container-page py-10 sm:py-12">
            <nav aria-label="Breadcrumb" class="text-sm text-white/70"><a href="{{ route('tools.index') }}" class="text-gold-200">{{ __('Free calculators') }}</a> / <span class="text-white">{{ $title }}</span></nav>
            <p class="eyebrow mt-5 text-gold-200">{{ __('Free · instant') }} · {{ $kind }}</p>
            <h1 class="mt-2 text-3xl text-white sm:text-4xl">{{ $title }}</h1>
            <p class="mt-3 max-w-2xl text-white/80">{{ $desc }}</p>
        </div>
    </section>
    <div class="container-page py-10">
        @if ($kind === 'entertainment')<p class="mt-3 max-w-2xl rounded-xl border border-gold-200 bg-gold-50 p-3 text-sm">This is a game for fun. It does not predict anything about a real relationship.</p>@endif
        @if ($kind === 'symbolic')<p class="mt-3 max-w-2xl rounded-xl bg-ivory-100 p-3 text-sm">Numerology and colour associations are traditional symbolic systems, not scientific measurements.</p>@endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[24rem_1fr]">
            <form method="POST" action="{{ route('tools.compute', $slug) }}" class="card h-fit space-y-4 p-5" novalidate>
                @csrf
                @if ($input === 'birth')
                    @include('public.tools._person', ['p' => '', 'legend' => null])
                @elseif ($input === 'couple')
                    @include('public.tools._person', ['p' => 'a_', 'legend' => 'Boy / groom'])
                    @include('public.tools._person', ['p' => 'b_', 'legend' => 'Girl / bride'])
                @else
                    @if (in_array($input, ['name', 'name_dob', 'two_names']))<x-field name="name" label="{{ $input === 'two_names' ? 'Your name' : 'Full name' }}" required />@endif
                    @if ($input === 'two_names')<x-field name="partner" label="Their name" required />@endif
                    @if (in_array($input, ['dob', 'dob_month', 'name_dob', 'vehicle']))<x-field name="birth_date" label="Date of birth" type="date" required />@endif
                    @if ($slug === 'personal-year')<x-field name="year" label="Year" type="number" :value="now('Asia/Kolkata')->year" />@endif
                    @if ($input === 'dob_month')<x-field name="month" label="Month" type="month" :value="now('Asia/Kolkata')->format('Y-m')" required />@endif
                    @if ($input === 'vehicle')<x-field name="plate" label="Vehicle number" placeholder="BR01AB1234" required />@endif
                @endif
                <button class="btn-primary w-full">Calculate</button>
                <p class="text-xs text-ink-600">{{ __('Your details are saved securely so you can download this result as a PDF and our team can help you with it. See our') }} <a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a>.</p>
            </form>

            <section aria-live="polite" aria-labelledby="res-h" class="min-w-0">
                <h2 id="res-h" class="sr-only">Result</h2>
                @if (! empty($engineError))
                    <div class="card border-red-200 bg-red-50 p-5" role="alert">The calculation service is unavailable right now. Please try again in a few minutes.</div>
                @elseif (! $r)
                    <div class="card p-8 text-center text-ink-600">Enter the details and press Calculate.</div>
                @else
                    @if (! empty($pdfUrl))
                        <div class="mb-4 flex justify-end"><a href="{{ $pdfUrl }}" class="btn-outline" data-track="Download PDF: {{ $slug }}" rel="nofollow">{{ __('Download PDF') }}</a></div>
                    @endif
                    @include('public.tools._result', ['r' => $r])
                    <div class="card mt-6 flex flex-wrap items-center justify-between gap-3 p-5">
                        <p class="text-sm">Want Acharya Ji to read this in detail?</p>
                        <a href="{{ route('services.show', 'janma-kundali-reading') }}" class="btn-primary">Book a reading</a>
                    </div>
                @endif
                @if ($r)<x-ask-ai class="mt-6" :q="'Explain my '.$title.' result in simple words. What does it mean for me?'" :label="__('Explain my result with AI')" :suggestions="['What remedies are suggested?', 'Should I book a consultation?']" />@endif
            </section>
        </div>
    </div>
</x-layouts.public>
