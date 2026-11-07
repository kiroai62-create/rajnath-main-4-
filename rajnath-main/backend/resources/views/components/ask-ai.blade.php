@props(['q', 'label' => null, 'suggestions' => []])
{{-- "Ask AI about this" — opens the chat widget and asks with the current page as context. --}}
<div {{ $attributes->merge(['class' => 'rounded-[var(--radius-card)] border border-gold-200 bg-gradient-to-r from-gold-50 to-white p-4 print:hidden']) }}>
    <p class="flex items-center gap-2 text-sm font-semibold text-ink-900"><span aria-hidden="true">✨</span>{{ __('Have a question? Ask our AI assistant') }}</p>
    <div class="mt-3 flex flex-wrap gap-2">
        <button type="button" x-data="askAi" @click="go" data-q="{{ $q }}" class="btn-gold min-h-10 px-4 text-sm">{{ $label ?? __('Ask AI about this') }}</button>
        @foreach ($suggestions as $s)
            <button type="button" x-data="askAi" @click="go" data-q="{{ $s }}" class="btn-outline min-h-10 px-3 text-sm">{{ $s }}</button>
        @endforeach
    </div>
</div>
