<x-layouts.admin title="Languages">
    <p class="max-w-3xl text-sm text-ink-600">
        Visitors pick a language from the globe button at the top of every page; English is the default. Text that is not translated yet is
        translated by AI with the key saved in <a href="{{ route('admin.assistant.edit') }}">AI assistant</a>, then kept here so you can correct any line.
        A line you edit yourself is never changed by AI.
    </p>

    @unless ($aiReady)
        <div role="alert" class="card mt-5 border-gold-200 bg-gold-50 px-4 py-3 text-sm">
            <strong>No AI key yet.</strong> Pages are shown in the chosen language only where a translation already exists.
            <a href="{{ route('admin.assistant.edit') }}">Add a Groq, Gemini, Cloudflare or OpenAI key</a> and switch that provider on to translate the rest automatically.
        </div>
    @endunless
    @if ($lastError)
        <p class="mt-3 text-xs text-ink-600">Last AI error: {{ \Illuminate\Support\Str::limit($lastError, 200) }}</p>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_24rem]">
        <section class="card min-w-0 p-5">
            <h2 class="text-lg">Progress</h2>
            <div class="table-wrap mt-3" role="region" aria-label="Translation progress" tabindex="0">
                <table class="table">
                    <thead><tr><th scope="col">Language</th><th scope="col">Shown to visitors</th><th scope="col">Translated</th><th scope="col">Waiting</th><th scope="col">Failed</th><th scope="col">Edited by staff</th>@can('content.publish')<th scope="col"><span class="sr-only">Actions</span></th>@endcan</tr></thead>
                    <tbody>
                    @foreach ($locales as $code => $name)
                        @php $s = $stats[$code] ?? ['done' => 0, 'pending' => 0, 'failed' => 0, 'manual' => 0]; @endphp
                        <tr>
                            <td><a href="{{ route('admin.languages.index', ['locale' => $code]) }}" class="font-medium">{{ $name }}</a> <span class="text-xs text-ink-600">{{ config('locales.english_names')[$code] ?? $code }}</span></td>
                            <td>@if (in_array($code, $enabled, true))<span class="badge-success">On</span>@else<span class="badge-ink">Off</span>@endif</td>
                            <td>{{ number_format($s['done']) }}</td><td>{{ number_format($s['pending']) }}</td><td>{{ number_format($s['failed']) }}</td><td>{{ number_format($s['manual']) }}</td>
                            @can('content.publish')
                            <td class="whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.languages.translate') }}" class="inline">@csrf
                                    <input type="hidden" name="locale" value="{{ $code }}"><input type="hidden" name="retry" value="1">
                                    <button class="btn-outline min-h-9 px-3 text-xs" @disabled(! $aiReady)>Translate now</button>
                                </form>
                                <a href="{{ url('/?lang='.$code) }}" target="_blank" rel="noopener" class="btn-ghost min-h-9 px-3 text-xs">Preview</a>
                            </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @can('content.publish')
            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.languages.translate') }}">@csrf<input type="hidden" name="locale" value="all"><input type="hidden" name="retry" value="1"><button class="btn-primary" @disabled(! $aiReady)>Translate all waiting text</button></form>
                <form method="POST" action="{{ route('admin.languages.scan') }}">@csrf<button class="btn-outline" @disabled(! $aiReady || $sync)>Translate the whole website now</button></form>
            </div>
            <p class="mt-2 text-xs text-ink-600">"Translate the whole website now" opens every page in every language once, so visitors never see English first. Without it, each page is translated the first time someone opens it in that language.</p>
            @endcan
        </section>

        @can('settings.manage')
        <form method="POST" action="{{ route('admin.languages.update') }}" class="card h-fit space-y-4 p-5">
            @csrf @method('PUT')
            <h2 class="text-lg">Settings</h2>
            <fieldset>
                <legend class="field-label">Languages visitors can choose</legend>
                <label class="flex min-h-9 items-center gap-2 text-sm"><input type="checkbox" checked disabled class="size-4"> English (always on, default)</label>
                @foreach ($locales as $code => $name)
                    <label class="flex min-h-9 items-center gap-2 text-sm"><input type="checkbox" name="locales[]" value="{{ $code }}" @checked(in_array($code, $enabled, true)) class="size-4 accent-ink-900"> {{ $name }} <span class="text-ink-600">({{ config('locales.english_names')[$code] ?? $code }})</span></label>
                @endforeach
            </fieldset>
            <label class="flex min-h-10 items-center gap-2 text-sm"><input type="hidden" name="auto" value="0"><input type="checkbox" name="auto" value="1" @checked($auto) class="size-4 accent-ink-900"> Translate new text automatically with AI</label>
            <button class="btn-primary w-full">Save</button>
        </form>
        @endcan
    </div>

    <section class="mt-8" aria-labelledby="lines-h">
        <h2 id="lines-h" class="text-lg">Edit translations</h2>
        <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
            <x-select name="locale" label="Language" :value="$locale" :options="$locales" />
            <x-select name="status" label="Show" :value="$status" :options="['all' => 'All', 'done' => 'Translated', 'pending' => 'Waiting', 'failed' => 'Failed', 'manual' => 'Edited by staff']" />
            <x-field name="q" label="Search English or translation" :value="$q" />
            <button class="btn-outline">Show</button>
        </form>
        <div class="table-wrap mt-4" role="region" aria-label="Translation lines" tabindex="0">
            <table class="table">
                <thead><tr><th scope="col" class="w-2/5">English</th><th scope="col">{{ $locales[$locale] }}</th></tr></thead>
                <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td class="align-top text-sm">{{ $line->source }}<br><span class="text-xs text-ink-600">{{ ['done' => 'Translated', 'pending' => 'Waiting', 'failed' => 'Failed'][$line->status] ?? $line->status }}{{ $line->origin === 'manual' ? ' · edited by staff' : ($line->origin === 'ai' ? ' · AI' : '') }}</span></td>
                        <td>
                            @can('content.publish')
                            <form method="POST" action="{{ route('admin.languages.line', $line->id) }}" class="flex flex-col gap-2 sm:flex-row sm:items-start">
                                @csrf @method('PUT')
                                <label for="line-{{ $line->id }}" class="sr-only">Translation</label>
                                <textarea id="line-{{ $line->id }}" name="text" rows="2" class="input text-sm" lang="{{ $locale }}" @if (\App\Http\Middleware\SetLocale::rtl($locale)) dir="rtl" @endif>{{ $line->text }}</textarea>
                                <button class="btn-outline min-h-9 px-3 text-xs">Save</button>
                            </form>
                            @else
                                <span lang="{{ $locale }}">{{ $line->text ?? '—' }}</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-8 text-center text-ink-600">No lines yet. Open the website in this language, or press "Translate the whole website now".</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $lines->links() }}</div>
        <p class="mt-2 text-xs text-ink-600">Empty a box and save to let AI translate that line again. Short labels such as menu items also come from the reviewed files in lang/; those are not listed here.</p>
    </section>
</x-layouts.admin>
