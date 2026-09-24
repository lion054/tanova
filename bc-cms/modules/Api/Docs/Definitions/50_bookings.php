<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

$statuses = ['draft', 'unpaid', 'processing', 'partial_payment', 'paid', 'confirmed', 'completed', 'cancelled'];

// ── Shared shapes ────────────────────────────────────────────────────────────

Doc::schema('Money', S::obj([
    'currency' => S::str('ISO currency code', 'USD'),
    'total' => S::money('What the booking costs, add-ons and fees included.', 670.0),
    'paid' => S::money('Received so far.', 200.0),
    'balance' => S::money('Still owing: total minus paid, never below zero.', 470.0),
    'addons_total' => S::money('The part of the total that is add-ons.', 100.0),
], ['currency', 'total', 'paid', 'balance']));

Doc::schema('BookingRecord', S::obj([
    'id' => S::int('', 41),
    'code' => S::str('The booking code. Use it in every booking URL.', '6cb76db43484b32344682bbf4d4aa889'),
    'object_model' => S::enum(['tour', 'hotel', 'car', 'boat', 'event', 'space', 'flight', 'tanova_trip'], 'The kind of thing booked.'),
    'object_id' => S::int('Its id', 123),
    'status' => S::enum($statuses, 'See "Booking statuses" in the guides.'),
    'total' => S::str('Decimal string, for example "670.00".', '670.00'),
    'paid' => S::nullable(S::str('Decimal string.', '200.00')),
    'currency' => S::nullable(S::str('', 'USD')),
    'total_guests' => S::int('', 3),
    'start_date' => S::nullable(S::str('Date and time the trip starts.', '2026-11-10 09:00:00')),
    'end_date' => S::nullable(S::str('', '2026-11-10 17:00:00')),
    'first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'email' => S::email(), 'phone' => S::nullable(S::str('', '+263770000000')),
    'customer_notes' => S::nullable(S::str('', '')),
    'created_at' => S::dt(), 'updated_at' => S::dt(),
], ['id', 'code', 'status'], 'The booking as stored. More fields than listed may be present; rely on the ones documented here. For a cleaner, stable shape use `GET /bookings/{code}/overview`.'));

Doc::schema('BookingOverview', S::obj([
    'code' => S::str('', 'ABC12345'),
    'status' => S::enum($statuses),
    'service' => S::obj(['type' => S::str('', 'tour'), 'id' => S::int('', 123), 'title' => S::nullable(S::str('', 'Tandem Gorge Swing'))]),
    'tier' => S::nullable(S::str('The option chosen, when the tour has options.', 'Signature')),
    'start_date' => S::nullable(S::date()), 'end_date' => S::nullable(S::date()),
    'guests' => S::int('', 3),
    'customer' => S::obj(['name' => S::str('', 'Ann Ray'), 'email' => S::email(), 'phone' => S::nullable(S::str()), 'account_id' => S::nullable(S::int('The guest account, when they booked signed in.', 12))]),
    'customer_notes' => S::nullable(S::str()),
    'money' => S::ref('Money'),
    'source' => S::nullable(S::str('Where it came from, for example `vendor_app`.', 'vendor_app')),
    'check_in' => S::nullable(S::ref('CheckIn')),
    'counts' => S::obj(['travellers' => S::int('', 3), 'documents' => S::int('', 1), 'timeline' => S::int('', 6), 'addons' => S::int('', 1)]),
    'next_statuses' => S::arr(S::str('', 'confirmed'), 'Where the booking may go from here.'),
    'created_at' => S::dt(),
], ['code', 'status', 'service', 'money']));

Doc::schema('TimelineEntry', S::obj([
    'id' => S::int('', 7),
    'channel' => S::enum(['note', 'email', 'whatsapp', 'sms', 'call']),
    'direction' => S::enum(['out', 'in', 'internal'], '`out`: you told them. `in`: they told you. `internal`: a private note.'),
    'subject' => S::nullable(S::str('', 'Status: unpaid → confirmed')),
    'body' => S::str('', 'Changed by the vendor.'),
    'created_at' => S::dt(),
]));

Doc::schema('Traveller', S::obj([
    'id' => S::int('', 5), 'name' => S::str('', 'Tom Ray'), 'is_lead' => S::bool('The person the booking is for.', false),
    'date_of_birth' => S::nullable(S::date('', '2015-03-02')), 'nationality' => S::nullable(S::str('', 'Zimbabwean')),
    'passport_number' => S::nullable(S::str()), 'dietary' => S::nullable(S::str('', 'Vegetarian')), 'notes' => S::nullable(S::str()),
    'source' => S::enum(['vendor', 'customer'], 'Who filled it in: you, or the guest through the traveller-details form.'),
], ['id', 'name']));

Doc::schema('PaymentPlanRow', S::obj([
    'id' => S::int('', 3), 'label' => S::str('', 'Deposit'), 'amount' => S::money('', 200.0), 'due_date' => S::nullable(S::date('', '2026-10-27')),
    'status' => S::enum(['pending', 'paid', 'waived']), 'paid_at' => S::nullable(S::dt()), 'overdue' => S::bool('Pending and past its due date.', false),
]));

Doc::schema('LedgerEntry', S::obj([
    'id' => S::int('', 9), 'type' => S::enum(['payment', 'refund']), 'amount' => S::money('', 200.0),
    'method' => S::enum(['paypal', 'card', 'bank', 'cash', 'other']), 'reference' => S::nullable(S::str('Your reference, for example a bank transaction id.', 'TXN-8841')),
    'note' => S::nullable(S::str()), 'plan_id' => S::nullable(S::int('The schedule row it went towards.', 3)), 'occurred_at' => S::dt(),
], ['id', 'type', 'amount', 'method']));

Doc::schema('BookingPayments', ['allOf' => [S::ref('Money'), S::obj([
    'plan' => S::arr(S::ref('PaymentPlanRow'), 'The payment schedule, in order.'),
    'ledger' => S::arr(S::ref('LedgerEntry'), 'Every payment and refund, newest first.'),
])]]);

Doc::schema('BookingDocument', S::obj([
    'id' => S::int('', 2), 'name' => S::str('', 'Entry voucher'), 'url' => S::nullable(S::url('', 'https://example.com/voucher.pdf')),
    'visible_to_customer' => S::bool('The guest sees it in their trip brief.', true),
]));

Doc::schema('BookingAddon', S::obj([
    'id' => S::int('', 4), 'upsell_id' => S::nullable(S::int('The add-on in your catalogue.', 2)), 'name' => S::str('', 'Photo package'),
    'unit_price' => S::money('The price that applied to this booking\'s own service.', 50.0), 'qty' => S::int('', 2), 'total' => S::money('Price for the party and days, times qty.', 100.0),
]));

Doc::schema('CheckIn', S::obj([
    'status' => S::enum(['expected', 'checked_in', 'checked_out', 'no_show']),
    'checkin_at' => S::nullable(S::dt()), 'checkout_at' => S::nullable(S::dt()), 'guests_present' => S::nullable(S::int('How many turned up.', 3)), 'notes' => S::nullable(S::str()),
], ['status']));

// ── Legacy: list, create, read ───────────────────────────────────────────────

Doc::op('GET', '/bookings')->tag('Bookings')->scope('bookings:read')
    ->summary('List bookings')
    ->description('Your bookings, newest first. Search and filter the same way as the Bookings screen in the portal.')
    ->legacy('The list is a framework paginator: the rows are in `data.data`, and `data` also carries `current_page`, `last_page`, `per_page` and `total`.')
    ->query([
        P::q('guest name, e-mail, phone, booking code or number'),
        P::enum('status', $statuses, 'Only this status.'),
        P::enum('service', ['tour', 'hotel', 'car', 'boat', 'event', 'space', 'flight'], 'Only this kind of thing (`object_model` also works).'),
        P::date('from', 'Booked on or after this date.'), P::date('to', 'Booked on or before this date.'),
        P::date('trip_from', 'Trip starts on or after this date.'), P::date('trip_to', 'Trip starts on or before this date.'),
        P::sort(['newest' => 'newest first', 'oldest' => 'oldest first', 'trip' => 'trip date, soonest', 'trip_late' => 'trip date, latest', 'amount' => 'biggest amount'], 'newest'),
        P::page(), P::int('per_page', 'Rows per page, up to 100.', ['default' => 15]),
    ])
    ->returns(200, S::obj(['data' => S::obj([
        'current_page' => S::int('', 1), 'data' => S::arr(S::ref('BookingRecord')), 'per_page' => S::int('', 15), 'total' => S::int('', 120), 'last_page' => S::int('', 8),
    ])]));

Doc::op('GET', '/bookings/{code}')->tag('Bookings')->scope('bookings:read')
    ->summary('Get a booking (as stored)')
    ->description('The booking record with its payment record. For a cleaner shape with the money worked out, use `/bookings/{code}/overview`.')
    ->legacy('Returns the stored record; fields beyond the documented ones may appear.')
    ->returns(200, S::one('BookingRecord'));

Doc::op('POST', '/bookings')->tag('Bookings')->scope('bookings:write')
    ->summary('Create a booking')
    ->description("Books one of **your** tours, hotels, cars, boats or events on behalf of a guest who has no account. The price, fees and seats are worked out here; you send choices, never prices.\n\nThe booking starts `unpaid`. Send the guest to `checkout_url` to pay, or record money you received yourself with `POST /bookings/{code}/payments`.\n\nFor tours: the day must be running and have enough seats (`sold_out` says how many are left), and `tier_id` prices the whole party by one of the tour's options.\n\nSend an `Idempotency-Key` so a retry never books twice.")
    ->body(S::obj([
        'service_type' => S::enum(['tour', 'hotel', 'car', 'boat', 'event'], 'What is being booked.'),
        'service_id' => S::int('Its id. Must be yours.', 123),
        'start_date' => S::date('Today or later.', '2026-11-10'),
        'end_date' => S::date('For stays and multi-day services.', '2026-11-12'),
        'adults' => S::int('At least 1.', 2), 'children' => S::int('', 1),
        'tier_id' => S::int('Tours only: the option to book (see `/services/tours/{id}/tiers`).', 2),
        'first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'email' => S::email(), 'phone' => S::str('', '+263770000000'),
        'notes' => S::str('The guest\'s request.', 'Vegetarian lunch'),
        'extra_price' => ['type' => 'object', 'description' => 'Optional extras defined on the service, keyed as the service defines them.', 'additionalProperties' => true],
    ], ['service_type', 'service_id', 'start_date', 'adults', 'first_name', 'last_name', 'email']))
    ->errors([
        'invalid_type' => [422, 'That service type is not bookable.'],
        'not_bookable' => [409, 'The service is switched off for booking.'],
        'not_available' => [409, 'The tour is not running on that day.'],
        'sold_out' => [409, 'Not enough seats that day. The error carries `seats_left`.'],
        'tier_party_size' => [422, 'The chosen option does not take that many guests. Carries `min_guests` and `max_guests`.'],
        'tier_not_found' => [404, 'That option is off or belongs to another tour.'],
        'booking_validation' => [422, 'The service refused the booking; the message says why.'],
    ])
    ->returns(201, S::obj(['data' => S::obj([
        'booking_code' => S::str('', '6cb76db43484b32344682bbf4d4aa889'), 'status' => S::str('', 'unpaid'), 'total' => S::money('', 670.0), 'checkout_url' => S::url('Where the guest pays.', 'https://portal.example.com/booking/6cb76.../checkout'),
    ])]));

Doc::op('PATCH', '/bookings/{code}/status')->tag('Bookings')->scope('bookings:write')
    ->summary('Confirm, complete or cancel a booking')
    ->description("Runs the same flow as the portal, so all of this happens: the change is written to the booking's timeline (with your `reason`), the guest and you are e-mailed, webhooks fire, **loyalty points are awarded when a paid trip is completed**, and **waiting guests are told when a cancellation frees seats**.\n\nAllowed moves: unpaid or processing or partial payment → confirmed or cancelled; paid → confirmed, completed or cancelled; confirmed → completed or cancelled. `next_statuses` on the overview lists what is possible now.")
    ->body(S::obj(['status' => S::enum(['confirmed', 'completed', 'cancelled']), 'reason' => S::str('Written to the timeline. Optional.', 'Guest asked to cancel')], ['status']))
    ->errors(['invalid_transition' => [422, 'That move is not possible from the current status.']])
    ->returns(200, S::obj(['message' => S::str('', 'Booking status updated.'), 'data' => S::obj(['code' => S::str('', 'ABC12345'), 'status' => S::str('', 'confirmed'), 'next_statuses' => S::arr(S::str('', 'completed'))])]));

// ── Operations on a booking ──────────────────────────────────────────────────

Doc::op('GET', '/bookings/{code}/overview')->tag('Bookings')->scope('bookings:read')
    ->summary('Get a booking, cleanly')
    ->description('One call for everything you show on a booking screen: what, when, who, the money worked out (`balance` included), check-in, counts, and where the booking may go next.')
    ->returns(200, S::one('BookingOverview'));

Doc::op('GET', '/bookings/{code}/timeline')->tag('Bookings')->scope('bookings:read')
    ->summary('The booking\'s timeline')
    ->description('Everything that happened, newest first: status changes, trip briefs sent, and the notes and conversations you recorded.')
    ->query(P::paging())->returns(200, S::page('TimelineEntry'));

Doc::op('POST', '/bookings/{code}/timeline')->tag('Bookings')->scope('bookings:write')
    ->summary('Add a note or record a conversation')
    ->description('Keeps a record only; **nothing is sent to the guest**. Use it for a private `note`, or to log a `call`, `whatsapp`, `sms` or `email` you had. A note is always internal.')
    ->body(S::obj([
        'channel' => S::enum(['note', 'email', 'whatsapp', 'sms', 'call'], '', 'call'),
        'direction' => S::enum(['out', 'in', 'internal'], 'Defaults to `out`; a note is always `internal`.', 'in'),
        'subject' => S::str('', 'Asked about pickup'), 'body' => S::str('', 'Wants pickup at the hotel.'),
    ], ['channel', 'body']))
    ->returns(201, S::one('TimelineEntry'));

Doc::op('GET', '/bookings/{code}/travellers')->tag('Bookings')->scope('bookings:read')
    ->summary('Who is travelling')
    ->description('The travellers on the booking. `meta.expected` is the number of guests booked and `meta.filled` how many have details, so you can tell "3 of 4 filled in". The guest can also fill these in themselves through the link from `GET /bookings/{code}/guest-form`.')
    ->returns(200, S::obj(['data' => S::arr(S::ref('Traveller')), 'meta' => S::obj(['expected' => S::int('', 4), 'filled' => S::int('', 3)])]));

$traveller = ['name' => S::str('', 'Tom Ray'), 'is_lead' => S::bool('', false), 'date_of_birth' => S::date('Not in the future.', '2015-03-02'), 'nationality' => S::str('', 'Zimbabwean'), 'passport_number' => S::str(), 'dietary' => S::str('', 'Vegetarian'), 'notes' => S::str()];
Doc::op('POST', '/bookings/{code}/travellers')->tag('Bookings')->scope('bookings:write')->summary('Add a traveller')
    ->description('Dates of birth also feed birthday greetings (see `POST /occasions/import`).')->body(S::obj($traveller, ['name']))->returns(201, S::one('Traveller'));
Doc::op('PUT', '/bookings/{code}/travellers/{id}')->tag('Bookings')->scope('bookings:write')->summary('Change a traveller')
    ->description('Send only what changes.')->body(S::obj($traveller), false)->returns(200, S::one('Traveller'));
Doc::op('DELETE', '/bookings/{code}/travellers/{id}')->tag('Bookings')->scope('bookings:write')->summary('Remove a traveller')->returns(204);

Doc::op('GET', '/bookings/{code}/payments')->tag('Bookings')->scope('bookings:read')
    ->summary('Payments: schedule, ledger and balance')
    ->description('What has been paid and when, what is due and when, and the balance. Use this to reconcile against your bank.')
    ->returns(200, S::one('BookingPayments'));

Doc::op('PUT', '/bookings/{code}/payment-plan')->tag('Bookings')->scope('bookings:write')
    ->summary('Set the payment schedule')
    ->description("Replaces the **unpaid** part of the schedule; rows already paid are kept and count towards the total.\n\n`full`: everything due before the trip. `deposit`: `percent`% now and the rest `balance_days` days before the trip. `split`: `parts` equal instalments a month apart, the last no later than `balance_days` before the trip. The last row takes any rounding.")
    ->body(S::obj([
        'mode' => S::enum(['full', 'deposit', 'split'], '', 'deposit'), 'percent' => S::num('Deposit only: 1 to 99.', 30, ['minimum' => 1]),
        'parts' => S::int('Split only: 2 to 12.', 3), 'balance_days' => S::int('Days before the trip that the balance is due. 0 to 365.', 14),
    ], ['mode']))
    ->returns(200, S::one('BookingPayments'));

Doc::op('DELETE', '/bookings/{code}/payment-plan')->tag('Bookings')->scope('bookings:write')->summary('Clear the unpaid schedule')->description('Rows already paid stay.')->returns(204);

Doc::op('POST', '/bookings/{code}/payments')->tag('Bookings')->scope('bookings:write')
    ->summary('Record a payment you received')
    ->description("For money that came in outside the portal: a bank transfer, cash, a card machine. It counts towards the schedule (the row you name, else the earliest unpaid ones), moves `paid`, and **the booking's status follows the money** (partial payment, then paid).\n\nA payment above the balance is refused (`exceeds_balance`) unless you send `allow_overpayment: true`. **Send an `Idempotency-Key`**: a retried request then records the money once.")
    ->body(S::obj([
        'amount' => S::money('More than 0.', 200.0), 'method' => S::enum(['paypal', 'card', 'bank', 'cash', 'other'], '', 'bank'),
        'reference' => S::str('Your reference, e.g. a bank transaction id.', 'TXN-8841'), 'note' => S::str(), 'plan_id' => S::int('The schedule row this settles.', 3),
        'allow_overpayment' => S::bool('Accept more than the balance.', false),
    ], ['amount', 'method']))
    ->errors(['exceeds_balance' => [422, 'The amount is more than what is still owing.']])
    ->returns(201, S::obj(['data' => ['allOf' => [S::ref('LedgerEntry'), S::obj(['booking' => ['allOf' => [S::obj(['status' => S::str('', 'paid')]), S::ref('Money')]]])]]]));

Doc::op('POST', '/bookings/{code}/refunds')->tag('Bookings')->scope('bookings:write')
    ->summary('Record a refund')
    ->description("**This only keeps the record.** Return the money through PayPal or your bank yourself. A refund cannot be more than has been paid. With `cancel: true`, refunding everything paid on a booking that is not completed also cancels it.")
    ->body(S::obj([
        'amount' => S::money('', 100.0), 'method' => S::enum(['paypal', 'card', 'bank', 'cash', 'other'], '', 'bank'),
        'reference' => S::str(), 'note' => S::str('', 'Cancelled by the guest'), 'cancel' => S::bool('Cancel the booking when everything paid is refunded.', false),
    ], ['amount', 'method']))
    ->errors(['refund_not_allowed' => [422, 'More than was paid, or not more than nothing.']])
    ->returns(201, S::obj(['data' => ['allOf' => [S::ref('LedgerEntry'), S::obj(['booking' => ['allOf' => [S::obj(['status' => S::str('', 'confirmed')]), S::ref('Money')]]])]]]));

Doc::op('GET', '/bookings/{code}/documents')->tag('Bookings')->scope('bookings:read')->summary('Documents on a booking')->returns(200, S::many('BookingDocument'));
Doc::op('POST', '/bookings/{code}/documents')->tag('Bookings')->scope('bookings:write')
    ->summary('Add a document by link')
    ->description('A voucher, ticket or confirmation the guest should have. Give an `https` link to the file. Documents marked `visible_to_customer` are included in the trip brief.')
    ->body(S::obj(['name' => S::str('', 'Entry voucher'), 'url' => S::url('An https link.', 'https://example.com/voucher.pdf'), 'visible_to_customer' => S::bool('Default true.', true)], ['name', 'url']))
    ->returns(201, S::one('BookingDocument'));
Doc::op('DELETE', '/bookings/{code}/documents/{id}')->tag('Bookings')->scope('bookings:write')->summary('Remove a document')->returns(204);

Doc::op('POST', '/bookings/{code}/trip-brief')->tag('Bookings')->scope('bookings:write')
    ->summary('E-mail the guest their trip brief')
    ->description('Sends the trip, the reference, what is still to pay, their visible documents and the traveller-details link, and records it in the timeline. **A test key sends nothing**: it answers with `simulated: true` and the address it would have used.')
    ->body(S::obj(['note' => S::str('A personal line added to the e-mail.', 'Meet at the gate at 8.30.')]), false)
    ->errors(['no_email' => [422, 'The booking has no valid e-mail address.']])
    ->returns(200, S::obj(['data' => S::obj(['sent_to' => S::email(), 'documents_linked' => S::int('', 1)])]));

Doc::op('GET', '/bookings/{code}/guest-form')->tag('Bookings')->scope('bookings:read')
    ->summary('The link where the guest fills in who is travelling')
    ->description('A long unguessable link to a page with no login, tied to this one booking. Send it to the guest yourself, or use the trip brief.')
    ->returns(200, S::obj(['data' => S::obj(['url' => S::url('', 'https://portal.example.com/guest-form/QF3k...')])]));
Doc::op('POST', '/bookings/{code}/guest-form')->tag('Bookings')->scope('bookings:write')
    ->summary('Make a new guest-form link')->description('The old link stops working at once.')
    ->returns(200, S::obj(['data' => S::obj(['url' => S::url()])]));

Doc::op('POST', '/bookings/{code}/invoice')->tag('Bookings')->scope('bookings:write')
    ->summary('Create an invoice from the booking')
    ->description('Lines for the trip, each add-on (included ones show as free) and any booking fees, with what has been paid recorded against it. There is one live invoice per booking: asking again returns it (200) instead of making another (201). A voided invoice does not count.')
    ->returns(201, S::obj(['data' => S::obj(['id' => S::int('', 5), 'number' => S::str('', 'INV-2026-005'), 'status' => S::str('', 'part_paid'), 'total' => S::money('', 670.0), 'amount_paid' => S::money('', 200.0), 'created' => S::bool('False when it already existed.', true)])]));

Doc::op('GET', '/bookings/{code}/addons')->tag('Bookings')->scope('bookings:read')->summary('Add-ons on a booking')->returns(200, S::many('BookingAddon'));
Doc::op('POST', '/bookings/{code}/addons')->tag('Bookings')->scope('bookings:write')
    ->summary('Add an add-on to a booking')
    ->description('Takes the price that applies to this booking\'s own service (a per-service price wins over the general one), works out the total for the party and days, and **adds it to the booking total**. Add-ons are your revenue and are not commissionable.')
    ->body(S::obj(['upsell_id' => S::int('An add-on from your catalogue.', 2), 'qty' => S::int('Default 1.', 2)], ['upsell_id']))
    ->returns(201, S::obj(['data' => ['allOf' => [S::ref('BookingAddon'), S::obj(['booking' => S::ref('Money')])]]]));
Doc::op('DELETE', '/bookings/{code}/addons/{id}')->tag('Bookings')->scope('bookings:write')->summary('Take an add-on off')->description('The booking total goes back down.')->returns(204);

Doc::op('GET', '/bookings/{code}/check-in')->tag('Bookings')->scope('bookings:read')->summary('Check-in state')->returns(200, S::one('CheckIn'));
Doc::op('POST', '/bookings/{code}/check-in')->tag('Bookings')->scope('bookings:write')->summary('Check the guests in')
    ->body(S::obj(['guests_present' => S::int('How many turned up.', 3), 'notes' => S::str()]), false)->returns(200, S::one('CheckIn'));
Doc::op('POST', '/bookings/{code}/check-out')->tag('Bookings')->scope('bookings:write')->summary('Check the guests out')->returns(200, S::one('CheckIn'));
Doc::op('POST', '/bookings/{code}/no-show')->tag('Bookings')->scope('bookings:write')->summary('Mark a no-show')->returns(200, S::one('CheckIn'));
