# Invoicing, payments and supplier bills

Invoices, quotations and their money live in one place: **TourPay** in the portal. Everything the screens do is here in the API, with the same rules.

## The documents

| Kind | What it is | Numbered |
|---|---|---|
| `invoice` | What a client owes you | `INV-2026-001` |
| `quotation` | An offer the client can accept or decline | `QUO-2026-001` |
| `credit_note` | Lowers what an invoice is owed | `CN-2026-001` |

Numbers run **per business, per kind, per year**, so another business's `INV-2026-001` is a different invoice. The prefix is yours (`PUT /invoices/settings`).

Each document has its **own currency**, so a business can bill in USD, ZAR and EUR. Totals across currencies use **your own rates** and a base currency you choose; nothing is converted unless you set a rate (`GET /invoices/reports/receivables` never adds currencies together).

## From a quotation to being paid

```bash
# 1. Offer it
curl -X POST "$BASE/invoices" -H "Authorization: Bearer $KEY" -H "Idempotency-Key: $(uuidgen)" -H "Content-Type: application/json" \
  -d '{"type":"quotation","bill_to_name":"Ann Ray","bill_to_email":"ann@example.com","valid_days":14,
       "lines":[{"description":"7-day Zambia and Zimbabwe","quantity":2,"unit_price":1450}]}'
curl -X POST "$BASE/invoices/12/send" …                 # e-mails it with the link the client opens

# 2. The client accepts on the link (their pay page). Then:
curl -X POST "$BASE/invoices/12/convert" …               # a draft invoice with the same lines, linked to the quotation

# 3. Split it into a deposit and a balance
curl -X PUT "$BASE/invoices/13/schedule" … -d '{"mode":"deposit","percent":30,"balance_days":14}'
curl -X POST "$BASE/invoices/13/send" …
```

To invoice a **booking**, use `POST /bookings/{code}/invoice`: it fills the invoice from what was bought, the add-ons, any fees and what has been paid. It returns the same invoice if asked twice.

The status of an invoice **follows its payments**: `draft`, `sent`, `part_paid`, `paid`. `overdue` is never stored, it is a fact about the due date (`overdue: true`). A paid or void document is a record and no longer changes.

## One set of books

Money is recorded once, in a single append-only ledger, and every total you see is worked out from it: a booking's `paid`, an invoice's `amount_paid`, a supplier bill's paid amount, the Finance statement and what the platform can pay out to you. A payment is never edited or deleted; a mistake is corrected by a reversing entry. The ledger is also tamper-evident: every entry is sealed with a hash that includes the one before it, so an entry changed or removed behind the application's back is found by the nightly check.

- **A booking and its invoice show the same money.** A payment recorded on the booking (or made through a checkout on it) also counts on the booking's invoice, and a payment on the invoice also counts on the booking, in the same currency. Refunds recorded on the booking come off the invoice too. If the invoice is in another currency than the booking, the two are kept apart.
- **The booking's status follows the money** (part paid, paid, back to unpaid after a refund), the way it does for any online payment. A paid booking is never marked completed: the trip still has to happen.
- **Paying twice at once is safe.** Two payments arriving together are counted one after the other, and a payment that would overpay is refused. Sending the same payment again (same `gateway_ref`, or the same reference within a minute on a booking) returns the one already recorded.
- **Who holds the money matters for payouts.** Money you collect yourself (your own gateway keys, bank transfers, cash) is in your hands. Money the platform collected through its own checkout is held for you until it is paid out. The payout balance only counts money the platform actually holds, never more than your share of that booking, and comes down if you refunded the guest yourself.
- **Every invoice has its own currency, and currencies are never mixed.** A payment, its invoice and the statement stay in the currency they were made in. If a booking is in another currency than its invoice, the booking's paid amount is credited at that day's exchange rate (your own rates from TourPay settings first, otherwise the free daily feed by Rates By Exchange Rate API); with no rate known, nothing is guessed and the payment is simply not linked to the booking.
- **Commission on money you collect yourself.** When a guest pays you directly, the platform's commission share of that payment is recorded as owed, in the currency the guest paid in. It shows on your statement and is held back from your next payout in the payout currency.
- **One payment schedule.** A booking and its invoice share the same schedule of deposit, balance or instalments: change it on either and the other follows; what is paid decides which rows show as paid.
- **Every night the books are checked** against each other. If anything differs, the platform's health check turns red so it is looked at the same day.

## Getting paid

`pay_url` on every invoice is a page for your client: they see what is owed and the payments so far, and pay in whichever way you have switched on.

- **Online (Stripe, PayPal, Paystack, Paynow, Pesapal, Selcom):** through **your own** account with each provider. Paynow serves Zimbabwe (EcoCash, OneMoney, cards), Selcom serves Tanzania (mobile money, cards), Pesapal serves East Africa and more. Only the methods that can take the invoice's currency are offered to your client. You paste your keys in the portal (TourPay, *Settings*, *Getting paid*); the money goes to you and never through the platform. Keys can **only** be entered in the portal: no API key can read or change them, so a leaked key can never redirect your money. `GET /invoices/settings` lists which methods are on (`online_payment_methods`).
- **Bank transfer:** the client sees your bank details and can tell you they paid, with a reference and a proof of payment. It appears as a payment with `status: "pending"` that **does not count** until you confirm it: `POST /invoices/{id}/payments/{paymentId}/approve` (or `reject`).
- **You record it yourself:** `POST /invoices/{id}/payments`.

An online payment is recorded **only when the provider confirms it to us**, never because the client's browser says so, and only once, however often they return to the page. If they pay and close the tab, it is found by a check that runs every ten minutes.

With a schedule, the client can pay **just what is due next** or everything. Which instalments are paid is worked out from the confirmed payments, in order.

When a payment arrives, the client can be e-mailed a receipt (`send_receipts`), and the `invoice.paid` webhook fires when an invoice is paid in full.

## Credit notes and refunds

```bash
curl -X POST "$BASE/invoices/13/credit-note" … -d '{"amount":300,"reason":"Guest dropped an activity"}'
# the invoice now owes 300 less. If it was already paid, "refund_due" shows what goes back:
curl -X POST "$BASE/invoices/13/refunds" … -d '{"amount":300,"method":"bank","reference":"RF-1"}'
```

`POST …/refunds` keeps the **record**: send the money back yourself, the way it came. A refund can never be more than `refund_due`. A credit note carries its share of the tax, so the tax report takes it back.

## Several taxes, a discount

Send `tax_lines` (up to six named taxes) instead of one `tax_rate`: `[{"name":"VAT","rate":15},{"name":"Tourism levy","rate":1}]`. `tax_mode` says whether prices already include the tax (`inclusive`, the portal's default) or it is added on top (`exclusive`, the API's default). The invoice shows each tax and its share.

## Reminders

**Off unless you turn them on** (`remind_enabled` in `PUT /invoices/settings`). Then, for invoices you have sent that still have a balance: once a few days before the due date, then every few days while overdue, up to a limit you set, never twice in a day. They go on **your own** e-mail or connected WhatsApp number.

## Reports

| Endpoint | Answers |
|---|---|
| `GET /invoices/reports/receivables` | Who owes you, in age bands (not due, 1 to 30, 31 to 60, 61 to 90, over 90 days late), per currency and client |
| `GET /invoices/reports/revenue` | Invoiced and received by month, less credit notes and refunds |
| `GET /invoices/reports/tax` | Tax charged by tax and currency, credit notes taken back |
| `GET /invoices/reports/statement?client=ann@example.com` | One client's account with a running balance |

The portal downloads each as CSV as well.

## Supplier bills and what a booking earned

Record what suppliers charge you with `POST /bills` and link it to a booking. `GET /bookings/{code}/profit` then answers what that booking really earned: what it is billed (its live invoices, less credit notes; the booking's own total when it has none yet) minus its supplier bills. If they are in **different currencies** they are not added together and `mixed` is `true`.

```bash
curl -X POST "$BASE/bills" … -d '{"supplier_id":5,"currency":"USD","total":300,"bill_date":"2026-10-01","due_date":"2026-10-15","booking_id":41}'
curl -X POST "$BASE/bills/8/payments" … -d '{"amount":300,"method":"bank"}'
curl "$BASE/bookings/K7Q2X9/profit" -H "Authorization: Bearer $KEY"
```

Invoices, credit notes, reports, settings **and** supplier bills all use the `invoices` scope (`invoices:read` to look, `invoices:write` to change).

## Test mode

A test key sends nothing to your client (`POST /invoices/{id}/send` answers `simulated: true`) and cannot start a payment, but it reads and writes your real invoices, so create test documents with a test e-mail address and void them afterwards.
