<?php

/**
 * Vendor sidebar navigation structure.
 *
 * sections: ordered list of sections, each with a label and the menu
 *   keys that belong to it. Keys match what each module's getUserMenu()
 *   returns as array keys.
 *
 * gated: maps a menu key to the plan post_type that must be enabled
 *   in the vendor's active subscription plan. Items whose gate fails
 *   are rendered muted with a lock inside an "upgrade" block.
 */
return [

    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'keys'  => ['dashboard'],
        ],
        'bookings' => [
            'label' => 'Bookings',
            'keys'  => ['vendor-bookings', 'booking-history'],
        ],
        'operations' => [
            'label' => 'Operations',
            // hotel always includes space as a child; car includes boat as a child
            'keys'  => ['hotel', 'space', 'car', 'boat', 'tour', 'flight', 'event', 'visa'],
        ],
        'marketing' => [
            'label' => 'Marketing',
            'keys'  => ['coupon', 'news'],
        ],
        'reports' => [
            'label' => 'Reports',
            'keys'  => ['booking_report', 'enquiry'],
        ],
        'finance' => [
            'label' => 'Finance',
            'keys'  => ['wallet', 'payout', 'tourpay'],
        ],
        'account' => [
            'label' => 'Account',
            'keys'  => ['subscription', 'verification', '2fa', 'team', 'chat'],
        ],
    ],

    /**
     * Gate map: menu key → plan post_type.
     * A vendor must have this post_type enabled in their plan to access
     * the module. Items without a gate entry are always shown (if permission
     * is satisfied).
     */
    'gated' => [],

];
