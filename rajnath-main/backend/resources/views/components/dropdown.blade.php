@props(['id', 'align' => 'right', 'width' => 'w-64', 'label' => null, 'buttonClass' => 'btn-ghost px-3', 'chevron' => true])
{{--
    Dropdown (disclosure navigation pattern, see resources/js/app.js "dropdown").
    <x-dropdown id="unique-id" label="Accessible name">
        <x-slot:trigger>Visible button content</x-slot:trigger>
        <a href="…" class="menu-item">Link</a>
    </x-dropdown>
    Without JavaScript the button does nothing, so every dropdown must have a no-JS route to the
    same pages elsewhere on the page (mobile menu, account tabs, footer).
--}}
<div x-data="dropdown" {{ $attributes->merge(['class' => 'relative']) }} @keydown.escape="escape" @click.outside="close" @focusout="focusOut" @menu-open.window="onOther">
    <button type="button" x-ref="trigger" @click="toggle" @keydown="triggerKey" :aria-expanded="expanded" aria-expanded="false" aria-controls="{{ $id }}"
            @if ($label) aria-label="{{ $label }}" @endif
            class="{{ $buttonClass }} aria-expanded:bg-ivory-100 aria-expanded:text-ink-900">
        {{ $trigger }}
        @if ($chevron)
            <svg aria-hidden="true" class="size-4 shrink-0 opacity-60 transition-transform" :class="chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
        @endif
    </button>
    <div id="{{ $id }}" x-ref="panel" x-show="open" x-cloak @keydown="panelKey"
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @class(['menu-panel', $width, 'right-0' => $align === 'right', 'left-0' => $align === 'left'])>
        {{ $slot }}
    </div>
</div>
