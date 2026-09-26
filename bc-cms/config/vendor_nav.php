<?php

/**
 * Vendor sidebar navigation structure (Tanova portal).
 *
 * sections: ordered list of groups, each with a label and the menu keys that belong to it. Keys match what each module's getUserMenu()
 *   returns. Missing keys are skipped automatically.
 *
 * Products are grouped by Tanova OS (config/os_modules.php). A company sees the OS it operates; the others are offered once, at the
 * bottom of Products, as "Add ..." links to Plan & billing. A group with 'os' => key follows that rule.
 *
 * Rendering (themes/GoTrip/User/.../sidebar.blade.php):
 *   - 'overview'  renders flush at the top (no collapsible header).
 *   - working groups render as collapsible accordions (the group holding the active item opens by default).
 *   - 'settings'  is pinned at the bottom as a collapsible group.
 */
return [

    'sections' => [
        'overview' => [
            'label' => 'Home',
            'icon'  => 'icofont-home',
            'keys'  => ['dashboard', 'today', 'team', 'languages'],   // Languages sits on its own under Home, always in view   // Team (staff) stays in view at the top: adding employees is a first step, not a setting
        ],
        'bookings' => [
            'label' => 'Bookings',
            'icon'  => 'icofont-calendar',
            'keys'  => ['vendor-bookings', 'departures', 'checkin', 'waitlist', 'customers', 'enquiry'],
        ],
        'catalog' => [
            'label'  => 'Products',
            'icon'   => 'icofont-box',
            'groups' => [
                'stay'    => ['os' => 'stay',    'keys' => ['hotel', 'space']],
                'exp'     => ['os' => 'exp',     'keys' => ['tour']],
                'trans'   => ['os' => 'trans',   'keys' => ['car', 'boat']],
                'event'   => ['os' => 'event',   'keys' => ['event']],
                'airline' => ['os' => 'airline', 'keys' => ['flight']],
                'visa'    => ['os' => 'visa',    'keys' => ['visa']],
                'pricing' => ['label' => 'Pricing & Add-ons', 'keys' => ['pricing_tiers', 'upsells']],
            ],
        ],
        'tanova' => [
            'label' => 'Tanova AI',
            'icon'  => 'icofont-magic',
            'keys'  => ['tanova', 'marketplace', 'itineraries', 'concierge', 'inbox', 'meals', 'restaurants'],
        ],
        'engage' => [
            'label' => 'Marketing',
            'icon'  => 'icofont-megaphone-alt',
            'keys'  => ['loyalty', 'scheduled_messages', 'occasions', 'holidays', 'campaigns', 'coupon', 'news'],
        ],
        'insights' => [
            'label' => 'Reports',
            'icon'  => 'icofont-chart-bar-graph',
            'keys'  => ['analytics', 'shelves', 'booking_report', 'tracking'],
        ],
        'finance' => [
            'label' => 'Money',
            'icon'  => 'icofont-money',
            'keys'  => ['tourpay', 'statement', 'wallet', 'payout'],
        ],
        'settings' => [
            'label' => 'Company',
            'icon'  => 'icofont-gear',
            'keys'  => ['subscription', 'integrations', 'go_live', 'api_keys', 'api_docs', 'operators', 'ai_plan', 'verification', '2fa', 'help'],
        ],
    ],

    /** Entries not shown in the sidebar (still reachable by address): duplicates of a filter or a tab elsewhere. */
    'hidden' => ['catalogs', 'booking-history', 'my_plan'],

    /** Entries that belong to an OS but sit in another group: hidden when the company does not operate it. */
    'os_entries' => ['departures' => 'exp'],

    'gated' => [],

];
