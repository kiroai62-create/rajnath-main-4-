<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\ToolPdf;
use App\Domain\Astro\ToolDisplay;
use App\Domain\Astro\Tools;
use App\Http\Controllers\Controller;
use App\Support\Pdf\Documents;
use App\Support\Pdf\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/** Admin › PDF templates: how the PDF of each free calculator looks (one default + per-tool changes). */
class PdfTemplateController extends Controller
{
    public function __construct(private ToolPdf $pdf) {}

    public function index(Pdf $printer): View
    {
        $this->authorize('content.publish');

        return view('admin.pdf-templates.index', [
            'tools' => Tools::TOOLS, 'pdf' => $this->pdf, 'printer' => $printer->available(),
        ]);
    }

    public function edit(string $slug): View
    {
        $this->authorize('content.publish');
        abort_unless($slug === 'default' || isset(Tools::TOOLS[$slug]), 404);
        $sections = [];
        if ($slug !== 'default') {
            try {
                $sections = array_values(array_filter(array_map(fn ($s) => $s['title'] ?? null,
                    ToolDisplay::for(app(Tools::class)->compute($slug, ToolPdf::sampleInputs($slug)))['sections'])));
            } catch (RuntimeException $e) {
                report($e);
            }
        }

        return view('admin.pdf-templates.edit', [
            'slug' => $slug, 'name' => $slug === 'default' ? 'Default (all calculators)' : Tools::TOOLS[$slug][0],
            't' => $this->pdf->template($slug === 'default' ? '__none' : $slug), 'own' => $slug !== 'default' && $this->pdf->hasOwn($slug), 'sections' => $sections,
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $this->authorize('content.publish');
        abort_unless($slug === 'default' || isset(Tools::TOOLS[$slug]), 404);
        if ($request->boolean('reset') && $slug !== 'default') {
            $this->pdf->save($slug, null);

            return redirect()->route('admin.pdf-templates.edit', $slug)->with('status', 'This calculator now uses the default template.');
        }
        $d = $request->validate([
            'enabled' => ['nullable', 'boolean'], 'title' => ['nullable', 'string', 'max:120'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'intro' => ['nullable', 'string', 'max:1000'],
            'closing' => ['nullable', 'string', 'max:1000'], 'disclaimer' => ['nullable', 'string', 'max:1000'],
            'watermark' => ['nullable', 'string', 'max:40'], 'paper' => ['required', Rule::in(['A4', 'Letter'])],
            'show_charts' => ['nullable', 'boolean'], 'show_contact' => ['nullable', 'boolean'], 'show_birth' => ['nullable', 'boolean'],
            'hide_sections' => ['nullable', 'array'], 'hide_sections.*' => ['string', 'max:120'],
        ]);
        $values = [
            'enabled' => (bool) ($d['enabled'] ?? false), 'title' => trim((string) ($d['title'] ?? '')), 'accent' => strtolower($d['accent']),
            'intro' => trim((string) ($d['intro'] ?? '')), 'closing' => trim((string) ($d['closing'] ?? '')), 'disclaimer' => trim((string) ($d['disclaimer'] ?? '')),
            'watermark' => trim((string) ($d['watermark'] ?? '')), 'paper' => $d['paper'],
            'show_charts' => (bool) ($d['show_charts'] ?? false), 'show_contact' => (bool) ($d['show_contact'] ?? false), 'show_birth' => (bool) ($d['show_birth'] ?? false),
            'hide_sections' => array_values($d['hide_sections'] ?? []),
        ];
        $this->pdf->save($slug, $values);

        return redirect()->route('admin.pdf-templates.edit', $slug)->with('status', 'PDF template saved.');
    }

    /** The template with sample details: as HTML (preview) or as a real PDF (format=pdf). */
    public function preview(Request $request, string $slug, Documents $docs): Response
    {
        $this->authorize('content.publish');
        $tool = $slug === 'default' ? 'kundli' : $slug;
        abort_unless(isset(Tools::TOOLS[$tool]), 404);
        try {
            $result = app(Tools::class)->compute($tool, ToolPdf::sampleInputs($tool));
        } catch (RuntimeException $e) {
            report($e);

            return response('<p style="font-family:sans-serif;padding:2rem">The calculation service is unavailable, so a sample cannot be made right now.</p>', 503);
        }
        $html = $this->pdf->html($tool, $result, 'SAMPLE00');
        if ($request->query('format') === 'pdf') {
            return $docs->download($html, $tool.'-sample.pdf');
        }

        return response($html)->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:");
    }
}
