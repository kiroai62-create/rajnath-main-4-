@php
    $suggestions = [__("Today's Rashifal for Simha"), __('Aaj ka Panchang aur Rahu Kaal'), __('Book a video consultation'), __('Griha pravesh puja ka kharcha?'), __('Which gemstone for Shani?'), __('Office address and timings')];
@endphp
<x-layouts.public title="Ask our AI assistant" description="Ask about consultations, Rashifal, Panchang, puja, Vastu, prices and booking, in Hindi, Hinglish or English.">
    <section class="bg-cosmic stars relative overflow-hidden text-white">
        <img src="/images/brand/logo-mark-light.svg" alt="" aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 size-[28rem] animate-spin-slower opacity-10">
        <div class="container-page relative py-12">
            <p class="eyebrow text-gold-200">{{ __('Jyotish AI assistant') }}</p>
            <h1 class="mt-2 text-3xl text-white sm:text-4xl"><span class="text-gold-grad">{{ __('Ask anything') }}</span> {{ __('about astrology, puja and booking') }}</h1>
            <p class="mt-3 max-w-2xl text-white/80">{{ __('Ask about any service, price, puja, Panchang or Rashifal, in Hindi, Hinglish or English. The assistant can find a free time and book your consultation or puja; you then pay securely on our checkout. For personal guidance, please book a consultation with Acharya Ji.') }}</p>
        </div>
    </section>
    <section class="container-page -mt-6 pb-10">
        <div class="card relative mx-auto max-w-3xl overflow-hidden p-0" x-data="assistant" data-endpoint="{{ route('assistant.chat', [], false) }}" data-notice="{{ $live ? '' : \App\Domain\Assistant\Assistant::SITE_NOTICE }}">
            <p class="border-b border-gold-200/60 bg-gold-50 px-5 py-2.5 text-sm text-gold-700" x-show="hasNotice" x-text="notice" role="status"></p>
            <div x-ref="log" class="max-h-[60vh] min-h-72 space-y-3 overflow-y-auto bg-ivory-50 p-5" role="log" aria-live="polite" aria-label="{{ __('Conversation') }}">
                <div x-show="empty" class="chat-bot"><p>{{ __('Namaste! How can I help you today? Try one of these:') }}</p></div>
                <div x-show="empty" class="flex flex-wrap gap-2">
                    @foreach ($suggestions as $q)<button type="button" class="chip" data-q="{{ $q }}" @click="ask">{{ $q }}</button>@endforeach
                </div>
                <template x-for="m in messages" :key="m.id">
                    <div :class="m.cls"><p class="whitespace-pre-line" x-text="m.content"></p>
                        <div class="mt-2 flex flex-wrap gap-2" x-show="m.hasActions"><template x-for="a in m.actions" :key="a.key"><a :href="a.url" :class="a.cls" class="min-h-9 px-3 text-xs" x-text="a.label"></a></template></div></div>
                </template>
                <p class="flex items-center gap-1.5 text-sm text-ink-600" x-show="busy"><span class="size-2 animate-bounce rounded-full bg-gold-500"></span><span class="size-2 animate-bounce rounded-full bg-saffron-500 [animation-delay:.15s]"></span><span class="size-2 animate-bounce rounded-full bg-maroon-600 [animation-delay:.3s]"></span><span class="sr-only">{{ __('Typing…') }}</span></p>
            </div>
            <div class="border-t border-ink-200/60 p-4">
                <p class="field-error mb-2" role="alert" x-show="error" x-text="error"></p>
                <form class="flex items-end gap-2" @submit.prevent="send">
                    <div class="flex-1">
                        <label for="f-message" class="sr-only">{{ __('Your question') }}</label>
                        <textarea id="f-message" x-model="draft" @keydown="onKey" rows="2" maxlength="1000" class="input" placeholder="{{ __('Type your question…') }}"></textarea>
                    </div>
                    <button class="btn-gold" :disabled="cannotSend">{{ __('Send') }}</button>
                </form>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-ink-600">
                    <span>{{ \App\Domain\Assistant\Assistant::DISCLAIMER }}</span>
                    <span class="flex gap-2">
                        <button type="button" class="btn-ghost min-h-9 px-3 text-xs" x-show="!empty" @click="clear">{{ __('New chat') }}</button>
                        <a x-show="handoff" class="btn-outline min-h-9 px-3 text-xs" href="{{ $signedIn ? route('account.support.index') : route('contact') }}">{{ __('Talk to our team') }}</a>
                    </span>
                </div>
                @unless ($signedIn)<p class="mt-2 text-xs text-ink-600"><a href="{{ route('account.sign-in') }}">{{ __('Sign in') }}</a> {{ __('to ask more questions each day and to book from the chat.') }}</p>@endunless
            </div>
        </div>
    </section>
</x-layouts.public>
