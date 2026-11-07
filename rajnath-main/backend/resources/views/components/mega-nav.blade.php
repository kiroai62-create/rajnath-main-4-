@props(['menu'])
@php $here = request()->fullUrl(); @endphp
{{-- Desktop mega menu (xl and up). Panels sit under the header across the full width. --}}
<nav aria-label="{{ __('Primary') }}" class="hidden xl:block">
    <ul class="flex items-center gap-0.5">
        @foreach ($menu as $item)
            <li x-data="megaItem" @mouseenter="hoverIn" @mouseleave="hoverOut" @keydown.escape="escape" @click.outside="close" @focusout="focusOut" @menu-open.window="onOther">
                <button type="button" x-ref="trigger" @click="toggle" :aria-expanded="expanded" aria-expanded="false" aria-controls="mega-{{ $item['key'] }}"
                        class="btn-ghost gap-1 whitespace-nowrap px-3 text-[0.95rem] aria-expanded:bg-ivory-100 aria-expanded:text-ink-900">
                    {{ $item['label'] }}
                    <svg aria-hidden="true" class="size-4 opacity-60 transition-transform" :class="chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
                </button>
                <div id="mega-{{ $item['key'] }}" x-show="open" x-cloak
                     class="absolute inset-x-0 top-full z-50 max-h-[calc(100dvh-7rem)] overflow-y-auto border-y border-ink-200/60 bg-white shadow-[0_24px_48px_-24px_rgb(20_26_60/0.35)]">
                    <div class="container-page grid gap-8 py-8 @if ($item['feature']) grid-cols-[1fr_17rem] @endif">
                        <div class="grid gap-8" style="grid-template-columns: repeat({{ count($item['columns']) }}, minmax(0, 1fr))">
                            @foreach ($item['columns'] as $col)
                                @php [$title, $links] = $col; $grid = ($col[2] ?? null) === 'grid'; @endphp
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gold-700">{{ $title }}</p>
                                    <ul @class(['mt-3', 'grid grid-cols-3 gap-1' => $grid, 'space-y-0.5' => ! $grid])>
                                        @foreach ($links as [$label, $href, $note])
                                            <li><a href="{{ $href }}" @if ($href === $here) aria-current="page" @endif @class(['group flex flex-col rounded-lg no-underline hover:bg-ivory-100 focus-visible:bg-ivory-100 aria-[current=page]:bg-ivory-100', 'px-2 py-1.5 text-center text-sm' => $grid, 'px-2.5 py-2' => ! $grid])>
                                                <span class="text-sm font-medium text-ink-900 group-hover:text-ink-700">{{ $label }}</span>
                                                @if ($note && ! $grid)<span class="text-xs text-ink-600">{{ $note }}</span>@endif
                                            </a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        @if ($f = $item['feature'])
                            <a href="{{ $f['href'] }}" class="group relative block overflow-hidden rounded-2xl bg-ink-900 no-underline">
                                <x-framed-photo :src="$f['img']" ratio="4/3" class="w-full" />
                                <span class="block p-4">
                                    <span class="block font-semibold text-white">{{ $f['title'] }}</span>
                                    <span class="mt-1 block text-sm text-white/75">{{ $f['text'] }}</span>
                                    <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-gold-200">{{ $f['cta'] }}<svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/></svg></span>
                                </span>
                            </a>
                        @endif
                    </div>
                    <div class="border-t border-ink-200/60 bg-ivory-50">
                        <div class="container-page flex items-center justify-between py-3 text-sm">
                            <a href="{{ $item['href'] }}" class="font-semibold">{{ __('See everything in :section', ['section' => $item['label']]) }}</a>
                            <span class="text-ink-600">{{ __('Need help choosing?') }} <a href="{{ route('contact') }}">{{ __('Talk to our team') }}</a></span>
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</nav>
