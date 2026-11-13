@php
    $u = auth()->user();
    $mine = $u && ! $u->isStaff();
    $today = now('Asia/Kolkata')->format('Y-m-d');
@endphp
<x-layouts.public :title="__('Book a puja on request')" :description="__('Tell us the puja you need. Acharya Ji\'s team calls you to fix the muhurat, place and dakshina. Online, at home or at a temple.')"
    :jsonld="[\App\Support\Seo\JsonLd::breadcrumbs([[__('Puja on request'), route('puja-request.create')]])]">
    <x-page-hero :eyebrow="__('Puja on request')" :title="__('Tell us the puja you need')">{{ __($intro) }}</x-page-hero>
    <div class="container-page grid gap-10 py-12 lg:grid-cols-[1fr_22rem]">
        <div class="card p-5 sm:p-8">
            @if (! $open)
                <p class="text-ink-700">{{ __('Puja requests are paused for a few days. Please call or WhatsApp us.') }}</p>
                <a href="{{ route('contact') }}" class="btn-primary mt-4">{{ __('Contact us') }}</a>
            @else
            <form method="POST" action="{{ route('puja-request.store') }}" class="space-y-6" novalidate x-data="pujaForm" data-puja="{{ old('puja_type', $selected) }}" data-mode="{{ old('mode', 'online') }}">
                @csrf
                {{-- Honeypot field for bots; hidden from people and assistive technology. --}}
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                <fieldset class="space-y-4">
                    <legend class="text-lg font-semibold">{{ __('1. Which puja?') }}</legend>
                    <div>
                        <label for="f-puja_type" class="field-label">{{ __('Puja') }}</label>
                        <select id="f-puja_type" name="puja_type" class="input" @change="setPuja" required @error('puja_type') aria-invalid="true" aria-describedby="f-puja_type-error" @enderror>
                            <option value="">{{ __('Choose a puja') }}</option>
                            @foreach ($types as $t)<option value="{{ $t }}" @selected(old('puja_type', $selected) === $t)>{{ __($t) }}</option>@endforeach
                            <option value="other" @selected(old('puja_type', $selected) === 'other')>{{ __('Other / not sure (write below)') }}</option>
                        </select>
                        @error('puja_type')<p id="f-puja_type-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="isOther" x-cloak>
                        <x-field name="puja_other" :label="__('Name of the puja, or describe what you need')" :value="$other" />
                    </div>
                    <div>
                        <label for="f-purpose" class="field-label">{{ __('Purpose or problem (sankalp)') }}</label>
                        <textarea id="f-purpose" name="purpose" rows="3" maxlength="2000" class="input" placeholder="{{ __('For example: health of my father, delay in marriage, new home') }}">{{ old('purpose') }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="space-y-4 border-t border-ink-200/60 pt-6">
                    <legend class="text-lg font-semibold">{{ __('2. Where and when?') }}</legend>
                    <div>
                        <p class="field-label">{{ __('Where should the puja be done?') }}</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Models\PujaRequest::MODES as $k => $l)
                                <label class="flex min-h-11 items-center gap-2 rounded-xl border border-ink-200 px-3 text-sm has-[:checked]:border-maroon-600 has-[:checked]:bg-ivory-100">
                                    <input type="radio" name="mode" value="{{ $k }}" @change="setMode" @checked(old('mode', 'online') === $k) class="size-4 accent-ink-900"> {{ __($l) }}
                                </label>
                            @endforeach
                        </div>
                        @error('mode')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="isHome" x-cloak>
                        <x-field name="address" :label="__('Address or city for the puja')" autocomplete="street-address" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field name="preferred_date" :label="__('Preferred date')" type="date" :min="$today" />
                        <x-field name="alternate_date" :label="__('Second choice (optional)')" type="date" :min="$today" />
                        <x-select name="preferred_time" :label="__('Time of day')" :options="collect(\App\Models\PujaRequest::TIMES)->map(fn ($l) => __($l))->all()" />
                    </div>
                    <x-select name="samagri" :label="__('Puja samagri')" :options="['' => __('Choose')] + collect(\App\Models\PujaRequest::SAMAGRI)->map(fn ($l) => __($l))->all()" />
                </fieldset>

                <fieldset class="space-y-4 border-t border-ink-200/60 pt-6">
                    <legend class="text-lg font-semibold">{{ __('3. Sankalp details') }}</legend>
                    <x-field name="participants" :label="__('Names to be taken in the sankalp (optional)')" :hint="__('For example: Ramesh Kumar (self), Sunita Devi (wife)')" />
                    <x-field name="gotra" :label="__('Gotra (optional)')" />
                    <details class="rounded-xl border border-ink-200 p-3" @if (old('birth_date') || old('birth_place')) open @endif>
                        <summary class="cursor-pointer text-sm font-medium">{{ __('Add birth details for the muhurat (optional)') }}</summary>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <x-field name="birth_date" :label="__('Date of birth')" type="date" :max="$today" />
                            <x-field name="birth_time" :label="__('Time of birth')" type="time" />
                            <div class="sm:col-span-2"><x-field name="birth_place" :label="__('Place of birth')" /></div>
                        </div>
                    </details>
                </fieldset>

                <fieldset class="space-y-4 border-t border-ink-200/60 pt-6">
                    <legend class="text-lg font-semibold">{{ __('4. How do we reach you?') }}</legend>
                    <x-field name="name" :label="__('Your name')" :value="$mine ? $u->name : null" autocomplete="name" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="phone" :label="__('Mobile number')" type="tel" :value="$mine ? $u->phone_e164 : null" autocomplete="tel" inputmode="tel" required :hint="__('We will call or WhatsApp you on this number.')" />
                        <x-field name="email" :label="__('Email (optional)')" type="email" :value="$mine ? $u->email : null" autocomplete="email" />
                    </div>
                    <x-select name="language" :label="__('Language for the call')" :options="collect(\App\Models\PujaRequest::LANGUAGES)->mapWithKeys(fn ($l) => [$l => __($l)])->all()" />
                    <div>
                        <label for="f-message" class="field-label">{{ __('Anything else we should know? (optional)') }}</label>
                        <textarea id="f-message" name="message" rows="3" maxlength="2000" class="input">{{ old('message') }}</textarea>
                    </div>
                </fieldset>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="consent" value="1" class="mt-1 size-4 accent-ink-900" @checked(old('consent')) @if ($errors->has('consent')) aria-invalid="true" aria-describedby="consent-error" @endif>
                    <span>{{ __('I agree to be contacted about this request. My details are handled as described in the') }} <a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a>.</span>
                </label>
                @error('consent')<p id="consent-error" class="field-error">{{ $message }}</p>@enderror
                <button class="btn-primary w-full">{{ __('Send puja request') }}</button>
            </form>
            @endif
        </div>
        <aside class="space-y-4">
            <div class="card overflow-hidden">
                <x-photo src="images/art/family-puja.jpg" alt="" class="h-40 w-full object-cover" />
                <div class="p-5">
                    <h2 class="text-base">{{ __('How it works') }}</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-ink-700">
                        <li>{{ __('Send this form. Nothing is charged online.') }}</li>
                        <li>{{ __('Our team calls you within one working day.') }}</li>
                        <li>{{ __('Acharya Ji suggests the vidhi and a shubh muhurat.') }}</li>
                        <li>{{ __('We confirm the date, place and dakshina with you.') }}</li>
                    </ol>
                </div>
            </div>
            @if ($site('contact.whatsapp') || $site('contact.phone'))
                <div class="card p-5 text-sm">
                    <p class="font-semibold">{{ __('Prefer to talk?') }}</p>
                    @if ($site('contact.phone'))<a href="tel:{{ $site('contact.phone') }}" class="btn-outline mt-3 w-full">{{ __('Call') }} {{ $site('contact.phone') }}</a>@endif
                    @if ($site('contact.whatsapp'))<a href="https://wa.me/{{ ltrim($site('contact.whatsapp'), '+') }}" rel="noopener" class="btn-ghost mt-2 w-full">WhatsApp</a>@endif
                </div>
            @endif
        </aside>
    </div>
</x-layouts.public>
