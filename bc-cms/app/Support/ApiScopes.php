<?php

namespace App\Support;

/**
 * What a secret API key may be limited to. A key with no scopes has full access (so every key
 * that exists today keeps working); with scopes it can only call endpoints in those areas.
 * `x:write` also allows `x:read`. Publishable keys stay read-only whatever their scopes.
 */
class ApiScopes
{
    /** area => what it covers */
    public const AREAS = [
        'services'   => 'Listings (tours, hotels, cars, boats, spaces, events, flights, visas), seats, options and add-ons',
        'bookings'   => 'Bookings and everything on them: travellers, payments, refunds, documents, check-in, guest-form links',
        'customers'  => 'Customer records (the vendor\'s CRM) and guest accounts',
        'loyalty'    => 'Loyalty rule, tiers, members and point adjustments',
        'waitlist'   => 'The waitlist, and telling guests that space opened',
        'invoices'   => 'Invoices, their lines and payments',
        'messages'   => 'Scheduled messages, campaigns and occasions',
        'analytics'  => 'Reports: summary, revenue, occupancy, trending',
        'marketplace'=> 'The Tanova AI marketplace switches',
        'suppliers'  => 'Operators and suppliers',
        'webhooks'   => 'Webhook endpoints and their deliveries',
        'planner'    => 'The Tanova trip planner and concierge',
    ];

    /** Every scope a key may be given, e.g. "bookings:read". */
    public static function all(): array
    {
        $out = [];
        foreach (array_keys(self::AREAS) as $a) {
            $out[] = "$a:read";
            $out[] = "$a:write";
        }

        return $out;
    }

    /**
     * May a key holding [$held] call an endpoint that needs [$needed]?
     * null / empty / "*" means full access; "x:write" includes "x:read"; "*:read" is every read.
     */
    public static function allows(?array $held, string $needed): bool
    {
        if (!$held || in_array('*', $held, true) || in_array('*:write', $held, true)) {
            return true;
        }
        [$area, $level] = array_pad(explode(':', $needed, 2), 2, 'read');
        if ($level === 'read' && in_array('*:read', $held, true)) {
            return true;
        }

        return in_array("$area:$level", $held, true) || ($level === 'read' && in_array("$area:write", $held, true));
    }
}
