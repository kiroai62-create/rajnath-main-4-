@php
    $M = fn ($v) => \App\Support\Money::format((int) $v);
    $hour = (int) now('Asia/Kolkata')->format('G');
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $max = max(1, collect($series)->max(fn ($r) => $r[1]));
@endphp
<x-layouts.admin title="Dashboard">
    <section class="relative overflow-hidden rounded-[var(--radius-card)] bg-ink-900 p-6 text-white sm:p-8">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-40" style="background: radial-gradient(40rem 20rem at 90% -20%, rgb(217 164 65 / .45), transparent 60%)"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-gold-200">{{ now('Asia/Kolkata')->format('l, j F Y') }}</p>
                <h2 class="mt-1 text-2xl text-white">{{ $greet }}, {{ strtok(auth()->user()->name, ' ') }}</h2>
                <p class="mt-1 text-sm text-white/70">Here is what needs you today.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('content.author')<a href="{{ route('admin.blog.create') }}" class="btn border border-white/20 text-white hover:bg-white/10">New article</a>@endcan
                @can('media.manage')<a href="{{ route('admin.gallery.index') }}" class="btn border border-white/20 text-white hover:bg-white/10">Add photos</a>@endcan
                @can('catalogue.manage')<a href="{{ route('admin.catalogue.create') }}" class="btn-gold">New service</a>@endcan
            </div>
        </div>
    </section>

    @if ($kpis)
    <section aria-label="Key figures" @class(["mt-6 grid grid-cols-2 gap-4", "lg:grid-cols-5" => count($kpis) >= 5, "lg:grid-cols-4" => count($kpis) === 4, "lg:grid-cols-3" => count($kpis) <= 3])>
        @foreach ($kpis as [$label, $value, $type, $route])
            @php $tag = $route ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($route) href="{{ route($route) }}" @endif class="card block p-5 no-underline transition hover:-translate-y-0.5 hover:shadow-lg">
                <p class="text-sm text-ink-600">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-ink-900 sm:text-3xl">{{ match ($type) { 'money' => $M($value), 'rating' => $value ? number_format($value, 1).' / 5' : '—', default => number_format($value) } }}</p>
            </{{ $tag }}>
        @endforeach
    </section>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            @if ($series)
                <section class="card p-5" aria-labelledby="rev-h">
                    <div class="flex items-center justify-between"><h2 id="rev-h" class="text-base">Revenue, last 14 days</h2><a href="{{ route('admin.finance.index') }}" class="text-sm">Finance</a></div>
                    <ol class="mt-5 flex h-40 items-end gap-1.5" aria-label="Daily revenue">
                        @foreach ($series as [$d, $v])
                            <li class="group relative flex h-full flex-1 flex-col justify-end" title="{{ \Illuminate\Support\Carbon::parse($d)->format('j M') }}: {{ $M($v) }}">
                                <span class="block rounded-t-md bg-gradient-to-t from-ink-700 to-gold-500 transition group-hover:opacity-80" style="height: {{ max(2, round($v * 100 / $max)) }}%"></span>
                                <span class="sr-only">{{ \Illuminate\Support\Carbon::parse($d)->format('j M') }}: {{ $M($v) }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-2 flex justify-between text-xs text-ink-600"><span>{{ \Illuminate\Support\Carbon::parse($series[0][0])->format('j M') }}</span><span>Today</span></div>
                </section>
            @endif

            @if ($orders->isNotEmpty())
                <section aria-labelledby="ord-h">
                    <div class="flex items-center justify-between"><h2 id="ord-h" class="text-base">Latest orders</h2><a href="{{ route('admin.finance.index') }}" class="text-sm">All orders</a></div>
                    <div class="table-wrap mt-3"><table class="table">
                        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Amount</th><th scope="col">Status</th></tr></thead>
                        <tbody>@foreach ($orders as $o)<tr>
                            <td><a href="{{ route('admin.finance.order', $o->ref) }}" class="font-medium">{{ \Illuminate\Support\Str::limit($o->label, 40) }}</a><br><span class="text-xs text-ink-600">{{ \Illuminate\Support\Carbon::parse($o->created_at, 'UTC')->tz('Asia/Kolkata')->format('j M, H:i') }}</span></td>
                            <td>{{ $o->name }}</td><td class="tabular-nums">{{ $M($o->subtotal_minor) }}</td>
                            <td><span @class(['badge-success' => $o->status === 'paid', 'badge-gold' => $o->status === 'pending_payment', 'badge-ink' => ! in_array($o->status, ['paid', 'pending_payment'])])>{{ ucwords(str_replace('_', ' ', $o->status)) }}</span></td>
                        </tr>@endforeach</tbody>
                    </table></div>
                </section>
            @endif

            @if ($recent->isNotEmpty())
                <section aria-labelledby="recent">
                    <div class="flex items-center justify-between"><h2 id="recent" class="text-base">Recent activity</h2><a href="{{ route('admin.audit.index') }}" class="text-sm">Audit log</a></div>
                    <ul class="card mt-3 divide-y divide-ink-200/60">
                        @foreach ($recent as $r)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm"><span><code class="text-xs">{{ $r->action }}</code> <span class="text-ink-600">by {{ $r->actor_label }}</span></span>
                                <span class="whitespace-nowrap text-xs text-ink-600">{{ \Illuminate\Support\Carbon::parse($r->created_at, 'UTC')->tz('Asia/Kolkata')->diffForHumans() }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            @if ($attention)
                <section aria-labelledby="att-h">
                    <h2 id="att-h" class="text-base">Needs attention</h2>
                    <ul class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ($attention as [$label, $n, $route])
                            <li><a href="{{ route($route) }}" @class(['card flex h-full flex-col justify-between p-4 no-underline transition hover:-translate-y-0.5', 'ring-2 ring-gold-500' => $n > 0])>
                                <span class="text-sm text-ink-600">{{ $label }}</span>
                                <span @class(['mt-2 text-2xl font-semibold tabular-nums', 'text-gold-700' => $n > 0, 'text-ink-400' => $n === 0])>{{ number_format($n) }}</span>
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($upcoming->isNotEmpty())
                <section class="card p-5" aria-labelledby="up-h">
                    <div class="flex items-center justify-between"><h2 id="up-h" class="text-base">Upcoming consultations</h2><a href="{{ route('admin.ops.bookings') }}" class="text-sm">Calendar</a></div>
                    <ul class="mt-3 space-y-3">
                        @foreach ($upcoming as $b)
                            @php $t = \Illuminate\Support\Carbon::parse($b->start_utc, 'UTC')->tz('Asia/Kolkata'); @endphp
                            <li class="flex items-center gap-3">
                                <span class="grid w-12 shrink-0 rounded-xl bg-ivory-100 py-1.5 text-center"><span class="text-[0.65rem] font-semibold uppercase text-gold-700">{{ $t->format('M') }}</span><span class="text-lg font-semibold leading-none">{{ $t->format('j') }}</span></span>
                                <span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $b->name }}</span><span class="block text-xs text-ink-600">{{ $t->format('g:i A') }} · {{ ucwords(str_replace('_', ' ', $b->mode ?? '')) }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="card p-5" aria-labelledby="stats">
                <h2 id="stats" class="text-base">At a glance</h2>
                <dl class="mt-3 grid grid-cols-2 gap-4">
                    @foreach ($stats as $label => $value)
                        <div><dt class="text-xs text-ink-600">{{ $label }}</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ number_format($value) }}</dd></div>
                    @endforeach
                </dl>
            </section>
        </div>
    </div>
</x-layouts.admin>
