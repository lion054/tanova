# Getting started

The Tsoka Vendor API lets your own website, app or back office do everything you can do in the portal: run listings and seats, take and manage bookings, keep customer records, run loyalty and the waitlist, invoice, send messages, and read reports. Every answer is about **your** account only.

## 1. Make a key

Open **Settings → API keys** in the portal and generate one.

| Key | Looks like | Use it for |
|---|---|---|
| Secret | `sk_live_…`, `sk_test_…` | Your server. Can read and write. Never put it in a web page or an app. |
| Publishable | `pk_live_…`, `pk_test_…` | A web page. Can only read. |

Start with a **test** key: it needs no plan, is never capped, and sends nothing to a guest. See *Test mode*.

## 2. Make a first call

```bash
curl https://YOUR-PORTAL/api/v/me \
  -H "Authorization: Bearer sk_test_xxxxxxxxxxxxxxxx"
```

You get your account, plan and keys back:

```json
{ "data": { "vendor": { "id": 7, "name": "Luxsav", "plan": "Enterprise Plan" }, "api_keys": [ … ] } }
```

Every response carries `X-Tsoka-Mode: test` or `live`, so you always know which world you are in.

## 3. Take a booking end to end

1. `GET /services/tours` (or `/catalogue?location_id=6`) to show what you sell.
2. `GET /services/tours/{id}/departures` to show which days have seats.
3. `POST /bookings` with the tour, day, party and guest. You get a `booking_code` and a `checkout_url`.
4. Send the guest to `checkout_url` to pay, or record cash you took yourself with `POST /bookings/{code}/payments`.
5. Listen for `booking.paid` (a webhook) or read `GET /bookings/{code}`.
6. When they have travelled, `PATCH /bookings/{code}/status` with `completed`: loyalty points are earned.

The **Recipes** guide has each of these as code.

## Where things are

- **Reference**: every endpoint, with its fields, errors and examples in cURL, JavaScript, PHP and Python.
- **OpenAPI**: `GET /api/v/openapi.json`, for code generators and API tools. No key needed.
- **Postman**: `GET /api/v/postman.json`, import it and set `api_key`.
- **Base URL**: `https://YOUR-PORTAL/api/v`.
