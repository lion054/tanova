<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

Doc::schema('CrmCustomer', S::obj([
    'id' => S::int('', 12),
    'first_name' => S::nullable(S::str('', 'Ann')), 'last_name' => S::nullable(S::str('', 'Ray')),
    'name' => S::str('First and last name together.', 'Ann Ray'),
    'email' => S::nullable(S::email('Always lower case.')),
    'phone' => S::nullable(S::str('Normalised: digits with a leading +.', '+263770000000')),
    'date_of_birth' => S::nullable(S::date('Used for birthday greetings.', '1990-06-15')),
    'nationality' => S::nullable(S::str('', 'Zimbabwean')),
    'passport_number' => S::nullable(S::str('Sensitive. Only shown to keys with the customers scope.', 'AB123456')),
    'notes' => S::nullable(S::str('Your private notes.', 'Prefers early trips')),
    'tags' => S::arr(S::str('', 'vip'), 'Free-text labels you choose.'),
    'bookings_count' => S::int('Bookings that count, worked out from your bookings.', 3),
    'total_spent' => S::money('Money spent with you.', 940.0),
    'first_booking_at' => S::nullable(S::dt()), 'last_booking_at' => S::nullable(S::dt()),
    'source' => S::enum(['manual', 'booking', 'sync'], 'Where the record came from.'),
    'created_at' => S::dt(),
], ['id', 'name']));

Doc::schema('CrmBookingSummary', S::obj([
    'code' => S::str('', 'ABC12345'), 'status' => S::str('', 'confirmed'),
    'service' => S::obj(['type' => S::str('', 'tour'), 'id' => S::int('', 123), 'title' => S::nullable(S::str('', 'Tandem Gorge Swing'))]),
    'start_date' => S::nullable(S::date()), 'guests' => S::int('', 2), 'total' => S::money('', 400.0), 'paid' => S::money('', 400.0),
]));

Doc::op('GET', '/crm/customers')->tag('Customers')->scope('customers:read')
    ->summary('List customers')
    ->description('Everyone who has booked with you, plus anyone you added by hand. The list follows your bookings: run `POST /crm/customers/sync` after importing bookings from elsewhere.')
    ->query([
        P::q('first name, last name, e-mail or phone'),
        P::enum('type', ['repeat', 'once', 'none'], '`repeat`: booked more than once. `once`: exactly once. `none`: added but never booked.'),
        P::str('tag', 'Only customers with this tag.', ['example' => 'vip']),
        P::sort(['recent' => 'last booked', 'name' => 'name A to Z', 'spent' => 'most spent', 'bookings' => 'most bookings', 'newest' => 'newest added'], 'recent'),
        ...P::paging(),
    ])
    ->returns(200, S::page('CrmCustomer'));

Doc::op('GET', '/crm/customers/{id}')->tag('Customers')->scope('customers:read')
    ->summary('Get a customer with their latest bookings')
    ->returns(200, S::obj(['data' => ['allOf' => [S::ref('CrmCustomer'), S::obj(['recent_bookings' => S::arr(S::ref('CrmBookingSummary'), 'Up to five, newest first.')])]]]));

Doc::op('GET', '/crm/customers/{id}/bookings')->tag('Customers')->scope('customers:read')
    ->summary('List a customer\'s bookings')
    ->description('Matched by e-mail, or by the guest account when they booked while signed in.')
    ->query(P::paging())
    ->returns(200, S::page('CrmBookingSummary'));

$fields = [
    'first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'email' => S::email(), 'phone' => S::str('', '+263 77 000 0000'),
    'date_of_birth' => S::date('Not in the future.', '1990-06-15'), 'nationality' => S::str('', 'Zimbabwean'), 'passport_number' => S::str('', 'AB123456'),
    'notes' => S::str('', 'Prefers early trips'), 'tags' => S::arr(S::str('', 'vip'), 'Replaces the tags. Up to 30.'),
];

Doc::op('POST', '/crm/customers')->tag('Customers')->scope('customers:write')
    ->summary('Add a customer')
    ->description('For someone who has not booked yet. A record needs an **e-mail or a phone number**, or it could never be matched to a booking later.')
    ->body(S::obj($fields))
    ->returns(201, S::one('CrmCustomer'));

Doc::op('PUT', '/crm/customers/{id}')->tag('Customers')->scope('customers:write')
    ->summary('Change a customer')
    ->description('Send only what changes. `tags` replaces the whole list. Clearing both e-mail and phone is refused.')
    ->body(S::obj($fields), false)
    ->returns(200, S::one('CrmCustomer'));

Doc::op('DELETE', '/crm/customers/{id}')->tag('Customers')->scope('customers:write')
    ->summary('Delete a customer')->description('Removes the record only. Their bookings are not touched.')->returns(204);

Doc::op('POST', '/crm/customers/sync')->tag('Customers')->scope('customers:write')
    ->summary('Rebuild customers from your bookings')
    ->description("Matches bookings to customers by e-mail, then phone, creates the ones that do not exist, and recalculates each one's bookings and spend from scratch. A booking with neither an e-mail nor a phone cannot identify anyone and is skipped. Safe to repeat.")
    ->returns(200, S::obj(['data' => S::obj(['created' => S::int('', 4), 'updated' => S::int('', 9), 'skipped' => S::int('Bookings with no e-mail or phone.', 1)])]));
