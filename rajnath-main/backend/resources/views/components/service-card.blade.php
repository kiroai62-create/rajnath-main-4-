@props(['o'])
@php $t = $o->t(); $from = $o->fromPriceMinor(); @endphp
<article class="card card-hover group flex h-full flex-col overflow-hidden">
    @if ($o->image_path)
        <x-framed-photo :src="$o->image_path" ratio="4/3" class="w-full" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <p class="eyebrow">{{ $o->family->label() }}</p>
        <h3 class="mt-1.5 text-base"><a href="{{ route('services.show', $o->slug) }}" class="no-underline after:absolute after:inset-0 group-hover:text-maroon-700">{{ $t?->title }}</a></h3>
        <p class="mt-2 flex-1 text-sm text-ink-600">{{ $t?->summary }}</p>
        <p class="mt-4 flex items-center justify-between text-sm">
            <span class="font-semibold text-ink-900">{{ $from === null ? __('Price on request') : __('From :price', ['price' => \App\Support\Money::format($from)]) }}</span>
            <span aria-hidden="true" class="inline-flex items-center gap-1 font-semibold text-gold-700 transition group-hover:translate-x-1">{{ __('View') }}<svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L11.6 10 7.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/></svg></span>
        </p>
    </div>
</article>
