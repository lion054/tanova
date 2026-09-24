# Guest accounts and payments

Use this when **your own app or site** has guests who sign in, book, pay and see their trips: what the LuxSav app does. If you only take bookings for people without accounts, use `POST /bookings` instead.

## Two credentials on every guest call

1. **Your API key**, as always: it says which business's app is calling.
2. **The guest's token**, in `X-Customer-Token`: it says which guest is signed in.

```bash
curl https://YOUR-PORTAL/api/v/customer/bookings \
  -H "Authorization: Bearer sk_live_…" \
  -H "X-Customer-Token: 412|Zk3q…"
```

Sign-up, sign-in and anything that changes data need a **secret** key (or one allowed `customers:write`), so the app talks to *your server*, and your server calls Tsoka. Do not ship a secret key inside a mobile app. A guest's token, unlike your key, is safe to keep on their device: it can only reach the `customers/*` and `customer/*` endpoints, never your dashboard or the rest of the API.

## The flow

```
POST /customers/register   or   POST /customers/login   →  { token, profile }
        │  keep the token
        ▼
GET  /services/tours … /catalogue                        (browse: your key only)
POST /customer/bookings                                  (book as this guest)
POST /customer/bookings/{code}/pay                       → { approval_url }
        │  open approval_url in a browser / web view; the guest approves on PayPal
        ▼
GET  /customer/bookings/{code}                           (poll until status is "paid")
```

## Paying

`POST /customer/bookings/{code}/pay` starts a PayPal payment for what is owed and returns an **`approval_url`**. Open it in a browser or an in-app web view; **nothing is charged until the guest approves there.** The booking becomes `paid` only when PayPal confirms to us, so after the guest returns, **read the booking again** and trust its `status`, not the fact that they came back.

To pay for several bookings at once (a trip's activities), `POST /customer/bookings/pay` with `{"codes": [...]}` (up to 20): one PayPal payment, each booking marked paid for its own total.

Before asking for money we check the seats are still the guest's. A booking that sat unpaid only holds its seats for a while (30 minutes by default); if someone else took them, the answer is `409 sold_out`, the booking is cancelled and **nothing was charged**.

| Booking `status` in the guest's list | Meaning |
|---|---|
| `awaiting_payment` | Made, not paid |
| `paid` | Paid; you have not confirmed yet |
| `confirmed` | You confirmed it |
| `completed` | The trip happened |
| `cancelled` | Cancelled |

A guest can cancel a booking **that is not paid** with `POST /customer/bookings/{code}/cancel`. A paid booking is cancelled by you (`PATCH /bookings/{code}/status`), because money may have to go back.

**Test keys cannot start payments** (`409 test_mode`), and PayPal must be set up on the platform (`503 payment_unavailable` until it is). Refunds are recorded in the portal or with `POST /bookings/{code}/refunds`; the money itself goes back through PayPal or the bank by hand.

## The waitlist

When a day is full, the guest can ask to be told: `POST /customer/waitlist` with the tour, day and party. They appear on **your** waitlist with `source: app`. When you cancel a booking that frees seats, or press *tell them* in the portal (`POST /waitlist/notify-openings`), they are e-mailed, and their entry becomes `converted` when they book.

## Accounts are platform-wide

A guest account is **one per e-mail address across the whole Tsoka platform**, not one per business. Consequences you should design for:

- Someone who already booked through another business's app registers with you as `email_taken`: send them to sign in, with the password they already have.
- `DELETE /customers/me` erases the person, so it also detaches them from bookings made with other businesses. Their **bookings stay** (each business still has to honour them) but no longer point at anyone. Ask the guest to confirm with their password; the endpoint requires it.
- Sign-up is rate limited, and `forgot-password` always answers the same, so neither can be used to discover who has an account.

## Sign-out

`POST /customers/logout` revokes the token that made the call. Other devices stay signed in.
