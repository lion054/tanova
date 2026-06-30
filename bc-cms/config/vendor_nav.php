<?php

/**
 * Vendor sidebar navigation structure (Tanova portal).
 *
 * sections: ordered list of groups, each with a label and the menu keys that
 *   belong to it. Keys match what each module's getUserMenu() returns. Missing
 *   keys are skipped automatically.
 *
 * Rendering (themes/GoTrip/User/.../sidebar.blade.php):
 *   - 'overview'  renders flush at the top (no collapsible header).
 *   - working groups render as collapsible accordions (one open at a time;
 *     the group containing the active item opens by default).
 *   - 'settings'  is pinned at the bottom as a collapsible group.
 *
 * gated: maps a menu key → plan post_type that must be enabled. Gated items the
 *   vendor can't access yet render muted in an "upgrade" block.
 */
return [

    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'keys'  => ['dashboard', 'today'],
        ],
        'bookings' => [
            'label' => 'Bookings',
            'keys'  => ['vendor-bookings', 'checkin', 'waitlist', 'booking-history', 'enquiry'],
        ],
        'catalog' => [
            'label'  => 'Catalog',
            // Nested sub-groups so an expanded Catalog stays short and scannable.
            'groups' => [
                'stays'      => ['label' => 'Stays', 'keys' => ['hotel', 'space']],
                'activities' => ['label' => 'Activities', 'keys' => ['tour', 'event']],
                'transport'  => ['label' => 'Transport', 'keys' => ['car', 'boat', 'flight']],
                'access'     => ['label' => 'Access', 'keys' => ['visa']],
                'pricing'    => ['label' => 'Pricing & Add-ons', 'keys' => ['pricing_tiers', 'upsells']],
            ],
        ],
        'tanova' => [
            'label' => 'Tanova',
            'keys'  => ['marketplace', 'inbox'],
        ],
        'engage' => [
            'label' => 'Engage',
            'keys'  => ['loyalty', 'scheduled_messages', 'occasions', 'campaigns', 'coupon', 'news'],
        ],
        'insights' => [
            'label' => 'Insights',
            'keys'  => ['analytics', 'booking_report', 'tracking'],
        ],
        'finance' => [
            'label' => 'Finance',
            'keys'  => ['wallet', 'payout', 'tourpay'],
        ],
        'settings' => [
            'label' => 'Settings',
            'keys'  => ['go_live', 'subscription', 'api_keys', 'team', 'integrations', 'verification', '2fa', 'help'],
        ],
    ],

    'gated' => [],

];
