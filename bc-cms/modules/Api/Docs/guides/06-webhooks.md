# Webhooks

Instead of asking "has anything changed?", let Tsoka tell you. Register an https address and choose events; when one happens we `POST` a signed JSON body to it.

Register in **Settings → API keys → Webhooks**, or with `POST /api/v/webhooks` (`webhooks:write`). The secret that signs deliveries is shown **once**. Send yourself a test with `POST /api/v/webhooks/{id}/test`.

## The body

Every delivery has the same envelope:

```json
{
  "id": "evt_9f2c1d0a7b34e5f6a1b2c3d4",
  "type": "booking.paid",
  "created": 1790000000,
  "api_version": "2026-06-30",
  "data": { "object": { "code": "K7Q2X9", "status": "paid", "total": "400.00", "currency": "USD", "customer": { … } } }
}
```

- `id` is **the same on every retry** of that event. Store the ids you handled and ignore repeats.
- `data.object` depends on `type`. The reference lists the fields of each (`BookingWebhook`, `WaitlistWebhook`, …).
- Booking events also carry `event` and `booking` at the top level, exactly as they did before the envelope, so an older receiver keeps working.

## Events

| Event | When |
|---|---|
| `booking.created` | A booking was made and is being processed |
| `booking.paid` | A booking was paid in full |
| `booking.confirmed` | A booking was confirmed |
| `booking.cancelled` | A booking was cancelled |
| `booking.completed` | A trip was completed (loyalty points are earned now) |
| `booking.payment_received` | Money was recorded against a booking, by you or through PayPal |
| `booking.refund_recorded` | A refund was recorded |
| `booking.travellers_submitted` | A guest filled in the traveller-details form |
| `waitlist.joined` / `.notified` / `.converted` | Someone joined, was told a seat opened, or booked |
| `customer.created` | A customer record was created |
| `loyalty.points_earned` | A guest earned points for a trip |
| `invoice.created` / `.paid` / `.voided` | An invoice changed |

`GET /api/v/webhooks/events` lists them with the object each carries. Subscribe to `*` to get all of them, including ones added later.

## Verify the signature

Every delivery has these headers:

| Header | Value |
|---|---|
| `X-Tsoka-Signature-256` | `t=1790000000,v1=<hex>,v2=<hex>` |
| `X-Tsoka-Event` | The event type |
| `X-Tsoka-Event-Id` | The `id` from the body |
| `X-Tsoka-Delivery` | This delivery's id |

**Use `v2`.** It is `HMAC-SHA256(secret, t + "." + rawBody)` in hex. Because it covers the timestamp, a captured request cannot be replayed later: **reject a `t` more than 5 minutes from now.** (`v1` is the HMAC of the body alone and is kept only for receivers written before `v2`.)

Always verify against the **raw** body bytes, before parsing the JSON, and compare in constant time.

### Node.js

```js
import crypto from 'node:crypto';

export function verify(rawBody, header, secret) {
  const parts = Object.fromEntries(header.split(',').map(p => p.split('=')));
  const t = Number(parts.t);
  if (!t || Math.abs(Date.now() / 1000 - t) > 300) return false;          // too old or too new
  const expected = crypto.createHmac('sha256', secret).update(`${t}.${rawBody}`).digest('hex');
  return parts.v2 && crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(parts.v2));
}
// Express: app.post('/hooks/tsoka', express.raw({ type: '*/*' }), (req, res) => {
//   if (!verify(req.body.toString('utf8'), req.get('X-Tsoka-Signature-256'), process.env.TSOKA_SECRET)) return res.sendStatus(400);
//   const event = JSON.parse(req.body); … res.sendStatus(200);
// });
```

### PHP

```php
function verifyTsoka(string $rawBody, string $header, string $secret): bool
{
    parse_str(str_replace(',', '&', $header), $p);          // t, v1, v2
    if (empty($p['t']) || empty($p['v2']) || abs(time() - (int) $p['t']) > 300) {
        return false;
    }
    $expected = hash_hmac('sha256', $p['t'] . '.' . $rawBody, $secret);

    return hash_equals($expected, $p['v2']);
}

$ok = verifyTsoka(file_get_contents('php://input'), $_SERVER['HTTP_X_TSOKA_SIGNATURE_256'] ?? '', getenv('TSOKA_SECRET'));
```

### Python

```python
import hmac, hashlib, time

def verify(raw_body: bytes, header: str, secret: str) -> bool:
    parts = dict(p.split('=', 1) for p in header.split(','))
    t = int(parts.get('t', 0))
    if not t or abs(time.time() - t) > 300 or 'v2' not in parts:
        return False
    expected = hmac.new(secret.encode(), f'{t}.'.encode() + raw_body, hashlib.sha256).hexdigest()
    return hmac.compare_digest(expected, parts['v2'])
```

## Answer fast, retry safely

- Answer **2xx within 10 seconds**. Do the real work afterwards (queue it). Redirects are not followed.
- Anything else, or no answer, is a failure and is **retried** after 1 minute, 5 minutes, 30 minutes, 2 hours and 12 hours, then given up (six attempts in all). Every attempt is in the delivery log.
- Because of retries, an event can arrive **more than once and out of order**. Handle it by `id`, and when order matters read the current state (`GET /bookings/{code}`) instead of trusting the sequence.
- `GET /webhooks/{id}/deliveries` shows every attempt with what your endpoint answered; `POST …/deliveries/{deliveryId}/redeliver` sends one again with the same id.
- Delivery happens **after** the request that caused the event has been answered, so a slow receiver never slows your customers down.

## Rules for the address

It must be public **https**. Private, loopback and internal addresses are refused when you register **and again on every delivery**, so pointing a name at an internal address later does not work either. In test mode events still fire (they describe real changes), so register a test URL.
