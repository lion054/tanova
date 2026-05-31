@extends('layouts.user')
@section('title', __('API Keys'))
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- ONE-TIME KEY REVEAL --}}
            @if(session('new_key'))
            <div class="alert alert-warning d-flex align-items-start gap-3">
                <div style="font-size:1.5rem">🔑</div>
                <div class="flex-grow-1">
                    <strong>{{ __('Copy your new API key now — it will not be shown again.') }}</strong>
                    <p class="mb-1 text-muted small">Key name: <em>{{ session('new_key_name') }}</em></p>
                    <div class="input-group mt-2" style="max-width:600px">
                        <input type="text" id="new-key-input" class="form-control font-monospace"
                               value="{{ session('new_key') }}" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('new-key-input').value);this.textContent='Copied!'">
                            {{ __('Copy') }}
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- TABS: API KEYS & DOCUMENTATION                                 --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys-panel" type="button" role="tab">
                        <i class="fa fa-key me-2"></i>{{ __('API Keys') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-panel" type="button" role="tab">
                        <i class="fa fa-book me-2"></i>{{ __('Documentation') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- TAB 1: API KEYS --}}
                <div class="tab-pane fade show active" id="keys-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Your API Keys') }}</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-new-key">
                    + {{ __('Generate New Key') }}
                </button>
            </div>

            <div class="table-responsive mb-5">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Domain') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Annual usage') }}</th>
                            <th>{{ __('Last used') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($keys as $key)
                        @php $pct = $key->rate_limit > 0 ? round($key->annualUsageCount() / $key->rate_limit * 100) : 0; @endphp
                        <tr>
                            <td><strong>{{ $key->name }}</strong></td>
                            <td>
                                @if($key->domain)
                                    <code>{{ $key->domain }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($key->active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Revoked') }}</span>
                                @endif
                            </td>
                            <td>
                                @php $annual = $key->annualUsageCount(); @endphp
                                <div class="progress" style="height:6px;width:120px;display:inline-block;vertical-align:middle">
                                    <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                         style="width:{{ min($pct,100) }}%"></div>
                                </div>
                                <small class="ms-2">{{ number_format($annual) }} / {{ number_format($key->rate_limit) }}</small>
                            </td>
                            <td>{{ $key->last_used_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                @if($key->active)
                                <form method="POST" action="{{ route('vendor.api_keys.rotate', $key->id) }}" style="display:inline"
                                      onsubmit="return confirm('{{ __('Rotate this key? Your current key will stop working immediately.') }}')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning">{{ __('Rotate') }}</button>
                                </form>
                                <form method="POST" action="{{ route('vendor.api_keys.revoke', $key->id) }}" style="display:inline"
                                      onsubmit="return confirm('{{ __('Revoke this key? This cannot be undone.') }}')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Revoke') }}</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                {{ __('No API keys yet. Generate one to start integrating your website.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

                {{-- END TAB 1 --}}
                </div>

                {{-- TAB 2: DOCUMENTATION --}}
                <div class="tab-pane fade" id="docs-panel" role="tabpanel">
                    <div class="alert alert-info mb-4">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>{{ __('Note:') }}</strong> {{ __('All API requests return only YOUR data. You cannot access other vendors\' data.') }}
                    </div>

                    {{-- Authentication --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('Authentication') }}</div>
                        <div class="card-body">
                            <p class="mb-2">{{ __('Include your API key in every request:') }}</p>
                            <pre class="bg-light p-2 rounded border">Authorization: Bearer YOUR_API_KEY</pre>
                        </div>
                    </div>

                    {{-- 1. Get Services --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('1. Get Your Services') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Fetch your hotels, tours, spaces, and other services.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/services/{type}</pre>
                            <p class="mb-2"><strong>{{ __('Types:') }}</strong> hotels, tours, spaces, cars, flights, boats</p>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/services/hotels' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small">{{ __('Returns: List of your services with pricing, availability, and details. Only YOUR services are returned.') }}</p>
                        </div>
                    </div>

                    {{-- 2. Tanova (Trip Planning Engine) --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('2. Trip Planning (Tanova)') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Tanova is a deterministic, weather-aware trip planning engine. Generate 8 customized itinerary packages with activities, accommodations, and real-time weather forecasts. Only your vendor data is included.') }}</p>

                            {{-- 2.1 Generate Itineraries --}}
                            <h6 class="mt-4 mb-3">{{ __('2.1 Generate Trip Itineraries') }}</h6>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/tanova/generate</pre>
                            <p class="mb-2"><strong>{{ __('Request Body:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">{
  "destination": "Victoria Falls",
  "start_date": "2026-06-15",
  "end_date": "2026-06-20",
  "guests": 2,
  "place_id": 6,
  "budget": "mid-range",
  "notes": "Prefer adventure activities"
}</pre>
                            <p class="mb-2 small"><strong>{{ __('Budget Tiers:') }}</strong></p>
                            <ul class="small mb-3">
                                <li><code>budget</code> — USD $2,000</li>
                                <li><code>mid-range</code> — USD $5,000 (recommended)</li>
                                <li><code>luxury</code> — USD $10,000</li>
                            </ul>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X POST '{{ rtrim(config('app.url'),'/') }}/api/v/tanova/generate' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{
    "destination": "Victoria Falls",
    "start_date": "2026-06-15",
    "end_date": "2026-06-20",
    "guests": 2,
    "budget": "mid-range"
  }'</pre>
                            <p class="text-muted small">{{ __('Returns: Trip with 8 packages, each containing daily itinerary, activities, accommodation, meals, weather forecast, and pricing.') }}</p>

                            {{-- 2.2 List Trips --}}
                            <h6 class="mt-4 mb-3">{{ __('2.2 List Your Generated Trips') }}</h6>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips</pre>
                            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
                            <ul class="small mb-3">
                                <li><code>page</code> — pagination (default: 1)</li>
                                <li><code>per_page</code> — results per page (default: 15, max: 100)</li>
                            </ul>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/tanova/trips?page=1&per_page=15' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: Paginated list of your generated trips with summary info and links.') }}</p>

                            {{-- 2.3 Get Trip Details --}}
                            <h6 class="mt-4 mb-3">{{ __('2.3 Get Trip Details') }}</h6>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips/{id}</pre>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/tanova/trips/42' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: Full trip details including all 8 packages with complete day-by-day itinerary, weather forecast, activities, accommodation, and cost breakdown.') }}</p>

                            {{-- 2.4 Get Replan Suggestions --}}
                            <h6 class="mt-4 mb-3">{{ __('2.4 Get Weather-Based Replan Suggestions') }}</h6>
                            <p class="text-muted small mb-3">{{ __('Analyze weather changes since trip generation and get Claude AI suggestions for intelligent activity swaps.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips/{id}/replan</pre>
                            <p class="mb-2"><strong>{{ __('How It Works:') }}</strong></p>
                            <ol class="small mb-3">
                                <li>Fetches current weather forecast for trip dates</li>
                                <li>Detects significant changes: 5°C+ temperature shift, 20%+ rain change, or condition changes</li>
                                <li>Uses Claude AI to intelligently suggest activity swaps</li>
                                <li>Returns structured recommendations for your review</li>
                            </ol>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/tanova/trips/42/replan' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: has_changes (boolean), weather_changes (array), suggestions (activity swap recommendations).') }}</p>

                            <div class="alert alert-info small mt-3">
                                <strong>{{ __('Note:') }}</strong> {{ __('All trips include 14-day weather forecasts. Dates beyond 14 days show "unavailable" weather data. Vendor isolation ensures your trips only use your accommodations and services.') }}
                            </div>
                        </div>
                    </div>

                    {{-- 3. Bookings --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('3. Get Bookings') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Fetch customer bookings for your services.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/bookings</pre>
                            <p class="mb-2"><strong>{{ __('Query Parameters (optional):') }}</strong></p>
                            <ul class="small mb-3">
                                <li><code>status</code> — completed, pending, cancelled</li>
                                <li><code>service_type</code> — hotel, tour, space, car, flight, boat</li>
                                <li><code>date_from</code> — filter by check-in date (YYYY-MM-DD)</li>
                                <li><code>date_to</code> — filter by check-out date (YYYY-MM-DD)</li>
                            </ul>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/bookings?status=completed&service_type=hotel' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small">{{ __('Returns: Only YOUR bookings with customer details, dates, and payment status.') }}</p>
                        </div>
                    </div>

                    {{-- 4. Chat/Concierge --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('4. Customer Chat (Concierge)') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Get and respond to customer messages.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoints:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/concierge/conversations
POST /api/v/concierge/conversations/{id}/messages</pre>
                            <p class="mb-2"><strong>{{ __('Example (Get conversations):') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/concierge/conversations' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small">{{ __('Returns: Only YOUR customer conversations and messages.') }}</p>
                        </div>
                    </div>

                    {{-- 5. Submit Enquiry --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('5. Submit Enquiry') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Submit a new customer enquiry for your service.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/enquiries</pre>
                            <p class="mb-2"><strong>{{ __('Request Body:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">{
  "service_id": 1,
  "service_type": "hotel",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+1234567890",
  "check_in": "2026-07-01",
  "check_out": "2026-07-05",
  "guests": 2,
  "message": "Do you have availability?"
}</pre>
                            <p class="mt-3 text-muted small">{{ __('Returns: Enquiry confirmation with ID and status.') }}</p>
                        </div>
                    </div>

                    {{-- 6. Get/Update Booking --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('6. Get & Update Booking') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Get details of a specific booking and update its status.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoints:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/bookings/{id}
PUT /api/v/bookings/{id}</pre>
                            <p class="mb-2"><strong>{{ __('Get Booking Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/bookings/123' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mb-2"><strong>{{ __('Update Status Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">curl -X PUT '{{ rtrim(config('app.url'),'/') }}/api/v/bookings/123' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"status": "confirmed"}'</pre>
                            <p class="text-muted small">{{ __('Status values: pending, confirmed, cancelled, completed') }}</p>
                        </div>
                    </div>

                    {{-- 7. Cancel Booking --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('7. Cancel Booking') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Cancel a booking and process refund.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/bookings/{id}/cancel</pre>
                            <p class="mb-2"><strong>{{ __('Request Body:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">{
  "reason": "Customer requested cancellation",
  "refund_percent": 100
}</pre>
                            <p class="mt-3 text-muted small">{{ __('Refund percent: 0-100. Platform will process the refund to customer.') }}</p>
                        </div>
                    </div>

                    {{-- 8. Update Service Details --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('8. Update Service Details') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Update your service information (pricing, title, description, availability).') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">PUT /api/v/services/{type}/{id}</pre>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">curl -X PUT '{{ rtrim(config('app.url'),'/') }}/api/v/services/hotels/42' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -d '{"title": "Updated Hotel Name", "price": 150, "is_available": true}'</pre>
                            <p class="text-muted small">{{ __('Fields: title, description, price, is_available, capacity, etc.') }}</p>
                        </div>
                    </div>

                    {{-- 9. Get Customer Profile --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('9. Get Customer Profile') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Get detailed information about a customer.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/customers/{id}</pre>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/customers/789' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: Name, email, phone, address, booking history, reviews given.') }}</p>
                        </div>
                    </div>

                    {{-- 10. Payment & Invoices --}}
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">{{ __('10. Payment & Invoices') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Get your earnings, invoices, and payment history.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoints:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/payments
GET /api/v/invoices
GET /api/v/payouts</pre>
                            <p class="mb-2"><strong>{{ __('Get Payments Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/payments?status=completed' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: Amount, date, booking ID, customer, status. Filter by date range or status.') }}</p>
                        </div>
                    </div>

                    {{-- 11. Analytics/Dashboard --}}
                    <div class="card">
                        <div class="card-header fw-semibold">{{ __('11. Analytics & Dashboard Stats') }}</div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ __('Get quick metrics about your business performance.') }}</p>
                            <p class="mb-2"><strong>{{ __('Endpoint:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/analytics/dashboard</pre>
                            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
                            <ul class="small mb-3">
                                <li><code>period</code> — today, week, month, year</li>
                                <li><code>service_type</code> — filter by service (optional)</li>
                            </ul>
                            <p class="mb-2"><strong>{{ __('Example:') }}</strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '{{ rtrim(config('app.url'),'/') }}/api/v/analytics/dashboard?period=month' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small">{{ __('Returns: Total revenue, bookings count, occupancy rate, avg rating, new enquiries, cancellations.') }}</p>
                        </div>
                    </div>
                </div>
                {{-- END TAB 2 --}}
            </div>

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- ALLOWED ORIGINS (CORS)                                       --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Allowed Origins') }} <small class="text-muted fw-normal fs-6">{{ __('(for browser calls from your website)') }}</small></h5>
            </div>
            <form method="POST" action="{{ route('vendor.api_keys.origins.store') }}" class="d-flex gap-2 mb-3">
                @csrf
                <input type="text" name="origin" class="form-control" style="max-width:360px"
                       placeholder="https://yourwebsite.com" pattern="https://.*">
                <button class="btn btn-outline-primary">{{ __('Add Origin') }}</button>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>{{ __('Origin') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($origins as $origin)
                        <tr>
                            <td><code>{{ $origin->origin }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('vendor.api_keys.origins.destroy', $origin->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')">{{ __('Remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center">{{ __('No origins added. Server-to-server calls work without this.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- WEBHOOKS                                                      --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Webhooks') }} <small class="text-muted fw-normal fs-6">{{ __('(push booking events to your website)') }}</small></h5>
            </div>
            <form method="POST" action="{{ route('vendor.api_keys.webhooks.store') }}" class="card p-3 mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">{{ __('Endpoint URL') }}</label>
                        <input type="url" name="url" class="form-control" placeholder="https://yoursite.com/webhooks/tsoka" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">{{ __('Events') }}</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(\Modules\Vendor\Models\VendorWebhook::$supportedEvents as $evt)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="events[]" value="{{ $evt }}" id="evt-{{ $evt }}">
                                <label class="form-check-label small" for="evt-{{ $evt }}">{{ $evt }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100">{{ __('Register') }}</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('URL') }}</th>
                            <th>{{ __('Events') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Deliveries') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($webhooks as $wh)
                        <tr>
                            <td><code>{{ $wh->url }}</code></td>
                            <td>{{ implode(', ', $wh->events) }}</td>
                            <td>
                                @if($wh->active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td>{{ $wh->deliveries_count }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('vendor.api_keys.webhooks.destroy', $wh->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">{{ __('No webhooks registered.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- Modal: Generate new key --}}
<div class="modal fade" id="modal-new-key" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('vendor.api_keys.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Generate API Key') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Key name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. dare2travel website') }}" required maxlength="100">
                        <small class="text-muted">{{ __('A label so you remember what this key is for.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Website domain') }}</label>
                        <input type="text" name="domain" class="form-control" placeholder="dare2travel.com">
                        <small class="text-muted">{{ __('Your website domain. Browser calls from this domain will be allowed automatically (CORS). Leave blank for server-to-server use only.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Annual request limit') }}</label>
                        <input type="number" name="rate_limit" class="form-control" value="100000" min="0" max="10000000">
                        <small class="text-muted">{{ __('Maximum API calls per year. 0 = unlimited.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Generate Key') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
