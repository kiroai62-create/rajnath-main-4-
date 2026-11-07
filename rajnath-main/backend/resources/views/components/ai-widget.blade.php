{{-- Floating "Ask AI" button (bottom right) with a chat panel. Works with or without an AI key:
     without one it answers from the website (Domain/Assistant/SiteGuide) and shows a short notice. --}}
@php
    $live = app(\App\Domain\Assistant\Assistant::class)->live();
    $suggestions = [__("Today's Rashifal for Mesha"), __("Aaj ka Panchang aur Rahu Kaal"), __('Book a video consultation'), __('Kaal Sarp puja price'), __('Office address and timings')];
@endphp
<div x-data="aiWidget" @keydown.escape.window="escape" class="print:hidden">
    <button type="button" x-ref="opener" @click="toggle" :aria-expanded="expanded" aria-expanded="false" aria-controls="ai-panel" :aria-label="openerLabel"
            class="group fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 z-50 inline-flex min-h-14 items-center gap-2.5 rounded-full bg-gradient-to-r from-ink-900 via-ink-800 to-maroon-700 py-2 pl-2 pr-5 text-sm font-semibold text-white shadow-[0_16px_36px_-12px_rgb(27_20_70/0.75)] ring-2 ring-gold-300/70 transition hover:-translate-y-0.5 sm:right-6">
        <span class="relative grid size-10 place-items-center rounded-full bg-ink-950 animate-pulse-ring">
            <img src="/images/brand/logo-mark-light.svg" alt="" width="36" height="36" class="size-9 transition duration-700 group-hover:rotate-[360deg]">
        </span>
        <span x-show="!open">{{ __('Ask AI') }}</span>
        <span x-show="open" x-cloak>{{ __('Close') }}</span>
    </button>

    <section id="ai-panel" x-show="open" x-cloak role="dialog" aria-modal="false" aria-labelledby="ai-panel-h"
             class="fixed inset-x-3 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-50 flex max-h-[min(40rem,calc(100dvh-7rem))] flex-col overflow-hidden rounded-3xl border border-gold-200/70 bg-white shadow-[0_30px_80px_-24px_rgb(27_20_70/0.6)] sm:inset-x-auto sm:right-6 sm:w-[25rem]"
             x-data="assistant" data-endpoint="{{ route('assistant.chat', [], false) }}" data-notice="{{ $live ? '' : \App\Domain\Assistant\Assistant::SITE_NOTICE }}">
        <header class="bg-cosmic stars relative flex items-center gap-3 px-4 py-3.5 text-white">
            <img src="/images/brand/logo-mark-light.svg" alt="" width="40" height="40" class="size-10 animate-spin-slow">
            <div class="min-w-0 flex-1">
                <h2 id="ai-panel-h" class="text-base text-white">{{ __('Jyotish AI assistant') }}</h2>
                <p class="flex items-center gap-1.5 text-xs text-white/75"><span @class(['size-2 rounded-full', 'bg-emerald-400' => $live, 'bg-gold-300' => ! $live])></span>{{ $live ? __('Online · Hindi, Hinglish or English') : __('Website guide · Hindi, Hinglish or English') }}</p>
            </div>
            <button type="button" class="btn-ghost min-h-9 px-2 text-xs text-white/80 hover:bg-white/10 hover:text-white" @click="clear" x-show="!empty">{{ __('New chat') }}</button>
            <a href="{{ route('assistant') }}" class="btn-ghost min-h-9 px-2 text-xs text-white/80 hover:bg-white/10 hover:text-white">{{ __('Full page') }}</a>
        </header>
        <p class="border-b border-gold-200/60 bg-gold-50 px-4 py-2 text-xs text-gold-700" x-show="hasNotice" x-text="notice" role="status"></p>
        <div x-ref="log" class="flex-1 space-y-3 overflow-y-auto bg-ivory-50 p-4" role="log" aria-live="polite" aria-label="{{ __('Conversation') }}">
            <div x-show="empty" class="chat-bot">
                <p>{{ __('Namaste! I can help with consultations and prices, today\'s Rashifal and Panchang, pujas, Vastu, gemstones and booking.') }}</p>
            </div>
            <div x-show="empty" class="flex flex-wrap gap-2">
                @foreach ($suggestions as $q)<button type="button" class="chip text-xs" data-q="{{ $q }}" @click="ask">{{ $q }}</button>@endforeach
            </div>
            <template x-for="m in messages" :key="m.id">
                <div :class="m.cls"><p class="whitespace-pre-line" x-text="m.content"></p>
                    <div class="mt-2 flex flex-wrap gap-2" x-show="m.hasActions"><template x-for="a in m.actions" :key="a.key"><a :href="a.url" :class="a.cls" class="min-h-9 px-3 text-xs" x-text="a.label"></a></template></div></div>
            </template>
            <p class="flex items-center gap-1.5 text-sm text-ink-600" x-show="busy"><span class="size-2 animate-bounce rounded-full bg-gold-500"></span><span class="size-2 animate-bounce rounded-full bg-saffron-500 [animation-delay:.15s]"></span><span class="size-2 animate-bounce rounded-full bg-maroon-600 [animation-delay:.3s]"></span><span class="sr-only">{{ __('Typing…') }}</span></p>
        </div>
        <div class="border-t border-ink-200/60 bg-white p-3">
            <p class="field-error mb-2" role="alert" x-show="error" x-text="error"></p>
            <form class="flex items-end gap-2" @submit.prevent="send">
                <label for="ai-w-message" class="sr-only">{{ __('Your question') }}</label>
                <textarea id="ai-w-message" x-model="draft" @keydown="onKey" rows="1" maxlength="1000" class="input min-h-11 resize-none" placeholder="{{ __('Ask anything…') }}"></textarea>
                <button class="btn-gold min-h-11 px-4" :disabled="cannotSend" aria-label="{{ __('Send') }}"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.4 21 12 3.4 3.6l-.01 6.53L15 12 3.39 13.87z"/></svg></button>
            </form>
            <div class="mt-2 flex items-center justify-between gap-2 text-[0.7rem] leading-4 text-ink-600">
                <span>{{ __('AI answers may be imperfect. Not medical, legal or financial advice.') }}</span>
                <a x-show="handoff" class="shrink-0 font-semibold" href="{{ auth()->check() && ! auth()->user()->isStaff() ? route('account.support.index') : route('contact') }}">{{ __('Talk to our team') }}</a>
            </div>
        </div>
    </section>
</div>
