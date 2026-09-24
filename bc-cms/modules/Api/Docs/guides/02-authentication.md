# Keys, scopes and modes

Send your key on every request:

```
Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx
```

No key, or one that is not a Tsoka key, is `401 missing_api_key`. A key that does not exist is `401 invalid_api_key`. A revoked or expired key, or one that has used its yearly allowance, is `403` (`api_key_revoked`, `api_key_expired`, `rate_limit_exceeded`).

## Secret and publishable

A **publishable** key (`pk_`) may only make `GET` requests, whatever else it is allowed. Anything else is `403 read_only_key`. It is meant for a web page, and browser calls are allowed only from the domains you registered (**Settings → API keys → domain**, or *Allowed origins*).

A **secret** key (`sk_`) reads and writes. Keep it on a server.

## Scopes: give a key only what it needs

A secret key can be limited to some **areas**. A key made for your booking website does not need invoices.

| Scope area | Covers |
|---|---|
| `services` | Listings, seats (departures), options, add-ons, locations, categories |
| `bookings` | Bookings and everything on them: travellers, payments, refunds, documents, check-in |
| `customers` | Customer records, and guest accounts and their own bookings |
| `loyalty` | Loyalty rule, tiers, members, point adjustments |
| `waitlist` | The waitlist and telling guests a seat opened |
| `invoices` | Invoices, lines and payments |
| `messages` | Scheduled messages, campaigns, occasions |
| `analytics` | Reports: summary, revenue, occupancy, trending |
| `marketplace` | The Tanova marketplace switches |
| `suppliers` | Operators and suppliers |
| `webhooks` | Webhook endpoints and their delivery log |
| `planner` | The Tanova trip planner, day plans and the concierge inbox |

Each is `:read` or `:write`, for example `bookings:read`. **`:write` includes `:read`.** A key with no scopes has full access (so every key that existed before scopes keeps working). Scopes are chosen when the key is made and are kept when it is rotated.

Calling something outside the key's scopes is:

```json
{ "error": { "code": "insufficient_scope", "message": "This key is not allowed to use \"invoices:read\". …", "scope": "invoices:read" } }
```

Every endpoint in the reference shows the scope it needs.

## Live and test

`sk_live_` / `pk_live_` keys work on your real business and need an active plan (`402 subscription_required` if it lapsed). `sk_test_` / `pk_test_` keys do not. See *Test mode* for exactly what they change.

## Making keys with the API

Keys can be made in the portal, or with the account API (a portal sign-in token, not a key): `POST /api/vendor/api-keys` with `name`, `type` (`secret` or `publishable`), `mode` (`live` or `test`), optional `domain`, `rate_limit` and `scopes`. The new key is returned **once**. `POST /api/vendor/api-keys/{id}/rotate` makes a new one and stops the old one at once. The same account API manages allowed origins (`/api/vendor/allowed-origins`) and reads usage (`/api/vendor/usage`). Webhooks can be managed with a key at `/api/v/webhooks`, so a system can register itself.
