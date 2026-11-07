@php
    use App\Domain\Store\ProductGuide;
    $form = $p ? ProductGuide::toForm(ProductGuide::for($p)) : ['highlights' => '', 'sections' => [], 'faqs' => ''];
    $sections = old('sections', $form['sections']);
    $target = min(12, max(6, count($sections) + 2));
    while (count($sections) < $target) { $sections[] = ['title' => '', 'kind' => 'list', 'items' => '']; }
    $customised = $p && ProductGuide::saved($p) !== [];
@endphp
<x-layouts.admin :title="$p ? 'Edit '.$p->name : 'New product'">
    @if ($p)
        <form method="POST" action="{{ route('admin.store.ai', $p->ref) }}" class="card mb-6 max-w-3xl space-y-3 border-gold-200 bg-gold-50 p-5 sm:p-6" aria-labelledby="ai-h">
            @csrf
            <h2 id="ai-h" class="text-lg">✨ Enhance with AI</h2>
            <p class="text-sm text-ink-600">AI rewrites the summary, description, highlights, benefits, how to wear, cautions, care and FAQs from this product's facts. It fills the form below as a draft; nothing goes live until you press <strong>Save product</strong>.</p>
            <div><label for="f-instructions" class="field-label">What should the AI focus on? (optional)</label>
                <textarea id="f-instructions" name="instructions" rows="2" maxlength="600" class="input" placeholder="e.g. Add a Hindi line in each section, mention it suits students, keep it short">{{ old('instructions') }}</textarea></div>
            @error('ai')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <button class="btn-gold">Write a draft with AI</button>
        </form>
    @endif
    <form method="POST" action="{{ $p ? route('admin.store.update', $p->ref) : route('admin.store.create') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-5 sm:p-6">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-field name="name" label="Name" :value="$p?->name" required placeholder="Yellow Sapphire (Pukhraj), Ceylon" /></div>
            <x-select name="category" label="Category" :value="$p?->category ?? 'gemstone'" :options="\App\Domain\Store\Store::CATEGORIES" />
            <x-field name="slug" label="Web address" :value="$p?->slug" hint="Leave empty to create from the name." />
            <div class="sm:col-span-2"><x-field name="summary" label="Short summary" :value="$p?->summary" required /></div>
            <x-field name="weight" label="Weight" :value="$p?->weight" placeholder="5.25 carat (5.8 ratti)" />
            <x-field name="origin" label="Origin" :value="$p?->origin" placeholder="Sri Lanka" />
            <div class="sm:col-span-2"><x-field name="certification" label="Certificate (lab and number, as on the certificate)" :value="$p?->certification" /></div>
            <x-field name="price" label="Price (₹, incl. shipping)" :value="$p ? $p->price_minor / 100 : null" inputmode="decimal" required hint="Changing price needs price permission." />
            <x-field name="stock" label="Stock" type="number" min="0" :value="$p?->stock ?? 1" required />
            <x-field name="max_per_order" label="Max per order" type="number" min="1" max="10" :value="$p?->max_per_order ?? 1" required />
            <x-field name="sort" label="Order" type="number" :value="$p?->sort ?? 0" />
            <x-field name="ship_weight_g" label="Packed parcel weight (g)" type="number" min="10" :value="$p?->ship_weight_g ?? 200" hint="Used to book the courier." />
            <x-field name="ship_length_cm" label="Parcel length (cm)" type="number" min="1" :value="$p?->ship_length_cm ?? 12" />
            <x-field name="ship_breadth_cm" label="Parcel breadth (cm)" type="number" min="1" :value="$p?->ship_breadth_cm ?? 10" />
            <x-field name="ship_height_cm" label="Parcel height (cm)" type="number" min="1" :value="$p?->ship_height_cm ?? 6" />
        </div>
        <div><label for="f-description" class="field-label">Description</label><textarea id="f-description" name="description" rows="5" class="input">{{ old('description', $p?->description) }}</textarea></div>
        <div><label for="f-image" class="field-label">Photo (JPG/PNG/WebP, re-encoded on upload)</label><input id="f-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="input">
            @error('image')<p class="field-error">{{ $message }}</p>@enderror
            @if ($p?->image_path)<img src="/{{ $p->image_path }}" alt="" class="mt-3 h-32 rounded-xl object-cover">@endif</div>
        <fieldset class="space-y-4 rounded-2xl border border-ink-200 p-4 sm:p-5">
            <legend class="px-1 font-semibold">Product page details</legend>
            <p class="text-sm text-ink-600">{{ $customised ? 'This product uses your own text.' : 'Showing the built-in text. Change anything and save to make it your own.' }} One point per line. In a "Table" section write each row as <code>Label: value</code>. Empty sections are left out.</p>
            <div><label for="f-highlights" class="field-label">Highlights (shown next to the price)</label><textarea id="f-highlights" name="highlights" rows="4" class="input">{{ old('highlights', $form['highlights']) }}</textarea></div>
            @foreach ($sections as $i => $sec)
                <div class="grid gap-3 rounded-xl bg-ivory-50 p-3 sm:grid-cols-[1fr_9rem]">
                    <x-field :name="'sections['.$i.'][title]'" :id="'f-sec-'.$i" :label="'Section '.($i + 1).' title'" :value="$sec['title'] ?? ''" placeholder="Benefits" />
                    <x-select :name="'sections['.$i.'][kind]'" :id="'f-kind-'.$i" label="Shown as" :options="['list' => 'Points', 'table' => 'Table']" :value="$sec['kind'] ?? 'list'" />
                    <div class="sm:col-span-2"><label for="f-items-{{ $i }}" class="field-label">Lines</label><textarea id="f-items-{{ $i }}" name="sections[{{ $i }}][items]" rows="4" class="input">{{ $sec['items'] ?? '' }}</textarea></div>
                </div>
            @endforeach
            <div><label for="f-faqs" class="field-label">FAQs (Q: question, next line A: answer, blank line between)</label><textarea id="f-faqs" name="faqs" rows="8" class="input">{{ old('faqs', $form['faqs']) }}</textarea></div>
            @if ($customised)<label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="details_reset" value="1" class="size-4 accent-ink-900"> Go back to the built-in text</label>@endif
        </fieldset>
        <label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" name="active" value="1" class="size-4 accent-ink-900" @checked(old('active', $p?->active))> On sale</label>
        <button class="btn-primary">Save product</button>
    </form>
</x-layouts.admin>
