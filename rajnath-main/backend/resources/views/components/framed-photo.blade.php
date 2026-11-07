@props(['src', 'alt' => '', 'ratio' => '4/3', 'eager' => false, 'focus' => 'center 30%', 'thumb' => null])
@php
    // Shows a photo in a fixed-ratio box without cutting faces or heads: when the photo's own shape
    // is close to the box it fills it (focused slightly above centre, where faces usually are);
    // otherwise the whole photo is shown on a soft blurred copy of itself.
    $path = public_path($src);
    [$w, $h] = is_file($path) ? (getimagesize($path) ?: [0, 0]) : [0, 0];
    [$rw, $rh] = array_map('floatval', explode('/', $ratio)) + [1 => 1];
    $box = $rh > 0 ? $rw / $rh : 1.333;
    $own = $h > 0 ? $w / $h : $box;
    $fill = abs($own - $box) / $box <= 0.12;
    $bg = $thumb ?: (is_file(public_path(preg_replace('/(\.\w+)$/', '-thumb$1', $src))) ? preg_replace('/(\.\w+)$/', '-thumb$1', $src) : $src);
@endphp
<div {{ $attributes->merge(['class' => 'photo-frame']) }} style="aspect-ratio: {{ $rw }} / {{ $rh }}">
    @if ($fill)
        <x-photo :src="$src" :alt="$alt" :eager="$eager" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]" style="object-position: {{ $focus }}" />
    @else
        <img src="/{{ $bg }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="photo-frame-bg">
        <x-photo :src="$src" :alt="$alt" :eager="$eager" class="relative h-full w-full object-contain drop-shadow-[0_10px_24px_rgb(0_0_0/0.35)] transition duration-700 group-hover:scale-[1.03]" />
    @endif
</div>
