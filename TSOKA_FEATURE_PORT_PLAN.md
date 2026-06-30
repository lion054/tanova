# Tsoka / Tanova — Feature Port Plan

Porting the dare2travel admin feature set into Tsoka Portal.

> **Context:** dare2travel was built privately as a *single-operator* back office. Tsoka is a
> *multi-tenant SaaS*. Every feature below must be re-implemented with strict tenant isolation —
> **data does not mix between vendors.**

---

## Status: Phases 0–6 ✅ COMPLETE + 2 hardening passes ✅

**Final hardening (all flagged issues resolved):**

- **Accepted quotes apply to the booking** — `QuoteController::accept()` now sets the booking total/
  currency to the agreed amount and supersedes other open quotes (transactional). Tested.
- **Add-ons are payout-correct** — `recomputeBookingTotal()` bumps BOTH `total` and
  `total_before_fees` by the add-on sum: the fee/earning delta stays constant (reports unaffected) and
  the vendor payout (`total_before_fees − commission + service_fee`) increases by the add-on — i.e.
  add-ons are vendor revenue, uncommissioned. (Verified payout uses `total_before_fees`, not `total`.)
- **Scheduled-message dispatch fixed** — occasion messages no longer collapse all recipients under a
  null `booking_id` (dedup now keyed on booking-or-recipient); logs upsert (one row per recipient),
  so skipped/failed rows no longer accumulate.
- **Team-member scoping** — all booking queries use `resolve_current_vendor_id()` (owner-aware), not
  `Auth::id()`; `created_by` still records the acting user.
- **Pre-existing bug** — dead `BookingsController` route refs removed; `route:list` works (960 routes).
- **Code hygiene** — unused `Auth` imports removed from 6 refactored controllers.

**Test suite: 16 tests / 68 assertions, all passing** (model isolation, HTTP smoke of all 14 pages,
end-to-end MCP marketplace flow with vendor routing, quote-apply). Live-DB writes are rolled back;
verified zero stray data. Remaining open items are external only (live WhatsApp/Telegram/email/AI/
payment delivery need real credentials/SMTP/gateway to confirm in staging — code paths wired and
structurally verified).

**Phase 5 (central Tanova MCP marketplace) — DONE:**

- **Public MCP API** at `/api/mcp/*` (14 endpoints), Tanova-branded manifest + OpenAPI, rate-limited,
  registered via `RouterServiceProvider::mapMcpRoutes()`. `McpController` reuses `TanovaEngine`.
- **Discovery / transaction split** — discovery (`destinations`, `experiences`, `availability`) spans
  visible listings ACROSS vendors (`MarketplaceListing::public()` = `withoutVendorScope` + visible);
  the booking draft is stamped with the owning vendor (resolved from the chosen experience's
  `author_id`), and `submit_booking` creates a Booking under that `vendor_id` — **fails loud** (422)
  if the owner can't be resolved. Verified by an end-to-end test (rolled back on live DB).
- **Marketplace visibility** — `bc_marketplace_listings` + `MarketplaceListing` + vendor toggle page.
- **AI Requests inbox** — `/vendor/ai-requests` lists MCP bookings (vendor-scoped via BelongsToVendor),
  with the traveller↔operator thread and **"Draft with AI (Evalyne style)"** reusing
  `ConciergeAiService`. Plus a count endpoint for the polling badge.
- **Marketplace KPIs** — Tanova sessions / bookings / conversion added to Analytics.
- **Booking routing → vendor dashboard** — confirmed: MCP booking → Booking(vendor_id=owner) →
  AI Requests + Booking Report. `created_by`/`Auth` not relied on (public flow).

**Phase 6 (admin/portal UX polish) — DONE:**

- **Cmd/Ctrl+K command palette**, **AI-inbox polling badge** (60s), **inactivity timer** (30 min) —
  one self-contained partial included in the vendor sidebar.
- **Go-Live checklist** (`/vendor/go-live`) and embedded **Operations manual** (`/vendor/help`).

**Audit (this build):** all PHP lint-clean; all Blade views compile; `route:list` 960 routes (14 MCP +
9 new vendor); migrations applied; **isolation suite 11 tests/32 assertions**, **HTTP smoke 15
assertions** (all 14 pages render), **MCP flow 3 tests/18 assertions** (manifest, discovery gate,
draft→quote→submit, vendor routing). Live-DB rollback verified — no stray data. Channel/email/AI
delivery still requires live credentials to confirm in staging.

---

## Status: Phases 0–4 ✅ + hardening pass ✅

**Hardening pass (closing carry-forward items from 1–4):**

- **Pre-existing bug fixed** — removed dead, broken `BookingsController`/`ServicesController`/
  `TanovaController` route refs in `routes/api.php` (duplicates of the real `api-vendor.php`).
  `php artisan route:list` now works (938 routes).
- **Add-ons reflected in booking total** — idempotent: base total snapshotted to booking meta
  `ops_base_total`; `total = base + Σ add-ons` on attach/detach. Commission columns intentionally
  untouched (documented).
- **Non-email channels wired** — `VendorChannelDispatcher` sends via each vendor's own connected
  WhatsApp (Graph API) / Telegram (Bot API) credentials; degrades to `skipped` (with reason) when a
  channel isn't connected or no recipient id exists. `vendor:dispatch-scheduled-messages` uses it.
- **Campaigns queued** — `SendCampaignJob` (ShouldQueue); request returns immediately, recipients
  resolved by the campaign's own `vendor_id` in the worker.
- **Analytics cached** — per-vendor 5-min cache (`?refresh=1` busts it).
- **Edit UIs added** — inline collapsible edit forms for pricing tiers, upsells, scheduled messages.
- **Menu** — new modules grouped into **Daily Operations / Operations / Engagement / Reports**
  sections (via `config/vendor_nav.php`) and **bolded** for visibility.

**Verification:** all PHP lint-clean; 11 Blade views compile; `route:list` 938 routes; **11 model
isolation tests** (32 assertions) + **HTTP smoke test** (every page renders without 500 as a real
vendor against live data); dispatch command dry-run OK. Tests run against a throwaway MySQL DB —
never live data.

---

## How Tsoka enforces tenancy today (baseline)

- Tenant key is `vendor_id` = `auth()->user()->vendor_id` (the vendor/tenant a user belongs to).
- Tenant-owned tables carry a `vendor_id` column.
- Controllers **manually** filter `->where('vendor_id', $vendorId)` and guard with
  `abort_if($row->vendor_id !== $vendorId, 403)`.
- Plan gating uses the `pro_plan` middleware + `VendorPlan` / `VendorSubscription` / `VendorPlanMeta`.

**Risk:** isolation is manual and scattered (the `?? 0` fallback can collide). Phase 0 makes it structural.

---

## Guiding principle (applies to EVERY phase)

1. Every new table has an indexed `vendor_id` column.
2. Every model is **automatically** scoped to the current vendor (global scope, not manual filtering).
3. **No plan-gating** — these features are available to every vendor. (No `feature:<key>`
   middleware, no capability registry. Entitlement checks can be added later if billing tiers ever
   need them, but are explicitly out of scope now.)
4. No cross-vendor lookups, no shared sequences. Human-facing numbers (invoice #, ticket #) are **per-vendor**.
5. Background jobs/crons loop **per vendor** — never operate across the whole table.

---

## Phase 0 — Tenancy foundation (FIRST, blocks everything) — ✅ DONE

Make isolation structural, not something each developer must remember.

**Delivered:**

- `resolve_current_vendor_id()` helper (`app/Helpers/AppHelper.php`) — single source of truth for
  the current tenant. Order: API-key `VendorContext` → authenticated user (`vendor_id ?: id`, so a
  team member resolves to its owner) → null for CLI/system. Replaces the scattered, fragile
  `auth()->user()?->vendor_id ?? 0`.
- `App\Scope\VendorScope` — global scope that auto-filters `vendor_id = current vendor`; no-op when
  no tenant resolves (CLI/jobs/system); exposes `->withoutVendorScope()` escape hatch for
  super-admin / marketplace-discovery / background reads.
- `App\Traits\BelongsToVendor` — boots the scope + auto-stamps `vendor_id` on create. Opt a model in
  with one `use BelongsToVendor;`. Column overridable via `VENDOR_COLUMN`.
- Retrofitted `TanovaTrip` onto the trait; backfill migration
  (`..._backfill_tanova_trip_vendor_id.php`) sets `vendor_id = user_id` for legacy null rows
  (vendor owners were previously saved with null `vendor_id` — fixed by the auto-stamp).
- `tests/Feature/Tanova/BelongsToVendorScopeTest.php` — 6 passing tests (auto-stamp, scoped reads,
  team-member→owner, cross-vendor 404/invisibility, `withoutVendorScope`, system no-op). Runs
  against an isolated throwaway MySQL DB — never the real `tsoka_portal` data.

**Note:** the trait is opt-in per model. Existing Botble admin models (Tour/Hotel/Booking) were
**not** retrofitted in Phase 0 — they have admin list views that expect all rows; retrofitting them
needs per-controller `withoutVendorScope()` audits and belongs in their respective phases. New
feature tables (Phases 1–5) adopt the trait from day one.

*Effort: small–medium. Non-negotiable prerequisite. Complete.*

---

## Phase 1 — Booking depth — ✅ DONE

Builds on existing Bookings module. All models use `BelongsToVendor` (auto-isolated). Lives in the
**Vendor portal** (`/vendor/*`, menu via `ModuleProvider::getUserMenu()`).

**Delivered:**

- **Pricing tiers** — `bc_vendor_pricing_tiers` + `VendorPricingTier` (percentage/fixed markup,
  `applyTo()` helper), `PricingTierController` CRUD, `/vendor/pricing-tiers` page.
- **Upsells / add-ons** — `bc_vendor_upsells` (catalog) + `bc_booking_upsells` (per-booking,
  **price snapshotted** so catalog edits never rewrite history). `VendorUpsell::computeTotal()`
  respects per_booking / per_person / per_night. `UpsellController` catalog CRUD + attach/detach.
- **Counter-offer / quote flow** — `bc_booking_quotes` + `BookingQuote` (sent/accepted/declined/
  countered chain via `parent_id`). `QuoteController`: send / counter / accept / decline.
- **Per-booking ops page** — `/vendor/bookings/{id}/ops` (`BookingOpsController`) aggregates add-ons,
  quotes and check-in for one booking.

**Note:** `bc_bookings.total` is deliberately **not** mutated by add-ons — they're an additive ledger
summed for display. Folding add-ons into the booking total touches commission/payout math and is
deferred to a payments-aware follow-up.

*Effort: medium. Complete. Isolation verified.*

---

## Phase 2 — Daily operations — ✅ DONE

Self-contained, no external integrations. All vendor-portal pages, `BelongsToVendor`-isolated.

**Delivered:**

- **Waitlist** — `bc_vendor_waitlist` + `VendorWaitlist` (waiting/notified/converted/cancelled),
  `WaitlistController` CRUD + status actions, `/vendor/waitlist` page with filter.
- **Check-In** — `bc_booking_checkins` + `BookingCheckin` (expected/checked_in/checked_out/no_show,
  one per booking). `CheckinController` index (by date) + check-in / check-out / no-show actions.
- **Today** — `TodayController` + `/vendor/today`: arrivals / departures / in-house / waitlist
  counts and lists for a chosen date (read-only).

*Effort: medium. Complete. Isolation verified.*

---

## Phase 3 — Automation & engagement — ✅ DONE

All models use `BelongsToVendor`. The dispatcher runs in CLI (scope is a no-op there) and **loops
per vendor explicitly**, stamping every log/query with that vendor's id — never crossing tenants.

**Delivered:**

- **Loyalty** — `bc_vendor_loyalty_{tiers,accounts,transactions}` + `LoyaltyTier`/`LoyaltyAccount`/
  `LoyaltyTransaction`. `LoyaltyController`: tier CRUD, member list, points adjust (append-only
  ledger + auto tier re-eval via `LoyaltyTier::forPoints()`).
- **Scheduled Messages** — `bc_vendor_scheduled_messages` (+ logs) + `ScheduledMessage`.
  `ScheduledMessageController` CRUD/toggle. **`vendor:dispatch-scheduled-messages`** command
  (scheduled daily 08:00 in `Kernel`): resolves due messages by trigger/offset, email sends via
  `Mail::raw`, idempotent per (message, booking), other channels logged as integration hook points.
- **Occasions** — `bc_vendor_occasions` + `VendorOccasion` + CRUD; feeds the `occasion` trigger
  (annual month/day match).
- **Email campaigns** — `bc_vendor_campaigns` + `VendorCampaign`. `CampaignController`: draft + send
  to audience (all/completed/upcoming) resolved from the **vendor's own** booking emails at send time.

**Note:** non-email channels (WhatsApp/Telegram/SMS) are logged as pending hook points — wiring them
to each vendor's existing integration credentials is a focused follow-up. Large campaigns send
synchronously for now; queueing is a later optimization.

*Effort: large. Complete. Isolation verified.*

---

## Phase 4 — Analytics dashboards — ✅ DONE

- `AnalyticsController` + `/vendor/analytics`: per-vendor KPI cards (12-month revenue, bookings,
  loyalty members, waitlisted, no-shows) and **Chart.js** charts — revenue trend, bookings/month,
  status breakdown, service-type breakdown. Every query filtered by the current vendor (bookings
  explicitly; ops models via `BelongsToVendor`). Chart.js loaded via CDN (no bundle dependency).

**Note:** queries run live (12-month window). Pre-aggregating into a per-vendor stats table (cron)
remains a future optimization if dashboards get heavy at scale.

*Effort: medium. Complete. Isolation verified.*

---

## Phase 5 — Outward AI / MCP layer (flagship Tanova upgrade)

Biggest differentiator, most involved.

### Architecture: ONE central MCP marketplace, branded **Tanova**

There is a **single** MCP server / OpenAPI spec / manifest / tool set, operated centrally and
branded **Tanova** (the consumer-facing AI travel concierge). **Tsoka Portal** stays the internal
B2B platform name and is never exposed to end users or AI platforms. Vendors are *suppliers* behind
the Tanova marketplace — they do **not** stand up or configure their own MCP; they only flip
**visibility switches**. This removes per-vendor setup friction and centralises rate-limiting, abuse
protection, and versioning.

**Mode: Marketplace only.** One public entry point. AI platforms (ChatGPT/Gemini/Copilot) talk to
"Tanova"; Tanova searches across all opted-in vendor inventory and auto-routes each booking to the
owning vendor. (Think "Booking.com via ChatGPT," under the Tanova brand.) Per-vendor branded MCP
endpoints are explicitly out of scope.

Everything public is branded Tanova:

- MCP server host (e.g. `mcp.tanova.*`), manifest name, tool descriptions, and assistant persona.
- The traveller never sees a vendor or "Tsoka" name during discovery — only Tanova. The fulfilling
  vendor is surfaced at/after booking confirmation (as the supplier of record), not as the brand.

The principle that keeps it safe: **separate discovery from transaction.**

- **Discovery (cross-vendor, Tanova-branded):** the marketplace surfaces only inventory each vendor
  has explicitly flagged as Tanova-visible. A tool result never returns another vendor's *private*
  data (customers, notes, payments) — only public listing fields.
- **Transaction (always single-vendor):** the moment a draft / booking / deposit is created it is
  stamped with the owning `vendor_id` and fully isolated thereafter (Phase 0 global scope applies).
  Payment routes to **that** vendor's payment account.

So "data doesn't mix" still holds — the only cross-vendor reads happen on the explicitly-public
Tanova discovery surface, gated by the per-listing visibility flag.

### Vendor manages (lightweight)

- Per-listing **visibility toggle** ("list on Tanova marketplace").
- Optional per-channel rules (e.g. visible to ChatGPT but not Gemini).
- Payment account. (Marketplace access is available to all vendors — no plan-gate.)

### Tanova platform manages (centrally)

- One Tanova MCP server, one OpenAPI spec, one manifest, the **12 MCP tools**
  (`generate_itinerary`, `check_availability`, `submit_booking`, `pay_deposit`, …) reusing `TanovaEngine`.
- Tenant resolution: marketplace search → cross-vendor read of *visible* rows only; everything else →
  owning vendor only.
- Rate limits, abuse protection, spec versioning — once, not per vendor.
- Booking routing: match selected listing → owning vendor → that vendor's payment account + inbox.

### Booking routing → vendor dashboard (end-to-end)

The path **Tanova booking → vendor dashboard** is already partly wired and must be completed:

#### Already works today

- `TanovaAdminController::moveToBookings()` creates a real `Booking` stamped with `vendor_id`
  (guest details, dates, total, `status = PROCESSING`). — `pro/Tanova/Controllers/TanovaAdminController.php`
- The vendor's **Booking Report** (`VendorController::bookingReport()` → `getBookingHistory(..., Auth::id())`)
  lists bookings filtered to that vendor's id. — `modules/Vendor/Controllers/VendorController.php`
- So the chain *Tanova trip → Booking(vendor_id) → vendor Booking Report* exists.

#### Gap to close in this phase

1. **Derive vendor from the listing, not the session.** Today `moveToBookings` sets
   `vendor_id = $trip->user_id ?? Auth::id()`, which assumes a logged-in admin. In the marketplace
   flow there is no admin — the MCP `submit_booking` tool must resolve `vendor_id` from the
   **owning vendor of the selected experience/listing**. No `Auth::id()` / `?? 0` fallback — if the
   owner can't be resolved, **fail loudly** (a wrong fallback would drop the booking into the wrong
   vendor's dashboard).
2. **Auto-run the move.** `submit_booking` (+ `pay_deposit`) calls the booking-creation logic
   automatically on confirmation, instead of waiting for a human "Move to Bookings" click.

#### Resulting automatic flow

> Traveller books on Tanova → MCP resolves owning vendor from the chosen listing → Booking created
> with that `vendor_id` → appears in that vendor's dashboard (Booking Report + AI Requests inbox) →
> payment routed to that vendor's account.

Isolation holds throughout: the booking is stamped to exactly one vendor at creation; every vendor
only sees `vendor_id = self` rows.

### Also in this phase

- **AI Requests inbox** — vendor-scoped inbox for AI-generated bookings with conversation threads.
- **"Draft with AI" replies** in concierge, reusing the existing Claude integration.
- Per-vendor conversion KPIs (vendor sees only their own).

*Effort: large. Sequence last — depends on Phase 1 (quote flow) and benefits from Phase 4 (analytics).*

---

## Phase 6 — Admin/portal UX polish

Pure front-end, no tenancy implications. Slot in anytime after Phase 1.

- Cmd+K command palette, real-time polling badges (bookings / AI inbox), inactivity timeout,
  Go-Live checklist, embedded ops manual.

*Effort: small–medium.*

---

## Suggested sequencing

`0 → 1 → 2 → 3 → 4 → 5`, with **6** dropped in opportunistically.
Phase 0 is the mandatory first step; Phase 5 (MCP) is highest-value but should follow the booking/quote plumbing.

All four feature areas (Booking depth, Ops & analytics, Automation, MCP) are in scope.

---

## Per-phase definition of done (checklist)

- [ ] New tables carry indexed `vendor_id`
- [ ] Models use `BelongsToVendor` (auto-scope)
- [ ] Per-vendor sequences for human-facing numbers (invoices, tickets)
- [ ] Crons/jobs loop per vendor
- [ ] Cross-tenant isolation test added and passing
