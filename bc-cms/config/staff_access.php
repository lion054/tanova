<?php

/**
 * What the staff of a vendor company may open. Staff are employees of one company: they see that company's data only, never the
 * platform's admin area, and never the owner-only pages. The owner ticks which modules each person gets (Team screen).
 *
 * Deny by default: a staff member can open a page only if it matches a module they have, "always", or "personal" (their own account).
 * tests/Feature/Api/StaffAccessTest.php fails if any portal page is in none of these lists, so a new page must be placed.
 */
return [
    'modules' => [
        'bookings'  => ['label' => 'Bookings and check-in', 'patterns' => ['user/bookings', 'user/booking-history', 'user/booking/*', 'vendor/bookings/*', 'vendor/checkin', 'vendor/checkin/*', 'vendor/departures', 'vendor/departures/*', 'vendor/waitlist', 'vendor/waitlist/*', 'vendor/enquiry-report', 'vendor/enquiry-report/*']],
        'customers' => ['label' => 'Customers, loyalty and occasions', 'patterns' => ['vendor/customers', 'vendor/customers/*', 'vendor/loyalty', 'vendor/loyalty/*', 'vendor/occasions', 'vendor/occasions/*']],
        'catalog'   => ['label' => 'Catalogue and pricing', 'patterns' => [
            'user/tour', 'user/tour/*', 'user/hotel', 'user/hotel/*', 'user/space', 'user/space/*', 'user/car', 'user/car/*', 'user/boat', 'user/boat/*',
            'user/event', 'user/event/*', 'user/flight', 'user/flight/*', 'user/visa', 'user/visa/*',
            'vendor/meals', 'vendor/meals/*', 'vendor/restaurants', 'vendor/restaurants/*', 'vendor/catalogs', 'vendor/catalogs/*', 'vendor/pricing-tiers', 'vendor/pricing-tiers/*',
            'vendor/upsells', 'vendor/upsells/*', 'vendor/marketplace', 'vendor/marketplace/*', 'vendor/itineraries', 'vendor/itineraries/*']],
        'finance'   => ['label' => 'Finance: TourPay, invoices, statement', 'patterns' => ['user/tourpay', 'user/tourpay/*', 'vendor/invoices', 'vendor/invoices/*']],
        'marketing' => ['label' => 'Marketing: campaigns, messages, coupons, news', 'patterns' => ['vendor/campaigns', 'vendor/campaigns/*', 'vendor/scheduled-messages', 'vendor/scheduled-messages/*', 'vendor/holidays', 'vendor/holidays/*', 'user/coupon', 'user/coupon/*', 'vendor/news', 'vendor/news/*']],
        'insights'  => ['label' => 'Reports and analytics', 'patterns' => ['vendor/analytics', 'vendor/analytics/*', 'vendor/trending', 'vendor/trending/*', 'vendor/booking-report', 'vendor/booking-report/*']],
        'tanova'    => ['label' => 'Tanova trips, inbox and concierge', 'patterns' => ['user/tanova', 'user/tanova/*', 'user/concierge', 'user/concierge/*', 'vendor/inbox', 'vendor/inbox/*', 'vendor/ai-requests', 'vendor/ai-requests/*']],
    ],

    // Every staff member may open these (the company dashboard and help).
    'always' => ['user/dashboard', 'vendor/today', 'vendor/help'],

    // Only the company owner: who works here, the plan, keys, integrations, money leaving to the owner, the owner's own profile settings.
    'owner_only' => [
        'vendor/team', 'vendor/team/*', 'vendor/subscription', 'vendor/subscription/*', 'vendor/api-keys', 'vendor/api-keys/*', 'vendor/api-docs', 'vendor/api-docs/*',
        'user/integrations', 'user/integrations/*', 'vendor/go-live', 'vendor/ai-plan', 'vendor/ai-plan/*', 'vendor/operators', 'vendor/operators/*', 'vendor/payouts', 'vendor/payouts/*',
        'user/wallet', 'user/wallet/*', 'user/plan', 'user/plan/*', 'user/my-plan', 'user/upgrade-vendor',
    ],

    // A staff member's own account: their profile, password, two-factor. Never the company's.
    'personal' => ['user/profile', 'user/profile/*', 'user/verification', 'user/verification/*', 'user/2fa', 'user/two-factor-*', 'user/confirm-password', 'user/confirmed-password-status', 'user/password', 'user/password/*', 'user/permanently_delete', 'user/chat', 'user/chat/*', 'user/wishlist', 'user/wishlist/*'],
];
