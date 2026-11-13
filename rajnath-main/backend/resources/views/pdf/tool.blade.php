{{-- Free-calculator PDF, laid out by Admin › PDF templates. Self-contained: no external assets. --}}
@php
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $t['accent']) ? $t['accent'] : '#b8891d';
    $c = $r['chart'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { size: {{ $t['paper'] === 'Letter' ? 'Letter' : 'A4' }}; margin: 16mm 14mm 18mm; }
    * { box-sizing: border-box; }
    body { font-family: "Noto Sans", "Noto Sans Devanagari", "DejaVu Sans", Arial, sans-serif; color: #141a3c; font-size: 11pt; line-height: 1.55; margin: 0; }
    h1 { font-size: 20pt; margin: 0 0 4pt; color: {{ $accent }}; } h2 { font-size: 13pt; margin: 16pt 0 6pt; padding-bottom: 3pt; border-bottom: 1px solid {{ $accent }}55; }
    p { margin: 0 0 7pt; } .muted { color: #555c7a; font-size: 9.5pt; }
    .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid {{ $accent }}; padding-bottom: 8pt; margin-bottom: 12pt; }
    .logo svg { width: 180pt; height: auto; display: block; margin-bottom: 2pt; } .right { text-align: right; }
    table { width: 100%; border-collapse: collapse; margin: 6pt 0; } th, td { border: 1px solid #c9cde0; padding: 4pt 6pt; text-align: left; vertical-align: top; font-size: 9.5pt; }
    th { background: {{ $accent }}1f; }
    .highlight { font-size: 16pt; font-weight: 700; color: {{ $accent }}; margin: 4pt 0 6pt; }
    .charts { display: flex; gap: 18pt; justify-content: center; flex-wrap: wrap; } .charts figure { width: 220pt; margin: 0; text-align: center; } .charts svg { width: 220pt; height: auto; } .charts figcaption { font-size: 9pt; color: #555c7a; }
    section { break-inside: avoid; } .note { background: #f7f4ea; border: 1px solid #e6d9b3; padding: 7pt 9pt; font-size: 9pt; margin-top: 14pt; }
    .closing { margin-top: 16pt; padding: 9pt 11pt; border-left: 3pt solid {{ $accent }}; background: {{ $accent }}12; }
    .foot { margin-top: 18pt; font-size: 8.5pt; color: #555c7a; border-top: 1px solid #e3e5ef; padding-top: 6pt; }
    .wm { position: fixed; top: 42%; left: 0; right: 0; text-align: center; font-size: 54pt; font-weight: 700; color: {{ $accent }}; opacity: .07; transform: rotate(-28deg); z-index: -1; }
</style>
</head>
<body>
    @if (trim((string) $t['watermark']) !== '')<div class="wm">{{ $t['watermark'] }}</div>@endif
    <div class="head">
        <div><div class="logo" role="img" aria-label="{{ $brand }}">{!! file_get_contents(public_path('images/brand/logo-full.svg')) !!}</div><div class="muted">{{ $tagline }}</div></div>
        <div class="right muted">{{ $made }} IST @if ($reference)<br>Ref. {{ substr($reference, -8) }}@endif</div>
    </div>
    <h1>{{ $title }}</h1>
    @if (trim((string) $t['intro']) !== '')<p class="muted">{{ $t['intro'] }}</p>@endif

    @if ($t['show_charts'] && $c && in_array($r['tool'], ['kundli', 'lagna-navamsa'], true))
        <section class="charts">
            @include('public.tools._chart', ['houses' => $c['houses'], 'label' => 'Rashi chart (D1)'])
            @include('public.tools._chart', ['houses' => $c['navamsa'], 'label' => 'Navamsa chart (D9)'])
        </section>
    @endif
    @if ($t['show_charts'] && $r['tool'] === 'lal-kitab')
        <section class="charts">@include('public.tools._chart', ['houses' => collect($r['lal_kitab'])->map(fn ($pl, $i) => ['sign' => $i - 1, 'planets' => $pl])->all(), 'label' => 'Lal Kitab chart'])</section>
    @endif

    @foreach ($display['sections'] as $s)
        <section>
            @if (! empty($s['title']))<h2>{{ $s['title'] }}</h2>@endif
            @if (! empty($s['highlight']))<p class="highlight">{{ $s['highlight'] }}</p>@endif
            @if (! empty($s['text']))<p>{{ $s['text'] }}</p>@endif
            @if (! empty($s['rows']))
                <table><tbody>@foreach ($s['rows'] as [$k, $v])<tr><th style="width: 38%">{{ $k }}</th><td>{{ $v }}</td></tr>@endforeach</tbody></table>
            @endif
            @if (! empty($s['items']))<ul>@foreach ($s['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>@endif
            @if (! empty($s['table']))
                <table><thead><tr>@foreach ($s['table']['head'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>@foreach ($s['table']['rows'] as $row)<tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table>
            @endif
        </section>
    @endforeach
    @if (! empty($display['note']))<p class="note">{{ $display['note'] }}</p>@endif

    @if (trim((string) $t['closing']) !== '')<p class="closing">{{ $t['closing'] }}</p>@endif
    <div class="foot">
        @if (trim((string) $t['disclaimer']) !== ''){{ $t['disclaimer'] }}<br>@endif
        @if ($t['show_contact'] && $contact){{ $brand }} · {{ implode(' · ', $contact) }}@endif
    </div>
</body>
</html>
