{{-- Decorative zodiac wheel: 12 rashis around a sun mandala (SVG). --}}
@php
    $signs = ['मेष', 'वृष', 'मिथुन', 'कर्क', 'सिंह', 'कन्या', 'तुला', 'वृश्चिक', 'धनु', 'मकर', 'कुंभ', 'मीन'];
    $pt = fn (float $r, float $deg) => [round(200 + $r * cos(deg2rad($deg - 90)), 2), round(200 + $r * sin(deg2rad($deg - 90)), 2)];
@endphp
<svg aria-hidden="true" viewBox="0 0 400 400" {{ $attributes->merge(['class' => '']) }}>
    <defs>
        <linearGradient id="zwGold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFE9A3"/><stop offset=".5" stop-color="#F2B536"/><stop offset="1" stop-color="#D9822B"/></linearGradient>
        <radialGradient id="zwCore" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#F7DD96" stop-opacity=".55"/><stop offset="1" stop-color="#F7DD96" stop-opacity="0"/></radialGradient>
    </defs>
    <circle cx="200" cy="200" r="196" fill="none" stroke="url(#zwGold)" stroke-width="1.5" opacity=".8"/>
    <circle cx="200" cy="200" r="186" fill="none" stroke="url(#zwGold)" stroke-width=".8" stroke-dasharray="2 5" opacity=".7"/>
    <circle cx="200" cy="200" r="150" fill="none" stroke="url(#zwGold)" stroke-width="1.2" opacity=".75"/>
    @for ($i = 0; $i < 12; $i++)
        @php [$x1, $y1] = $pt(150, $i * 30); [$x2, $y2] = $pt(196, $i * 30); [$tx, $ty] = $pt(169, $i * 30 + 15); @endphp
        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="url(#zwGold)" stroke-width="1" opacity=".7"/>
        <text x="{{ $tx }}" y="{{ $ty }}" fill="#F7DD96" font-size="15" font-family="'Noto Sans Devanagari Variable', sans-serif" text-anchor="middle" dominant-baseline="middle" transform="rotate({{ $i * 30 + 15 }} {{ $tx }} {{ $ty }})">{{ $signs[$i] }}</text>
    @endfor
    @for ($i = 0; $i < 72; $i++)
        @php [$x1, $y1] = $pt(118, $i * 5); [$x2, $y2] = $pt($i % 2 ? 138 : 146, $i * 5); @endphp
        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="url(#zwGold)" stroke-width="1" opacity=".55"/>
    @endfor
    <circle cx="200" cy="200" r="112" fill="none" stroke="url(#zwGold)" stroke-width="1" opacity=".6"/>
    <circle cx="200" cy="200" r="100" fill="url(#zwCore)"/>
    @for ($i = 0; $i < 8; $i++)
        @php [$x, $y] = $pt(84, $i * 45); @endphp
        <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $i % 2 ? 2 : 3.2 }}" fill="#F7DD96" opacity=".85"/>
    @endfor
</svg>
