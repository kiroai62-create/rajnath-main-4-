@php use App\Support\AppConfig; $order = array_flip($app->order()); @endphp
<x-layouts.admin title="Mobile app">
    <p class="max-w-3xl text-sm text-ink-600">Control the Android app without publishing a new version. Phones read these settings every time the app opens. Push messages are sent from <a href="{{ route('admin.push.edit') }}">Push notifications</a>; the hero text, stats and photo come from <a href="{{ route('admin.profile.edit') }}">About &amp; awards</a>.</p>
    <form method="POST" action="{{ route('admin.app.update') }}" class="mt-6 grid max-w-6xl gap-6 xl:grid-cols-2">
        @csrf @method('PUT')

        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="ann-h">
            <div><h2 id="ann-h" class="text-lg">Announcement banner</h2><p class="text-sm text-ink-600">A banner at the top of the app home screen, e.g. a festival puja or an offer.</p></div>
            <label class="flex min-h-11 items-center gap-3 text-sm"><input type="hidden" name="announce_on" value="0"><input type="checkbox" name="announce_on" value="1" class="size-4 accent-ink-900" @checked(old('announce_on', $app->get('announce_on')))> Show the banner in the app</label>
            <x-field name="announce_text" label="Text" :value="$app->get('announce_text')" maxlength="200" placeholder="Diwali Lakshmi puja bookings are open" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="announce_cta" label="Button text (optional)" :value="$app->get('announce_cta')" placeholder="Book now" />
                <x-select name="announce_style" label="Colour" :options="['gold' => 'Gold', 'cosmic' => 'Night sky (dark blue)', 'maroon' => 'Maroon']" :value="$app->get('announce_style', 'gold')" />
            </div>
            <x-field name="announce_link" label="Opens (optional)" :value="$app->get('announce_link')" placeholder="app:puja" hint="app:store, app:puja, app:chadhava, app:kundli, app:panchang, app:rashifal, app:assistant, app:service/<slug>, app:product/<slug> — or a website link." />
        </section>

        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="feat-h">
            <div><h2 id="feat-h" class="text-lg">Features in the app</h2><p class="text-sm text-ink-600">Untick to hide a feature from the app (buttons, tiles and menu entries). The website is not affected.</p></div>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach (AppConfig::FEATURES as $key => $label)
                    <li><label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="features[]" value="{{ $key }}" class="size-4 accent-ink-900" @checked($app->featureOn($key))> {{ $label }}</label></li>
                @endforeach
            </ul>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="support_whatsapp" label="WhatsApp for app users (optional)" :value="$app->get('support_whatsapp')" placeholder="+91 98350 00000" hint="Blank = the number in Settings." />
                <x-field name="support_phone" label="Phone for app users (optional)" :value="$app->get('support_phone')" placeholder="+91 98350 00000" />
            </div>
        </section>

        <section class="card p-5 sm:p-6 xl:col-span-2" aria-labelledby="sec-h">
            <h2 id="sec-h" class="text-lg">App home screen sections</h2>
            <p class="text-sm text-ink-600">Untick a section to hide it. Change the numbers to reorder (small first).</p>
            <div class="table-wrap mt-4"><table class="table">
                <thead><tr><th scope="col">Show</th><th scope="col">Section</th><th scope="col">Order</th></tr></thead>
                <tbody>
                    @foreach (AppConfig::SECTIONS as $key => $label)
                        <tr>
                            <td><input type="checkbox" name="sections[]" value="{{ $key }}" class="size-4 accent-ink-900" aria-label="Show {{ $label }}" @checked($app->sectionOn($key))></td>
                            <td>{{ $label }}</td>
                            <td><input type="number" name="order[{{ $key }}]" value="{{ $order[$key] + 1 }}" min="1" max="99" class="input w-24" aria-label="Order of {{ $label }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>

        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="upd-h">
            <div><h2 id="upd-h" class="text-lg">App version and updates</h2><p class="text-sm text-ink-600">Phones older than the <strong>minimum</strong> must update before they can use the app. Phones older than the <strong>latest</strong> see a gentle "update available" note.</p></div>
            @unless ($canLock)<p class="badge-ink">Only staff who can change Settings may edit this part.</p>@endunless
            <fieldset @disabled(! $canLock) class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="min_version" label="Minimum version" :value="$app->get('min_version')" :placeholder="$app->minVersion()" />
                    <x-field name="latest_version" label="Latest version" :value="$app->get('latest_version')" placeholder="1.11.0" />
                </div>
                <x-field name="update_url" label="Update link" :value="$app->get('update_url')" placeholder="https://play.google.com/store/apps/details?id=com.astrorajnathjha.app" />
                <x-field name="update_text" label="Update message" :value="$app->get('update_text')" maxlength="200" />
            </fieldset>
        </section>

        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="mnt-h">
            <div><h2 id="mnt-h" class="text-lg">Maintenance</h2><p class="text-sm text-ink-600">Shows a "we'll be back soon" screen in the app instead of the normal screens, with your phone and WhatsApp. Use it only during server work.</p></div>
            <fieldset @disabled(! $canLock) class="space-y-4">
                <label class="flex min-h-11 items-center gap-3 text-sm"><input type="hidden" name="maintenance_on" value="0"><input type="checkbox" name="maintenance_on" value="1" class="size-4 accent-danger-700" @checked(old('maintenance_on', $app->get('maintenance_on')))> Show the maintenance screen in the app</label>
                <x-field name="maintenance_text" label="Message" :value="$app->get('maintenance_text')" maxlength="300" placeholder="The app is being updated. Please try again in a little while." />
            </fieldset>
        </section>

        <div class="xl:col-span-2"><button class="btn-primary">Save app settings</button></div>
    </form>
</x-layouts.admin>
