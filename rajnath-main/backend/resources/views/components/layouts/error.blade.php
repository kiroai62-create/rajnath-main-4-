@props(['code', 'title', 'message', 'reference' => false])
@php
    // Error pages must render even when the database or cache is down, so they use only config,
    // routes and the (rescued) $site settings helper — no menus, no user lookups, no queries.
    $area = request()->is('admin', 'admin/*', 'staff/*') ? 'admin' : (request()->is('account', 'account/*') ? 'account' : 'public');
    $homeUrl = $area === 'admin' ? url('/admin') : url('/');
    $homeLabel = $area === 'admin' ? 'Back to the control centre' : __('Go to the home page');
    // "Go back" only to a page on this site (never an external referrer).
    $prev = url()->previous();
    $back = parse_url($prev, PHP_URL_HOST) === request()->getHost() && ! in_array(rtrim($prev, '/'), [rtrim(request()->fullUrl(), '/'), rtrim($homeUrl, '/')], true) ? $prev : null;
    $requestId = request()->attributes->get('request_id');
    $phone = $site('contact.phone');
    $email = $site('contact.email');
    $brand = $site('brand.name') ?: config('app.name');
@endphp
<x-layouts.base :title="$title" robots="noindex,nofollow" :jsonld="false" class="flex min-h-dvh flex-col bg-ivory-50">
    <header class="border-b border-ink-200/60 bg-white/95 pt-[env(safe-area-inset-top)]">
        <div class="container-page flex h-16 items-center justify-between gap-4">
            <a href="{{ $homeUrl }}" class="flex min-w-0 items-center gap-2.5 no-underline">
                <img src="/images/brand/logo-mark.svg" alt="" width="44" height="44" class="size-11">
                <span class="truncate font-semibold text-ink-900">{{ $brand }}</span>
            </a>
            @if ($area !== 'admin' && \Illuminate\Support\Facades\Route::has('contact'))
                <a href="{{ route('contact') }}" class="btn-ghost px-3 text-sm">{{ __('Contact') }}</a>
            @endif
        </div>
    </header>

    <main id="main" class="flex flex-1 items-center py-12 sm:py-20">
        <div class="container-page">
            <div class="mx-auto max-w-2xl text-center">
                <p class="inline-flex items-center gap-2 rounded-full bg-gold-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-gold-700 ring-1 ring-gold-200">
                    <span aria-hidden="true" class="size-1.5 rounded-full bg-gold-500"></span>{{ __('Error') }} {{ $code }}
                </p>
                <p aria-hidden="true" class="mt-6 bg-gradient-to-b from-ink-900 to-ink-600 bg-clip-text text-7xl font-semibold tracking-tight text-transparent sm:text-8xl">{{ $code }}</p>
                <h1 class="mt-4 text-2xl sm:text-3xl">{{ $title }}</h1>
                <p class="mx-auto mt-3 max-w-xl text-base leading-7 text-ink-600">{{ $message }}</p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @isset($actions)
                        {{ $actions }}
                    @else
                        <a href="{{ $homeUrl }}" class="btn-primary">{{ $homeLabel }}</a>
                    @endisset
                    @if ($back)<a href="{{ $back }}" class="btn-outline">{{ __('Go back') }}</a>@endif
                </div>

                {{ $slot }}

                @if ($reference && $requestId)
                    <p class="mt-10 text-sm text-ink-600">
                        {{ __('If you contact us about this, please quote this reference:') }}
                        <code class="ml-1 select-all rounded-md bg-ivory-100 px-2 py-0.5 font-mono text-xs text-ink-900">{{ $requestId }}</code>
                    </p>
                @endif

                @if ($area !== 'admin' && ($phone || $email))
                    <div class="mx-auto mt-10 max-w-md rounded-2xl border border-ink-200/70 bg-white p-5 text-sm text-ink-700 shadow-[var(--shadow-card)]">
                        <p class="font-semibold text-ink-900">{{ __('Need help right away?') }}</p>
                        <p class="mt-1 flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                            @if ($phone)<a href="tel:{{ $phone }}">{{ __('Call') }} {{ $phone }}</a>@endif
                            @if ($email)<a href="mailto:{{ $email }}" class="break-all">{{ $email }}</a>@endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <footer class="border-t border-ink-200/60 bg-ivory-100 pb-[env(safe-area-inset-bottom)]">
        <p class="container-page py-5 text-center text-xs text-ink-600">© {{ now()->year }} {{ $brand }}</p>
    </footer>
</x-layouts.base>
