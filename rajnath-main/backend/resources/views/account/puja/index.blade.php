<x-layouts.account :title="__('Puja requests')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-600">{{ __('Tell us the puja you need; our team calls you to fix the date, place and dakshina.') }}</p>
        <a href="{{ route('puja-request.create') }}" class="btn-primary">{{ __('Request a puja') }}</a>
    </div>
    @if ($requests->isEmpty() && $rows->isEmpty())
        <div class="card mt-5 p-8 text-center"><p class="text-ink-600">{{ __('No puja requested yet.') }}</p></div>
    @endif
    @if ($requests->isNotEmpty())
        <ul class="mt-5 space-y-3">
            @foreach ($requests as $q)
                <li class="card flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-medium" translate="no">{{ $q->puja_type }}</p>
                        <p class="text-xs text-ink-600">{{ __('Requested') }} {{ $q->created_at->tz('Asia/Kolkata')->format('d M Y') }}
                            @if ($q->scheduled_at) · {{ __('Fixed for') }} {{ $q->scheduled_at->tz('Asia/Kolkata')->format('d M Y, g:i A') }}@endif</p>
                        @if ($q->meeting_link && $q->status === 'scheduled')<a href="{{ $q->meeting_link }}" rel="noopener" target="_blank" class="text-sm">{{ __('Join the live puja') }}</a>@endif
                    </div>
                    <span @class(['badge-gold' => in_array($q->status, ['new', 'contacted']), 'badge-success' => in_array($q->status, ['scheduled', 'completed']), 'badge-ink' => in_array($q->status, ['cancelled', 'spam'])])>{{ __(\App\Models\PujaRequest::STATUSES[$q->status] ?? $q->status) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
    @if ($rows->isNotEmpty())
        <h2 class="mt-8 text-base">{{ __('Earlier puja bookings') }}</h2>
        <ul class="mt-3 space-y-3">
            @foreach ($rows as $r)
                <li class="card flex flex-wrap items-center justify-between gap-3 p-4">
                    <div><p class="font-medium">{{ $r->title }}</p><p class="text-xs text-ink-600">Booked {{ \Illuminate\Support\Carbon::parse($r->created_at)->tz('Asia/Kolkata')->format('d M Y') }}@if ($r->scheduled_at) · Scheduled {{ \Illuminate\Support\Carbon::parse($r->scheduled_at, 'UTC')->tz('Asia/Kolkata')->format('d M Y, g:i A') }}@endif</p></div>
                    <div class="flex items-center gap-3">@include('account.puja._status', ['s' => $r->status])<a href="{{ route('account.puja.show', $r->ref) }}" class="btn-outline">{{ $r->status === 'awaiting_details' ? 'Add sankalp details' : 'Open' }}</a></div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.account>
