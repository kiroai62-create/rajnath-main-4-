@php $T = \App\Domain\Content\HoroscopeTypes::class; $src = ['ai' => 'AI', 'engine' => 'Built-in engine', 'staff' => 'Edited by staff']; @endphp
<x-layouts.admin title="Rashifal on the website">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-3xl text-sm text-ink-600">This is the text visitors see today. Change any sign's text and press Save: it goes live at once and AI will not replace it.
            The earlier text is kept in the <a href="{{ route('admin.audit.index') }}">audit log</a>.</p>
        <a href="{{ route('admin.ops.horoscopes') }}" class="btn-outline">All entries &amp; write new</a>
    </div>
    <p class="mt-3 flex flex-wrap gap-2 text-xs">Live today:
        @foreach ($counts as $s => $n)<span class="badge-ink">{{ $src[$s] ?? $s }}: {{ $n }}</span>@endforeach
    </p>

    <form method="GET" class="mt-5 flex flex-wrap items-end gap-3">
        <x-select name="kind" label="Type" :value="$kind" :options="collect($T::PUBLIC_KINDS)->mapWithKeys(fn ($k) => [$k => $T::KINDS[$k][1]])->all()" />
        <x-select name="period" label="Period" :value="$period" :options="collect($T::PERIODS)->map(fn ($p) => $p[1])->all()" />
        @if ($kind === 'rashi')<x-select name="topic" label="Topic" :value="$topic" :options="collect($T::TOPICS)->map(fn ($t) => $t[1])->all()" />@endif
        <x-select name="locale" label="Language" :value="$locale" :options="['hi' => 'हिन्दी', 'en' => 'English']" />
        <button class="btn-outline min-h-10">Show</button>
    </form>

    <div class="mt-6 grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        @foreach ($names as $i => $name)
            @php $h = ($rows[$i] ?? collect())->first(); @endphp
            <section class="card flex flex-col p-5" aria-labelledby="s-{{ $i }}">
                <div class="flex items-start justify-between gap-2">
                    <h2 id="s-{{ $i }}" class="text-base">{{ $name }} <span class="text-sm font-normal text-ink-600">{{ \Illuminate\Support\Str::before($namesEn[$i] ?? '', ' (') }}</span></h2>
                    @if ($h)<span @class(['badge-success' => $h->status === 'published', 'badge-gold' => $h->status !== 'published'])>{{ $h->status === 'published' ? ($src[$h->source] ?? $h->source) : 'Draft (not shown)' }}</span>@endif
                </div>
                @if (! $h)
                    <p class="mt-3 text-sm text-ink-600">Nothing for this period yet. It is written automatically within the hour, or <a href="{{ route('admin.ops.horoscopes') }}">write it now</a>.</p>
                @else
                    <p class="mt-1 text-xs text-ink-600">{{ \Illuminate\Support\Carbon::parse($h->starts_on)->format('d M') }} – {{ \Illuminate\Support\Carbon::parse($h->ends_on)->format('d M Y') }} · v{{ $h->version }} · updated {{ \Illuminate\Support\Carbon::parse($h->updated_at, 'UTC')->tz('Asia/Kolkata')->format('d M, g:i A') }}</p>
                    @can('content.publish')
                        <form method="POST" action="{{ route('admin.rashifal.update', $h->id) }}" class="mt-3 flex flex-1 flex-col gap-2">
                            @csrf @method('PUT')
                            <input type="hidden" name="version" value="{{ $h->version }}">
                            <label for="b-{{ $h->id }}" class="sr-only">Text for {{ $name }}</label>
                            <textarea id="b-{{ $h->id }}" name="body" rows="7" minlength="40" maxlength="5000" class="input flex-1 text-sm" lang="{{ $h->locale }}">{{ $h->body }}</textarea>
                            <button class="btn-primary self-start">Save</button>
                        </form>
                    @else
                        <p class="mt-3 whitespace-pre-line text-sm" lang="{{ $h->locale }}">{{ $h->body }}</p>
                    @endcan
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.admin>
