<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\S;

/*
 * Guest accounts: the people who use YOUR app or site. Every call carries your API key (which app is calling);
 * once a guest has signed in, the calls that are about them also carry their own token in `X-Customer-Token`.
 */

Doc::schema('GuestProfile', S::obj([
    'id' => S::int('', 88), 'first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'email' => S::email(), 'phone' => S::str('Empty when none.', '+263770000000'),
], ['id', 'first_name', 'last_name', 'email']));
Doc::schema('GuestSession', S::obj([
    'token' => S::str('Send it as `X-Customer-Token` on every guest call. It stays valid until they sign out.', '412|Zk3q...'), 'profile' => S::ref('GuestProfile'),
], ['token', 'profile']));
Doc::schema('GuestBooking', S::obj([
    'code' => S::str('', 'K7Q2X9'), 'status' => S::enum(['awaiting_payment', 'paid', 'confirmed', 'completed', 'cancelled'], 'In words an app can show.'),
    'total' => S::num('', 400), 'paid' => S::num('', 0), 'currency' => S::str('', 'USD'),
    'service' => S::obj(['type' => S::nullable(S::str('', 'tour')), 'id' => S::int('', 123), 'title' => S::str('', 'Tandem Gorge Swing')]),
    'start_date' => S::nullable(S::date()), 'end_date' => S::nullable(S::date()), 'guests' => S::int('', 2), 'created_at' => S::nullable(S::dt()),
], ['code', 'status', 'total']));
Doc::schema('GuestWaitlistEntry', S::obj([
    'id' => S::int('', 9), 'tour_id' => S::nullable(S::int('', 123)), 'title' => S::nullable(S::str('', 'Tandem Gorge Swing')), 'date' => S::nullable(S::date()),
    'party_size' => S::int('', 2), 'status' => S::enum(['waiting', 'notified', 'converted', 'cancelled', 'expired']), 'told_at' => S::nullable(S::dt('When they were told a seat opened.')),
], ['id', 'status']));

$guestErrors = ['unauthenticated' => [401, 'No `X-Customer-Token`, or it is not a guest\'s.']];
$bool = fn (string $k, string $d) => S::obj(['data' => S::obj([$k => S::bool($d)])]);

Doc::op('POST', '/customers/register')->tag('Guests')->auth('key')->scope('customers:write')
    ->summary('Create a guest account')
    ->description("Signs someone up as a **guest** of your app and returns their token. Accounts made here are always guests, never vendors or admins, and a guest token opens nothing except the guest endpoints.\n\n**One account per e-mail address, platform-wide.** A guest who already has an account through another LuxSav-style app on the platform gets `email_taken` and signs in with the same password.\n\nRate limited (the `login` limit) to slow guessing.")
    ->body(S::obj([
        'first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'email' => S::email(), 'password' => S::str('At least 8 characters.', 'a long secret'), 'phone' => S::str(),
        'terms' => S::bool('Must be true: they accepted your terms.'), 'device_name' => S::str('Shown in their signed-in devices.', 'Ann\'s iPhone'),
    ], ['first_name', 'last_name', 'email', 'password', 'terms']))
    ->errors(['email_taken' => [422, 'An account with that e-mail exists.'], 'registration_closed' => [403, 'The platform is not accepting sign-ups.'], 'unavailable' => [503, 'Sign-up is unavailable right now.']])
    ->returns(201, S::one('GuestSession'));
Doc::op('POST', '/customers/login')->tag('Guests')->scope('customers:write')->summary('Sign a guest in')
    ->description('Rate limited. A vendor or admin account cannot sign in here (`not_a_customer`).')
    ->body(S::obj(['email' => S::email(), 'password' => S::str(), 'device_name' => S::str('', 'Ann\'s iPhone')], ['email', 'password']))
    ->errors(['invalid_credentials' => [401, 'The e-mail and password do not match.'], 'not_a_customer' => [403, 'That account is not a guest account.']])
    ->returns(200, S::one('GuestSession'));
Doc::op('POST', '/customers/forgot-password')->tag('Guests')->scope('customers:write')->summary('E-mail a password reset link')
    ->description('Always answers `sent: true`, whether or not the address has an account, so it cannot be used to find out who is registered.')
    ->body(S::obj(['email' => S::email()], ['email']))->returns(200, $bool('sent', 'Always true.'));

Doc::op('GET', '/customers/me')->tag('Guests')->auth('key+guest')->scope('customers:read')->summary('The signed-in guest')
    ->errors($guestErrors)->returns(200, S::one('GuestProfile'));
Doc::op('PUT', '/customers/me')->tag('Guests')->auth('key+guest')->scope('customers:write')->summary('Change their name or phone')
    ->description('Send only what changes. The e-mail cannot be changed here.')
    ->body(S::obj(['first_name' => S::str('', 'Ann'), 'last_name' => S::str('', 'Ray'), 'phone' => S::nullable(S::str())]), false)->errors($guestErrors)->returns(200, S::one('GuestProfile'));
Doc::op('POST', '/customers/change-password')->tag('Guests')->auth('key+guest')->scope('customers:write')->summary('Change their password')
    ->body(S::obj(['current_password' => S::str(), 'password' => S::str('At least 8 characters.')], ['current_password', 'password']))
    ->errors($guestErrors + ['invalid_credentials' => [422, 'The current password is wrong.']])->returns(200, $bool('changed', 'True.'));
Doc::op('POST', '/customers/logout')->tag('Guests')->auth('key+guest')->scope('customers:write')->summary('Sign out this device')
    ->description('Revokes the token that made the request. Other devices stay signed in.')->errors($guestErrors)->returns(200, $bool('signed_out', 'True.'));
Doc::op('DELETE', '/customers/me')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Delete their account')
    ->description("Erases the person: their sign-in, name and phone go, and every token stops working. Their **bookings stay** (you still have to honour them) but no longer point at anyone.\n\n**Accounts are platform-wide**, so this also detaches the person from bookings made with other apps on the platform. Ask them to confirm with their password, which is why it is required.")
    ->body(S::obj(['password' => S::str('Their password, as confirmation.')], ['password']))
    ->errors($guestErrors + ['invalid_credentials' => [422, 'The password is wrong.']])->returns(200, $bool('deleted', 'True.'));

// ── Their bookings ───────────────────────────────────────────────────────────

$codePath = ['code' => ['string', 'The booking code.']];
Doc::op('GET', '/customer/bookings')->tag('Guests')->auth('key+guest')->scope('customers:read')
    ->summary('Their bookings with you')->description('Newest first, up to 100. Only their own bookings with **your** business, never anyone else\'s.')
    ->errors($guestErrors)->returns(200, S::many('GuestBooking'));
Doc::op('GET', '/customer/bookings/{code}')->tag('Guests')->auth('key+guest')->scope('customers:read')->summary('One of their bookings')->path($codePath)
    ->errors($guestErrors + ['not_found' => [404, 'Not their booking.']])->returns(200, S::one('GuestBooking'));
Doc::op('POST', '/customer/bookings')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Book as the signed-in guest')
    ->description("Same rules as `POST /bookings` (seats, options, availability, the same errors), but the booking belongs to the guest, so it appears in their list. Their name, e-mail and phone are filled in from their account unless you send others.\n\nIt starts unpaid and holds its seats for 30 minutes: pay it with `POST /customer/bookings/{code}/pay`.")
    ->body(S::obj([
        'service_type' => S::enum(['tour', 'hotel', 'car', 'boat', 'event']), 'service_id' => S::int('Must be yours.', 123), 'start_date' => S::date('Today or later.'), 'end_date' => S::date('After the start.'),
        'adults' => S::int('1 to 50.', 2), 'children' => S::int('0 to 20.', 0), 'tier_id' => S::int('Tours: the option to book.', 2),
        'first_name' => S::str('Default: from their account.'), 'last_name' => S::str('Default: from their account.'), 'email' => S::email('Default: from their account.'), 'phone' => S::str(), 'notes' => S::str(),
    ], ['service_type', 'service_id', 'start_date', 'adults']))
    ->errors($guestErrors + ['not_bookable' => [409, 'Switched off for booking.'], 'not_available' => [409, 'Not running that day.'], 'sold_out' => [409, 'Not enough seats. Carries `seats_left`.'], 'invalid_type' => [422, 'Not a bookable type.']])
    ->returns(201, S::one('GuestBooking'));
Doc::op('POST', '/customer/bookings/{code}/pay')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Start paying for one booking')->path($codePath)
    ->description("Starts a PayPal payment for what is owed and returns the address where the guest **approves it**: open it in a browser or in-app web view. The booking turns paid only when PayPal confirms, so afterwards read the booking again. Nothing is charged by this call.\n\nBefore asking for money, seats are re-checked: if they were taken while the booking sat unpaid, the booking is cancelled and you get `sold_out` (nothing charged).\n\nA `sk_test_` key answers `test_mode`.")
    ->errors($guestErrors + ['test_mode' => [409, 'Payments are not started with a test key.'], 'not_payable' => [409, 'Nothing is owed, or it is not awaiting payment.'], 'payment_unavailable' => [503, 'PayPal is not set up.'], 'payment_failed' => [502, 'PayPal could not start it. Nothing was charged.'], 'sold_out' => [409, 'The seats were taken. The booking is cancelled.']])
    ->returns(200, S::obj(['data' => S::obj(['code' => S::str('', 'K7Q2X9'), 'approval_url' => S::url('Send the guest here.', 'https://www.paypal.com/checkoutnow?token=...'), 'amount' => S::num('What will be charged.', 400)])]));
Doc::op('POST', '/customer/bookings/pay')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Pay for several bookings at once')
    ->description('One PayPal payment for up to 20 of their unpaid bookings (a trip\'s activities). Each stays its own booking to you and is marked paid for its own total when PayPal confirms.')
    ->body(S::obj(['codes' => S::arr(S::str('', 'K7Q2X9'), '1 to 20 booking codes.')], ['codes']))
    ->errors($guestErrors + ['not_found' => [404, 'One of the codes is not theirs.'], 'test_mode' => [409, 'Payments are not started with a test key.'], 'not_payable' => [409, 'One does not need a payment.'], 'payment_unavailable' => [503, 'PayPal is not set up.'], 'payment_failed' => [502, 'PayPal could not start it.'], 'sold_out' => [409, 'Seats were taken.']])
    ->returns(200, S::obj(['data' => S::obj(['code' => S::str('The booking that carries the payment.'), 'codes' => S::arr(S::str()), 'approval_url' => S::url(), 'amount' => S::num('', 800)])]));
Doc::op('POST', '/customer/bookings/{code}/cancel')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Cancel an unpaid booking')->path($codePath)
    ->description('Only while nothing has been paid. A paid booking is cancelled by you (`PATCH /bookings/{code}/status`), because money may have to go back.')
    ->errors($guestErrors + ['not_cancellable' => [409, 'It is paid: ask the business.']])->returns(200, S::one('GuestBooking'));

// ── Their waitlist ───────────────────────────────────────────────────────────

Doc::op('GET', '/customer/waitlist')->tag('Guests')->auth('key+guest')->scope('customers:read')->summary('What they are waiting for')
    ->description('Their open entries (waiting or told), soonest day first. They also appear on your waitlist with `source: app`.')->errors($guestErrors)->returns(200, S::many('GuestWaitlistEntry'));
Doc::op('POST', '/customer/waitlist')->tag('Guests')->auth('key+guest')->scope('customers:write')
    ->summary('Ask to be told when a full day has room')
    ->description('Only for a day that is full for that party: when there is room the answer is `seats_available` and they should book instead. Asking twice for the same tour and day returns the existing entry. They are told by e-mail when a seat opens.')
    ->body(S::obj(['tour_id' => S::int('', 123), 'date' => S::date('Today or later.'), 'party_size' => S::int('Default 1.', 2), 'phone' => S::str('Default: from their account.')], ['tour_id', 'date']))
    ->errors($guestErrors + ['not_found' => [404, 'That tour is not one of yours or is not published.'], 'seats_available' => [409, 'There is room: book it.']])->returns(201, S::one('GuestWaitlistEntry'));
Doc::op('DELETE', '/customer/waitlist/{id}')->tag('Guests')->auth('key+guest')->scope('customers:write')->summary('Stop waiting')->path(['id' => ['integer', 'The entry id.']])
    ->description('Marks the entry cancelled.')->errors($guestErrors + ['not_found' => [404, 'Not theirs.']])
    ->returns(200, S::obj(['data' => S::obj(['id' => S::int(), 'status' => S::str('', 'cancelled')])]));
