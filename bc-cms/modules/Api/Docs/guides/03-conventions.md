# Conventions

Everything below holds for every endpoint, so the reference does not repeat it.

## Success

```json
{ "data": { … } }                              // one thing
{ "data": [ … ], "meta": { "page": 1, "per_page": 25, "total": 120, "last_page": 5 } }   // a list
```

Creating something answers `201`; deleting answers `204` with no body.

Some older endpoints (the `/services/tours` style lists, `/tanova/trips`, `/concierge/conversations`) keep the framework's page format so nobody's code breaks. The reference marks them **legacy shape** and shows exactly what they return.

## Lists: search, filter, sort, page

Every list takes the same four things the portal's screens do:

| Parameter | Meaning |
|---|---|
| `q` | Search. Every word must match somewhere; order does not matter. `vic falls` finds "Victoria Falls Bridge". |
| `sort` | One of the values listed on that endpoint. |
| `page` | Starts at 1. |
| `per_page` | 10, 25, 50 or 100. Default 25. |

plus the filters that screen has (`status`, `from`, `to`, …). Unknown filter values are ignored, never an error, so a stale link still works. Dates are `YYYY-MM-DD`.

## Ids

Numbers, except **bookings**, which use their `code`. A thing that is not yours, or does not exist, is `404`: the API never confirms that another business's record exists.

## Time and money

Times are ISO 8601 with a zone (`2026-11-10T09:00:00+00:00`). Days are `YYYY-MM-DD`. Money is a number in the business's currency (USD unless you changed it); some legacy fields are decimal strings and the reference says which.

## Errors

Always this shape, with the right HTTP status:

```json
{ "error": { "code": "sold_out", "message": "Only 2 seats are left that day.", "seats_left": 2 } }
```

**Branch on `code`, not on `message`.** The reference lists the codes each endpoint can give. These are common to all:

| Status | Code | Meaning |
|---|---|---|
| 401 | `missing_api_key`, `invalid_api_key` | Key missing or wrong |
| 402 | `subscription_required` | A live key on a lapsed plan |
| 403 | `read_only_key`, `insufficient_scope`, `forbidden` | Not allowed |
| 404 | `not_found` | Not there, or not yours |
| 405 | `method_not_allowed` | Wrong verb for that path |
| 409 | (varies) | Fine request, but the state says no (sold out, already paid) |
| 422 | `validation_failed` | Bad input. `fields` says which: `{"fields":{"email":["…"]}}` |
| 429 | `too_many_requests` | Too fast. See *Rate limits* |
| 500 | `server_error` | Ours. It carries a `reference`: quote it to us |

## Caching

Successful `GET`s carry an `ETag`. Send it back as `If-None-Match` and an unchanged answer is `304` with no body.

## Versioning

The API is versioned by date. Send `Tsoka-Version: 2026-06-30` to pin it; the version used is echoed in `X-Tsoka-Version`. Additions (new fields, endpoints, error codes) do not make a new version, so **ignore fields you do not know**. Anything that could break you gets a new date, and the old one keeps working.
