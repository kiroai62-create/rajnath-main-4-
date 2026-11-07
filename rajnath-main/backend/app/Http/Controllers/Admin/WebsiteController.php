<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use App\Support\Settings\SettingsRepository;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin › Website & home page: announcement bar, header bar, home sections on/off and order. */
class WebsiteController extends Controller
{
    public function edit(SiteContent $site): View
    {
        $this->authorize('content.publish');

        return view('admin.website.edit', ['web' => $site]);
    }

    public function update(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $this->authorize('content.publish');
        $keys = array_keys(SiteContent::SECTIONS);
        $d = $request->validate([
            'announce_on' => ['nullable', 'boolean'], 'announce_text' => ['nullable', 'string', 'max:200'],
            'announce_link' => ['nullable', 'string', 'max:255', 'regex:#^(https://|/)#'], 'announce_cta' => ['nullable', 'string', 'max:40'],
            'utility_on' => ['nullable', 'boolean'], 'utility_text' => ['nullable', 'string', 'max:160'],
            'sections' => ['nullable', 'array'], 'sections.*' => [Rule::in($keys)],
            'order' => ['nullable', 'array'], 'order.*' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], ['announce_link.regex' => 'Start the link with https:// or / (a page on this site).']);
        $order = collect($keys)->sortBy(fn ($k, $i) => [(int) ($d['order'][$k] ?? ($i + 1)), $i])->values()->all();
        $values = [
            'site.announce_on' => (bool) ($d['announce_on'] ?? false), 'site.announce_text' => $d['announce_text'] ?? null,
            'site.announce_link' => $d['announce_link'] ?? null, 'site.announce_cta' => $d['announce_cta'] ?? null,
            'site.utility_on' => (bool) ($d['utility_on'] ?? false), 'site.utility_text' => $d['utility_text'] ?? null,
            'site.home_hidden' => array_values(array_diff($keys, $d['sections'] ?? [])), 'site.home_order' => $order,
        ];
        foreach ($values as $k => $v) {
            if ($settings->get($k) !== $v) {
                $settings->set($k, $v, false, 'site');
            }
        }
        Navigation::forget();

        return back()->with('status', 'Website settings saved. The home page shows them now.');
    }
}
