<x-layouts.admin :title="'PDF template: '.$name">
    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('admin.pdf-templates.update', $slug) }}" class="card max-w-3xl space-y-4 p-5 sm:p-6">
            @csrf @method('PUT')
            @if ($slug !== 'default')
                <p class="text-sm text-ink-600">{{ $own ? 'This calculator has its own template.' : 'This calculator uses the default template. Saving here gives it its own.' }}</p>
            @endif
            <label class="flex min-h-9 items-center gap-2 text-sm"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked($t['enabled']) class="size-4 accent-ink-900"> Offer "Download PDF" for {{ $slug === 'default' ? 'calculators' : 'this calculator' }}</label>
            <x-field name="title" label="PDF title (empty = calculator name)" :value="$t['title']" maxlength="120" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="f-accent" class="field-label">Colour of headings and lines</label><input id="f-accent" name="accent" type="color" value="{{ old('accent', $t['accent']) }}" class="input h-11 p-1">@error('accent')<p class="field-error">{{ $message }}</p>@enderror</div>
                <x-select name="paper" label="Paper size" :value="$t['paper']" :options="['A4' => 'A4', 'Letter' => 'US Letter']" />
            </div>
            @foreach (['intro' => 'Text under the title', 'closing' => 'Closing box (for example: book a consultation)', 'disclaimer' => 'Small print at the bottom'] as $k => $l)
                <div><label for="f-{{ $k }}" class="field-label">{{ $l }}</label><textarea id="f-{{ $k }}" name="{{ $k }}" rows="3" maxlength="1000" class="input">{{ old($k, $t[$k]) }}</textarea>@error($k)<p class="field-error">{{ $message }}</p>@enderror</div>
            @endforeach
            <x-field name="watermark" label="Faint watermark across the page (optional)" :value="$t['watermark']" maxlength="40" placeholder="Acharya Dr. Rajnath Jha" />
            <fieldset class="space-y-1">
                <legend class="field-label">Include</legend>
                @foreach (['show_birth' => 'Birth details section', 'show_charts' => 'Kundli charts (D1, D9, Lal Kitab)', 'show_contact' => 'Phone, email and address at the bottom'] as $k => $l)
                    <label class="flex min-h-9 items-center gap-2 text-sm"><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" name="{{ $k }}" value="1" @checked($t[$k]) class="size-4 accent-ink-900"> {{ $l }}</label>
                @endforeach
            </fieldset>
            @if ($sections)
                <fieldset class="space-y-1">
                    <legend class="field-label">Leave out these sections</legend>
                    @foreach ($sections as $sec)
                        <label class="flex min-h-9 items-center gap-2 text-sm"><input type="checkbox" name="hide_sections[]" value="{{ $sec }}" @checked(in_array($sec, (array) $t['hide_sections'], true)) class="size-4 accent-ink-900"> {{ $sec }}</label>
                    @endforeach
                </fieldset>
            @endif
            <div class="flex flex-wrap gap-3 border-t border-ink-200/60 pt-4">
                <button class="btn-primary">Save template</button>
                @if ($own)<button name="reset" value="1" class="btn-outline" formnovalidate>Use the default template again</button>@endif
            </div>
        </form>
        <aside class="card h-fit space-y-3 p-5 text-sm">
            <h2 class="text-base">See it</h2>
            <p class="text-ink-600">Save first, then open the sample. It uses made-up details.</p>
            <a href="{{ route('admin.pdf-templates.preview', $slug) }}" target="_blank" rel="noopener" class="btn-outline w-full">Preview in browser</a>
            <a href="{{ route('admin.pdf-templates.preview', [$slug, 'format' => 'pdf']) }}" class="btn-outline w-full">Download sample PDF</a>
            <a href="{{ route('admin.pdf-templates.index') }}" class="btn-ghost w-full">All templates</a>
        </aside>
    </div>
</x-layouts.admin>
