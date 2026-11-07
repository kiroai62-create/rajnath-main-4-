{{-- Lightbox --}}
<div x-show="isOpen" x-cloak class="fixed inset-0 z-[70] flex flex-col bg-ink-950/95 text-white" role="dialog" aria-modal="true" aria-label="{{ __('Photo viewer') }}"
     @touchstart="touchStart" @touchend="touchEnd">
    <div class="flex items-center justify-between gap-3 p-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <p class="text-sm tabular-nums" x-text="counter"></p>
        <button type="button" x-ref="close" class="btn-ghost text-white" @click="close" aria-label="{{ __('Close') }}">
            <svg aria-hidden="true" class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="relative flex min-h-0 flex-1 items-center justify-center px-2">
        <button type="button" class="btn-ghost absolute left-1 z-10 text-white" @click="prev" aria-label="{{ __('Previous') }}"><svg aria-hidden="true" class="size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 6l-6 6 6 6"/></svg></button>
        <template x-if="isPhoto"><img :src="src" :alt="caption" class="max-h-full max-w-full object-contain"></template>
        <div x-show="isVideo" class="aspect-video w-full max-w-4xl">
            <button type="button" x-show="showPlay" class="relative block h-full w-full" @click="play" aria-label="{{ __('Play video') }}">
                <template x-if="isVideo"><img :src="src" alt="" class="h-full w-full object-cover"></template>
                <span class="absolute inset-0 grid place-items-center"><span class="grid size-16 place-items-center rounded-full bg-white/90 text-ink-900"><svg aria-hidden="true" class="ml-1 size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></span>
            </button>
            <template x-if="showFrame"><iframe :src="embed" class="h-full w-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" title="{{ __('Video') }}"></iframe></template>
        </div>
        <button type="button" class="btn-ghost absolute right-1 z-10 text-white" @click="next" aria-label="{{ __('Next') }}"><svg aria-hidden="true" class="size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 6l6 6-6 6"/></svg></button>
    </div>
    <p class="min-h-12 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] text-center text-sm" x-text="caption"></p>
</div>
