# Test mode

Use a **test key** (`sk_test_…` / `pk_test_…`) while you build. Here is exactly what it changes, so nothing surprises you.

## What is different

| | Test key | Live key |
|---|---|---|
| Needs an active plan | No | Yes (`402` if it lapsed) |
| Yearly request cap | None | The key's allowance |
| E-mail | **Never delivered** (captured and dropped) | Delivered |
| WhatsApp, SMS and other messages | **Not sent** | Sent |
| Campaigns | Not sent: `200` with `simulated: true` and the `recipients` count; the campaign stays a draft | Sent |
| Trip brief e-mail | Not sent: `simulated: true` | Sent |
| Telling waitlisted guests a seat opened | Not sent and nobody's status changes: `simulated` | Sent |
| Guest payments (`POST /customer/bookings/{code}/pay`) | Refused: `409 test_mode` | Starts a PayPal payment |
| Response header | `X-Tsoka-Mode: test` | `X-Tsoka-Mode: live` |

## What is NOT different

**A test key reads and writes your real data.** There is no separate sandbox copy of your business. A booking you make with a test key is a real booking on your real calendar: it takes real seats, shows in your portal and can fire your live webhooks.

So while testing:

- Use an e-mail address you own for test guests, and `notes: "TEST"` so your team recognises them.
- Cancel test bookings afterwards (`PATCH /bookings/{code}/status` → `cancelled`), which also gives the seats back.
- Point webhooks at a test URL, not your production receiver.

## Testing without real guests seeing anything

Test keys make it safe to try *sending* things (campaigns, trip briefs, waitlist alerts, scheduled messages) because nothing leaves. They do not make it safe to try *booking* on a day a real guest wants. Use a tour you have set to draft, or a day you do not sell, for that.
