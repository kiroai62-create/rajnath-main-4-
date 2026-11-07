<?php

namespace App\Support;

use App\Support\Settings\SettingsRepository;

/**
 * Website controls edited in Admin › Website & home page: the announcement bar, the header
 * contact bar, and which home-page sections show and in what order. Defaults keep everything on.
 */
class SiteContent
{
    /** Home sections: key => label (default order). */
    public const SECTIONS = [
        'today' => "Today's Panchang and Moon-sign finder",
        'ways' => 'Ways to get guidance (call, written question, reports)',
        'rashifal' => 'Your sign today (12 rashis)',
        'featured' => 'Featured services',
        'grahas' => 'Where the nine planets are today',
        'ecosystem' => 'Everything in one place (12 tiles)',
        'steps' => 'How it works (4 steps)',
        'recognition' => 'Awards and recognition',
        'learn' => 'Learn Jyotish (guide topics)',
        'gallery' => 'Gallery albums',
        'reviews' => 'Client reviews',
        'videos' => 'Videos (In the spotlight)',
        'articles' => 'Latest articles',
        'families' => 'Browse by category',
        'why' => 'Why us and questions',
        'visit' => 'Visit or call (office)',
    ];

    public function __construct(private SettingsRepository $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $v = $this->settings->get('site.'.$key);

        return $v === null || $v === '' ? $default : $v;
    }

    public function sectionOn(string $key): bool
    {
        $off = (array) $this->get('home_hidden', []);

        return ! in_array($key, $off, true);
    }

    /** @return list<string> section keys in display order */
    public function order(): array
    {
        $order = array_values(array_unique(array_intersect((array) $this->get('home_order', []), array_keys(self::SECTIONS))));
        // Sections added later than the saved order go in their default place (after their default neighbour).
        $prev = null;
        foreach (array_keys(self::SECTIONS) as $key) {
            if (! in_array($key, $order, true)) {
                $at = $prev === null ? 0 : array_search($prev, $order, true) + 1;
                array_splice($order, $at, 0, [$key]);
            }
            $prev = $key;
        }

        return $order;
    }

    /** @return array{text: string, link: ?string, cta: ?string}|null */
    public function announcement(): ?array
    {
        if (! $this->get('announce_on')) {
            return null;
        }
        $text = (string) $this->get('announce_text', '');

        return $text === '' ? null : ['text' => $text, 'link' => $this->get('announce_link'), 'cta' => $this->get('announce_cta')];
    }

    public function utilityText(): string
    {
        return (string) $this->get('utility_text', __('Vedic astrology, Vastu and puja with Acharya Dr. Rajnath Jha · Patna and online worldwide'));
    }

    public function utilityOn(): bool
    {
        return (bool) $this->get('utility_on', true);
    }
}
