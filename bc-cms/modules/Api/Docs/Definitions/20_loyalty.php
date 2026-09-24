<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

Doc::schema('LoyaltyRule', S::obj([
    'enabled' => S::bool('Whether guests earn points at all.'),
    'spend_per_point' => S::num('How much a guest spends for one point. 10 means one point per 10 spent.', 10),
], ['enabled', 'spend_per_point']));

Doc::schema('LoyaltyTier', S::obj([
    'id' => S::int('Tier id', 3),
    'name' => S::str('Tier name', 'Gold'),
    'min_points' => S::int('Points a member needs to be in this tier.', 2000),
    'earn_multiplier' => S::num('Multiplies the points earned on later trips. 1.5 earns half as much again.', 1.5),
    'perks' => S::nullable(S::str('What the tier gives, in your words.', 'Priority support, free upgrades')),
], ['id', 'name', 'min_points', 'earn_multiplier']));

Doc::schema('LoyaltyMember', S::obj([
    'id' => S::int('Member id', 12),
    'email' => S::email('The guest\'s e-mail. One member per e-mail.'),
    'name' => S::nullable(S::str('Name', 'Ann Ray')),
    'points' => S::int('Current balance. Never below zero.', 520),
    'tier' => S::nullable(S::obj(['id' => S::int('', 2), 'name' => S::str('', 'Silver')])),
    'next_tier' => S::nullable(S::obj(['id' => S::int('', 3), 'name' => S::str('', 'Gold'), 'points_needed' => S::int('Points still to earn', 1480)])),
    'trips' => S::int('Trips that count (not cancelled or unpaid).', 4),
    'spent' => S::money('Money paid across those trips.', 1840.0),
    'last_trip' => S::nullable(S::date('Start date of the latest trip.')),
    'created_at' => S::dt('When the member was first created'),
], ['id', 'email', 'points']));

Doc::schema('LoyaltyMemberDetail', ['allOf' => [S::ref('LoyaltyMember'), S::obj([
    'history' => S::arr(S::ref('LoyaltyTransaction'), 'The 50 most recent point movements, newest first.'),
])]]);

Doc::schema('LoyaltyTransaction', S::obj([
    'id' => S::int('', 88),
    'points' => S::int('Positive when earned, negative when redeemed or taken.', 30),
    'type' => S::enum(['earn', 'redeem'], 'Which way the points moved.'),
    'reason' => S::nullable(S::str('Why. Trips completed say so.', 'Trip ABC12345 completed')),
    'booking_id' => S::nullable(S::int('The booking that earned it, when one did.', 41)),
    'created_at' => S::dt(),
], ['id', 'points', 'type']));

Doc::op('GET', '/loyalty/rule')->tag('Loyalty')->scope('loyalty:read')
    ->summary('Get the earning rule')
    ->description('How guests earn points. When a trip is marked **completed**, the guest earns one point for every `spend_per_point` they actually paid (rounded down), multiplied by the earn multiplier of the tier they are in. Once per booking, however many times completion is reported. A trip with nothing paid earns nothing.')
    ->returns(200, S::one('LoyaltyRule'));

Doc::op('PUT', '/loyalty/rule')->tag('Loyalty')->scope('loyalty:write')
    ->summary('Change the earning rule')
    ->description('Send only what you want to change. Turning the rule off stops new points; balances stay.')
    ->body(S::obj(['enabled' => S::bool('Turn earning on or off.', true), 'spend_per_point' => S::num('Between 0.01 and 100000.', 5)]), false)
    ->returns(200, S::one('LoyaltyRule'));

Doc::op('GET', '/loyalty/tiers')->tag('Loyalty')->scope('loyalty:read')
    ->summary('List tiers')->description('Lowest threshold first.')
    ->returns(200, S::many('LoyaltyTier'));

Doc::op('POST', '/loyalty/tiers')->tag('Loyalty')->scope('loyalty:write')
    ->summary('Create a tier')
    ->description('Members are moved into the right tier straight away.')
    ->body(S::obj([
        'name' => S::str('Tier name', 'Gold'),
        'min_points' => S::int('Threshold', 2000),
        'earn_multiplier' => S::num('Defaults to 1.', 1.5),
        'perks' => S::str('Optional', 'Priority support'),
    ], ['name', 'min_points']))
    ->returns(201, S::one('LoyaltyTier'));

Doc::op('PUT', '/loyalty/tiers/{id}')->tag('Loyalty')->scope('loyalty:write')
    ->summary('Change a tier')->description('Send only what changes. Members are re-tiered.')
    ->body(S::obj(['name' => S::str('', 'Gold'), 'min_points' => S::int('', 2500), 'earn_multiplier' => S::num('', 1.5), 'perks' => S::str('', 'Free upgrades')]), false)
    ->returns(200, S::one('LoyaltyTier'));

Doc::op('DELETE', '/loyalty/tiers/{id}')->tag('Loyalty')->scope('loyalty:write')
    ->summary('Delete a tier')->description('Members who were in it move to the tier their balance now earns. Points are never removed.')
    ->returns(204);

Doc::op('GET', '/loyalty/members')->tag('Loyalty')->scope('loyalty:read')
    ->summary('List members')
    ->description('The same search and filters as the Loyalty screen in the portal.')
    ->query([
        P::q('name or e-mail'),
        P::str('tier', 'A tier id, or `none` for members with no tier yet.', ['example' => 'none']),
        P::sort(['points' => 'most points', 'least' => 'fewest points', 'name' => 'name A to Z', 'recent' => 'recently active'], 'points'),
        ...P::paging(),
    ])
    ->returns(200, S::page('LoyaltyMember'));

Doc::op('GET', '/loyalty/members/{id}')->tag('Loyalty')->scope('loyalty:read')
    ->summary('Get a member with their history')
    ->returns(200, S::one('LoyaltyMemberDetail'));

Doc::op('POST', '/loyalty/adjustments')->tag('Loyalty')->scope('loyalty:write')
    ->summary('Give or take points')
    ->description("Creates the member if this e-mail has none. `points` is positive to give, negative to take; the balance never goes below zero, and the tier follows the new balance. Returns the member.\n\nUse it for goodwill, corrections and redemptions.")
    ->body(S::obj([
        'email' => S::email('The guest'),
        'name' => S::str('Used only when the member is created.', 'Ann Ray'),
        'points' => S::int('Not zero. Between -1,000,000 and 1,000,000.', 100),
        'reason' => S::str('Shown in their history.', 'Apology for the late pickup'),
    ], ['email', 'points']))
    ->returns(201, S::one('LoyaltyMember'));
