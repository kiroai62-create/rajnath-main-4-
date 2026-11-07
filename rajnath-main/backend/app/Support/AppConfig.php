<?php

namespace App\Support;

use App\Support\Settings\SettingsRepository;

/**
 * Mobile app controls edited in Admin › Mobile app: maintenance screen, minimum and latest
 * version (force / suggest update), an announcement banner, which home sections show and in
 * what order, and which features are switched on. Sent to the app in GET /api/v1/meta → app.
 * Defaults keep everything on, so an empty settings table changes nothing.
 */
class AppConfig
{
    /** App home sections: key => label (default order). */
    public const SECTIONS = [
        'greeting' => 'Greeting with today\'s tithi and nakshatra',
        'quick' => 'Quick actions (round buttons)',
        'hero' => 'Banner with Acharya Ji\'s photo and Book button',
        'ecosystem' => 'Everything in one place (tiles)',
        'featured' => 'Popular services',
        'signs' => 'Your sign today (12 rashis)',
        'store' => 'From our store (products)',
        'articles' => 'Latest articles',
        'more' => 'More for you (Rashifal, gallery, assistant)',
        'reviews' => 'Client reviews',
    ];

    /** Features the owner can switch off in the app: key => label. */
    public const FEATURES = [
        'assistant' => 'AI assistant (Ask AI button and chat)',
        'store' => 'Gem store and samagri shop',
        'puja' => 'Online puja and chadhava booking',
        'palm' => 'Palm reading',
        'consultation' => 'Book a call / consultation',
        'wallet' => 'Wallet recharge',
        'blog' => 'Articles and courses',
        'gallery' => 'Photo gallery',
    ];

    public function __construct(private SettingsRepository $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $v = $this->settings->get('app.'.$key);

        return $v === null || $v === '' ? $default : $v;
    }

    public function sectionOn(string $key): bool
    {
        return ! in_array($key, (array) $this->get('home_hidden', []), true);
    }

    public function featureOn(string $key): bool
    {
        return ! in_array($key, (array) $this->get('features_off', []), true);
    }

    /** @return list<string> section keys in display order (sections added later keep their default place) */
    public function order(): array
    {
        $order = array_values(array_unique(array_intersect((array) $this->get('home_order', []), array_keys(self::SECTIONS))));
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

    public function minVersion(): string
    {
        return (string) $this->get('min_version', config('app.min_mobile_version', '1.0.0'));
    }

    /** The block sent to the app inside GET /api/v1/meta. */
    public function toApi(): array
    {
        $announce = $this->get('announce_on') && trim((string) $this->get('announce_text', '')) !== ''
            ? ['text' => (string) $this->get('announce_text'), 'cta' => $this->get('announce_cta'), 'link' => $this->get('announce_link'), 'style' => (string) $this->get('announce_style', 'gold')]
            : null;

        return [
            'maintenance' => ['on' => (bool) $this->get('maintenance_on', false), 'message' => (string) $this->get('maintenance_text', 'The app is being updated. Please try again in a little while.')],
            'update' => [
                'min_version' => $this->minVersion(),
                'latest_version' => (string) $this->get('latest_version', $this->minVersion()),
                'url' => (string) $this->get('update_url', 'https://play.google.com/store/apps/details?id=com.astrorajnathjha.app'),
                'message' => (string) $this->get('update_text', 'A new version of the app is available with improvements and fixes.'),
            ],
            'announcement' => $announce,
            'home' => ['order' => $this->order(), 'hidden' => array_values(array_intersect((array) $this->get('home_hidden', []), array_keys(self::SECTIONS)))],
            'features' => collect(array_keys(self::FEATURES))->mapWithKeys(fn ($k) => [$k => $this->featureOn($k)])->all(),
            'support' => ['whatsapp' => $this->get('support_whatsapp'), 'phone' => $this->get('support_phone')],
        ];
    }
}
