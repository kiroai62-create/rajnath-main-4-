<x-layouts.account :title="$t->title">
    @php
        $price = \App\Support\Money::format((int) $v->price_minor);
        $step = ['palm' => 'Upload photos of both palms', 'puja' => 'Sankalp details', 'report' => 'Birth details for your report', 'question' => 'Your question', 'visit' => 'Visit details'][$kind];
        $isHome = str_contains(mb_strtolower($v->name), 'home');
    @endphp
    <ol class="mb-5 flex flex-wrap gap-2 text-xs font-semibold" aria-label="Steps">
        <li class="rounded-full bg-gold-50 px-3 py-1.5 text-ink-900 ring-1 ring-gold-200">1. {{ $step }}</li>
        <li class="rounded-full bg-ivory-100 px-3 py-1.5 text-ink-600">2. Pay {{ $price }} securely</li>
        <li class="rounded-full bg-ivory-100 px-3 py-1.5 text-ink-600">3. Done — we update you at every step</li>
    </ol>
    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('checkout.details.submit', $v->ref) }}" @if ($kind === 'palm') enctype="multipart/form-data" @endif class="card space-y-5 p-5 sm:p-6" novalidate>
            @csrf
            <input type="hidden" name="key" value="{{ old('key', $key) }}"><input type="hidden" name="use_wallet" value="{{ $useWallet ? 1 : 0 }}"><input type="hidden" name="coupon" value="{{ $coupon }}">
            <h2 class="text-lg">{{ $step }}</h2>
            @error('variant')<p class="field-error" role="alert">{{ $message }}</p>@enderror

            @if ($kind === 'palm')
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (['left' => 'Left palm', 'right' => 'Right palm'] as $k => $l)
                        <div>
                            <label for="f-{{ $k }}" class="field-label">{{ $l }}</label>
                            <input id="f-{{ $k }}" name="{{ $k }}" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" required class="input file:mr-3 file:rounded-full file:border-0 file:bg-ivory-100 file:px-3 file:py-1.5" @error($k) aria-invalid="true" aria-describedby="e-{{ $k }}" @enderror>
                            @error($k)<p id="e-{{ $k }}" class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-select name="dominant_hand" label="Hand you write with" :value="old('dominant_hand', 'right')" :options="['right' => 'Right', 'left' => 'Left']" />
                    <x-field name="age" label="Age (optional)" type="number" min="1" max="120" />
                    <x-select name="language" label="Report language" :value="old('language', 'en')" :options="['en' => 'English', 'hi' => 'Hindi']" />
                </div>
                <div><label for="f-question" class="field-label">Your main question (optional)</label><textarea id="f-question" name="question" rows="3" maxlength="1000" class="input">{{ old('question') }}</textarea></div>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="consent" value="1" class="mt-1 size-4 accent-ink-900" @checked(old('consent'))>
                    <span>I agree that my palm photos are used only to prepare this report. They are seen only by Acharya Ji's team and are deleted automatically after the report period.</span></label>
                @error('consent')<p class="field-error">{{ $message }}</p>@enderror
            @elseif ($kind === 'puja')
                <fieldset>
                    <legend class="field-label">Names for the sankalp (up to {{ max(1, (int) $v->participants) }})</legend>
                    @php $devs = old('devotees', [['name' => $user->name, 'relation' => 'Self']]); $devs = array_pad($devs, max(1, min(8, (int) $v->participants)), ['name' => '', 'relation' => '']); @endphp
                    <div class="space-y-2">
                        @foreach ($devs as $i => $dv)
                            <div class="grid grid-cols-[1fr_9rem] gap-2">
                                <div><label for="dn{{ $i }}" class="sr-only">Name {{ $i + 1 }}</label><input id="dn{{ $i }}" name="devotees[{{ $i }}][name]" value="{{ $dv['name'] ?? '' }}" class="input" placeholder="Full name"></div>
                                <div><label for="dr{{ $i }}" class="sr-only">Relation {{ $i + 1 }}</label><input id="dr{{ $i }}" name="devotees[{{ $i }}][relation]" value="{{ $dv['relation'] ?? '' }}" class="input" placeholder="Relation"></div>
                            </div>
                        @endforeach
                    </div>
                    @error('devotees')<p class="field-error">{{ $message }}</p>@enderror @error('devotees.0.name')<p class="field-error">{{ $message }}</p>@enderror
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="gotra" label="Gotra (optional)" />
                    <x-field name="phone" label="Mobile for coordination" type="tel" :value="old('phone', $user->phone_e164)" required />
                    <x-select name="location" label="Where" :value="old('location', $isHome ? 'home' : 'online')" :options="['online' => 'Performed on my behalf (watch online / photos)', 'temple' => 'At a temple, I will attend', 'home' => 'At my home']" />
                    <x-field name="preferred_date" label="Preferred date (optional)" type="date" :min="now('Asia/Kolkata')->format('Y-m-d')" />
                </div>
                <div><label for="f-address" class="field-label">Address (for puja at home)</label><textarea id="f-address" name="address" rows="2" class="input">{{ old('address') }}</textarea>@error('address')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="f-wish" class="field-label">Your wish / purpose (optional)</label><textarea id="f-wish" name="wish" rows="2" maxlength="500" class="input">{{ old('wish') }}</textarea></div>
            @elseif (in_array($kind, ['report', 'question'], true))
                @if ($profiles->isEmpty())
                    <p class="rounded-xl bg-gold-50 p-4 text-sm ring-1 ring-gold-200">Add your birth details (date, time and place of birth) first. <a href="{{ route('account.birth-profiles.create', ['back' => url()->full()]) }}" class="font-semibold">Add birth details</a></p>
                @else
                    @php $opts = $profiles->mapWithKeys(fn ($p) => [$p->ref => $p->name.' — '.$p->birth_date?->format('d M Y').($p->birth_utc ? '' : ' (no birth time)')])->all(); @endphp
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-select name="profile_a" :label="$twoProfiles ? 'First person' : 'Birth details'" :options="$opts" :value="old('profile_a')" />
                        @if ($twoProfiles)<x-select name="profile_b" label="Second person" :options="$opts" :value="old('profile_b')" />@endif
                        <x-select name="language" label="Report language" :value="old('language', 'en')" :options="['en' => 'English', 'hi' => 'Hindi']" />
                    </div>
                    <p class="text-xs text-ink-600">Someone else? <a href="{{ route('account.birth-profiles.create', ['back' => url()->full()]) }}">Add another birth profile</a>.</p>
                @endif
                <div><label for="f-question" class="field-label">{{ $kind === 'question' ? 'Your question (one per line for two or three questions)' : 'Anything you want us to focus on (optional)' }}</label>
                    <textarea id="f-question" name="question" rows="4" maxlength="2000" class="input" @if ($kind === 'question') required @endif>{{ old('question') }}</textarea>@error('question')<p class="field-error">{{ $message }}</p>@enderror</div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="phone" label="Mobile number" type="tel" :value="old('phone', $user->phone_e164)" required />
                    <x-field name="preferred_date" label="Preferred date (optional)" type="date" :min="now('Asia/Kolkata')->format('Y-m-d')" />
                    @if ($v->mode === 'onsite')<x-field name="city" label="City / town" required />@endif
                </div>
                @if ($v->mode === 'onsite')<div><label for="f-address" class="field-label">Property address</label><textarea id="f-address" name="address" rows="2" class="input" required>{{ old('address') }}</textarea>@error('address')<p class="field-error">{{ $message }}</p>@enderror</div>@endif
                <div><label for="f-notes" class="field-label">What should we know? (optional)</label><textarea id="f-notes" name="notes" rows="3" maxlength="1000" class="input">{{ old('notes') }}</textarea></div>
            @endif

            <button class="btn-primary w-full sm:w-auto" @if (in_array($kind, ['report', 'question'], true) && $profiles->isEmpty()) disabled @endif>Continue to payment · {{ $price }}</button>
            <p class="text-xs text-ink-600">Nothing is charged until you pay on the next screen. Your details are kept private and used only for this service.</p>
        </form>
        <aside class="card h-fit p-5 text-sm">
            <p class="eyebrow">{{ $o->family?->label() }}</p>
            <h2 class="mt-1 text-base">{{ $t->title }}</h2>
            <p class="mt-1 text-ink-700">{{ $v->label() }}</p>
            <p class="mt-3 text-lg font-semibold">{{ $price }}</p>
            @if ($kind === 'palm')
                <h3 class="mt-5 text-sm font-semibold">Tips for clear photos</h3>
                <ul class="mt-2 list-disc space-y-1.5 pl-5 text-ink-700">
                    <li>Use daylight or bright light; no flash glare.</li>
                    <li>Open your palm fully, fingers slightly apart.</li>
                    <li>Hold the phone straight above the palm and include the wrist.</li>
                </ul>
            @endif
            <a href="{{ route('services.show', $o->slug) }}" class="mt-5 inline-block">← Back to the service</a>
        </aside>
    </div>
</x-layouts.account>
