<?php

use Modules\Api\Docs\Doc;
use Modules\Api\Docs\P;
use Modules\Api\Docs\S;

Doc::schema('InvoiceLine', S::obj(['id' => S::int('', 11), 'description' => S::str('', 'Gorge swing, 10 Nov 2026, 2 guests'), 'quantity' => S::num('', 1), 'unit_price' => S::money('', 400.0), 'line_total' => S::money('quantity x unit price.', 400.0)], ['id', 'description']));
Doc::schema('InvoicePayment', S::obj([
    'id' => S::int('', 4), 'amount' => S::money('Negative for a refund.', 200.0), 'method' => S::enum(['cash', 'bank', 'card', 'mobile_money', 'paypal', 'stripe', 'paystack', 'other']),
    'reference' => S::nullable(S::str('', 'TXN-8841')), 'paid_at' => S::date(), 'notes' => S::nullable(S::str()),
    'source' => S::enum(['manual', 'api', 'gateway', 'guest', 'booking', 'refund', 'migrated'], 'How it got here: you or the API, the guest paying online (`gateway`), a bank transfer the guest reported (`guest`), the booking, a refund.'),
    'status' => S::enum(['confirmed', 'pending', 'rejected'], '`pending`: a bank transfer the guest says they made, waiting for you. Only `confirmed` money counts.'),
], ['id', 'amount', 'method', 'status']));
Doc::schema('InvoiceInstalment', S::obj(['label' => S::str('', 'Deposit (30%)'), 'amount' => S::money('', 300.0), 'due_date' => S::date(), 'paid' => S::money('How much of it the payments have covered.', 300.0), 'remaining' => S::money('', 0.0), 'late' => S::bool()], ['label', 'amount', 'due_date']));
Doc::schema('Invoice', S::obj([
    'id' => S::int('', 5), 'number' => S::str('Your running number for the year, INV-2026-001, INV-2026-002... (the prefix is yours to set in the invoice settings).', 'INV-2026-005'),
    'type' => S::enum(['invoice', 'quotation', 'credit_note'], 'A credit note lowers what an invoice is owed (`parent_id` is that invoice).'),
    'status' => S::enum(['draft', 'sent', 'part_paid', 'paid', 'void', 'credited', 'accepted', 'declined', 'expired'], 'Invoices: `draft`, `sent`, then `part_paid` / `paid` following the payments, `credited` when a credit note covered all of it, `void`. Quotations: `draft`, `sent`, `accepted`, `declined`, `expired`.'),
    'overdue' => S::bool('Issued, not paid, and past its due date.', false),
    'booking_id' => S::nullable(S::int('The booking it was made from.', 41)), 'customer_id' => S::nullable(S::int('The customer record.', 12)), 'parent_id' => S::nullable(S::int('For a credit note: the invoice it credits. For an invoice made from a quotation: the quotation.')),
    'bill_to' => S::obj(['name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'address' => S::nullable(S::str('', 'Harare, Zimbabwe'))]),
    'title' => S::nullable(S::str('', 'Victoria Falls, 4 days')),
    'issue_date' => S::date('', '2026-10-01'), 'due_date' => S::nullable(S::date('', '2026-10-31')), 'valid_days' => S::nullable(S::int('Quotations: how long it stays open.', 14)), 'currency' => S::str('Each invoice has its own currency.', 'USD'),
    'subtotal' => S::money('Before tax (after tax when prices include it: the amount without tax).', 670.0), 'discount' => S::money('Taken off the items.', 0.0), 'tax_rate' => S::num('The combined rate, percent.', 0),
    'tax_mode' => S::enum(['inclusive', 'exclusive'], '`inclusive`: prices already contain the tax. `exclusive`: tax is added on top.'), 'tax_amount' => S::money('', 0.0),
    'tax_lines' => S::arr(S::obj(['name' => S::str('', 'VAT'), 'rate' => S::num('', 15), 'amount' => S::money('', 100.0)]), 'Each named tax and its share. Empty when there is only one plain rate.'),
    'total' => S::money('The invoice total, with tax.', 670.0), 'credit_total' => S::money('Credit notes issued against it.', 0.0), 'amount_paid' => S::money('Confirmed payments, less refunds.', 200.0),
    'balance' => S::money('Total, less credit notes, less what was paid.', 470.0), 'refund_due' => S::money('What is due back to the client after a credit note on a paid invoice.', 0.0),
    'notes' => S::nullable(S::str()), 'terms' => S::nullable(S::str('', 'Payment within 30 days')),
    'pay_url' => S::url('The page your client opens to view it, pay online and accept a quotation.', 'https://portal.example.com/tourpay/pay/6f0c1e5e-3b0a-4a3e-9d0e-2a5c6a7d8b11'),
    'sent_at' => S::nullable(S::dt()), 'viewed_at' => S::nullable(S::dt('When the client first opened it.')), 'created_at' => S::dt(),
], ['id', 'number', 'type', 'status', 'total']));
Doc::schema('InvoiceDetail', ['allOf' => [S::ref('Invoice'), S::obj(['lines' => S::arr(S::ref('InvoiceLine')), 'payments' => S::arr(S::ref('InvoicePayment')), 'schedule' => S::arr(S::ref('InvoiceInstalment'), 'Empty unless you split it into instalments.')])]]);

Doc::op('GET', '/invoices')->tag('Invoices')->scope('invoices:read')
    ->summary('List invoices')
    ->description('Add `include=lines,payments` to get those inside each invoice. `meta.summary` gives what is outstanding, what is paid and how many there are (over all your invoices, not just this page).')
    ->query([
        P::q('invoice number, customer name or e-mail'),
        P::enum('type', ['invoice', 'quotation', 'credit_note'], 'Default `invoice`.'),
        P::enum('status', ['draft', 'sent', 'part_paid', 'paid', 'void', 'credited', 'accepted', 'declined', 'expired', 'overdue'], '`overdue` is issued, unpaid and past due. The quotation statuses apply to `type=quotation`.'),
        P::int('customer_id', 'Only this customer.'),
        P::date('from', 'Issued on or after.'), P::date('to', 'Issued on or before.'), P::int('booking_id', 'Only invoices made from this booking.'),
        P::str('include', 'Comma separated: `lines`, `payments`.', ['example' => 'lines']),
        P::sort(['newest' => 'newest first', 'oldest' => 'oldest first', 'amount' => 'biggest amount', 'due' => 'due soonest', 'number' => 'number'], 'newest'),
        ...P::paging(),
    ])
    ->returns(200, S::obj(['data' => S::arr(S::ref('Invoice')), 'meta' => ['allOf' => [S::ref('PageMeta'), S::obj(['summary' => S::obj(['outstanding' => S::money('', 1840.0), 'paid' => S::money('', 5200.0), 'count' => S::int('', 34)])])]]]));

Doc::op('GET', '/invoices/{id}')->tag('Invoices')->scope('invoices:read')->summary('Get an invoice with its lines and payments')->returns(200, S::one('InvoiceDetail'));

Doc::op('POST', '/invoices')->tag('Invoices')->scope('invoices:write')
    ->summary('Create an invoice')
    ->description('Starts as a `draft`, which you can still edit and delete. Send `lines` to fill it in one call. To invoice a booking, use `POST /bookings/{code}/invoice` instead, which fills it from the booking.')
    ->body(S::obj([
        'bill_to_name' => S::str('', 'Ann Ray'), 'bill_to_email' => S::email(), 'bill_to_address' => S::str('', 'Harare'),
        'customer_id' => S::int('A customer record of yours.', 12), 'booking_id' => S::int('A booking of yours.', 41),
        'issue_date' => S::date('Default today.'), 'due_date' => S::date('Not before the issue date.', '2026-10-31'), 'currency' => S::str('Three letters. Default USD.', 'USD'),
        'tax_rate' => S::num('Percent, 0 to 100.', 0), 'discount' => S::money('Taken off before tax.', 0.0), 'notes' => S::str(), 'terms' => S::str('', 'Payment within 30 days'),
        'type' => S::enum(['invoice', 'quotation'], 'Default invoice.'), 'title' => S::str('', 'Victoria Falls, 4 days'), 'valid_days' => S::int('Quotations only.', 14),
        'tax_mode' => S::enum(['inclusive', 'exclusive'], 'Default `exclusive` for the API (the portal form defaults to tax-inclusive prices).'),
        'tax_lines' => S::arr(S::obj(['name' => S::str('', 'VAT'), 'rate' => S::num('', 15)], ['name', 'rate']), 'Several named taxes (up to 6). When given, `tax_rate` is their sum.'),
        'lines' => S::arr(S::obj(['description' => S::str('', 'Gorge swing'), 'quantity' => S::num('', 2), 'unit_price' => S::money('', 200.0)], ['description', 'quantity', 'unit_price']), 'Up to 100.'),
    ], ['bill_to_name']))
    ->returns(201, S::one('InvoiceDetail'));

Doc::op('PUT', '/invoices/{id}')->tag('Invoices')->scope('invoices:write')
    ->summary('Change an invoice')
    ->description('Send only what changes. A **paid or void** invoice is a record and answers `409 invoice_locked`.')
    ->body(S::obj(['bill_to_name' => S::str(), 'bill_to_email' => S::email(), 'bill_to_address' => S::str(), 'issue_date' => S::date(), 'due_date' => S::date(), 'tax_rate' => S::num('', 15), 'discount' => S::money('', 10.0), 'notes' => S::str(), 'terms' => S::str()]), false)
    ->errors(['invoice_locked' => [409, 'The invoice is paid or void.']])
    ->returns(200, S::one('InvoiceDetail'));

Doc::op('DELETE', '/invoices/{id}')->tag('Invoices')->scope('invoices:write')
    ->summary('Delete a draft')->description('Only a draft can be deleted. An issued invoice is a record: void it instead.')
    ->errors(['not_a_draft' => [409, 'The invoice has been issued. Void it instead.']])->returns(204);

Doc::op('POST', '/invoices/{id}/lines')->tag('Invoices')->scope('invoices:write')
    ->summary('Add a line')->description('Totals are recalculated. Returns the whole invoice.')
    ->body(S::obj(['description' => S::str('', 'Airport transfer'), 'quantity' => S::num('', 1), 'unit_price' => S::money('', 30.0)], ['description', 'quantity', 'unit_price']))
    ->errors(['invoice_locked' => [409, 'The invoice is paid or void.']])->returns(201, S::one('InvoiceDetail'));
Doc::op('DELETE', '/invoices/{id}/lines/{lineId}')->tag('Invoices')->scope('invoices:write')->summary('Remove a line')
    ->path(['lineId' => ['integer', 'The line id.']])->errors(['invoice_locked' => [409, 'The invoice is paid or void.']])->returns(204);

Doc::op('POST', '/invoices/{id}/payments')->tag('Invoices')->scope('invoices:write')
    ->summary('Record a payment against the invoice')
    ->description('The status follows: part paid, then paid. A payment **above the balance is refused** (`exceeds_balance`) rather than accepted, because overpaying is nearly always a typo and would make the ledger lie. Send an `Idempotency-Key` so a retry records it once.')
    ->body(S::obj(['amount' => S::money('', 200.0), 'method' => S::enum(['cash', 'bank', 'card', 'mobile_money', 'paypal', 'stripe', 'paystack', 'other'], '', 'bank'), 'reference' => S::str('', 'TXN-8841'), 'paid_at' => S::date('Default today.'), 'notes' => S::str()], ['amount', 'method']))
    ->errors(['exceeds_balance' => [422, 'More than the balance.'], 'invoice_locked' => [409, 'The invoice is void.']])
    ->returns(201, S::one('InvoiceDetail'));
Doc::op('DELETE', '/invoices/{id}/payments/{paymentId}')->tag('Invoices')->scope('invoices:write')->summary('Remove a payment')->description('Use it to correct a mistake. Status and balance are recalculated.')
    ->path(['paymentId' => ['integer', 'The payment id.']])->returns(204);

Doc::op('POST', '/invoices/{id}/issue')->tag('Invoices')->scope('invoices:write')
    ->summary('Issue a draft')->description('Moves a draft with at least one line to `sent`. From then on it can only be voided, not deleted.')
    ->errors(['not_a_draft' => [409, 'It has been issued already.'], 'no_lines' => [409, 'A draft with no lines cannot be issued.']])->returns(200, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/void')->tag('Invoices')->scope('invoices:write')
    ->summary('Void an invoice')->description('The invoice is kept as a record and no longer counts as owing.')->returns(200, S::one('InvoiceDetail'));
Doc::op('GET', '/invoices/{id}/pdf')->tag('Invoices')->scope('invoices:read')->summary('Download the PDF')->description('An A4 PDF of the invoice, ready to send.')->file('application/pdf');

// ── More you can do with an invoice ──────────────────────────────────────────

Doc::op('POST', '/invoices/{id}/payments/{paymentId}/{decision}')->tag('Invoices')->scope('invoices:write')
    ->summary('Confirm or refuse a bank transfer the guest reported')
    ->path(['paymentId' => ['integer', 'The payment id (its `status` is `pending`).'], 'decision' => ['string', '`approve` once the money reached your account, or `reject`.']])
    ->description("A guest who paid by bank transfer can tell you so from the pay page, with a reference and a proof of payment. It waits as a `pending` payment and **does not count** until you `approve` it. `reject` keeps it on record but it never counts.")
    ->errors(['not_pending' => [409, 'That payment is not waiting for confirmation.'], 'exceeds_balance' => [409, 'The invoice was paid by other means meanwhile: it is more than is still owed.']])
    ->returns(200, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/credit-note')->tag('Invoices')->scope('invoices:write')
    ->summary('Issue a credit note')
    ->description("Lowers what the invoice is owed by `amount` (or everything not yet credited when you leave it out). It is issued at once with its own number (`CN-…`) and carries its share of the tax, so the tax report takes it back. If the client had already paid, the difference shows as `refund_due` on the invoice; send the money, then record it with `POST /invoices/{id}/refunds`.")
    ->body(S::obj(['amount' => S::money('Leave out to credit all that is left.', 100.0), 'reason' => S::str('Up to 240 characters.', 'Guest dropped an activity')], ['reason']))
    ->errors(['not_creditable' => [409, 'Only an issued invoice can be credited (not a draft or a void one).'], 'exceeds_invoice' => [422, 'More than what is not yet credited.']])
    ->returns(201, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/refunds')->tag('Invoices')->scope('invoices:write')
    ->summary('Record a refund you sent')
    ->description('Keeps the record only: **send the money back yourself** through the way it came. A refund is a negative entry in the payments and can never be more than `refund_due`.')
    ->body(S::obj(['amount' => S::money('', 100.0), 'method' => S::enum(['cash', 'bank', 'card', 'mobile_money', 'paypal', 'stripe', 'paystack', 'other']), 'paid_at' => S::date('Default today.'), 'reference' => S::str(), 'notes' => S::str()], ['amount', 'method']))
    ->errors(['exceeds_refund_due' => [422, 'More than is due back. Issue a credit note first.']])->returns(201, S::one('InvoiceDetail'));
Doc::op('PUT', '/invoices/{id}/schedule')->tag('Invoices')->scope('invoices:write')
    ->summary('Split it into a deposit and a balance, or equal parts')
    ->description("Replaces the payment schedule. `deposit`: `percent`% now and the rest `balance_days` days before the due date. `split`: `parts` equal payments a month apart. `none`: back to one payment. Which instalments are paid is worked out from the confirmed payments, in order, so it never drifts. The guest sees what is due next on the pay page and can pay just that.")
    ->body(S::obj(['mode' => S::enum(['deposit', 'split', 'none']), 'percent' => S::num('Deposit only: 1 to 99.', 30), 'parts' => S::int('Split only: 2 to 12.', 3), 'balance_days' => S::int('Deposit only: 0 to 365.', 14)], ['mode']))
    ->errors(['invoice_locked' => [409, 'Paid or void.'], 'no_total' => [422, 'Add lines first.']])->returns(200, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/convert')->tag('Invoices')->scope('invoices:write')
    ->summary('Turn a quotation into an invoice')->description('Copies its lines and prices into a new draft invoice with its own number. The quotation is kept and linked (`parent_id`). A quotation converts once.')
    ->errors(['not_a_quotation' => [409, 'Only a quotation can be converted.'], 'already_converted' => [409, 'It already has an invoice.']])->returns(201, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/duplicate')->tag('Invoices')->scope('invoices:write')
    ->summary('Copy as a new draft')->description('Same lines and terms, a new number and a new pay link, today\'s issue date, nothing paid.')->returns(201, S::one('InvoiceDetail'));
Doc::op('POST', '/invoices/{id}/send')->tag('Invoices')->scope('invoices:write')
    ->summary('E-mail it to the client')
    ->description("Sends the PDF with a link to view and pay it. A draft becomes `sent`. **A test key sends nothing**: it answers `simulated: true`.")
    ->body(S::obj(['email' => S::email('Default: the client\'s address on the invoice.')]), false)
    ->errors(['no_email' => [422, 'No valid address.'], 'no_lines' => [409, 'Nothing on it yet.'], 'send_failed' => [502, 'The mail server refused it.']])
    ->returns(200, S::obj(['data' => S::obj(['sent_to' => S::email(), 'simulated' => S::bool('Only with a test key.'), 'invoice' => S::ref('Invoice')], ['sent_to'])]));

// ── Settings ─────────────────────────────────────────────────────────────────

Doc::schema('InvoiceSettings', S::obj([
    'invoice_prefix' => S::str('The number starts with it: `INV-2026-001`.', 'INV'), 'quote_prefix' => S::str('', 'QUO'), 'default_currency' => S::nullable(S::str('', 'USD')), 'default_tax_rate' => S::nullable(S::num('', 15)),
    'default_tax_mode' => S::enum(['inclusive', 'exclusive']), 'default_due_days' => S::nullable(S::int('Payment due this many days after the issue date.', 14)), 'default_valid_days' => S::nullable(S::int('', 14)),
    'default_terms' => S::nullable(S::str('', '30% deposit to confirm')), 'default_notes' => S::nullable(S::str()),
    'banking_details' => ['type' => 'object', 'description' => 'Shown to clients who pay by bank transfer: `bank`, `account_name`, `account_number`, `branch_code`, `swift`.', 'additionalProperties' => S::str()],
    'template' => S::int('1 to 5.', 1), 'base_currency' => S::nullable(S::str('Totals across currencies are shown in it.', 'USD')),
    'rates' => ['type' => 'object', 'description' => 'What 1 unit of each currency is worth in the base currency, by **your** rates: `{"ZAR": 0.054}`.', 'additionalProperties' => S::num()],
    'send_receipts' => S::bool('E-mail the client when a payment is received.'), 'bank_enabled' => S::bool('Offer bank transfer on the pay page.'),
    'online_payment_methods' => S::arr(S::enum(['stripe', 'paypal', 'paystack']), 'The gateways you have set up in the portal and switched on. Their keys are **never** returned or set through the API.'),
    'reminders' => S::obj(['enabled' => S::bool('Off unless you turn it on.'), 'before_days' => S::int('First reminder, days before the due date.', 3), 'overdue_every_days' => S::int('', 7), 'max' => S::int('At most this many reminders per invoice.', 4), 'channel' => S::enum(['email', 'whatsapp'])]),
], ['invoice_prefix', 'reminders']));
Doc::op('GET', '/invoices/settings')->tag('Invoices')->scope('invoices:read')->summary('Numbering, defaults, reminders and rates')->returns(200, S::one('InvoiceSettings'));
Doc::op('PUT', '/invoices/settings')->tag('Invoices')->scope('invoices:write')
    ->summary('Change them')
    ->description("Send only what changes. Reminders are **off** until you set `remind_enabled: true`, and only for invoices you have sent that still have a balance. Online payment keys (Stripe, PayPal, Paystack) can only be entered in the portal, so a leaked API key can never redirect your money.")
    ->body(S::obj([
        'invoice_prefix' => S::str('Letters, digits and dashes, up to 12.', 'INV'), 'quote_prefix' => S::str(), 'default_currency' => S::str('', 'USD'), 'default_tax_rate' => S::num('', 15), 'default_tax_mode' => S::enum(['inclusive', 'exclusive']),
        'default_due_days' => S::int('', 14), 'default_valid_days' => S::int('', 14), 'default_terms' => S::str(), 'default_notes' => S::str(), 'banking_details' => ['type' => 'object', 'additionalProperties' => S::str()], 'template' => S::int('1 to 5.', 1),
        'base_currency' => S::str('', 'USD'), 'rates' => ['type' => 'object', 'description' => 'Each rate above 0.', 'additionalProperties' => S::num(), 'example' => ['ZAR' => 0.054]],
        'send_receipts' => S::bool(), 'bank_enabled' => S::bool(), 'remind_enabled' => S::bool(), 'remind_before_days' => S::int('0 to 60.', 3), 'remind_overdue_every' => S::int('1 to 60.', 7), 'remind_max' => S::int('1 to 20.', 4), 'remind_channel' => S::enum(['email', 'whatsapp']),
    ]), false)->returns(200, S::one('InvoiceSettings'));

// ── Reports ──────────────────────────────────────────────────────────────────

Doc::op('GET', '/invoices/reports/receivables')->tag('Invoices')->scope('invoices:read')
    ->summary('Who owes you, and how late')
    ->description('Everything still owed, per currency and per client, in age bands: not due yet, 1 to 30, 31 to 60, 61 to 90 and over 90 days late. Currencies are never added together.')
    ->returns(200, S::obj(['data' => S::arr(S::obj([
        'currency' => S::str('', 'USD'), 'total' => S::money('', 1840.0),
        'buckets' => ['type' => 'object', 'description' => 'Amounts by age: `current`, `d30`, `d60`, `d90`, `d90p`.', 'additionalProperties' => S::num(), 'example' => ['current' => 500, 'd30' => 340, 'd60' => 0, 'd90' => 0, 'd90p' => 1000]],
        'clients' => S::arr(S::obj(['name' => S::str('', 'Ann Ray'), 'email' => S::nullable(S::email()), 'invoices' => S::int('', 2), 'total' => S::money('', 840.0), 'buckets' => ['type' => 'object', 'additionalProperties' => S::num()]])),
    ]))]));
Doc::op('GET', '/invoices/reports/revenue')->tag('Invoices')->scope('invoices:read')
    ->summary('Invoiced and received, by month')
    ->description('`invoiced`: issued in the month, less credit notes. `received`: money that arrived in the month, less refunds. Per currency, as it was, not converted.')
    ->query([P::date('from', 'Default: the start of this year.'), P::date('to', 'Default: today.')])
    ->returns(200, S::obj(['data' => S::arr(S::obj(['month' => S::str('', '2026-10'), 'currency' => S::str('', 'USD'), 'invoices' => S::int('', 12), 'invoiced' => S::money('', 8400.0), 'received' => S::money('', 6100.0)]))]));
Doc::op('GET', '/invoices/reports/tax')->tag('Invoices')->scope('invoices:read')
    ->summary('Tax charged, by tax')
    ->description('Invoices issued in the period (drafts and void ones excluded). Credit notes take their share of tax back. One row per currency and tax.')
    ->query([P::date('from', 'Default: the start of this year.'), P::date('to', 'Default: today.')])
    ->returns(200, S::obj(['data' => S::arr(S::obj(['currency' => S::str('', 'USD'), 'name' => S::str('', 'VAT'), 'rate' => S::num('', 15), 'taxable' => S::money('The amount the tax was charged on.', 8000.0), 'tax' => S::money('', 1200.0), 'documents' => S::int('', 12)]))]));
Doc::op('GET', '/invoices/reports/statement')->tag('Invoices')->scope('invoices:read')
    ->summary('One client\'s account')
    ->description('Everything billed to a client, credited and paid, with a running balance per currency. Pass their e-mail address (or name).')
    ->query([P::str('client', 'The client\'s e-mail address, or name.', ['required' => true, 'example' => 'ann@example.com'])])
    ->returns(200, S::obj(['data' => S::obj([
        'events' => S::arr(S::obj(['date' => S::date(), 'type' => S::enum(['invoice', 'payment', 'credit note', 'refund']), 'reference' => S::str('', 'INV-2026-004 · EFT 88'), 'currency' => S::str('', 'USD'), 'billed' => S::money('', 1100.0), 'paid_or_credited' => S::money('', 0.0), 'balance' => S::money('', 1100.0)])),
        'balances' => ['type' => 'object', 'description' => 'What the client owes now, per currency.', 'additionalProperties' => S::num(), 'example' => ['USD' => 490]],
    ])]));
