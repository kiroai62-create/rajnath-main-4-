<?php

namespace App\Support;

use App\Models\User;

/** Admin sidebar: grouped sections, each item [label, route, permission|null, icon]. Filtered by permission. */
class AdminMenu
{
    public const GROUPS = [
        'Overview' => [
            ['Dashboard', 'admin.dashboard', null, 'home'],
        ],
        'Customers' => [
            ['Enquiries', 'admin.enquiries.index', 'bookings.view', 'inbox'],
            ['Bookings & hours', 'admin.ops.bookings', 'bookings.view', 'calendar'],
            ['Support', 'admin.ops.tickets', 'support.view', 'chat'],
            ['Customers', 'admin.customers.index', 'customers.view', 'users'],
            ['Reviews', 'admin.reviews.index', 'content.review', 'star'],
            ['Privacy requests', 'admin.privacy.index', 'privacy.handle', 'shield'],
        ],
        'Fulfilment' => [
            ['Puja bookings', 'admin.ops.pujas', 'puja.fulfil', 'flame'],
            ['Priests & temples', 'admin.ops.suppliers', 'suppliers.manage', 'temple'],
            ['Reports to write', 'admin.ops.reports', 'reports.fulfil', 'doc'],
            ['Palmistry', 'admin.palm.index', 'reports.fulfil', 'hand'],
            ['Gem orders', 'admin.store.orders', 'puja.fulfil', 'truck'],
        ],
        'Catalogue & store' => [
            ['Catalogue', 'admin.catalogue.index', 'catalogue.view', 'grid'],
            ['Gem store', 'admin.store.products', 'catalogue.view', 'gem'],
            ['Coupons', 'admin.coupons.index', 'prices.manage', 'tag'],
        ],
        'Website' => [
            ['Website & home page', 'admin.website.edit', 'content.publish', 'layout'],
            ['Mobile app', 'admin.app.edit', 'content.publish', 'devices'],
            ['About & awards', 'admin.profile.edit', 'content.author', 'award'],
            ['Gallery', 'admin.gallery.index', 'media.manage', 'image'],
            ['Articles', 'admin.blog.index', 'content.author', 'pen'],
            ['Rashifal', 'admin.ops.horoscopes', 'content.author', 'sun'],
            ['FAQs', 'admin.faqs.index', 'content.author', 'help'],
            ['Legal pages', 'admin.legal.index', 'legal.manage', 'scale'],
        ],
        'Astrologers & team' => [
            ['Team', 'admin.team.index', 'team.view', 'users'],
            ['Practitioners', 'admin.practitioners.index', 'practitioners.view', 'user-star'],
            ['Astrologer applications', 'admin.applications.index', 'practitioners.view', 'doc'],
        ],
        'Money' => [
            ['Finance', 'admin.finance.index', 'finance.view', 'rupee'],
            ['Payments setup', 'admin.payments.settings', 'providers.manage', 'card'],
        ],
        'Settings' => [
            ['Settings', 'admin.settings.edit', 'settings.view', 'cog'],
            ['Email & SMS', 'admin.providers.edit', 'providers.manage', 'mail'],
            ['Push notifications', 'admin.push.edit', 'providers.manage', 'bell'],
            ['Calling (in-app, Zoom, Meet)', 'admin.calling.edit', 'providers.manage', 'phone'],
            ['AI assistant', 'admin.assistant.edit', 'assistant.knowledge', 'spark'],
        ],
        'System' => [
            ['Audit log', 'admin.audit.index', 'audit.view', 'list'],
            ['Operational health', 'admin.health', 'health.view', 'pulse'],
        ],
    ];

    /** Heroicons-style 24px outline paths. */
    public const ICONS = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        'inbox' => 'M3 13h5l1.5 3h5L16 13h5M5 5h14l2 8v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6z',
        'calendar' => 'M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
        'chat' => 'M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8a9.9 9.9 0 0 1-4-.8L3 20l1.3-3.9A7.6 7.6 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8z',
        'users' => 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm11 9v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
        'star' => 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z',
        'shield' => 'M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6z',
        'flame' => 'M12 21a6 6 0 0 0 6-6c0-4-3-6-4-10-2 2-3 4-3 6-1-1-2-2-2-4-2 2-3 5-3 8a6 6 0 0 0 6 6z',
        'temple' => 'M3 21h18M5 21V10m14 11V10M9 21v-6h6v6M12 3l9 7H3z',
        'doc' => 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8zm0 0v5h5M9 13h6M9 17h6',
        'hand' => 'M8 13V5.5a1.5 1.5 0 0 1 3 0V12m0-1V4.5a1.5 1.5 0 0 1 3 0V12m0-6.5a1.5 1.5 0 0 1 3 0V14a7 7 0 0 1-7 7 6 6 0 0 1-5-3l-2.5-4.5a1.5 1.5 0 0 1 2.6-1.5L8 14',
        'truck' => 'M3 6h11v10H3zm11 4h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
        'grid' => 'M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z',
        'gem' => 'M6 3h12l3 6-9 12L3 9zM3 9h18M9 3l3 18 3-18',
        'tag' => 'M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9zM7.5 7.5h.01',
        'layout' => 'M4 4h16v16H4zM4 9h16M9 9v11',
        'award' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm-3.5 4.5L7 22l5-2 5 2-1.5-2.5M8.5 13.5 7 22m8.5-8.5L17 22',
        'image' => 'M4 5h16v14H4zm0 11 5-5 4 4 2-2 5 5M15.5 9.5h.01',
        'pen' => 'm4 20 4-1L19 8l-3-3L5 16zM14 7l3 3',
        'sun' => 'M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0-15v2m0 16v2M4.2 4.2l1.4 1.4m12.8 12.8 1.4 1.4M2 12h2m16 0h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4',
        'help' => 'M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3m.1 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'scale' => 'M12 3v18M7 21h10M5 7h14M5 7l-3 7a3 3 0 0 0 6 0zm14 0-3 7a3 3 0 0 0 6 0z',
        'user-star' => 'M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-7 9v-1a4 4 0 0 1 4-4h4m7 0 1.2 2.4 2.6.4-1.9 1.8.5 2.6-2.4-1.3-2.4 1.3.5-2.6-1.9-1.8 2.6-.4z',
        'rupee' => 'M7 4h10M7 8h10M7 4c5 0 7 2 7 4s-2 5-7 5l7 7',
        'card' => 'M3 6h18v12H3zm0 4h18M7 15h3',
        'cog' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2-1.2L14.5 3h-5l-.4 2.6a7.6 7.6 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2 1.2l.4 2.6h5l.4-2.6a7.6 7.6 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z',
        'mail' => 'M3 6h18v12H3zm0 0 9 7 9-7',
        'bell' => 'M18 16V11a6 6 0 1 0-12 0v5l-2 2h16zm-8 4a2 2 0 0 0 4 0',
        'phone' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z',
        'spark' => 'M12 3v4m0 10v4M3 12h4m10 0h4M6 6l2.5 2.5m7 7L18 18M6 18l2.5-2.5m7-7L18 6',
        'list' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        'pulse' => 'M3 12h4l3-8 4 16 3-8h4',
        'practice' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 9a8 8 0 0 1 16 0',
        'logout' => 'M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 16l-4-4 4-4M6 12h10',
        'external' => 'M14 4h6v6m0-6L10 14M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
        'wallet' => 'M4 7h15a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zm0 0V6a2 2 0 0 1 2-2h10v3M16 13.5h.01',
        'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3c2.5 2.5 3.8 5.5 3.8 9S14.5 18.5 12 21c-2.5-2.5-3.8-5.5-3.8-9S9.5 5.5 12 3z',
        'bag' => 'M6 8h12l-1 12H7zm3 0V6a3 3 0 0 1 6 0v2',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 9a8 8 0 0 1 16 0',
        'devices' => 'M4 5h12v9H4zm-2 12h16M18 9h4v10h-4z',
        'more' => 'M5 12h.01M12 12h.01M19 12h.01',
        'check' => 'm5 12 4 4 10-10',
    ];

    /** @return array<string, list<array{0: string, 1: string, 2: ?string, 3: string}>> */
    public static function for(User $u): array
    {
        $groups = self::GROUPS;
        if ($u->hasRole('practitioner')) {
            $groups['Overview'][] = ['My practice', 'admin.practice.show', null, 'practice'];
        }
        $out = [];
        foreach ($groups as $g => $items) {
            $ok = array_values(array_filter($items, fn ($i) => $i[2] === null || $u->can($i[2])));
            if ($ok) {
                $out[$g] = $ok;
            }
        }

        return $out;
    }

    public static function active(string $route): bool
    {
        return request()->routeIs(str_replace('.index', '', $route).'*');
    }
}
