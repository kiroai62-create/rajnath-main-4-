@props(['r', 'clamp' => false])
<figure {{ $attributes->merge(['class' => 'card flex h-full flex-col p-5 sm:p-6']) }}>
    <div class="flex items-center justify-between gap-3">
        <x-stars :rating="$r->rating" />
        @if (($r->source ?? null) === 'google')
            <span class="inline-flex items-center gap-1 text-xs font-medium text-ink-600"><svg aria-hidden="true" class="size-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.5 12.3c0-.8-.1-1.5-.2-2.3H12v4.3h5.9a5 5 0 01-2.2 3.3v2.7h3.6c2.1-1.9 3.2-4.8 3.2-8z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.6-2.7c-1 .7-2.2 1.1-3.7 1.1-2.9 0-5.3-1.9-6.2-4.5H2.1v2.8A11 11 0 0012 23z"/><path fill="#FBBC05" d="M5.8 14.2a6.6 6.6 0 010-4.4V7H2.1a11 11 0 000 10l3.7-2.8z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.7l3.2-3.2A11 11 0 002.1 7l3.7 2.8C6.7 7.3 9.1 5.4 12 5.4z"/></svg>{{ __('Google review') }}</span>
        @elseif ($r->verified)
            <span class="inline-flex items-center gap-1 text-xs font-medium text-success-700"><svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>{{ __('Verified purchase') }}</span>
        @endif
    </div>
    @if ($r->title)<p class="mt-3 font-semibold">{{ $r->title }}</p>@endif
    <blockquote @class(['mt-2 whitespace-pre-line text-ink-800 leading-7', 'line-clamp-6' => $clamp])>{{ $r->body }}</blockquote>
    @if ($r->reply)
        <div class="mt-4 rounded-xl bg-ivory-100 p-3 text-sm"><p class="text-xs font-semibold uppercase tracking-wide text-gold-700">{{ __('Reply from our team') }}</p><p class="mt-1 whitespace-pre-line">{{ $r->reply }}</p></div>
    @endif
    <figcaption class="mt-auto flex items-center gap-3 pt-5 text-sm">
        <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-full bg-ink-900 font-semibold text-white">{{ mb_strtoupper(mb_substr($r->display_name, 0, 1)) }}</span>
        <span><span class="block font-semibold text-ink-900">{{ $r->display_name }}{{ $r->city ? ', '.$r->city : '' }}</span>
            <span class="block text-xs text-ink-600">{{ collect([$r->subject, \Illuminate\Support\Carbon::parse($r->published_at ?? $r->created_at)->translatedFormat('M Y')])->filter()->implode(' · ') }}</span></span>
    </figcaption>
</figure>
