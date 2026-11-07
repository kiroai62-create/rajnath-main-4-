@props(['title'])
<x-layouts.base :title="$title" robots="noindex,nofollow">
    <main id="main" class="grid min-h-dvh place-items-center px-4 py-10">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-6 flex items-center justify-center gap-2.5 text-ink-900 no-underline">
                <img src="/images/brand/logo-mark.svg" alt="" width="48" height="48" class="size-12">
                <span class="font-semibold">{{ $site('brand.name') }}</span>
            </a>
            <div class="card p-6 sm:p-8">
                <h1 class="text-xl">{{ $title }}</h1>
                @if (session('status'))<p role="status" class="mt-3 text-sm text-success-700">{{ session('status') }}</p>@endif
                <div class="mt-6">{{ $slot }}</div>
            </div>
        </div>
    </main>
</x-layouts.base>
