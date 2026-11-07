<x-layouts.error code="404" :title="__('Page not found')"
    :message="__('The page you are looking for has moved, been removed or never existed. Check the address, or use one of the links below.')">
    @unless (request()->is('admin', 'admin/*', 'staff/*'))
        @php
            $popular = array_filter([
                ['services.index', [], __('All services'), __('Consultations, reports and pujas')],
                ['tools.show', ['kundli'], __('Free Kundli'), __('Your birth chart in a minute')],
                ['rashifal', [], __('Rashifal'), __('Daily, weekly and yearly horoscopes')],
                ['panchang', [], __("Today's Panchang"), __('Tithi, nakshatra and muhurat')],
                ['store.index', [], __('Gemstone store'), __('Certified, energised gemstones')],
                ['contact', [], __('Contact & timings'), __('Call, WhatsApp or visit us')],
            ], fn ($l) => \Illuminate\Support\Facades\Route::has($l[0]));
        @endphp
        <nav aria-label="{{ __('Popular pages') }}" class="mx-auto mt-12 max-w-2xl text-left">
            <p class="eyebrow text-center">{{ __('Popular pages') }}</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($popular as [$route, $params, $label, $note])
                    <li>
                        <a href="{{ route($route, $params) }}" class="group flex items-center justify-between gap-3 rounded-2xl border border-ink-200/70 bg-white px-4 py-3 no-underline shadow-[var(--shadow-card)] transition hover:border-gold-500">
                            <span class="min-w-0"><span class="block font-semibold text-ink-900">{{ $label }}</span><span class="block text-sm text-ink-600">{{ $note }}</span></span>
                            <svg aria-hidden="true" class="size-5 shrink-0 text-ink-400 transition group-hover:translate-x-0.5 group-hover:text-gold-700" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endunless
</x-layouts.error>
