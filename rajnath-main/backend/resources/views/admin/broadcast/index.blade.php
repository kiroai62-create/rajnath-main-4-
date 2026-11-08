@php use App\Http\Controllers\Admin\BroadcastController; @endphp
<x-layouts.admin title="Push messages">
    <p class="max-w-3xl text-sm text-ink-600">Send a message to all <strong>{{ number_format($audience) }}</strong> customers: it appears in the app inbox and on their account page, as a phone notification on {{ number_format($devices) }} {{ $devices === 1 ? 'device' : 'devices' }}, and by email.
        @unless ($pushReady)<span class="text-danger-700">Phone notifications are not set up yet (<a href="{{ route('admin.push.edit') }}">Push notifications</a>); the inbox and email still work.</span>@endunless</p>

    <div class="mt-6 grid max-w-6xl gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('admin.broadcast.ai') }}" class="card space-y-4 border-gold-200 bg-gold-50 p-5 sm:p-6" aria-labelledby="ai-h">
            @csrf
            <h2 id="ai-h" class="text-lg">✨ Write with AI</h2>
            <p class="text-sm text-ink-600">Say what the message is about. AI writes the title and text in the form on the right; nothing is sent until you press Send.</p>
            <div><label for="f-topic" class="field-label">Topic</label><textarea id="f-topic" name="topic" rows="3" maxlength="400" class="input" data-no-ai placeholder="Diwali Lakshmi puja bookings are open, last date 30 Oct">{{ old('topic') }}</textarea>
                @error('topic')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <x-select name="language" label="Language" :options="['en' => 'English', 'hi' => 'हिन्दी', 'both' => 'Hindi + English']" :value="old('language', 'en')" />
            <input type="hidden" name="kind" value="{{ old('kind', 'offer') }}"><input type="hidden" name="link" value="{{ old('link') }}">
            @if (! $aiReady)<p class="text-sm text-danger-700">AI is off. Add a key in <a href="{{ route('admin.assistant.edit') }}">AI assistant</a>.</p>@endif
            <button class="btn-gold" @disabled(! $aiReady)>Write a draft</button>
        </form>

        <form method="POST" action="{{ route('admin.broadcast.send') }}" class="card space-y-4 p-5 sm:p-6" aria-labelledby="msg-h">
            @csrf
            <h2 id="msg-h" class="text-lg">Message</h2>
            <x-field name="title" label="Title" :value="old('title')" maxlength="80" required placeholder="Diwali puja bookings are open 🪔" />
            <div><label for="f-body" class="field-label">Text</label><textarea id="f-body" name="body" rows="4" maxlength="300" class="input" required>{{ old('body') }}</textarea>
                @error('body')<p class="field-error">{{ $message }}</p>@enderror</div>
            <x-select name="kind" label="Type" :options="BroadcastController::KINDS" :value="old('kind', 'offer')" />
            <x-field name="link" label="Opens (optional)" :value="old('link')" placeholder="/services?family=puja" hint="A website page (/…), https:// link, or an app screen such as app:puja, app:store, app:product/ruby-manik." />
            <label class="flex min-h-11 items-start gap-3 text-sm"><input type="checkbox" name="confirm" value="1" class="mt-1 size-4 accent-ink-900"> <span>Send to all {{ number_format($audience) }} customers now. This cannot be undone.</span></label>
            @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
            <button class="btn-primary">Send message</button>
        </form>
    </div>

    <section class="card mt-6 max-w-6xl p-5 sm:p-6" aria-labelledby="sent-h">
        <h2 id="sent-h" class="text-lg">Sent messages</h2>
        @if ($recent->isEmpty())
            <p class="mt-2 text-sm text-ink-600">Nothing sent yet.</p>
        @else
            <div class="table-wrap mt-4"><table class="table">
                <thead><tr><th scope="col">Sent</th><th scope="col">Title</th><th scope="col">Type</th><th scope="col">Customers</th></tr></thead>
                <tbody>@foreach ($recent as $r)<tr><td>{{ \Illuminate\Support\Carbon::parse($r->sent_at)->tz('Asia/Kolkata')->format('d M Y, g:i A') }}</td><td>{{ $r->title }}</td><td>{{ $r->kind === 'offer' ? 'Offer' : 'Announcement' }}</td><td>{{ number_format($r->recipients) }}</td></tr>@endforeach</tbody>
            </table></div>
        @endif
    </section>
</x-layouts.admin>
