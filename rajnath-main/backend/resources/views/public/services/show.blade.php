@php $qa = \App\Support\Seo\JsonLd::serviceQuestions($o); @endphp
<x-layouts.public :title="$t->title" :description="$t->meta_description ?: $t->summary" :image="$o->image_path" :jsonld="[\App\Support\Seo\JsonLd::service($o), \App\Support\Seo\JsonLd::faq($qa), \App\Support\Seo\JsonLd::breadcrumbs([[__('Services'), route('services.index')], [$o->family->label(), route('services.index', ['family' => $o->family->slug])], [$t->title, route('services.show', $o->slug)]])]">
    <section class="bg-cosmic stars relative isolate overflow-hidden text-white">
        <x-zodiac-wheel class="pointer-events-none absolute -right-28 top-1/2 -z-10 hidden size-[28rem] -translate-y-1/2 animate-spin-slower opacity-25 sm:block" />
        <div class="container-page py-10 sm:py-12">
            <nav aria-label="Breadcrumb" class="text-sm text-white/70">
                <ol class="flex flex-wrap gap-1.5">
                    <li><a href="{{ route('services.index') }}" class="text-gold-200">{{ __('Services') }}</a> /</li>
                    <li><a href="{{ route('services.index', ['family' => $o->family->slug]) }}" class="text-gold-200">{{ $o->family->label() }}</a> /</li>
                    <li aria-current="page" class="text-white">{{ $t->title }}</li>
                </ol>
            </nav>
            <p class="eyebrow mt-5 text-gold-200">{{ $o->family->label() }}</p>
            <h1 class="mt-2 max-w-3xl text-3xl text-white sm:text-4xl">{{ $t->title }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-white/80">{{ $t->summary }}</p>
            <a href="#book-h" class="btn-gold mt-6">{{ __('See prices and book') }}</a>
        </div>
    </section>
    <div class="container-page py-10">
        <div class="grid gap-10 lg:grid-cols-[1fr_24rem]">
            <article class="min-w-0">
                @if ($o->image_path)
                    <x-framed-photo :src="$o->image_path" ratio="4/3" class="w-full rounded-[var(--radius-card)] shadow-[var(--shadow-card)]" />
                @endif
                @if ($t->process || $t->benefits || $t->faqList())
                    <nav aria-label="{{ __('On this page') }}" class="mt-6 flex flex-wrap gap-2 text-sm">
                        <a href="#about" class="chip">{{ __('About') }}</a>
                        @if ($t->process)<a href="#vidhi" class="chip">{{ __('How it is done') }}</a>@endif
                        @if ($t->benefits)<a href="#benefits" class="chip">{{ __('Benefits') }}</a>@endif
                        <a href="#steps" class="chip">{{ __('How booking works') }}</a>
                        <a href="#qa-h" class="chip">{{ __('Questions') }}</a>
                        <a href="#book-h" class="chip lg:hidden">{{ __('Prices') }}</a>
                    </nav>
                @endif
                @if ($t->body)
                    <h2 id="about" class="mt-10 text-xl">{{ __('About :title', ['title' => $t->title]) }}</h2>
                    <div class="mt-4 space-y-4 leading-7">@foreach (preg_split('/\R{2,}/', $t->body) as $para)<p>{{ $para }}</p>@endforeach</div>
                @endif
                @if ($t->process)
                    <h2 id="vidhi" class="mt-10 text-xl">{{ __('How it is done') }}</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($t->process as $i => $step)
                            <li class="card flex gap-4 p-4 text-sm leading-6"><span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-full bg-gold-50 font-semibold text-gold-700 ring-1 ring-gold-200">{{ $i + 1 }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                @endif
                @if ($t->benefits)
                    <h2 id="benefits" class="mt-10 text-xl">{{ __('Benefits') }}</h2>
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($t->benefits as $b)
                            <li class="flex gap-3 rounded-2xl bg-ivory-100 p-4 text-sm"><x-astro-icon name="sparkle" class="size-5 shrink-0 text-gold-700" /><span>{{ $b }}</span></li>
                        @endforeach
                    </ul>
                @endif
                @if ($t->includes)
                    <h2 class="mt-10 text-xl">{{ __("What's included") }}</h2>
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($t->includes as $i)
                            <li class="card flex gap-3 p-4 text-sm"><span aria-hidden="true" class="mt-0.5 text-gold-700">✓</span><span>{{ $i }}</span></li>
                        @endforeach
                    </ul>
                @endif
                @if ($t->audience)
                    <h2 class="mt-10 text-xl">{{ __('Who it is for') }}</h2>
                    <ul class="mt-4 list-disc space-y-1.5 pl-5 text-ink-700">@foreach ($t->audience as $a)<li>{{ $a }}</li>@endforeach</ul>
                @endif
                @if ($t->exclusions)<h2 class="mt-10 text-xl">{{ __('Not included') }}</h2><p class="mt-3 text-ink-700">{{ $t->exclusions }}</p>@endif
                <section class="mt-10" aria-labelledby="steps">
                    <h2 id="steps" class="text-xl">{{ __('How booking works') }}</h2>
                    <ol class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Support\BookingSteps::for($o) as $i => $s)
                            <li class="card p-4 text-sm"><p class="font-semibold"><span class="text-gold-700">{{ $i + 1 }}.</span> {{ $s['title'] }}</p><p class="mt-1 text-ink-700">{{ $s['text'] }}</p></li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-sm text-ink-600">{{ __('Every step is confirmed by SMS or email and an app notification. Need help? Ask our assistant or call :phone.', ['phone' => $site('contact.phone')]) }}</p>
                </section>
                <section class="mt-10" aria-labelledby="qa-h">
                    <h2 id="qa-h" class="text-xl">{{ __('Quick answers') }}</h2>
                    <dl class="mt-4 space-y-3">@foreach ($qa as [$q, $a])<div class="card p-4"><dt class="font-semibold">{{ $q }}</dt><dd class="mt-1 text-sm text-ink-700">{{ $a }}</dd></div>@endforeach</dl>
                </section>
                @if ($t->disclaimer)
                    <p class="mt-10 rounded-2xl border border-gold-200 bg-gold-50 p-4 text-sm text-ink-700"><strong>{{ __('Please note:') }}</strong> {{ $t->disclaimer }}</p>
                @endif
                <x-ask-ai class="mt-10" :q="'Tell me about '.$t->title.': how it works, what I get and the price options.'" :suggestions="['Which option should I choose?', 'How do I book it?', 'What happens after payment?']" />
            </article>

            <aside class="lg:sticky lg:top-24 lg:self-start" aria-labelledby="book-h">
                <div class="card p-5 sm:p-6">
                    <h2 id="book-h" class="text-lg">{{ __('Options & prices') }}</h2>
                    <ul class="mt-4 divide-y divide-ink-200/60">
                        @foreach ($o->variants->where('active', true) as $v)
                            <li class="flex items-start justify-between gap-3 py-3 text-sm">
                                <span><span class="font-medium">{{ $v->label() }}</span>
                                    <span class="block text-xs text-ink-600">{{ implode(' · ', array_filter([$v->duration_min ? __(':n min', ['n' => $v->duration_min]) : null, $v->participants > 1 ? __(':n people', ['n' => $v->participants]) : null, $v->urgent ? __('Priority') : null])) }}</span></span>
                                <span class="whitespace-nowrap font-semibold">{{ \App\Support\Money::format($v->price_minor) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @php $payable = app(\App\Domain\Payments\Razorpay::class)->configured() && $o->variants->where('active', true)->whereNotNull('price_minor')->isNotEmpty(); @endphp
                    @if ($payable)
                        @auth
                        <form method="POST" action="{{ route('checkout.buy') }}" class="mt-4 space-y-3">
                            @csrf <input type="hidden" name="key" value="{{ \App\Http\Controllers\Web\PaymentController::newKey() }}">
                            <x-select name="variant" :label="__('Choose option')" :options="$o->variants->where('active', true)->whereNotNull('price_minor')->mapWithKeys(fn ($v) => [$v->ref => $v->label().' — '.\App\Support\Money::format($v->price_minor)])->all()" />
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="use_wallet" value="1" class="size-4 accent-ink-900"> {{ __('Use wallet balance first') }}</label>
                            <x-field name="coupon" :label="__('Coupon code (optional)')" :value="old('coupon')" autocomplete="off" maxlength="32" class="input uppercase" />
                            @error('variant')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                            <button class="btn-primary w-full">{{ __('Continue') }}</button>
                        </form>
                        <p class="mt-3 text-xs text-ink-600">{{ $o->variants->contains(fn ($v) => \App\Domain\Booking\Slots::bookable($v)) ? __('Call options take you to a calendar to pick your time before you pay.') : __('First add your details (photos, names or birth details), then pay — payment is always the last step.') }}</p>
                        @else
                        <a href="{{ route('account.sign-in') }}" class="btn-primary mt-4 w-full">{{ __('Sign in to pay & book') }}</a>
                        @endauth
                    @else
                        <p class="mt-3 rounded-xl bg-ivory-100 p-3 text-xs text-ink-700">{{ __('Online payment is not open for this service yet. Send a request below and our team will call you to confirm the time and fee.') }}</p>
                    @endif
                </div>
                <div class="card mt-5 p-5 sm:p-6" id="request">
                    <h2 class="text-lg">{{ __('Request this service') }}</h2>
                    @include('public.partials.enquiry-form', ['offering' => $o])
                </div>
            </aside>
        </div>

        @php $rv = app(\App\Domain\Reviews\Reviews::class); $svcReviews = $rv->forOffering($o->id, 4); $svcSummary = $rv->summary($o->id); @endphp
        @if ($svcReviews->isNotEmpty())
            <section class="mt-16" aria-labelledby="rev-h">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="rev-h" class="text-xl">{{ __('What clients say') }}</h2>
                        <p class="mt-1 flex items-center gap-2 text-sm text-ink-600"><x-stars :rating="$svcSummary['average']" /> {{ number_format($svcSummary['average'], 1) }} · {{ trans_choice(':count review|:count reviews', $svcSummary['count'], ['count' => $svcSummary['count']]) }}</p>
                    </div>
                    <a href="{{ route('reviews', ['service' => $o->slug]) }}" class="btn-outline">{{ __('All reviews') }}</a>
                </div>
                <div class="mt-5 grid gap-5 md:grid-cols-2">@foreach ($svcReviews as $r)<x-review-card :r="$r" clamp />@endforeach</div>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-16" aria-labelledby="rel-h">
                <h2 id="rel-h" class="text-xl">{{ __('Related services') }}</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach ($related as $r)<div class="relative"><x-service-card :o="$r" /></div>@endforeach</div>
            </section>
        @endif
    </div>
</x-layouts.public>
