# Tanova → Tsoka Portal — Feature Port Plan

**Status:** Draft for review. No code written yet.
**Source:** `/home/lionel/Documents/tsokatravel/tanova` — standalone Next.js workspace, 42 pages / 138 API routes, MySQL shared with dare2travel, `d2t_*` tables.
**Target:** `bc-cms` — multi-tenant Laravel CMS (GoTrip), `bc_*` tables, `vendor_id` isolation.
**Date:** 2026-08-11

---

## 1. What is being ported

Ten capabilities present in Tanova and absent (or materially thinner) in the portal:

| # | Capability | Source page | Source tables |
|---|---|---|---|
| 1 | Meals catalog | `meals` | `d2t_meals_catalog` |
| 2 | Restaurants / dining | `dining` | `d2t_restaurants` |
| 3 | Holiday greeting calendar | `holidays` (336 ln) | `d2t_holidays` + greeting log |
| 4 | Manual itinerary builder | `itinerary-builder` (384 ln) | `d2t_itinerary_templates`, `d2t_itinerary_days` |
| 5 | Invoices + payment recording | `invoices` (866 ln) | `d2t_invoices`, `d2t_payments`, `d2t_payment_schedule` |
| 6 | Customers / CRM | `customers` (267 ln) | `d2t_customers` |
| 7 | Operators / supplier console | `operators` (472 ln) | `operators`, `operator_routes`, `operator_schedules`, `operator_fares`, `operator_availability`, `operator_sync_logs` |
| 8 | Unified catalogs view | `catalogs` (475 ln) | *(view over 1, 2 + activities/accommodations)* |
| 9 | AI plan settings | `settings/ai-plan` | `d2t_settings` |
| 10 | Document import + server-side PDF | `admin/import`, `bookings/by-ref/[ref]/pdf` | — |

### Correction to the earlier gap list

**"Holidays" is not holiday packages.** The columns are `(name, type, date, custom_subject, custom_body, active)` plus a greeting log — it is a **calendar of shared dates with templated greetings** (Christmas, Eid, Independence Day), broadcast to customers.

The portal already has `bc_vendor_occasions`, which is *per-customer* dates (`customer_name`, `type` = birthday | anniversary | custom, `occasion_date`). These are adjacent, not duplicates: one is per-person, one is shared-calendar. **Holidays should be built beside Occasions in the Engage area and share its delivery path**, not as a standalone module. Building it in isolation would create a second greeting sender.

---

## 2. The central problem: single-tenant → multi-tenant

This is the whole difficulty of the port, and it applies to every item above.

Tanova is **single-tenant**. It is dare2travel's admin panel with the `/admin` prefix stripped; the `d2t_` prefix is literally "dare2travel". Nothing in it carries an operator identifier, because there is only ever one operator. Authorisation is a JWT bearer check:

```js
if (decoded.role !== 'admin' && decoded.role !== 'operator') return null;
```

That is a *role* check, not a *tenancy* check. Ported verbatim it would let any vendor read every other vendor's data.

The portal is **multi-tenant by construction**: every business table carries `vendor_id` constrained to `users` with `cascadeOnDelete`, indexed as `(vendor_id, …)`, and every query is scoped. See `bc_booking_checkins` as the canonical example.

**Rule for this port:** every ported table gains `vendor_id`; every ported query gains the scope; no ported endpoint trusts a role alone. Where the source has a bare `SELECT * FROM d2t_x`, the target has a vendor-scoped Eloquent query. This is not mechanical translation — each endpoint needs a decision about *whose* data it returns.

---

## 3. Prerequisite — extract the source schema (blocks everything)

**The exact columns for most source tables cannot be read from this machine.** The API builds several INSERT statements dynamically (`INSERT INTO d2t_restaurants (${cols})`), and `db-backups/` holds only `luxsav`, `tsoka_portal` and `tsokanew` — there is no dare2travel dump.

Before Phase 1, dump the structure of the source tables:

```sh
mysqldump --no-data --skip-add-drop-table <dare2travel_db> \
  d2t_meals_catalog d2t_restaurants d2t_holidays d2t_invoices \
  d2t_customers d2t_itinerary_templates d2t_itinerary_days \
  d2t_payments d2t_payment_schedule d2t_settings \
  operators operator_routes operator_schedules operator_fares \
  operator_availability operator_sync_logs \
  > tanova-schema.sql
```

Everything below is designed against table *names* and page behaviour, both of which are verified. Column lists are the one thing taken on trust until this dump exists.

---

## 4. Where each capability lands

The portal has two homes for new work: `modules/` (core, always on) and `pro/` (commercial, provider-registered). Service types with bookable inventory are full modules (Hotel, Tour, Car, Boat, Flight, Space, Event, Visa). Tanova-specific capability belongs in `pro/Tanova`.

| # | Capability | Target | New tables | Reuses | Size |
|---|---|---|---|---|---|
| 1+2 | Meals & Restaurants | `pro/Tanova` | `bc_tanova_meals`, `bc_tanova_restaurants` | `bc_tanova_accommodations` pattern | M |
| 3 | Holiday greetings | `modules/Vendor` (Engage) | `bc_vendor_holidays`, `bc_vendor_holiday_sends` | `ScheduledMessage` delivery, `VendorOccasion` UI | S |
| 4 | Itinerary builder | `pro/Tanova` | `bc_tanova_itinerary_templates`, `bc_tanova_itinerary_days` | `bc_tanova_trips`, `TanovaEngine` | L |
| 5 | Invoices | `pro/Booking` | `bc_vendor_invoices`, `bc_vendor_invoice_lines`, `bc_vendor_invoice_payments` | `bc_bookings`, Payout, Wallet | L |
| 6 | Customers / CRM | `modules/Vendor` | `bc_vendor_customers` | `bc_bookings` guest data | M |
| 7 | Operators | `pro/Integrations` | `bc_operators` + 5 child tables | `WetuService` pattern, `VendorApiKey` | XL |
| 8 | Unified catalogs | `pro/Tanova` (UI only) | — | 1, 2, Tour, Hotel | S |
| 9 | AI plan settings | `pro/Ai` | — (`SettingClass`) | `pro/Ai` driver config | S |
| 10 | Import + PDF | `pro/Tanova` | — | new Composer deps | M |

### Notes on the two contentious placements

**Meals and Restaurants should be catalog rows, not booking modules.** In Tanova they are *itinerary ingredients* — things an itinerary day references — not independently bookable products with availability calendars, terms, translations and reviews. Building them as full portal service modules would be perhaps 5× the work for capability nobody asked for. `bc_tanova_accommodations` is the precedent: a Tanova-local catalog table, not a Hotel module.

**Restaurants were deliberately deleted from this codebase.** Migration `2024_01_02_000008_drop_bc_tanova_restaurants_table.php` dropped `bc_tanova_restaurants`, and `OverpassRestaurantService` replaced it with live OpenStreetMap lookups. **Find out why before re-creating it.** If the reason was "maintaining a restaurant list is not worth it, query OSM instead", then porting Tanova's `dining` page re-introduces a problem someone already solved. This is an open decision, not a task.

---

## 5. Phases

| Phase | Deliverable | Depends on |
|---|---|---|
| **0 — Schema + decisions** | Source schema dump (§3). Resolve the open decisions in §8. Confirm the restaurants question above. | — |
| **1 — Foundation** | Migrations for all new tables with `vendor_id` + indexes. Models with a shared `ScopedToVendor` trait. No UI. One reviewable migration set. | 0 |
| **2 — Catalog** | Meals + Restaurants CRUD (admin + vendor portal), unified Catalogs tab view, `vendor_nav` entries under Catalog. | 1 |
| **3 — Itinerary builder** | Day-by-day editor over `bc_tanova_trips`, with the source's validation rules: day count vs experience duration, warn on days missing title/description. Read-only integration with existing AI generation first, editing second. | 1, 2 |
| **4 — Customers + Holidays** | Customer records derived from bookings with a dedupe rule; holiday calendar + greeting sends wired into the existing scheduled-message path. | 1 |
| **5 — Invoices** | Invoice creation, line items, payment recording, PDF output. The reconciliation work in §7 lands here. | 1 |
| **6 — Operators** | Supplier directory, routes/schedules/fares, sync logs. Largest and least understood — deliberately last. | 1, 5 |
| **7 — Trimmings** | AI plan settings, document import, hardening, tests. | all |

Phases 2–4 are independent of each other and can be reordered or parallelised. Phase 5 must precede 6 (operator fares reference invoicing). Phase 3 is the highest product value.

---

## 6. Conventions every phase follows

Taken from existing portal code, not invented:

- Tables `bc_*`; migrations dated `2026_08_*`; `vendor_id` → `foreignId('vendor_id')->constrained('users')->cascadeOnDelete()`; index `(vendor_id, <most-filtered column>)`.
- Models in the module's `Models/`, scoped by a shared trait — never a bare `Model::all()`.
- Admin routes in `Routes/admin.php`, vendor portal in `Routes/web.php`, public API registered through `add_action('API_ROUTES')`.
- Vendor API endpoints under `/api/v/…`, inheriting the existing middleware stack (key resolution, idempotency, CORS, rate limiting, usage tracking) from `modules/Api/Routes/api-vendor.php`.
- Portal nav via `config/vendor_nav.php` — a `keys` entry in the right section plus a `gated` entry if the capability is plan-restricted.
- Views as Blade under the module's `Views/`, registered with `loadViewsFrom`.
- Eloquent throughout. The source's raw `query()` calls do not come across.

---

## 7. What must NOT be ported as-is

| Source pattern | Why it fails here | Replacement |
|---|---|---|
| `verifyToken` + `role === 'admin' \|\| 'operator'` | A role check where a tenancy check is needed — cross-tenant data leak | Laravel auth + vendor scoping |
| `d2t_*` table names | Names another company's tenant | `bc_*` |
| Raw `mysql2` queries | Bypasses Eloquent, scopes, and the audit path | Eloquent |
| Puppeteer PDF generation | A headless Chrome per PDF on a box already running 5 PM2 apps | `barryvdh/laravel-dompdf` over a Blade template |
| `pdf-parse` / `mammoth` import | Node-only | `smalot/pdfparser`, `phpoffice/phpword` |
| Stripe-first payment flow | Portal has TourPay, Payout and Wallet already | Route through existing payment infra |

---

## 8. Risks and open decisions

**Invoices are the biggest duplication risk.** The portal already has bookings, payouts, a wallet and TourPay. Adding an invoice table with its own payment records creates a second answer to "was this paid?" Before Phase 5, decide whether invoices are (a) a *view* over existing booking/payment data, or (b) a genuinely separate document with its own ledger for off-platform work. These are different builds; (a) is smaller and safer.

**Customers overlap `bc_bookings` guest data and the `User` model.** A vendor's "customer" may be a registered user, a guest name on a booking, or neither. Needs an explicit dedupe rule — email, then phone, then nothing — decided before Phase 4, or you get three records per person.

**Operators overlaps two existing systems**: vendor API keys (`bc_vendor_api_keys`) and `pro/Integrations` (which already has `WetuService`). Scope it as an extension of Integrations, not a parallel supplier system.

**Restaurants were removed on purpose** — see §4.

**Data migration is not in this plan.** Everything above builds empty tables. If dare2travel's existing records must come across, that is separate work needing a tenant mapping (which `vendor_id` does each `d2t_` row belong to?) and its own reconciliation pass.

### Decisions needed before Phase 1

1. **Restaurants** — re-create the catalog, or keep the OSM lookup that replaced it?
2. **Invoices** — view over existing data, or separate ledger?
3. **Customer identity** — what makes two bookings the same person?
4. **Gating** — which of these are on every plan, and which are paid tiers? Determines `vendor_nav` `gated` entries.
5. **Existing data** — port dare2travel's records, or start empty?
6. **Operators** — is this actually wanted, or was it dare2travel-specific supplier plumbing? It is the largest item by far and the least evidently general.

---

## 9. Effort

Rough order, assuming the schema dump exists and decisions are made:

| Phase | Relative size |
|---|---|
| 1 Foundation | 1× |
| 2 Catalog | 1.5× |
| 3 Itinerary builder | 3× |
| 4 Customers + Holidays | 2× |
| 5 Invoices | 3× |
| 6 Operators | 4× |
| 7 Trimmings | 1.5× |

Phase 6 alone is roughly a quarter of the total. If Operators turns out to be dare2travel-specific (decision 6), the port shrinks by a quarter for no lost capability — which is why that question is worth answering first.
