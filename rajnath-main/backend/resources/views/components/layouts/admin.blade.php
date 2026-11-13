@props(['title' => 'Admin'])
@php
    $u = auth()->user();
    $groups = \App\Support\AdminMenu::for($u);
    $icons = \App\Support\AdminMenu::ICONS;
    $initials = \App\Support\AccountMenu::initials($u->name);
    $roles = $u->getRoleNames()->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->implode(', ');
@endphp
<x-layouts.base :title="$title" robots="noindex,nofollow" class="bg-[#f6f5f1]">
    <div x-data="drawer" class="min-h-dvh lg:grid lg:grid-cols-[17rem_1fr]" @keydown.escape.window="escape">
        {{-- Desktop sidebar / mobile drawer --}}
        <aside id="admin-nav" x-ref="drawer" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-ink-900 text-white/80 pt-[env(safe-area-inset-top)] transition-transform lg:sticky lg:top-0 lg:h-dvh lg:w-auto lg:translate-x-0"
               :class="drawerClass" :inert="drawerInert" aria-label="Admin navigation">
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
                <img src="/images/brand/logo-mark-light.svg" alt="" width="40" height="40" class="size-10">
                <span class="leading-tight"><span class="block text-sm font-semibold text-white">{{ $site('brand.name') }}</span><span class="block text-[0.68rem] uppercase tracking-[0.16em] text-gold-200">Control centre</span></span>
            </div>
            <div class="shrink-0 px-3 pt-3" x-data="navFilter">
                <label for="nav-filter" class="sr-only">Find a section</label>
                <input id="nav-filter" type="search" @input="filter" placeholder="Find a section…" autocomplete="off"
                       class="block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:border-gold-500 focus:outline-none">
            </div>
            <nav class="min-h-0 flex-1 overflow-y-auto px-3 pb-6 pt-2" data-nav x-data="navGroups">
                @foreach ($groups as $g => $items)
                    @php $groupOn = collect($items)->contains(fn ($i) => \App\Support\AdminMenu::active($i[1])); @endphp
                    {{-- Collapsible group; the one holding the current page starts open (works without JavaScript) --}}
                    <details class="group/nav mt-3" data-nav-group data-group="{{ $g }}" data-active="{{ $groupOn ? '1' : '0' }}" @if ($groupOn || $g === 'Overview' || count($groups) <= 3) open @endif>
                        <summary class="flex min-h-9 cursor-pointer list-none items-center justify-between rounded-lg px-3 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-white/45 hover:bg-white/5 hover:text-white/80 [&::-webkit-details-marker]:hidden">
                            <span>{{ $g }}</span>
                            <span class="flex items-center gap-1.5">
                                <span class="rounded-full bg-white/10 px-1.5 text-[0.62rem] tracking-normal text-white/60">{{ count($items) }}</span>
                                <svg aria-hidden="true" class="size-4 transition-transform group-open/nav:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
                            </span>
                        </summary>
                        <ul class="mt-1 space-y-0.5">
                            @foreach ($items as [$label, $route, , $icon])
                                @php $on = \App\Support\AdminMenu::active($route); @endphp
                                <li data-nav-item data-label="{{ mb_strtolower($label.' '.$g) }}">
                                    <a href="{{ route($route) }}" @class([
                                        'group flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm no-underline transition',
                                        'bg-white/10 font-semibold text-white shadow-[inset_3px_0_0_var(--color-gold-500)]' => $on,
                                        'text-white/75 hover:bg-white/5 hover:text-white' => ! $on,
                                    ]) @if ($on) aria-current="page" @endif>
                                        <svg aria-hidden="true" @class(['size-[1.15rem] shrink-0', 'text-gold-200' => $on, 'text-white/45 group-hover:text-white/80' => ! $on]) fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icons[$icon] ?? $icons['grid'] }}"/></svg>
                                        <span class="truncate">{{ $label }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </nav>
            <div class="shrink-0 border-t border-white/10 p-3">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm text-white/75 no-underline hover:bg-white/5 hover:text-white">
                    <svg aria-hidden="true" class="size-[1.15rem] text-white/45" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6m0-6L10 14M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                    View website
                </a>
            </div>
        </aside>
        <div x-show="open" x-cloak @click="close" class="fixed inset-0 z-30 bg-ink-900/30 lg:hidden" aria-hidden="true"></div>

        <div class="min-w-0">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-ink-200/60 bg-white/90 px-4 backdrop-blur pt-[env(safe-area-inset-top)] sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" x-ref="opener" class="btn-ghost -ml-2 px-3 lg:hidden" @click="toggle" :aria-expanded="expanded" aria-expanded="false" aria-controls="admin-nav" aria-label="Open navigation">
                        <svg aria-hidden="true" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="hidden text-xs text-ink-600 sm:block"><a href="{{ route('admin.dashboard') }}" class="text-ink-600 no-underline hover:text-ink-900">Control centre</a> <span aria-hidden="true">/</span> {{ $title }}</p>
                        <h1 class="truncate text-lg font-semibold leading-tight">{{ $title }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-ghost hidden px-3 text-sm md:inline-flex">View website</a>
                    {{-- Signed-in staff member: user menu --}}
                    <x-dropdown id="admin-user-menu" width="w-72" label="Account menu for {{ $u->name }}" button-class="btn-ghost gap-2.5 py-1.5 pl-1.5 pr-2.5 text-left">
                        <x-slot:trigger>
                            <span aria-hidden="true" class="avatar">{{ $initials }}</span>
                            <span class="hidden leading-tight lg:block"><span class="block max-w-[12rem] truncate text-sm font-semibold">{{ $u->name }}</span><span class="block max-w-[12rem] truncate text-xs font-normal text-ink-600">{{ $roles }}</span></span>
                        </x-slot:trigger>
                        <div class="flex items-center gap-3 px-3 py-2.5">
                            <span aria-hidden="true" class="avatar">{{ $initials }}</span>
                            <span class="min-w-0 leading-tight">
                                <span class="block truncate text-sm font-semibold text-ink-900">{{ $u->name }}</span>
                                <span class="block truncate text-xs text-ink-600">{{ $u->email }}</span>
                            </span>
                        </div>
                        @if ($roles !== '')<p class="px-3 pb-2"><span class="badge-gold">{{ $roles }}</span></p>@endif
                        <div class="menu-sep"></div>
                        <a href="{{ route('admin.dashboard') }}" class="menu-item" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="home" />Dashboard</a>
                        <a href="{{ route('admin.account.show') }}" class="menu-item" @if (request()->routeIs('admin.account*')) aria-current="page" @endif><x-icon name="user" />My account &amp; password</a>
                        <a href="{{ route('admin.manual') }}" class="menu-item" @if (request()->routeIs('admin.manual')) aria-current="page" @endif><x-icon name="help" />User manual</a>
                        @if ($u->hasRole('practitioner'))
                            <a href="{{ route('admin.practice.show') }}" class="menu-item" @if (request()->routeIs('admin.practice*')) aria-current="page" @endif><x-icon name="practice" />My practice</a>
                        @endif
                        @can('team.view')
                            <a href="{{ route('admin.team.index') }}" class="menu-item" @if (request()->routeIs('admin.team*')) aria-current="page" @endif><x-icon name="users" />Team &amp; access</a>
                        @endcan
                        @can('health.view')
                            <a href="{{ route('admin.health') }}" class="menu-item" @if (request()->routeIs('admin.health*')) aria-current="page" @endif><x-icon name="pulse" />Operational health</a>
                        @endcan
                        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="menu-item"><x-icon name="external" />View website<span class="sr-only"> (opens in a new tab)</span></a>
                        <div class="menu-sep"></div>
                        <form method="POST" action="{{ route('staff.logout') }}">@csrf
                            <button type="submit" class="menu-item menu-item-danger"><x-icon name="logout" />Sign out</button>
                        </form>
                    </x-dropdown>
                </div>
            </header>
            <main id="main" class="px-4 py-6 sm:px-6 lg:px-8 pb-[calc(1.5rem+env(safe-area-inset-bottom))]">
                @if (($u->password_must_change || ! $u->hasMfa()) && ! request()->routeIs('admin.account*'))
                    <div role="alert" class="card mb-6 flex flex-wrap items-center justify-between gap-3 border-gold-200 bg-gold-50 px-4 py-3 text-sm text-ink-900">
                        <p><strong>{{ $u->password_must_change ? 'Please choose your own password.' : 'Two-factor sign-in is off for your account.' }}</strong>
                            {{ $u->password_must_change ? 'You are still using the password created for you.' : 'Turn it on to protect the admin even if your password leaks.' }}</p>
                        <a href="{{ route('admin.account.show') }}" class="btn-primary min-h-9 px-4 text-xs">Open My account</a>
                    </div>
                @endif
                @if (auth()->user()?->can('providers.manage') && ($aiProblem = \App\Domain\Assistant\AiHealth::problem()))
                    <div role="alert" class="card mb-6 flex flex-wrap items-center justify-between gap-3 border-gold-200 bg-gold-50 px-4 py-3 text-sm text-ink-900">
                        <p><strong>{{ $aiProblem['state'] === 'missing' ? 'AI assistant needs an API key.' : 'The AI key is not working, please check or change it.' }}</strong>
                            Visitors are getting basic answers from the website only, and Rashifal is written by the built-in engine.
                            @if ($aiProblem['error'])<span class="block text-xs text-ink-600">Last error: {{ \Illuminate\Support\Str::limit($aiProblem['error'], 180) }}</span>@endif</p>
                        <a href="{{ route('admin.assistant.edit') }}" class="btn-primary min-h-9 px-4 text-xs">{{ $aiProblem['state'] === 'missing' ? 'Add AI key' : 'Check AI keys' }}</a>
                    </div>
                @endif
                @if (session('status'))
                    <div role="status" class="card mb-6 border-green-200 bg-green-50 px-4 py-3 text-sm text-success-700">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="card mb-6 border-red-200 bg-red-50 px-4 py-3 text-sm text-danger-700">
                        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
