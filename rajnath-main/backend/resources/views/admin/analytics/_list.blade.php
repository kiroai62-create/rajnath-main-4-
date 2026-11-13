{{-- Top-N list with a share bar. $rows = [{k, n, v}], $title, $label (column), $format (optional fn for the key) --}}
@php $total = max(1, collect($rows)->sum('n')); $fmt = $format ?? fn ($k) => $k ?? 'Unknown'; @endphp
<section class="card min-w-0 p-5" aria-label="{{ $title }}">
    <h2 class="text-base">{{ $title }}</h2>
    @if (empty($rows))
        <p class="mt-3 text-sm text-ink-600">No data in this period yet.</p>
    @else
        <table class="mt-3 w-full text-sm">
            <thead><tr class="text-left text-xs text-ink-600"><th scope="col" class="pb-2 font-medium">{{ $label ?? 'Name' }}</th><th scope="col" class="pb-2 text-right font-medium">{{ $nLabel ?? 'Views' }}</th><th scope="col" class="pb-2 text-right font-medium">{{ $vLabel ?? 'People' }}</th></tr></thead>
            <tbody>
            @foreach ($rows as $row)
                <tr class="border-t border-ink-200/50">
                    <td class="relative max-w-0 py-1.5 pr-2"><span aria-hidden="true" class="absolute inset-y-1 left-0 rounded bg-gold-100" style="width: {{ round($row->n * 100 / $total) }}%"></span><span class="relative block truncate" title="{{ $fmt($row->k) }}">{{ $fmt($row->k) }}</span></td>
                    <td class="py-1.5 text-right tabular-nums">{{ number_format($row->n) }}</td>
                    <td class="py-1.5 text-right tabular-nums text-ink-600">{{ number_format($row->v) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>
