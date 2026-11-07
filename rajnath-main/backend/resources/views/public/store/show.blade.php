@php $g = \App\Domain\Store\ProductGuide::for($p); @endphp
<x-layouts.public :title="$p->name" :description="$p->summary" :image="$p->image_path" type="product" :jsonld="[\App\Support\Seo\JsonLd::product($p), \App\Support\Seo\JsonLd::breadcrumbs([[__('Gemstone store'), route('store.index')], [$p->name, route('store.show', $p->slug)]])]">
    <section class="bg-cosmic stars relative overflow-hidden text-white">
        <div class="container-page relative py-6">
            <nav aria-label="Breadcrumb" class="text-sm text-white/70"><a href="{{ route('store.index') }}" class="text-gold-200">Store</a> / <a href="{{ route('store.index', ['category' => $p->category]) }}" class="text-gold-200">{{ $g['category_label'] }}</a> / <span class="text-white">{{ $p->name }}</span></nav>
        </div>
    </section>
    <div class="container-page py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_26rem]">
            <article class="min-w-0">
                <div class="grid gap-8 md:grid-cols-2">
                    <div>
                        @if ($p->image_path)
                            <x-framed-photo :src="$p->image_path" :alt="$p->name" eager ratio="1/1" class="w-full rounded-[var(--radius-card)] ring-1 ring-gold-200 shadow-[var(--shadow-glow)]" />
                        @else
                            <div class="aspect-square w-full rounded-[var(--radius-card)] bg-ivory-100" aria-hidden="true"></div>
                        @endif
                        @if ($g['credit'])
                            <p class="mt-2 text-xs text-ink-600">Photo of this type of item: {{ $g['credit']['author'] }}, <a href="{{ $g['credit']['source'] }}" rel="noopener nofollow" target="_blank">Wikimedia Commons</a>, {{ $g['credit']['license'] }}. The item you receive is matched to its description and certificate.</p>
                        @endif
                    </div>
                    <div>
                        <p class="eyebrow">{{ $g['category_label'] }}</p>
                        <h1 class="mt-2 text-3xl">{{ $p->name }}</h1>
                        <p class="mt-3 text-lg text-ink-600">{{ $p->summary }}</p>
                        <p class="mt-4 text-3xl font-semibold text-maroon-700">{{ \App\Support\Money::format((int) $p->price_minor) }}</p>
                        <p class="text-sm text-ink-600">Inclusive of all taxes · free insured shipping in India</p>
                        <p class="mt-2 text-sm font-semibold {{ $p->stock ? 'text-success-700' : 'text-danger-700' }}">{{ $p->stock ? ($p->stock <= 3 ? 'Only '.$p->stock.' left' : 'In stock') : 'Out of stock' }}</p>
                        <ul class="mt-5 space-y-2">
                            @foreach ($g['highlights'] as $h)
                                <li class="flex gap-2 text-sm"><span class="mt-0.5 text-gold-700" aria-hidden="true">✦</span><span>{{ $h }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                @if ($p->description)
                    <section class="mt-10" aria-labelledby="about-h">
                        <h2 id="about-h" class="text-xl">About this item</h2>
                        <div class="mt-3 space-y-4 leading-7">@foreach (preg_split('/\R{2,}/', $p->description) as $para)<p>{{ $para }}</p>@endforeach</div>
                    </section>
                @endif

                <div class="mt-10 grid gap-6 xl:grid-cols-2">
                    @foreach ($g['sections'] as $i => $s)
                        <section class="card p-5 sm:p-6 {{ $s['kind'] === 'table' ? 'xl:col-span-2' : '' }}" aria-labelledby="sec-{{ $i }}">
                            <h2 id="sec-{{ $i }}" class="text-lg">{{ $s['title'] }}</h2>
                            @if ($s['kind'] === 'table')
                                <dl class="mt-4 divide-y divide-ink-100">
                                    @foreach ($s['items'] as [$k, $v])
                                        <div class="grid gap-1 py-2.5 sm:grid-cols-[11rem_1fr]"><dt class="text-sm font-semibold text-ink-600">{{ $k }}</dt><dd>{{ $v }}</dd></div>
                                    @endforeach
                                </dl>
                            @else
                                <ul class="mt-3 space-y-2">
                                    @foreach ($s['items'] as $it)
                                        <li class="flex gap-2"><span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ str_contains(strtolower($s['title']), 'caution') || str_contains(strtolower($s['title']), 'side') ? 'bg-danger-700' : 'bg-gold-500' }}" aria-hidden="true"></span><span>{{ $it }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @endforeach
                </div>

                <section class="mt-10" aria-labelledby="spec-h">
                    <h2 id="spec-h" class="text-xl">Specifications</h2>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($g['specs'] as [$k, $v])
                            <div class="card p-3"><dt class="text-xs text-ink-600">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                <section class="mt-10" aria-labelledby="faq-h">
                    <h2 id="faq-h" class="text-xl">Questions</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($g['faqs'] as $f)
                            <details class="card group p-4"><summary class="cursor-pointer font-semibold">{{ $f['q'] }}</summary><p class="mt-2 text-ink-600">{{ $f['a'] }}</p></details>
                        @endforeach
                    </div>
                </section>

                <p class="mt-8 rounded-2xl border border-gold-200 bg-gold-50 p-4 text-sm"><strong>Please note:</strong> Gemstones, rudraksha and yantras are traditional remedies and do not guarantee any result or replace medical, legal or financial advice. Wear a gemstone only after a chart consultation. Natural items vary in colour and inclusions.</p>
            </article>
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card p-5 sm:p-6">
                    <p class="text-2xl font-semibold">{{ \App\Support\Money::format((int) $p->price_minor) }}</p>
                    <p class="text-sm text-ink-600">Includes insured shipping within India.</p>
                    @if (! $p->stock)
                        <p class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-danger-700">Out of stock.</p>
                    @elseif (! $payable)
                        <p class="mt-4 rounded-xl bg-ivory-100 p-3 text-sm">Online ordering opens once payments are switched on. Call {{ $site('contact.phone') }} to order.</p>
                    @elseif (! auth()->check() || auth()->user()->isStaff())
                        <a href="{{ route('account.sign-in') }}" class="btn-primary mt-4 w-full">Sign in to order</a>
                    @else
                        <form method="POST" action="{{ route('store.buy') }}" class="mt-4 space-y-3" novalidate>
                            @csrf
                            <input type="hidden" name="product" value="{{ $p->ref }}"><input type="hidden" name="key" value="{{ \App\Http\Controllers\Web\PaymentController::newKey() }}">
                            @error('product')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                            <x-field name="quantity" label="Quantity" type="number" :value="1" min="1" :max="min($p->stock, $p->max_per_order)" required />
                            <fieldset class="space-y-3 rounded-2xl border border-ink-200 p-4"><legend class="px-1 text-sm font-semibold">Delivery address (India)</legend>
                                <x-field name="ship_name" label="Full name" :value="auth()->user()->name" autocomplete="name" required />
                                <x-field name="ship_phone" label="Mobile" type="tel" :value="auth()->user()->phone_e164" autocomplete="tel" required />
                                <x-field name="ship_line1" label="House / street" autocomplete="address-line1" required />
                                <x-field name="ship_line2" label="Area / landmark (optional)" autocomplete="address-line2" />
                                <div class="grid grid-cols-2 gap-3"><x-field name="ship_city" label="City" autocomplete="address-level2" required /><x-field name="ship_pincode" label="PIN code" inputmode="numeric" autocomplete="postal-code" required /></div>
                                <x-field name="ship_state" label="State" autocomplete="address-level1" required />
                            </fieldset>
                            <x-field name="coupon" label="Coupon code (optional)" :value="old('coupon')" autocomplete="off" maxlength="32" class="input uppercase" />
                            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="agree" value="1" class="mt-1 size-4 accent-ink-900" @checked(old('agree'))> <span>I have read the note about gemstones and the <a href="{{ route('terms') }}">terms</a>.</span></label>
                            @error('agree')<p class="field-error">{{ $message }}</p>@enderror
                            <button class="btn-primary w-full">Continue to payment</button>
                        </form>
                    @endif
                    <ul class="mt-5 space-y-2 border-t border-ink-100 pt-4 text-sm text-ink-600">
                        <li>✓ Energised before dispatch</li><li>✓ Tracked delivery in 3–7 days</li><li>✓ Secure payment by Razorpay</li>
                    </ul>
                    @if ($p->category === 'gemstone')
                        <a href="{{ route('services.show', 'gemstone-guidance') }}" class="btn-ghost mt-4 w-full">Not sure? Get gemstone guidance</a>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
