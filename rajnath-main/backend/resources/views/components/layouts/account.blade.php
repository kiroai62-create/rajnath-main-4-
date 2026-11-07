@props(['title'])
@php
    $items = \App\Support\AccountMenu::ITEMS;
    $isOn = fn (array $it) => \App\Support\AccountMenu::active($it['route']);
    $current = collect($items)->first($isOn);
    $more = array_values(array_filter($items, fn ($it) => ! $it['primary']));
    $moreOn = collect($more)->contains($isOn);
@endphp
<x-layouts.public :title="$title" robots="noindex,nofollow" :jsonld="false">
    <div class="container-page py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('My account') }}</p>
                <h1 class="mt-1 text-2xl">{{ $title }}</h1>
            </div>
            <form method="POST" action="{{ route('account.sign-out') }}">@csrf<button class="btn-outline"><x-icon name="logout" />{{ __('Sign out') }}</button></form>
        </div>

        {{-- Small screens: one dropdown listing every section --}}
        <nav aria-label="{{ __('Account') }}" class="mt-5 md:hidden">
            <x-dropdown id="account-sections" width="w-full" align="left" class="w-full" button-class="btn-outline w-full justify-between rounded-2xl px-4"
                        :label="__('Account section').': '.__($current['label'] ?? 'Overview')">
                <x-slot:trigger>
                    <span class="flex items-center gap-2"><x-icon :name="$current['icon'] ?? 'home'" />{{ __($current['label'] ?? 'Overview') }}</span>
                </x-slot:trigger>
                <ul>
                    @foreach ($items as $it)
                        <li><a href="{{ route($it['route']) }}" class="menu-item" @if ($isOn($it)) aria-current="page" @endif><x-icon :name="$it['icon']" />{{ __($it['label']) }}</a></li>
                    @endforeach
                </ul>
            </x-dropdown>
        </nav>

        {{-- Wider screens: main sections as tabs, the rest under "More" --}}
        <nav aria-label="{{ __('Account') }}" class="mt-5 hidden md:block">
            <ul class="flex flex-wrap items-center gap-2 border-b border-ink-200/70 pb-2">
                @foreach ($items as $it)
                    @continue(! $it['primary'])
                    @php $on = $isOn($it); @endphp
                    <li><a href="{{ route($it['route']) }}" @class(['btn px-4', 'bg-ink-900 text-white hover:bg-ink-700' => $on, 'text-ink-700 hover:bg-ivory-100' => ! $on]) @if ($on) aria-current="page" @endif>{{ __($it['label']) }}</a></li>
                @endforeach
                <li>
                    <x-dropdown id="account-more" width="w-64" align="left" :label="$moreOn ? __('More').': '.__($current['label']) : __('More sections')"
                                :button-class="$moreOn ? 'btn gap-1.5 bg-ink-900 px-4 text-white hover:bg-ink-700 aria-expanded:!bg-ink-700 aria-expanded:!text-white' : 'btn gap-1.5 px-4 text-ink-700 hover:bg-ivory-100'">
                        <x-slot:trigger>{{ $moreOn ? __($current['label']) : __('More') }}</x-slot:trigger>
                        <ul>
                            @foreach ($more as $it)
                                <li><a href="{{ route($it['route']) }}" class="menu-item" @if ($isOn($it)) aria-current="page" @endif><x-icon :name="$it['icon']" />{{ __($it['label']) }}</a></li>
                            @endforeach
                        </ul>
                    </x-dropdown>
                </li>
            </ul>
        </nav>
        @if ($errors->any())
            <div role="alert" class="card mt-5 border-red-200 bg-red-50 px-4 py-3 text-sm text-danger-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <div class="mt-6">{{ $slot }}</div>
    </div>
</x-layouts.public>
