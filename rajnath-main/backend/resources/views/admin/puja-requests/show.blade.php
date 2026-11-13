@php
    $M = \App\Models\PujaRequest::class;
    $ist = $r->scheduled_at?->tz('Asia/Kolkata');
    $wa = 'Namaste '.$r->name.', this is '.$site('brand.name').' about your '.$r->puja_type.' request.'.($ist ? ' The puja is fixed for '.$ist->format('d M Y, g:i A').' (India time).' : '');
@endphp
<x-layouts.admin :title="'Puja request: '.$r->puja_type">
    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        <section class="card p-5 sm:p-6">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                @foreach ([
                    'Name' => $r->name, 'Phone' => $r->phone_e164, 'Email' => $r->email ?? '—',
                    'Puja' => $r->puja_type, 'Where' => $M::MODES[$r->mode] ?? $r->mode, 'Address' => $r->address ?? '—',
                    'Preferred date' => $r->preferred_date?->format('d M Y') ?? 'Not given', 'Second choice' => $r->alternate_date?->format('d M Y') ?? '—',
                    'Time of day' => $M::TIMES[$r->preferred_time] ?? '—', 'Samagri' => $M::SAMAGRI[$r->samagri] ?? '—',
                    'Names for sankalp' => $r->participants ?? '—', 'Gotra' => $r->gotra ?? '—',
                    'Birth details' => $r->birth_date ? $r->birth_date->format('d M Y').($r->birth_time ? ' '.substr($r->birth_time, 0, 5) : '').($r->birth_place ? ', '.$r->birth_place : '') : '—',
                    'Language' => $r->language ?? '—',
                    'Received' => $r->created_at->tz('Asia/Kolkata')->format('d M Y, g:i A').' IST',
                    'Account' => $r->user_id ? 'Signed-in customer (gets notifications)' : 'Guest',
                    'Reference' => $r->ref, 'Last handled by' => $r->handler?->name ?? '—',
                ] as $k => $v)
                    <div><dt class="text-ink-600">{{ $k }}</dt><dd class="mt-0.5 break-words font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <h2 class="mt-6 text-sm text-ink-600">Purpose / problem</h2>
            <p class="mt-1 whitespace-pre-line rounded-xl bg-ivory-100 p-4 text-sm">{{ $r->purpose ?: '—' }}</p>
            <h2 class="mt-4 text-sm text-ink-600">Other message</h2>
            <p class="mt-1 whitespace-pre-line rounded-xl bg-ivory-100 p-4 text-sm">{{ $r->message ?: '—' }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="tel:{{ $r->phone_e164 }}" class="btn-primary">Call</a>
                <a href="https://wa.me/{{ ltrim($r->phone_e164, '+') }}?text={{ rawurlencode($wa) }}" rel="noopener" target="_blank" class="btn-outline">WhatsApp</a>
                @if ($r->email)<a href="mailto:{{ $r->email }}?subject={{ rawurlencode('Your puja request: '.$r->puja_type) }}" class="btn-outline">Email</a>@endif
                <a href="{{ route('admin.puja-requests.index') }}" class="btn-ghost">Back to list</a>
            </div>
        </section>
        @can('bookings.manage')
        <form method="POST" action="{{ route('admin.puja-requests.update', $r) }}" class="card h-fit space-y-4 p-5">
            @csrf @method('PUT')
            <h2 class="text-lg">Fix the puja</h2>
            <x-select name="status" label="Status" :value="$r->status" :options="$M::STATUSES" />
            <div class="grid grid-cols-2 gap-3">
                <x-field name="scheduled_date" label="Puja date" type="date" :value="$ist?->format('Y-m-d')" />
                <x-field name="scheduled_time" label="Time (IST)" type="time" :value="$ist?->format('H:i')" />
            </div>
            <x-field name="priest" label="Priest / pandit" :value="$r->priest" />
            <x-field name="quoted" label="Dakshina agreed (₹)" type="number" step="1" min="0" :value="$r->quoted_minor !== null ? $r->quoted_minor / 100 : null" />
            <x-field name="meeting_link" label="Live video link (online puja)" type="url" :value="$r->meeting_link" placeholder="https://meet.google.com/…" />
            <div><label for="f-note" class="field-label">Internal note (not shown to the customer)</label><textarea id="f-note" name="staff_note" rows="4" class="input">{{ old('staff_note', $r->staff_note) }}</textarea></div>
            <button class="btn-primary w-full">Save</button>
            <p class="text-xs text-ink-600">Setting "Date fixed" with a date notifies a signed-in customer. For guests, call or WhatsApp them.</p>
        </form>
        @endcan
    </div>
</x-layouts.admin>
