# Recipes

Working sequences for common jobs. Every write carries an `Idempotency-Key`; use a fresh UUID per action and reuse it only to retry.

```bash
BASE=https://YOUR-PORTAL/api/v
KEY=sk_test_xxxxxxxxxxxxxxxx
```

## Take a booking and get paid

```bash
# 1. Which days have seats?
curl "$BASE/services/tours/123/departures?from=2026-11-01&to=2026-11-30" -H "Authorization: Bearer $KEY"

# 2. Book. You send choices, never prices.
curl -X POST "$BASE/bookings" -H "Authorization: Bearer $KEY" -H "Idempotency-Key: $(uuidgen)" \
  -H "Content-Type: application/json" \
  -d '{"service_type":"tour","service_id":123,"start_date":"2026-11-10","adults":2,
       "first_name":"Ann","last_name":"Ray","email":"ann@example.com","tier_id":2}'
# → { "data": { "booking_code": "K7Q2X9", "status": "unpaid", "total": 400, "checkout_url": "https://…" } }
```

Send the guest to `checkout_url`. If they pay in person instead:

```bash
curl -X POST "$BASE/bookings/K7Q2X9/payments" -H "Authorization: Bearer $KEY" -H "Idempotency-Key: $(uuidgen)" \
  -H "Content-Type: application/json" -d '{"amount":400,"method":"cash","reference":"Receipt 118"}'
```

The booking moves to `paid` by itself when the money covers the total, and `booking.paid` fires.

**If it is `409 sold_out`**, the error carries `seats_left`: offer that many, or `POST /waitlist` for the rest.

## Take a deposit, then the balance

```bash
curl -X PUT "$BASE/bookings/K7Q2X9/payment-plan" … -d '{"mode":"deposit","percent":30,"balance_days":14}'
curl -X POST "$BASE/bookings/K7Q2X9/payments" … -d '{"amount":120,"method":"card"}'
curl "$BASE/bookings/K7Q2X9/payments" -H "Authorization: Bearer $KEY"      # schedule, ledger, what is owing
```

## Collect who is travelling

```bash
curl -X POST "$BASE/bookings/K7Q2X9/guest-form" -H "Authorization: Bearer $KEY" -H "Idempotency-Key: $(uuidgen)"
# → a link to give the guest: no login, they fill in names, passports and diets themselves
```

You get `booking.travellers_submitted` when they finish, and `GET /bookings/K7Q2X9/travellers` has the details. `POST /bookings/K7Q2X9/trip-brief` e-mails them the whole trip.

## Finish a trip

```bash
curl -X PATCH "$BASE/bookings/K7Q2X9/status" … -d '{"status":"completed","reason":"Trip done"}'
```

This is the same as pressing *Complete* in the portal: it is written to the timeline, the guest is e-mailed, `booking.completed` fires, **loyalty points are awarded**, and (for a cancellation) **waiting guests are told** the seats are free.

## Run the waitlist

```bash
curl "$BASE/waitlist?status=waiting&sort=queue" -H "Authorization: Bearer $KEY"
curl -X POST "$BASE/waitlist/notify-openings" … -d '{"tour_id":123,"date":"2026-11-10"}'      # tells everyone whose party now fits, in queue order
```

## Reconcile payments

```bash
curl "$BASE/bookings?status=paid&from=2026-11-01&to=2026-11-30" -H "Authorization: Bearer $KEY"
curl "$BASE/bookings/K7Q2X9/payments" -H "Authorization: Bearer $KEY"     # every payment and refund, with method and reference
curl "$BASE/analytics/revenue?period=30d" -H "Authorization: Bearer $KEY"
```

Or skip polling: subscribe to `booking.payment_received` and `booking.refund_recorded` and record each event by its `id`.

## Keep your own site in sync

```bash
# Once: everything at a destination
curl "$BASE/catalogue?location_id=6" -H "Authorization: Bearer $KEY"
# After that: only what changed
curl "$BASE/catalogue?location_id=6&updated_since=2026-11-01" -H "Authorization: Bearer $KEY"
```

Add `If-None-Match` with the last `ETag` and an unchanged answer costs nothing.

## Open a supplier's fares to your team

```bash
curl -X POST "$BASE/suppliers" … -d '{"name":"Intercape","type":"transport","commission_rate":10}'
curl -X POST "$BASE/suppliers/5/routes" … -d '{"origin":"Harare","destination":"Victoria Falls","departure_time":"06:30"}'
curl -X POST "$BASE/suppliers/5/fares"  … -d '{"route_id":9,"fare_class":"standard","nett_price":40,"sell_price":55}'
```
