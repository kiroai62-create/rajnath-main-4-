@php use App\Support\SiteContent; $order = array_flip($web->order()); @endphp
<x-layouts.admin title="Website & home page">
    <form method="POST" action="{{ route('admin.website.update') }}" class="grid max-w-6xl gap-6 xl:grid-cols-2">
        @csrf @method('PUT')
        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="ann-h">
            <div><h2 id="ann-h" class="text-lg">Announcement bar</h2><p class="text-sm text-ink-600">A strip at the very top of every page, e.g. a festival puja or an offer.</p></div>
            <label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="announce_on" value="1" class="size-4 accent-ink-900" @checked(old('announce_on', $web->get('announce_on')))> Show the announcement bar</label>
            <x-field name="announce_text" label="Text" :value="$web->get('announce_text')" maxlength="200" placeholder="Diwali Lakshmi puja bookings are open" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="announce_link" label="Link (optional)" :value="$web->get('announce_link')" placeholder="/services/maha-lakshmi-puja" />
                <x-field name="announce_cta" label="Button text (optional)" :value="$web->get('announce_cta')" placeholder="Book now" />
            </div>
        </section>
        <section class="card space-y-4 p-5 sm:p-6" aria-labelledby="util-h">
            <div><h2 id="util-h" class="text-lg">Header contact bar</h2><p class="text-sm text-ink-600">The dark strip above the menu with your phone, WhatsApp, Panchang and Free Kundli. Phone and WhatsApp come from <a href="{{ route('admin.settings.edit') }}">Settings</a>.</p></div>
            <label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="utility_on" value="1" class="size-4 accent-ink-900" @checked(old('utility_on', $web->utilityOn()))> Show the contact bar</label>
            <x-field name="utility_text" label="Line on the left" :value="$web->get('utility_text')" maxlength="160" :placeholder="$web->utilityText()" />
            <p class="text-sm text-ink-600">Home-page text, stats and photos: <a href="{{ route('admin.profile.edit') }}">About &amp; awards</a>. Menu items follow the <a href="{{ route('admin.catalogue.index') }}">Catalogue</a> (only services on sale are listed).</p>
        </section>
        <section class="card p-5 sm:p-6 xl:col-span-2" aria-labelledby="sec-h">
            <h2 id="sec-h" class="text-lg">Home page sections</h2>
            <p class="text-sm text-ink-600">Untick a section to hide it. Change the numbers to reorder (small first). The top banner with the Moon-sign form always shows.</p>
            <div class="table-wrap mt-4"><table class="table">
                <thead><tr><th scope="col">Show</th><th scope="col">Section</th><th scope="col">Order</th></tr></thead>
                <tbody>
                    @foreach (SiteContent::SECTIONS as $key => $label)
                        <tr>
                            <td><input type="checkbox" name="sections[]" value="{{ $key }}" class="size-4 accent-ink-900" aria-label="Show {{ $label }}" @checked($web->sectionOn($key))></td>
                            <td>{{ $label }}</td>
                            <td><input type="number" name="order[{{ $key }}]" value="{{ $order[$key] + 1 }}" min="1" max="99" class="input w-24" aria-label="Order of {{ $label }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
        <div class="xl:col-span-2"><button class="btn-primary">Save website settings</button> <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-ghost">Open the home page</a></div>
    </form>
</x-layouts.admin>
