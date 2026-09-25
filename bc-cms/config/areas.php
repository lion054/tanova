<?php

/**
 * Who may open which part of the portal. One list, read by App\Http\Middleware\AreaGuard and checked by tests/Feature/AreaAccessTest.php,
 * so a new screen cannot ship without someone deciding who it is for.
 *
 *   staff    the platform itself: the super admin (and any platform team the super admin creates). permission: dashboard_access.
 *            Employees of a vendor company are NOT this: they are company staff (config/staff_access.php) and never enter it.
 *   vendor   a business running on the portal. permission: dashboard_vendor_access
 *   account  any signed-in person (profile, own bookings, wallet, security).
 *
 * Patterns are Laravel request patterns (no leading slash). A path that matches nothing under admin/, vendor/ or user/ is refused by the
 * test, not silently allowed.
 */
return [
    'staff' => [
        'permission' => 'dashboard_access',
        'patterns'   => ['admin', 'admin/*'],
        // Endpoints that the vendor's own forms call (the media library behind every image picker). They keep their own permission checks.
        'open'       => ['admin/module/media', 'admin/module/media/*'],

        // Staff screens that have no permission check of their own: a staff role that lacks the permission is turned away here.
        // The first pattern that matches wins, so the specific ones come first.
        'permissions' => [
            'admin/module/user/wallet/report*'      => 'report_view',
            'admin/module/user/wallet/*'            => 'user_update',      // adding credit to a wallet is money
            'admin/module/user/plan-request*'       => 'user_update',
            'admin/module/core/module*'             => 'setting_update',
            'admin/module/core/tools*'              => 'setting_update',
            'admin/module/email/*'                  => 'setting_update',
            'admin/module/sms/*'                    => 'setting_update',
            'admin/module/report/statistic*'        => 'report_view',
            'admin/module/template/live/*'          => 'template_update',
            'admin/module/tour/availability*'       => 'tour_update',
            'admin/module/hotel/availability*'      => 'hotel_update',
            'admin/module/hotel/*/availability*'    => 'hotel_update',
            'admin/module/space/availability*'      => 'space_update',
            'admin/module/car/availability*'        => 'car_update',
            'admin/module/boat/availability*'       => 'boat_update',
            'admin/module/event/availability*'      => 'event_update',
            'admin/module/tourpay*'                 => 'tourpay_view',
        ],
    ],

    'vendor' => [
        'permission' => 'dashboard_vendor_access',
        'patterns'   => [
            'vendor/*',
            'user/dashboard',
            'user/tour', 'user/tour/*', 'user/hotel', 'user/hotel/*', 'user/space', 'user/space/*', 'user/car', 'user/car/*',
            'user/boat', 'user/boat/*', 'user/event', 'user/event/*', 'user/flight', 'user/flight/*', 'user/visa', 'user/visa/*',
            'user/coupon', 'user/coupon/*', 'user/tourpay', 'user/tourpay/*', 'user/tanova', 'user/tanova/*',
            'user/concierge', 'user/concierge/*', 'user/integrations', 'user/integrations/*',
        ],
    ],

    'account' => [
        'patterns' => [
            'user/profile', 'user/profile/*', 'user/booking', 'user/booking/*', 'user/bookings', 'user/booking-history', 'user/wallet', 'user/wallet/*',
            'user/plan', 'user/plan/*', 'user/my-plan', 'user/wishlist', 'user/wishlist/*', 'user/verification', 'user/verification/*',
            'user/2fa', 'user/two-factor-*', 'user/confirm-password', 'user/confirmed-password-status', 'user/permanently_delete',
            'user/upgrade-vendor', 'user/chat', 'user/chat/*', 'user/reset-password', 'user/reset-password/*', 'user/password', 'user/password/*',
        ],
    ],
];
