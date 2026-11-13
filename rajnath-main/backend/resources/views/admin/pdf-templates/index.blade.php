<x-layouts.admin title="PDF templates">
    <p class="max-w-3xl text-sm text-ink-600">Visitors can download every free calculator result as a PDF. Set the look once in the <strong>default template</strong>, then change single calculators where needed
        (for example a different title, intro or colour for Kundli Matching). Each template is text and simple choices only.</p>
    @unless ($printer)
        <p role="alert" class="card mt-4 border-gold-200 bg-gold-50 px-4 py-3 text-sm">PDF printing (Chromium) is not installed on this server, so downloads will fail. It is included in the Docker image used by <code>deploy/deploy.sh</code>.</p>
    @endunless
    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('admin.pdf-templates.edit', 'default') }}" class="btn-primary">Edit default template</a>
        <a href="{{ route('admin.pdf-templates.preview', 'default') }}" target="_blank" rel="noopener" class="btn-outline">Preview (Kundli sample)</a>
    </div>
    <div class="table-wrap mt-6" role="region" aria-label="Calculators" tabindex="0"><table class="table">
        <thead><tr><th scope="col">Calculator</th><th scope="col">Template</th><th scope="col">PDF download</th><th scope="col">PDF title</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        @foreach ($tools as $slug => [$title])
            @php $t = $pdf->template($slug); @endphp
            <tr>
                <td class="font-medium">{{ $title }}</td>
                <td>@if ($pdf->hasOwn($slug))<span class="badge-gold">Own template</span>@else<span class="badge-ink">Default</span>@endif</td>
                <td>@if ($t['enabled'])<span class="badge-success">On</span>@else<span class="badge-danger">Off</span>@endif</td>
                <td class="text-sm">{{ trim((string) $t['title']) ?: $title }}</td>
                <td class="whitespace-nowrap"><a href="{{ route('admin.pdf-templates.edit', $slug) }}" class="btn-outline min-h-9 px-3 text-xs">Edit</a>
                    <a href="{{ route('admin.pdf-templates.preview', $slug) }}" target="_blank" rel="noopener" class="btn-ghost min-h-9 px-3 text-xs">Preview</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</x-layouts.admin>
