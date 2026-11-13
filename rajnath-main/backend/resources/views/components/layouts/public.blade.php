@props(['title' => null, 'description' => null, 'image' => null, 'robots' => 'index,follow', 'jsonld' => null, 'type' => 'website'])
@php
    $me = auth()->user();
    $isStaff = $me?->isStaff() ?? false;
    $accountUrl = $me ? ($isStaff ? route('admin.dashboard') : route('account.dashboard')) : route('account.sign-in');
    $signOutUrl = $me ? ($isStaff ? route('staff.logout') : route('account.sign-out')) : null;
    $meName = $me ? (trim((string) $me->name) ?: __('My account')) : null;
    $meContact = $me ? ($me->email ?: $me->phone_e164) : null;
    $accountItems = \App\Support\AccountMenu::header();
    $curLang = app()->getLocale();
    $langNames = config('locales.available');
    $langs = \App\Http\Middleware\SetLocale::enabled();
@endphp
<x-layouts.base :title="$title" :description="$description" :image="$image" :robots="$robots" :jsonld="$jsonld" :type="$type">
    @php $menu = \App\Support\Navigation::menu(); $phone = $site('contact.phone'); $wa = $site('contact.whatsapp'); $sc = app(\App\Support\SiteContent::class); $ann = $sc->announcement(); @endphp
    @if ($ann)
        <div class="bg-gradient-to-r from-gold-700 via-gold-500 to-gold-700 text-ink-900" role="region" aria-label="{{ __('Announcement') }}">
            <p class="container-page flex min-h-10 flex-wrap items-center justify-center gap-x-3 gap-y-1 py-2 text-center text-sm font-semibold">
                <span>{{ $ann['text'] }}</span>
                @if ($ann['link'])<a href="{{ $ann['link'] }}" class="rounded-full bg-ink-900 px-3 py-1 text-xs font-semibold text-white no-underline hover:bg-ink-700">{{ $ann['cta'] ?: __('Learn more') }}</a>@endif
            </p>
        </div>
    @endif
    {{-- Utility bar --}}
    @if ($sc->utilityOn())
    <div class="hidden bg-gradient-to-r from-ink-950 via-ink-900 to-maroon-700 text-xs text-white/80 md:block">
        <div class="container-page flex h-9 items-center justify-between gap-4">
            <p class="truncate">{{ $sc->utilityText() }}</p>
            <ul class="flex items-center gap-4 whitespace-nowrap">
                @if ($phone)<li><a href="tel:{{ $phone }}" class="text-white/90 no-underline hover:text-white">{{ __('Call') }} {{ $phone }}</a></li>@endif
                @if ($wa)<li><a href="https://wa.me/{{ ltrim($wa, '+') }}" rel="noopener" class="text-white/90 no-underline hover:text-white">WhatsApp</a></li>@endif
                <li><a href="{{ route('panchang') }}" class="text-white/90 no-underline hover:text-white">{{ __("Today's Panchang") }}</a></li>
                <li><a href="{{ route('tools.show', 'kundli') }}" class="font-semibold text-gold-200 no-underline hover:text-white">{{ __('Free Kundli') }}</a></li>
            </ul>
        </div>
    </div>
    @endif
    <header class="sticky top-0 z-40 border-b border-gold-200/60 bg-white/90 shadow-[0_6px_24px_-18px_rgb(27_20_70/0.5)] backdrop-blur-md pt-[env(safe-area-inset-top)]" x-data="disclosure">
        <div aria-hidden="true" class="h-0.5 bg-gradient-to-r from-maroon-600 via-gold-500 to-saffron-500"></div>
        <div class="container-page flex h-[4.25rem] items-center justify-between gap-4">
            {{-- The logo never shrinks when the menu and buttons need room (xl:shrink-0). --}}
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 no-underline xl:shrink-0">
                <img src="/images/brand/logo-mark.svg" alt="" width="52" height="52" class="size-13 shrink-0 transition duration-700 hover:rotate-[30deg] xl:hidden">
                <img src="/images/brand/logo-full.svg" alt="{{ $site('brand.name') }}" width="198" height="51" class="hidden h-[3.4rem] w-auto max-w-none shrink-0 xl:block">
                <span class="min-w-0 leading-tight xl:sr-only"><span class="text-maroon-grad block truncate text-base font-bold">{{ $site('brand.name') }}</span>
                    <span class="hidden whitespace-nowrap text-[0.7rem] uppercase tracking-[0.14em] text-gold-700 sm:block">{{ __('Jyotish · Vastu · Puja') }}</span></span>
            </a>
            <x-mega-nav :menu="$menu" />
            <div class="flex items-center gap-1 sm:gap-2">
                @if (count($langs) > 1)
                    {{-- Language dropdown: shows the current language; each choice keeps the current page --}}
                    <x-dropdown id="lang-menu" width="w-56" :label="__('Language').': '.($langNames[$curLang] ?? $curLang)" button-class="btn-ghost gap-1.5 px-2.5 text-sm">
                        <x-slot:trigger>
                            <x-icon name="globe" />
                            <span class="font-semibold uppercase" translate="no">{{ $curLang }}</span>
                        </x-slot:trigger>
                        <p class="menu-label">{{ __('Language') }}</p>
                        @foreach ($langs as $l)
                            <a href="{{ request()->fullUrlWithQuery(['lang' => $l]) }}" lang="{{ $l }}" hreflang="{{ $l }}" class="menu-item" @if ($l === $curLang) aria-current="true" @endif translate="no">
                                <span class="flex-1">{{ $langNames[$l] ?? $l }}</span>
                                @if ($l === $curLang)<x-icon name="check" class="size-4 text-gold-700" />@endif
                            </a>
                        @endforeach
                    </x-dropdown>
                @endif
                @if ($me)
                    {{-- Account dropdown (signed in) --}}
                    <x-dropdown id="account-menu" width="w-72" :label="__('My account')" button-class="btn-ghost gap-2 py-1.5 pl-1.5 pr-3" class="hidden sm:block">
                        <x-slot:trigger>
                            <span aria-hidden="true" class="avatar size-8">{{ \App\Support\AccountMenu::initials($me->name) }}</span>
                            <span class="hidden max-w-[9rem] truncate lg:inline xl:hidden 2xl:inline">{{ $isStaff ? $meName : __('My account') }}</span>
                        </x-slot:trigger>
                        <div class="flex items-center gap-3 px-3 py-2.5">
                            <span aria-hidden="true" class="avatar">{{ \App\Support\AccountMenu::initials($me->name) }}</span>
                            <span class="min-w-0 leading-tight">
                                <span class="block truncate text-sm font-semibold text-ink-900">{{ $meName }}</span>
                                @if ($meContact)<span class="block truncate text-xs text-ink-600">{{ $meContact }}</span>@endif
                            </span>
                        </div>
                        <div class="menu-sep"></div>
                        @if ($isStaff)
                            <a href="{{ route('admin.dashboard') }}" class="menu-item"><x-icon name="grid" />Control centre</a>
                        @else
                            @foreach ($accountItems as $it)
                                <a href="{{ route($it['route']) }}" class="menu-item" @if (\App\Support\AccountMenu::active($it['route'])) aria-current="page" @endif><x-icon :name="$it['icon']" />{{ __($it['label']) }}</a>
                            @endforeach
                        @endif
                        <div class="menu-sep"></div>
                        <form method="POST" action="{{ $signOutUrl }}">@csrf
                            <button type="submit" class="menu-item menu-item-danger"><x-icon name="logout" />{{ __('Sign out') }}</button>
                        </form>
                    </x-dropdown>
                @else
                    <a href="{{ $accountUrl }}" class="btn-ghost hidden whitespace-nowrap px-3 sm:inline-flex">{{ __('Sign in') }}</a>
                @endif
                <a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-primary hidden whitespace-nowrap sm:inline-flex">{{ __('Book consultation') }}</a>
                <button type="button" x-ref="opener" class="btn-ghost px-3 xl:hidden" @click="toggle" :aria-expanded="expanded" aria-expanded="false" aria-controls="mobile-nav" aria-label="{{ __('Menu') }}">
                    <svg aria-hidden="true" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
        {{-- Mobile and tablet: every menu section as an accordion --}}
        <nav id="mobile-nav" aria-label="{{ __('Mobile') }}" class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-ink-200/60 bg-white xl:hidden" x-show="open" x-cloak @keydown.escape.window="escape">
            <div class="container-page py-3">
                <div class="grid grid-cols-2 gap-2 pb-3">
                    <a href="{{ route('tools.show', 'kundli') }}" class="btn-outline">{{ __('Free Kundli') }}</a>
                    <a href="{{ route('rashifal') }}" class="btn-outline">{{ __('Rashifal') }}</a>
                </div>
                <ul class="divide-y divide-ink-200/60 border-y border-ink-200/60">
                    @foreach ($menu as $item)
                        <li>
                            <details class="group">
                                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between py-3 font-semibold text-ink-900 [&::-webkit-details-marker]:hidden">
                                    {{ $item['label'] }}
                                    <svg aria-hidden="true" class="size-5 opacity-60 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
                                </summary>
                                <div class="space-y-4 pb-4">
                                    @foreach ($item['columns'] as $col)
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gold-700">{{ $col[0] }}</p>
                                            <ul @class(['mt-2 grid gap-1', 'grid-cols-3' => ($col[2] ?? null) === 'grid', 'sm:grid-cols-2' => ($col[2] ?? null) !== 'grid'])>
                                                @foreach ($col[1] as [$label, $href])
                                                    <li><a href="{{ $href }}" @if ($href === request()->fullUrl()) aria-current="page" @endif class="block rounded-lg px-2 py-2 text-sm text-ink-800 no-underline hover:bg-ivory-100 aria-[current=page]:bg-ivory-100 aria-[current=page]:font-semibold">{{ $label }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                    <a href="{{ $item['href'] }}" class="inline-block text-sm font-semibold">{{ __('See everything in :section', ['section' => $item['label']]) }}</a>
                                </div>
                            </details>
                        </li>
                    @endforeach
                    <li><a href="{{ route('store.index') }}" class="flex min-h-12 items-center py-3 font-semibold text-ink-900 no-underline">{{ __('Gemstone store') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="flex min-h-12 items-center py-3 font-semibold text-ink-900 no-underline">{{ __('Contact') }}</a></li>
                </ul>
                @if ($me)
                    <div class="mt-4 rounded-2xl border border-ink-200/70 bg-ivory-50 p-3">
                        <div class="flex items-center gap-3 px-1 pb-2">
                            <span aria-hidden="true" class="avatar">{{ \App\Support\AccountMenu::initials($me->name) }}</span>
                            <span class="min-w-0 leading-tight">
                                <span class="block truncate text-sm font-semibold text-ink-900">{{ $meName }}</span>
                                @if ($meContact)<span class="block truncate text-xs text-ink-600">{{ $meContact }}</span>@endif
                            </span>
                        </div>
                        <ul class="grid grid-cols-2 gap-1">
                            @if ($isStaff)
                                <li class="col-span-2"><a href="{{ route('admin.dashboard') }}" class="menu-item"><x-icon name="grid" />Control centre</a></li>
                            @else
                                @foreach ($accountItems as $it)
                                    <li><a href="{{ route($it['route']) }}" class="menu-item" @if (\App\Support\AccountMenu::active($it['route'])) aria-current="page" @endif><x-icon :name="$it['icon']" />{{ __($it['label']) }}</a></li>
                                @endforeach
                            @endif
                        </ul>
                        <form method="POST" action="{{ $signOutUrl }}" class="mt-1 border-t border-ink-200/70 pt-1">@csrf
                            <button type="submit" class="menu-item menu-item-danger"><x-icon name="logout" />{{ __('Sign out') }}</button>
                        </form>
                    </div>
                @endif
                <div class="grid gap-2 py-4">
                    @unless ($me)<a href="{{ $accountUrl }}" class="btn-outline w-full">{{ __('Sign in') }}</a>@endunless
                    <a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-primary w-full">{{ __('Book consultation') }}</a>
                    @if ($phone)<a href="tel:{{ $phone }}" class="btn-ghost w-full">{{ __('Call') }} {{ $phone }}</a>@endif
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
        @if (session('status'))
            <div class="container-page pt-4"><div role="status" class="card border-green-200 bg-green-50 px-4 py-3 text-sm text-success-700">{{ session('status') }}</div></div>
        @endif
        {{ $slot }}
    </main>
    @unless (request()->routeIs('assistant'))<x-ai-widget />@endunless

    <footer class="bg-cosmic relative isolate mt-24 overflow-hidden pb-[calc(5rem+env(safe-area-inset-bottom))] text-white/75 [&_a]:text-white/85 [&_a]:decoration-white/25 [&_a:hover]:text-gold-200">
        <div aria-hidden="true" class="h-1 bg-gradient-to-r from-maroon-600 via-gold-500 to-saffron-500"></div>
        <div aria-hidden="true" class="stars pointer-events-none absolute inset-0 -z-10 opacity-50"></div>
        <x-zodiac-wheel class="pointer-events-none absolute -bottom-40 -right-40 -z-10 size-[36rem] animate-spin-slower opacity-15" />
        <div class="container-page flex flex-col items-start justify-between gap-6 border-b border-white/10 py-10 md:flex-row md:items-center">
            <img src="/images/brand/logo-full-light.svg" alt="{{ $site('brand.name') }}" width="300" height="77" class="h-16 w-auto">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('services.index', ['family' => 'consultations']) }}" class="btn-gold !text-ink-950 no-underline">{{ __('Book consultation') }}</a>
                @if ($site('contact.whatsapp'))<a href="https://wa.me/{{ ltrim($site('contact.whatsapp'), '+') }}" rel="noopener" class="btn border border-white/25 !text-white no-underline hover:bg-white/10">WhatsApp</a>@endif
            </div>
        </div>
        <div class="container-page grid gap-8 py-12 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="font-semibold text-gold-200">{{ $site('brand.name') }}</p>
                <p class="mt-2">{{ $site('brand.tagline') }}</p>
                <address class="mt-3 not-italic">{{ app(\App\Support\Business::class)->addressLine() }}</address>
            </div>
            <div>
                <p class="font-semibold text-gold-200">{{ __('Explore') }}</p>
                <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1.5 lg:grid-cols-1">
                    <li><a href="{{ route('learn') }}">{{ __('Learn Jyotish') }}</a></li>
                    <li><a href="{{ route('services.index') }}">{{ __('All services') }}</a></li>
                    <li><a href="{{ route('about') }}">{{ __('About Acharya Ji') }}</a></li>
                    <li><a href="{{ route('awards') }}">{{ __('Awards and honours') }}</a></li>
                    <li><a href="{{ route('gallery') }}">{{ __('Gallery: pujas and events') }}</a></li>
                    <li><a href="{{ route('reviews') }}">{{ __('Client reviews') }}</a></li>
                    <li><a href="{{ route('blog') }}">{{ __('Articles and guides') }}</a></li>
                    <li><a href="{{ route('courses') }}">{{ __('Astrology courses (coming soon)') }}</a></li>
                    <li><a href="{{ route('store.index') }}">{{ __('Gemstone store') }}</a></li>
                    <li><a href="{{ route('services.show', 'palmistry') }}">{{ __('Palmistry report from photos') }}</a></li>
                    <li><a href="{{ route('tools.index') }}">{{ __('Free calculators') }}</a></li>
                    <li><a href="{{ route('panchang') }}">{{ __('Today\'s Panchang') }}</a></li>
                    <li><a href="{{ route('calendar') }}">{{ __('Vrat calendar') }}</a></li>
                    <li><a href="{{ route('rashifal') }}">{{ __('Rashifal') }}</a></li>
                    <li><a href="{{ route('gochar') }}">{{ __('Grah gochar (transits)') }}</a></li>
                    <li><a href="{{ route('navagraha') }}">{{ __('The nine planets') }}</a></li>
                    <li><a href="{{ route('muhurat.index') }}">{{ __('Shubh muhurat') }}</a></li>
                    <li><a href="{{ route('festivals') }}">{{ __('Festivals and holidays') }}</a></li>
                    <li><a href="{{ route('assistant') }}">{{ __('Ask our assistant') }}</a></li>
                    <li><a href="{{ route('faq') }}">{{ __('Questions & answers') }}</a></li>
                    <li><a href="{{ route('contact') }}">{{ __('Contact & timings') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="font-semibold text-gold-200">{{ __('Contact') }}</p>
                <ul class="mt-2 space-y-1.5">
                    @if ($site('contact.phone'))<li><a href="tel:{{ $site('contact.phone') }}">{{ $site('contact.phone') }}</a></li>@endif
                    @if ($site('contact.whatsapp'))<li><a href="https://wa.me/{{ ltrim($site('contact.whatsapp'), '+') }}" rel="noopener">WhatsApp</a></li>@endif
                    @if ($site('contact.email'))<li><a href="mailto:{{ $site('contact.email') }}" class="break-all">{{ $site('contact.email') }}</a></li>@endif
                </ul>
            </div>
            <div>
                <p class="font-semibold text-gold-200">{{ __('Important') }}</p>
                <p class="mt-2">{{ __('Astrological guidance is traditional and interpretive. It does not replace medical, legal or financial advice.') }}</p>
                <ul class="mt-3 space-y-1.5">
                    <li><a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a></li>
                    <li><a href="{{ route('terms') }}">{{ __('Terms of use') }}</a></li>
                    @php $legalDocs = app(\App\Domain\Legal\LegalDocuments::class); @endphp
                    @if ($legalDocs->isPublished('refunds'))<li><a href="{{ route('legal.refunds') }}">{{ __('Cancellation and refund policy') }}</a></li>@endif
                    @if ($legalDocs->isPublished('shipping'))<li><a href="{{ route('legal.shipping') }}">{{ __('Shipping and delivery policy') }}</a></li>@endif
                    @if ($site('site.show_source_link'))<li><a href="{{ $site('source.repository_url') }}" rel="noopener">{{ __('Source code (AGPL-3.0)') }}</a></li>@endif
                </ul>
            </div>
        </div>
        <p class="container-page border-t border-white/10 py-5 text-xs text-white/60">© {{ now()->year }} {{ $site('brand.name') }} · {{ __('Jyotish · Vastu · Puja') }} · {{ __('Made with devotion in Patna') }}</p>
    </footer>
</x-layouts.base>
