# GoTrip Travel B2B SaaS Platform

## What You Have

**gotrip/bc-cms** = BookingCore (Complete Laravel Travel Marketplace)
- 30+ modules already built
- Multi-vendor support
- Payment gateways (Stripe, Flutterwave, Paypal, etc.)
- Multi-currency & multi-language
- Booking system for: Hotels, Tours, Flights, Cars, Boats, Agencies, Events, Visa, etc.
- User management, roles, plans
- Dashboard, analytics, reports

**tsokaupgrade/public_html** = Frontend website
- Marketing pages
- Booking search & display
- Checkout

---

## The Vision: Multi-Tenant SaaS Travel Platform

```
┌─────────────────────────────────────────────────────┐
│ TsokaTravel SaaS Platform (tsokaupgrade)            │
│                                                      │
│ • Marketing website                                 │
│ • Operator login/signup                             │
│ • Operator dashboard                                │
│ • API key management                                │
│ • White-label site builder                          │
│ • Analytics                                         │
│ • Billing                                           │
└─────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────┐
│ GoTrip Multi-Tenant Backend (gotrip/bc-cms)         │
│                                                      │
│ Core Booking System:                                │
│ • Hotels, Tours, Flights, Cars, Boats              │
│ • Agencies, Events, Visa, Courses                  │
│ • Bookings, Payments, Invoices                     │
│ • Users, Reviews, Coupons                          │
│ • Multi-language, Multi-currency                   │
│                                                      │
│ SaaS Features (NEW):                                │
│ • API key authentication                           │
│ • Tenant isolation (vendor_id or new tenant_id)    │
│ • Usage tracking & rate limiting                   │
│ • White-label site configuration                   │
│ • Subscription & billing                           │
└─────────────────────────────────────────────────────┘
                    ↙          ↘
        ┌──────────────────┐   ┌──────────────────┐
        │ Hosted Sites     │   │ Self-Hosted      │
        │ (Our servers)    │   │ (Anywhere)       │
        │                  │   │                  │
        │ dare2travel      │   │ dare2travel.com  │
        │ .tsokatravel.com │   │ (Vercel, AWS...)│
        └──────────────────┘   └──────────────────┘
             ↓                         ↓
      ┌──────────────────┐   ┌──────────────────┐
      │ CUSTOMERS        │   │ CUSTOMERS        │
      │ Book hotels,     │   │ Book hotels,     │
      │ tours, flights   │   │ tours, flights   │
      │ from that vendor │   │ from that vendor │
      └──────────────────┘   └──────────────────┘
```

---

## Current Architecture

### GoTrip Structure

```
gotrip/
├── bc-cms/                  (Backend - Laravel)
│   ├── modules/
│   │   ├── Hotel/           (Hotel bookings)
│   │   ├── Tour/            (Tour bookings)
│   │   ├── Flight/          (Flight bookings)
│   │   ├── Car/             (Car rentals)
│   │   ├── Boat/            (Boat rentals)
│   │   ├── Agency/          (Travel agencies)
│   │   ├── Event/           (Event ticketing)
│   │   ├── Visa/            (Visa services)
│   │   ├── Booking/         (Core booking logic)
│   │   ├── Order/           (Order management)
│   │   ├── Payment/         (Payment processing)
│   │   ├── Vendor/          (Multi-vendor support)
│   │   ├── User/            (User & subscription)
│   │   ├── Dashboard/       (Admin dashboard)
│   │   ├── Report/          (Analytics)
│   │   ├── Language/        (I18n)
│   │   ├── Location/        (Geo data)
│   │   └── ... (20+ more)
│   ├── app/
│   ├── config/
│   ├── database/
│   └── composer.json
│
└── public_html/             (Frontend - HTML/JS/PHP)
    ├── css/
    ├── js/
    ├── index.php
    └── libs/
```

---

## Three-Phase SaaS Transformation

### Phase 1: Add SaaS Infrastructure to GoTrip Backend

**Goal:** Make GoTrip multi-tenant with API keys

**Changes to gotrip/bc-cms:**

1. **Add Tenant/Vendor Isolation**
   - Create `Tenant` model (if not using existing Vendor)
   - Add `tenant_id` to all models
   - Scope all queries to authenticated tenant

2. **Add API Key System**
   - `TenantApiKey` model
   - `TenantApiUsage` model
   - Middleware: `ApiKeyAuth`
   - Middleware: `TrackApiUsage`

3. **Add SaaS Configuration**
   - `TenantSubscription` model (already might exist as `VendorSubscription`)
   - `TenantPlan` model (rate limits, features)
   - `TenantWhiteLabelSite` model (branding, domain)

4. **Create API Endpoints**
   - `POST /api/tenant/api-keys` — generate key
   - `GET /api/tenant/api-keys` — list keys
   - `DELETE /api/tenant/api-keys/{id}` — revoke
   - `GET /api/tenant/usage` — usage stats
   - `POST /api/tenant/white-label-sites` — create site config
   - `GET /api/tenant/white-label-sites/{id}` — get site config

**Effort:** ~2-3 weeks
- Refactor Vendor module to Tenant (or extend it)
- Add API key models & middleware
- Scope existing endpoints
- Create new SaaS endpoints

---

### Phase 2: Build Operator Dashboard (tsokaupgrade)

**Goal:** Operators manage their tenant account

**tsokaupgrade changes:**

1. **Add Authentication**
   - NextAuth.js integration
   - Login/signup pages
   - Session management

2. **Add Operator Dashboard**
   - Dashboard home (stats)
   - Account settings
   - API keys manager
   - White-label sites manager
   - Billing/subscription
   - Team management

3. **Integrate with GoTrip API**
   - API client wrapper
   - Proxy requests to gotrip backend
   - Authenticate with Sanctum token

**Tech Stack:**
- Next.js 14 (already exists)
- NextAuth.js
- Tailwind CSS (already exists)
- Recharts (analytics)
- Axios (API client)

**Effort:** ~4-5 weeks

---

### Phase 3: White-Label Site Builder

**Goal:** Operators create & customize websites without code

**Implementation:**

1. **Site Builder Backend** (gotrip/bc-cms)
   - `WhiteLabelSite` model (branding, features, domain)
   - `WhiteLabelPage` model (CMS pages)
   - `WhiteLabelSetting` model (config)
   - Public API endpoints for site discovery

2. **Site Builder Frontend** (tsokaupgrade)
   - Branding editor (logo, colors, fonts)
   - Page editor (drag-drop or WYSIWYG)
   - Settings manager
   - Live preview

3. **White-Label Website Generator**
   - React template (public repo)
   - Reads site config from API
   - Uses tenant's API key
   - Displays bookings for their services
   - Can be hosted anywhere

**Effort:** ~6-8 weeks

---

## Implementation Details

### Phase 1A: API Key System in GoTrip

**New Models (gotrip/bc-cms/modules/Tenant/):**

```php
// TenantApiKey.php
class TenantApiKey extends Model {
    protected $fillable = ['tenant_id', 'name', 'key', 'key_hash', 'rate_limit', 'active', 'expires_at', 'last_used_at'];
    
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function usage() { return $this->hasMany(TenantApiUsage::class); }
    
    public static function generate(Tenant $tenant, string $name) { ... }
    public static function findByKey(string $key) { ... }
    public function isValid() { ... }
    public function recordUsage(...) { ... }
}

// TenantApiUsage.php
class TenantApiUsage extends Model {
    protected $fillable = ['tenant_api_key_id', 'endpoint', 'status_code', 'response_time_ms', 'method'];
    
    public function apiKey() { return $this->belongsTo(TenantApiKey::class); }
}

// Tenant.php (new, or extend Vendor)
class Tenant extends Model {
    public function apiKeys() { return $this->hasMany(TenantApiKey::class); }
    public function subscription() { return $this->hasOne(TenantSubscription::class); }
    public function whiteLabelSites() { return $this->hasMany(WhiteLabelSite::class); }
}
```

**New Middleware:**

```php
// app/Http/Middleware/ApiKeyAuth.php
class ApiKeyAuth {
    public function handle(Request $request, Closure $next) {
        $key = $request->header('Authorization');
        if (!str_starts_with($key, 'Bearer ')) abort(401);
        
        $apiKey = TenantApiKey::findByKey(substr($key, 7));
        if (!$apiKey->isValid()) abort(401);
        
        Auth::loginUsingId($apiKey->tenant_id);
        $request->attributes->set('api_key', $apiKey);
        
        return $next($request);
    }
}

// app/Http/Middleware/TrackApiUsage.php
class TrackApiUsage {
    public function handle(Request $request, Closure $next) {
        $start = microtime(true);
        $response = $next($request);
        $elapsed = (int)((microtime(true) - $start) * 1000);
        
        if ($apiKey = $request->attributes->get('api_key')) {
            $apiKey->recordUsage($request->path(), $response->status(), $elapsed, $request->method());
        }
        
        return $response;
    }
}
```

**Migration:**

```php
// database/migrations/2026_05_30_create_tenant_api_keys.php
Schema::create('tenant_api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->string('name');
    $table->string('key')->unique();
    $table->string('key_hash')->unique();
    $table->integer('rate_limit')->default(10000);
    $table->boolean('active')->default(true);
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});

Schema::create('tenant_api_usage', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_api_key_id')->constrained('tenant_api_keys')->cascadeOnDelete();
    $table->string('endpoint');
    $table->integer('status_code');
    $table->integer('response_time_ms');
    $table->string('method')->default('GET');
    $table->timestamp('created_at')->index();
});
```

**API Endpoints (gotrip/bc-cms/modules/Tenant/):**

```php
// TenantApiKeyController.php
class TenantApiKeyController extends Controller {
    public function index(Request $request) { /* list tenant's API keys */ }
    public function store(Request $request) { /* generate new key */ }
    public function destroy(Request $request, $id) { /* revoke key */ }
    public function rotate(Request $request, $id) { /* rotate key */ }
    public function validateKey(Request $request) { /* validate key (public) */ }
}
```

---

### Phase 1B: White-Label Site Configuration

**New Models:**

```php
// WhiteLabelSite.php
class WhiteLabelSite extends Model {
    protected $fillable = [
        'tenant_id', 'name', 'domain', 'custom_domain',
        'logo_url', 'primary_color', 'secondary_color',
        'show_hotels', 'show_tours', 'show_flights', 'show_cars',
        'featured_products', 'published_at'
    ];
    
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function pages() { return $this->hasMany(WhiteLabelPage::class); }
    public function settings() { return $this->hasMany(WhiteLabelSetting::class); }
}

// WhiteLabelPage.php
class WhiteLabelPage extends Model {
    public function site() { return $this->belongsTo(WhiteLabelSite::class); }
}
```

**Public API for Site Discovery:**

```php
// Routes
Route::get('/api/sites/{domain}', [WhiteLabelSiteController::class, 'showPublic']);
Route::get('/api/sites/{domain}/pages', [WhiteLabelSiteController::class, 'pagesPublic']);
```

---

### Phase 2: tsokaupgrade Dashboard Structure

```
tsokaupgrade/
├── app/
│   ├── (public)/              ← Marketing pages (exist)
│   ├── (auth)/                ← Auth pages (NEW)
│   │   ├── login/
│   │   ├── signup/
│   │   └── forgot-password/
│   ├── dashboard/             ← Protected routes (NEW)
│   │   ├── page.tsx
│   │   ├── account/
│   │   ├── api-keys/
│   │   ├── white-label-sites/
│   │   ├── analytics/
│   │   ├── billing/
│   │   └── layout.tsx
│   └── api/
│       ├── auth/[...nextauth]/ ← NextAuth
│       └── proxy/[...path]/    ← Proxy to gotrip
├── components/
│   ├── dashboard/
│   │   ├── ApiKeyManager.tsx
│   │   ├── WhiteLabelSiteBuilder.tsx
│   │   ├── AnalyticsDashboard.tsx
│   │   └── BillingPanel.tsx
│   └── shared/
├── lib/
│   ├── auth.ts              ← NextAuth config
│   ├── api-client.ts        ← GoTrip API wrapper
│   └── constants.ts
└── package.json
```

---

### Phase 3: White-Label Website

**Standalone React App:**

```
white-label-template/
├── .env.example
│   VITE_TSOKA_API=https://gotrip.tsokatravel.com
│   VITE_OPERATOR_API_KEY=<tenant_api_key>
│   VITE_SITE_SLUG=dare2travel
├── src/
│   ├── App.tsx
│   ├── services/
│   │   └── goTripApi.ts     ← Calls gotrip API
│   ├── pages/
│   │   ├── Home.tsx
│   │   ├── Hotels.tsx
│   │   ├── Tours.tsx
│   │   ├── Flights.tsx
│   │   ├── Bookings.tsx
│   │   └── [CustomPages]
│   └── components/
└── package.json
```

**Deployment Options:**
- Vercel (easiest)
- Netlify
- AWS
- Self-hosted VPS/Docker
- Their own server

---

## Pricing & Revenue Model

### Operator Tiers

```
Starter:     $29/month    10,000 API requests
Pro:         $99/month   100,000 API requests
Business:   $299/month   500,000 API requests
Enterprise: Custom       Unlimited + dedicated support
```

### What Operators Get
- ✅ Complete booking system (hotels, tours, flights, cars, etc.)
- ✅ Multi-language, multi-currency support
- ✅ Payment processing (20+ gateways)
- ✅ White-label website builder
- ✅ Analytics dashboard
- ✅ Team management
- ✅ API access for integrations
- ✅ Custom domain support
- ✅ Zero lock-in (can export data, run anywhere)

---

## Phase Timeline

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| **1: API Keys + Tenant Isolation** | 2-3 weeks | GoTrip is multi-tenant, supports API keys, rate limiting |
| **2: Operator Dashboard** | 4-5 weeks | tsokaupgrade has login, account management, billing |
| **3: Site Builder** | 6-8 weeks | Operators can create white-label sites, deploy anywhere |
| **Total** | **12-16 weeks** | **Complete B2B SaaS platform** |

---

## Success Criteria

### Phase 1
- ✅ Operators can generate API keys
- ✅ API keys authenticate requests
- ✅ Requests are rate-limited
- ✅ Usage is tracked

### Phase 2
- ✅ Operators can sign up & log in
- ✅ Operators can manage API keys
- ✅ Operators can see usage analytics
- ✅ Operators can manage subscription

### Phase 3
- ✅ Operators can create white-label sites
- ✅ Operators can customize branding
- ✅ Operators can add CMS pages
- ✅ Sites go live instantly
- ✅ Sites reach customers, handle bookings
- ✅ Bookings stored in operator's account

---

## Competitive Advantage

**What makes this unique:**

1. **Complete Booking System** — Hotels, tours, flights, cars, boats, agencies, events, visas (30+ modules)
2. **White-Label Ready** — Operators launch their own branded site in minutes
3. **Multi-Vendor Marketplace** — Already built into GoTrip
4. **Global Payments** — 20+ payment gateways supported
5. **Multi-Language/Currency** — Out of the box
6. **Zero Lock-In** — Operators can host anywhere
7. **Affordable** — $29-299/month (vs. $500+/mo SaaS competitors)
8. **Built for Travel** — Not generic, actually designed for travel businesses

---

## Next Steps

**Immediate:**
1. Audit GoTrip's existing Vendor module
   - Does it have multi-vendor isolation already?
   - What payment/subscription system exists?
   - How are tenants currently separated?

2. Plan API key integration
   - Extend existing vendor/tenant model
   - Or create new Tenant model

3. Start Phase 1 implementation
   - Add TenantApiKey, TenantApiUsage models
   - Create ApiKeyAuth middleware
   - Create API key endpoints

4. Prepare tsokaupgrade
   - Plan dashboard structure
   - Install NextAuth.js
   - Create API client wrapper

---

## Ready to Build?

Should we start with:
- **Phase 1** (Foundation - API keys + tenant isolation)
- **All three phases** (Full platform in 12-16 weeks)

What's your timeline?
