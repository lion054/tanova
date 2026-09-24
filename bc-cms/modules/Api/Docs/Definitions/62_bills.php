<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

Doc::schema('BillPayment', S::obj(['id' => S::int('', 3), 'amount' => S::money('', 100.0), 'method' => S::enum(['bank', 'cash', 'card', 'mobile_money', 'other']), 'reference' => S::nullable(S::str()), 'paid_at' => S::date(), 'notes' => S::nullable(S::str())], ['id', 'amount']));
Doc::schema('Bill', S::obj([
    'id' => S::int('', 8), 'supplier_id' => S::nullable(S::int('One of your suppliers (`/suppliers`).', 5)), 'supplier_name' => S::str('', 'Intercape'), 'booking_id' => S::nullable(S::int('The booking it was for, so its profit can be seen.', 41)),
    'reference' => S::nullable(S::str('The supplier\'s own invoice number.', 'IC-4471')), 'description' => S::nullable(S::str('', 'Transfers, 4 days')), 'currency' => S::str('', 'USD'),
    'total' => S::money('', 300.0), 'amount_paid' => S::money('', 100.0), 'balance' => S::money('', 200.0), 'status' => S::enum(['open', 'part_paid', 'paid', 'void']), 'overdue' => S::bool(),
    'bill_date' => S::date(), 'due_date' => S::nullable(S::date()), 'notes' => S::nullable(S::str()), 'created_at' => S::dt(),
], ['id', 'supplier_name', 'total', 'status']));
Doc::schema('BillDetail', ['allOf' => [S::ref('Bill'), S::obj(['payments' => S::arr(S::ref('BillPayment'))])]]);

$fields = [
    'supplier_id' => S::int('One of your suppliers.', 5), 'supplier_name' => S::str('Needed when there is no `supplier_id`.', 'Intercape'), 'booking_id' => S::int('A booking of yours.', 41),
    'reference' => S::str('', 'IC-4471'), 'description' => S::str('', 'Transfers, 4 days'), 'currency' => S::str('Three letters.', 'USD'), 'total' => S::money('', 300.0),
    'bill_date' => S::date(), 'due_date' => S::date('Not before the bill date.'), 'notes' => S::str(),
];

Doc::op('GET', '/bills')->tag('Suppliers')->scope('invoices:read')
    ->summary('List supplier bills')
    ->description('What you owe the people who deliver the trip. `meta.owed` is what is still owed, per currency, over all your open bills.')
    ->query([P::q('supplier, their reference or the description'), P::enum('status', ['open', 'part_paid', 'paid', 'void', 'overdue'], '`overdue`: still owed and past its due date.'), P::int('booking_id', 'Only bills for this booking.'), P::int('supplier_id', 'Only this supplier.'),
        P::date('from', 'Bill date on or after.'), P::date('to', 'Bill date on or before.'), P::sort(['newest' => 'newest first', 'due' => 'due soonest', 'amount' => 'biggest'], 'newest'), ...P::paging()])
    ->returns(200, S::obj(['data' => S::arr(S::ref('Bill')), 'meta' => ['allOf' => [S::ref('PageMeta'), S::obj(['owed' => ['type' => 'object', 'additionalProperties' => S::num(), 'example' => ['USD' => 1200.0]]])]]]));
Doc::op('GET', '/bills/{id}')->tag('Suppliers')->scope('invoices:read')->summary('One bill with its payments')->returns(200, S::one('BillDetail'));
Doc::op('POST', '/bills')->tag('Suppliers')->scope('invoices:write')->summary('Add a bill')
    ->description('Link it to a booking (`booking_id`) and that booking\'s profit takes it into account: see `GET /bookings/{code}/profit`.')
    ->body(S::obj($fields, ['currency', 'total', 'bill_date']))->errors(['not_found' => [404, 'That supplier or booking is not yours.']])->returns(201, S::one('BillDetail'));
Doc::op('PUT', '/bills/{id}')->tag('Suppliers')->scope('invoices:write')->summary('Change a bill')->description('Send only what changes.')
    ->body(S::obj($fields), false)->errors(['bill_void' => [409, 'A voided bill cannot be changed.'], 'not_found' => [404, 'That supplier or booking is not yours.']])->returns(200, S::one('BillDetail'));
Doc::op('DELETE', '/bills/{id}')->tag('Suppliers')->scope('invoices:write')->summary('Delete a bill')->description('Only one with no payments. Void the others.')
    ->errors(['has_payments' => [409, 'It has payments. Void it instead.']])->returns(204);
Doc::op('POST', '/bills/{id}/payments')->tag('Suppliers')->scope('invoices:write')->summary('Record a payment to the supplier')
    ->description('The status follows: part paid, then paid. More than is still owed is refused.')
    ->body(S::obj(['amount' => S::money('', 100.0), 'method' => S::enum(['bank', 'cash', 'card', 'mobile_money', 'other']), 'paid_at' => S::date('Default today.'), 'reference' => S::str(), 'notes' => S::str()], ['amount', 'method']))
    ->errors(['exceeds_balance' => [422, 'More than is still owed.'], 'bill_void' => [409, 'The bill is void.']])->returns(201, S::one('BillDetail'));
Doc::op('POST', '/bills/{id}/void')->tag('Suppliers')->scope('invoices:write')->summary('Void a bill')->description('Kept as a record; it no longer counts as owed or against a booking\'s profit.')->returns(200, S::one('BillDetail'));

Doc::op('GET', '/bookings/{code}/profit')->tag('Bookings')->scope('bookings:read')
    ->summary('What a booking earned')
    ->description("What the booking is billed (its live invoices, less credit notes; the booking's own total when it has no invoice yet) minus what suppliers charge for it (its bills, not voided ones). If the invoice and the bills are in **different currencies** they are not added together: `mixed` is true and the figures are for you to read, not to trust as a total.")
    ->returns(200, S::obj(['data' => S::obj([
        'booking_code' => S::str('', 'K7Q2X9'), 'currency' => S::nullable(S::str('', 'USD')), 'revenue' => S::money('', 900.0), 'cost' => S::money('', 400.0), 'profit' => S::money('', 500.0),
        'margin' => S::nullable(S::num('Profit as a percent of revenue.', 55.6)), 'mixed' => S::bool('Revenue and costs are in different currencies.'), 'invoiced' => S::bool('Revenue comes from an invoice, not just the booking total.'), 'bills' => S::int('How many bills are counted.', 2),
    ])]));
