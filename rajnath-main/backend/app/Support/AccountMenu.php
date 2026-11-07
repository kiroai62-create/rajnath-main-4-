<?php

namespace App\Support;

/**
 * Customer account sections, used by the account tabs and the header account dropdown.
 * primary: shown as a tab on wide screens (the rest go in the "More" dropdown);
 * header: also listed in the header account dropdown.
 */
class AccountMenu
{
    /** @var list<array{label: string, route: string, icon: string, primary: bool, header: bool}> */
    public const ITEMS = [
        ['label' => 'Overview', 'route' => 'account.dashboard', 'icon' => 'home', 'primary' => true, 'header' => true],
        ['label' => 'Bookings', 'route' => 'account.bookings', 'icon' => 'calendar', 'primary' => true, 'header' => true],
        ['label' => 'Puja', 'route' => 'account.puja.index', 'icon' => 'flame', 'primary' => true, 'header' => true],
        ['label' => 'Reports', 'route' => 'account.reports.index', 'icon' => 'doc', 'primary' => true, 'header' => true],
        ['label' => 'Orders', 'route' => 'account.orders', 'icon' => 'bag', 'primary' => true, 'header' => true],
        ['label' => 'Wallet', 'route' => 'account.wallet', 'icon' => 'wallet', 'primary' => true, 'header' => true],
        ['label' => 'Notifications', 'route' => 'account.notifications', 'icon' => 'bell', 'primary' => false, 'header' => true],
        ['label' => 'Reviews', 'route' => 'account.reviews.index', 'icon' => 'star', 'primary' => false, 'header' => false],
        ['label' => 'Palmistry', 'route' => 'account.palm.index', 'icon' => 'hand', 'primary' => false, 'header' => false],
        ['label' => 'Birth profiles', 'route' => 'account.birth-profiles.index', 'icon' => 'user-star', 'primary' => false, 'header' => false],
        ['label' => 'Profile & contact', 'route' => 'account.profile', 'icon' => 'user', 'primary' => false, 'header' => true],
        ['label' => 'Devices', 'route' => 'account.devices', 'icon' => 'devices', 'primary' => false, 'header' => false],
        ['label' => 'Help', 'route' => 'account.support.index', 'icon' => 'help', 'primary' => false, 'header' => true],
        ['label' => 'Join as astrologer', 'route' => 'account.apply', 'icon' => 'award', 'primary' => false, 'header' => false],
        ['label' => 'Privacy', 'route' => 'account.privacy', 'icon' => 'shield', 'primary' => false, 'header' => false],
    ];

    public static function active(string $route): bool
    {
        return request()->routeIs(str_replace('.index', '', $route).'*');
    }

    /** @return list<array{label: string, route: string, icon: string, primary: bool, header: bool}> */
    public static function header(): array
    {
        return array_values(array_filter(self::ITEMS, fn ($i) => $i['header']));
    }

    /** Two-letter initials for an avatar, e.g. "Rahul Kumar" -> "RK"; falls back to a person icon text. */
    public static function initials(?string $name): string
    {
        $out = collect(preg_split('/\s+/u', trim((string) $name)) ?: [])
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        return $out !== '' ? $out : '?';
    }
}
