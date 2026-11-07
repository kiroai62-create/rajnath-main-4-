<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AppConfig;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin › Mobile app: everything the owner controls in the Android app without a new release.
 * Content (announcement, home sections, features) needs content.publish; maintenance and
 * forced updates can lock every user out, so they also need settings.manage.
 */
class AppController extends Controller
{
    public function edit(AppConfig $app): View
    {
        $this->authorize('content.publish');

        return view('admin.app.edit', ['app' => $app, 'canLock' => auth()->user()->can('settings.manage')]);
    }

    public function update(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $this->authorize('content.publish');
        $sections = array_keys(AppConfig::SECTIONS);
        $features = array_keys(AppConfig::FEATURES);
        $version = ['nullable', 'string', 'max:20', 'regex:/^\d+(\.\d+){0,2}$/'];
        $d = $request->validate([
            'announce_on' => ['nullable', 'boolean'], 'announce_text' => ['nullable', 'string', 'max:200'],
            'announce_cta' => ['nullable', 'string', 'max:40'], 'announce_style' => ['nullable', Rule::in(['gold', 'cosmic', 'maroon'])],
            'announce_link' => ['nullable', 'string', 'max:255', 'regex:#^(https://|/|app:)#'],
            'sections' => ['nullable', 'array'], 'sections.*' => [Rule::in($sections)],
            'order' => ['nullable', 'array'], 'order.*' => ['nullable', 'integer', 'min:1', 'max:99'],
            'features' => ['nullable', 'array'], 'features.*' => [Rule::in($features)],
            'support_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ]{8,18}$/'], 'support_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ]{8,18}$/'],
            'maintenance_on' => ['nullable', 'boolean'], 'maintenance_text' => ['nullable', 'string', 'max:300'],
            'min_version' => $version, 'latest_version' => $version,
            'update_url' => ['nullable', 'url:https', 'max:255'], 'update_text' => ['nullable', 'string', 'max:200'],
        ], [
            'announce_link.regex' => 'Start the link with https://, / (a page on the website) or app: (a screen in the app, e.g. app:store).',
            'min_version.regex' => 'Write the version like 1.11.0', 'latest_version.regex' => 'Write the version like 1.11.0',
        ]);
        $order = collect($sections)->sortBy(fn ($k, $i) => [(int) ($d['order'][$k] ?? ($i + 1)), $i])->values()->all();
        $values = [
            'app.announce_on' => (bool) ($d['announce_on'] ?? false), 'app.announce_text' => $d['announce_text'] ?? null,
            'app.announce_cta' => $d['announce_cta'] ?? null, 'app.announce_link' => $d['announce_link'] ?? null, 'app.announce_style' => $d['announce_style'] ?? 'gold',
            'app.home_hidden' => array_values(array_diff($sections, $d['sections'] ?? [])), 'app.home_order' => $order,
            'app.features_off' => array_values(array_diff($features, $d['features'] ?? [])),
            'app.support_whatsapp' => $d['support_whatsapp'] ?? null, 'app.support_phone' => $d['support_phone'] ?? null,
        ];
        if ($request->user()->can('settings.manage')) {
            $values += [
                'app.maintenance_on' => (bool) ($d['maintenance_on'] ?? false), 'app.maintenance_text' => $d['maintenance_text'] ?? null,
                'app.min_version' => $d['min_version'] ?? null, 'app.latest_version' => $d['latest_version'] ?? null,
                'app.update_url' => $d['update_url'] ?? null, 'app.update_text' => $d['update_text'] ?? null,
            ];
            if (! empty($d['min_version']) && ! empty($d['latest_version']) && version_compare($d['min_version'], $d['latest_version'], '>')) {
                return back()->withInput()->withErrors(['latest_version' => 'The latest version cannot be lower than the minimum version.']);
            }
        }
        foreach ($values as $k => $v) {
            if ($settings->get($k) !== $v) {
                $settings->set($k, $v, false, 'app');
            }
        }

        return back()->with('status', 'App settings saved. Phones pick them up the next time the app opens.');
    }
}
