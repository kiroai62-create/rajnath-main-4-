@props(['name'])
{{-- Outline icon from App\Support\AdminMenu::ICONS (decorative: always aria-hidden). --}}
<svg aria-hidden="true" {{ $attributes->merge(['class' => 'size-[1.15rem] shrink-0']) }} fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ \App\Support\AdminMenu::ICONS[$name] ?? \App\Support\AdminMenu::ICONS['grid'] }}"/></svg>
