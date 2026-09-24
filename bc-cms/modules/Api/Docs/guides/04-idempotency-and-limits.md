# Idempotency, rate limits and retries

## Idempotency: retry a write safely

A network can fail after your request was carried out but before you saw the answer. Retrying a `POST /bookings` then risks booking twice. Prevent it by sending an `Idempotency-Key` header on any `POST`, `PUT`, `PATCH` or `DELETE`:

```bash
curl -X POST https://YOUR-PORTAL/api/v/bookings \
  -H "Authorization: Bearer sk_live_…" \
  -H "Idempotency-Key: 6f0c1e5e-3b0a-4a3e-9d0e-2a5c6a7d8b11" \
  -H "Content-Type: application/json" \
  -d '{ … }'
```

- The **first** request with a key is carried out and its answer is stored.
- A **retry** with the same key and the same body gets the **same answer back**, with the header `Idempotent-Replayed: true`, and nothing is done twice.
- The same key with a **different** request is `422 idempotency_key_reused`.
- The same key while the first is still running is `409 idempotency_in_progress`: wait a moment and retry.
- A `5xx` or `429` answer is **not** stored, so retrying after one really tries again.
- Keys belong to your account and are remembered for **30 days**. Use a fresh random value (a UUID) for each new action, and reuse it only when retrying that action.

Send one on every write. It costs nothing.

## Rate limits

Two limits apply to each key.

| Limit | Value | What you see |
|---|---|---|
| **Per minute** | 120 requests a minute | `429 too_many_requests` with a `Retry-After` header (seconds) |
| **Per year** | The key's yearly allowance (shown in the portal; 0 means unlimited; test keys are never capped) | `403 rate_limit_exceeded` once used up |

Live keys also send `X-RateLimit-Limit`, `X-RateLimit-Remaining` (of the yearly allowance) and `X-RateLimit-Reset` (Unix time the allowance restarts).

**Be kind to it:** page instead of asking for everything, use `If-None-Match` so unchanged answers are free, and cache what rarely changes (locations, categories, add-ons).

## Retrying

| You got | Do |
|---|---|
| `429` | Wait `Retry-After` seconds, then retry |
| `5xx` or no answer | Retry after 1 s, 2 s, 4 s… up to five times, with the **same** `Idempotency-Key` |
| `409` | Do not blindly retry: the state changed. Read it again |
| Other `4xx` | Do not retry: fix the request |
