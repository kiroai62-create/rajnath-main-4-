<?php

namespace App\Domain\Analytics;

use App\Domain\Astro\ToolDisplay;
use App\Domain\Astro\Tools;
use App\Support\Business;
use App\Support\Settings\SettingsRepository;
use Illuminate\Support\Carbon;

/**
 * PDF of a free-calculator result, laid out by an owner-editable template (Admin › PDF
 * templates): one default template plus an optional template per tool. Templates only hold
 * text and simple choices (colour, sections, paper), never code.
 */
class ToolPdf
{
    public const FIELDS = ['enabled', 'title', 'accent', 'intro', 'closing', 'disclaimer', 'watermark', 'paper', 'show_charts', 'show_contact', 'show_birth', 'hide_sections'];

    public const DEFAULT = [
        'enabled' => true,
        'title' => '',
        'accent' => '#b8891d',
        'intro' => 'Prepared with the Swiss Ephemeris for the birth details below.',
        'closing' => 'For a detailed reading of this chart, book a consultation with Acharya Ji.',
        'disclaimer' => 'Astrological guidance is traditional and interpretive. It does not replace medical, legal or financial advice.',
        'watermark' => '',
        'paper' => 'A4',
        'show_charts' => true,
        'show_contact' => true,
        'show_birth' => true,
        'hide_sections' => [],
    ];

    /** Sample inputs used for the admin preview. */
    public const SAMPLE = [
        'birth' => ['name' => 'Sample Person', 'birth_date' => '1990-05-14', 'birth_time' => '06:30'],
        'couple' => ['a_name' => 'Sample Groom', 'a_birth_date' => '1990-05-14', 'a_birth_time' => '06:30', 'b_name' => 'Sample Bride', 'b_birth_date' => '1992-08-21', 'b_birth_time' => '14:15'],
        'name' => ['name' => 'Sample Person'],
        'dob' => ['birth_date' => '1990-05-14'],
        'dob_month' => ['birth_date' => '1990-05-14'],
        'name_dob' => ['name' => 'Sample Person', 'birth_date' => '1990-05-14'],
        'vehicle' => ['birth_date' => '1990-05-14', 'plate' => 'BR01AB1234'],
        'two_names' => ['name' => 'Rahul', 'partner' => 'Priya'],
    ];

    public function __construct(private SettingsRepository $settings) {}

    /** @return array<string, mixed> */
    public function template(string $slug): array
    {
        $all = (array) $this->settings->get('pdf.tool_templates', []);
        $base = array_merge(self::DEFAULT, (array) ($all['default'] ?? []));

        return array_merge($base, array_filter((array) ($all[$slug] ?? []), fn ($v) => $v !== null));
    }

    public function hasOwn(string $slug): bool
    {
        return ! empty(((array) $this->settings->get('pdf.tool_templates', []))[$slug] ?? null);
    }

    /** @param  array<string, mixed>|null  $values  null removes the tool's own template */
    public function save(string $slug, ?array $values): void
    {
        $all = (array) $this->settings->get('pdf.tool_templates', []);
        if ($values === null) {
            unset($all[$slug]);
        } elseif ($slug === 'default') {
            $all['default'] = array_intersect_key($values, array_flip(self::FIELDS));
        } else {
            // Only what differs from the default is kept, so later changes to the default still apply.
            $base = array_merge(self::DEFAULT, (array) ($all['default'] ?? []));
            $own = array_filter(array_intersect_key($values, array_flip(self::FIELDS)), fn ($v, $k) => $v !== ($base[$k] ?? null), ARRAY_FILTER_USE_BOTH);
            if ($own) {
                $all[$slug] = $own;
            } else {
                unset($all[$slug]);
            }
        }
        $this->settings->set('pdf.tool_templates', $all, false, 'pdf');
    }

    /** @return array<string, string> */
    public static function sampleInputs(string $slug): array
    {
        $in = self::SAMPLE[Tools::TOOLS[$slug][1]];
        foreach (['', 'a_', 'b_'] as $p) {
            if (isset($in[$p.'birth_time'])) {
                $in[$p.'place_id'] = 1260086;   // Patna
            }
        }
        if ($slug === 'personal-year') {
            $in['year'] = (int) now('Asia/Kolkata')->year;
        }
        if (Tools::TOOLS[$slug][1] === 'dob_month') {
            $in['month'] = now('Asia/Kolkata')->format('Y-m');
        }

        return $in;
    }

    /**
     * @param  array<string, mixed>  $result  Tools::compute() output
     * @param  array<string, mixed>|null  $template  unsaved template for a preview
     */
    public function html(string $slug, array $result, ?string $reference = null, ?array $template = null): string
    {
        $t = $template ? array_merge($this->template($slug), $template) : $this->template($slug);
        $display = ToolDisplay::for($result);
        $hide = array_map('mb_strtolower', array_map('trim', (array) $t['hide_sections']));
        $display['sections'] = array_values(array_filter($display['sections'], function ($s) use ($hide, $t) {
            if (! $t['show_birth'] && ($s['title'] ?? '') === 'Birth details') {
                return false;
            }

            return ! in_array(mb_strtolower((string) ($s['title'] ?? '')), $hide, true);
        }));
        $business = app(Business::class);

        return view('pdf.tool', [
            'title' => trim((string) $t['title']) ?: Tools::TOOLS[$slug][0], 't' => $t, 'display' => $display, 'r' => $result,
            'brand' => (string) $this->settings->get('brand.name', config('brand')['brand.name'] ?? config('app.name')),
            'tagline' => (string) $this->settings->get('brand.tagline', ''),
            'contact' => array_filter([$business->phone(), $business->email(), parse_url((string) config('app.url'), PHP_URL_HOST), $business->addressLine()]),
            'reference' => $reference, 'made' => Carbon::now('Asia/Kolkata')->format('d M Y, g:i A'), 'lang' => 'en',
        ])->render();
    }
}
