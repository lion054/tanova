<?php $__env->startSection('title', __('API Keys')); ?>
<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">

            
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            
            <?php if(session('new_key')): ?>
            <div class="alert alert-warning d-flex align-items-start gap-3">
                <div style="font-size:1.5rem">🔑</div>
                <div class="flex-grow-1">
                    <strong><?php echo e(__('Copy your new API key now — it will not be shown again.')); ?></strong>
                    <p class="mb-1 text-muted small">Key name: <em><?php echo e(session('new_key_name')); ?></em></p>
                    <div class="input-group mt-2" style="max-width:600px">
                        <input type="text" id="new-key-input" class="form-control font-monospace"
                               value="<?php echo e(session('new_key')); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('new-key-input').value);this.textContent='Copied!'">
                            <?php echo e(__('Copy')); ?>

                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            
            
            
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys-panel" type="button" role="tab">
                        <i class="fa fa-key me-2"></i><?php echo e(__('API Keys')); ?>

                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-panel" type="button" role="tab">
                        <i class="fa fa-book me-2"></i><?php echo e(__('Documentation')); ?>

                    </button>
                </li>
            </ul>

            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="keys-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo e(__('Your API Keys')); ?></h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-new-key">
                    + <?php echo e(__('Generate New Key')); ?>

                </button>
            </div>

            <div class="table-responsive mb-5">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo e(__('Name')); ?></th>
                            <th><?php echo e(__('Domain')); ?></th>
                            <th><?php echo e(__('Status')); ?></th>
                            <th><?php echo e(__('Annual usage')); ?></th>
                            <th><?php echo e(__('Last used')); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $keys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $pct = $key->rate_limit > 0 ? round($key->annualUsageCount() / $key->rate_limit * 100) : 0; ?>
                        <tr>
                            <td><strong><?php echo e($key->name); ?></strong></td>
                            <td>
                                <?php if($key->domain): ?>
                                    <code><?php echo e($key->domain); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($key->active): ?>
                                    <span class="badge bg-success"><?php echo e(__('Active')); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo e(__('Revoked')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $annual = $key->annualUsageCount(); ?>
                                <div class="progress" style="height:6px;width:120px;display:inline-block;vertical-align:middle">
                                    <div class="progress-bar <?php echo e($pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success')); ?>"
                                         style="width:<?php echo e(min($pct,100)); ?>%"></div>
                                </div>
                                <small class="ms-2"><?php echo e(number_format($annual)); ?> / <?php echo e(number_format($key->rate_limit)); ?></small>
                            </td>
                            <td><?php echo e($key->last_used_at?->diffForHumans() ?? '—'); ?></td>
                            <td class="text-end">
                                <?php if($key->active): ?>
                                <form method="POST" action="<?php echo e(route('vendor.api_keys.rotate', $key->id)); ?>" style="display:inline"
                                      onsubmit="return confirm('<?php echo e(__('Rotate this key? Your current key will stop working immediately.')); ?>')">
                                    <?php echo csrf_field(); ?>
                                    <button class="btn btn-sm btn-outline-warning"><?php echo e(__('Rotate')); ?></button>
                                </form>
                                <form method="POST" action="<?php echo e(route('vendor.api_keys.revoke', $key->id)); ?>" style="display:inline"
                                      onsubmit="return confirm('<?php echo e(__('Revoke this key? This cannot be undone.')); ?>')">
                                    <?php echo csrf_field(); ?>
                                    <button class="btn btn-sm btn-outline-danger"><?php echo e(__('Revoke')); ?></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <?php echo e(__('No API keys yet. Generate one to start integrating your website.')); ?>

                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

                
                </div>

                
                <div class="tab-pane fade" id="docs-panel" role="tabpanel">
                    <div class="alert alert-info mb-4">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong><?php echo e(__('Note:')); ?></strong> <?php echo e(__('All API requests return only YOUR data. You cannot access other vendors\' data.')); ?>

                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('Authentication')); ?></div>
                        <div class="card-body">
                            <p class="mb-2"><?php echo e(__('Include your API key in every request:')); ?></p>
                            <pre class="bg-light p-2 rounded border">Authorization: Bearer YOUR_API_KEY</pre>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('1. Get Your Services')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Fetch your hotels, tours, spaces, and other services.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/services/{type}</pre>
                            <p class="mb-2"><strong><?php echo e(__('Types:')); ?></strong> hotels, tours, spaces, cars, flights, boats</p>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/services/hotels' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small"><?php echo e(__('Returns: List of your services with pricing, availability, and details. Only YOUR services are returned.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('2. Trip Planning (Tanova)')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Tanova is a deterministic, weather-aware trip planning engine. Generate 8 customized itinerary packages with activities, accommodations, and real-time weather forecasts. Only your vendor data is included.')); ?></p>

                            
                            <h6 class="mt-4 mb-3"><?php echo e(__('2.1 Generate Trip Itineraries')); ?></h6>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/tanova/generate</pre>
                            <p class="mb-2"><strong><?php echo e(__('Request Body:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">{
  "destination": "Victoria Falls",
  "start_date": "2026-06-15",
  "end_date": "2026-06-20",
  "guests": 2,
  "place_id": 6,
  "budget": "mid-range",
  "notes": "Prefer adventure activities"
}</pre>
                            <p class="mb-2 small"><strong><?php echo e(__('Budget Tiers:')); ?></strong></p>
                            <ul class="small mb-3">
                                <li><code>budget</code> — USD $2,000</li>
                                <li><code>mid-range</code> — USD $5,000 (recommended)</li>
                                <li><code>luxury</code> — USD $10,000</li>
                            </ul>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X POST '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/tanova/generate' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{
    "destination": "Victoria Falls",
    "start_date": "2026-06-15",
    "end_date": "2026-06-20",
    "guests": 2,
    "budget": "mid-range"
  }'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Trip with 8 packages, each containing daily itinerary, activities, accommodation, meals, weather forecast, and pricing.')); ?></p>

                            
                            <h6 class="mt-4 mb-3"><?php echo e(__('2.2 List Your Generated Trips')); ?></h6>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips</pre>
                            <p class="mb-2"><strong><?php echo e(__('Query Parameters:')); ?></strong></p>
                            <ul class="small mb-3">
                                <li><code>page</code> — pagination (default: 1)</li>
                                <li><code>per_page</code> — results per page (default: 15, max: 100)</li>
                            </ul>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/tanova/trips?page=1&per_page=15' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Paginated list of your generated trips with summary info and links.')); ?></p>

                            
                            <h6 class="mt-4 mb-3"><?php echo e(__('2.3 Get Trip Details')); ?></h6>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips/{id}</pre>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/tanova/trips/42' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Full trip details including all 8 packages with complete day-by-day itinerary, weather forecast, activities, accommodation, and cost breakdown.')); ?></p>

                            
                            <h6 class="mt-4 mb-3"><?php echo e(__('2.4 Get Weather-Based Replan Suggestions')); ?></h6>
                            <p class="text-muted small mb-3"><?php echo e(__('Analyze weather changes since trip generation and get Claude AI suggestions for intelligent activity swaps.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/tanova/trips/{id}/replan</pre>
                            <p class="mb-2"><strong><?php echo e(__('How It Works:')); ?></strong></p>
                            <ol class="small mb-3">
                                <li>Fetches current weather forecast for trip dates</li>
                                <li>Detects significant changes: 5°C+ temperature shift, 20%+ rain change, or condition changes</li>
                                <li>Uses Claude AI to intelligently suggest activity swaps</li>
                                <li>Returns structured recommendations for your review</li>
                            </ol>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/tanova/trips/42/replan' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: has_changes (boolean), weather_changes (array), suggestions (activity swap recommendations).')); ?></p>

                            <div class="alert alert-info small mt-3">
                                <strong><?php echo e(__('Note:')); ?></strong> <?php echo e(__('All trips include 14-day weather forecasts. Dates beyond 14 days show "unavailable" weather data. Vendor isolation ensures your trips only use your accommodations and services.')); ?>

                            </div>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('3. Get Bookings')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Fetch customer bookings for your services.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/bookings</pre>
                            <p class="mb-2"><strong><?php echo e(__('Query Parameters (optional):')); ?></strong></p>
                            <ul class="small mb-3">
                                <li><code>status</code> — completed, pending, cancelled</li>
                                <li><code>service_type</code> — hotel, tour, space, car, flight, boat</li>
                                <li><code>date_from</code> — filter by check-in date (YYYY-MM-DD)</li>
                                <li><code>date_to</code> — filter by check-out date (YYYY-MM-DD)</li>
                            </ul>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/bookings?status=completed&service_type=hotel' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small"><?php echo e(__('Returns: Only YOUR bookings with customer details, dates, and payment status.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('4. Customer Chat (Concierge)')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Get and respond to customer messages.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoints:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/concierge/conversations
POST /api/v/concierge/conversations/{id}/messages</pre>
                            <p class="mb-2"><strong><?php echo e(__('Example (Get conversations):')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/concierge/conversations' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mt-3 text-muted small"><?php echo e(__('Returns: Only YOUR customer conversations and messages.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('5. Submit Enquiry')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Submit a new customer enquiry for your service.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/enquiries</pre>
                            <p class="mb-2"><strong><?php echo e(__('Request Body:')); ?></strong></p>
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
                            <p class="mt-3 text-muted small"><?php echo e(__('Returns: Enquiry confirmation with ID and status.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('6. Get & Update Booking')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Get details of a specific booking and update its status.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoints:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/bookings/{id}
PUT /api/v/bookings/{id}</pre>
                            <p class="mb-2"><strong><?php echo e(__('Get Booking Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/bookings/123' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="mb-2"><strong><?php echo e(__('Update Status Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">curl -X PUT '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/bookings/123' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"status": "confirmed"}'</pre>
                            <p class="text-muted small"><?php echo e(__('Status values: pending, confirmed, cancelled, completed')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('7. Cancel Booking')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Cancel a booking and process refund.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">POST /api/v/bookings/{id}/cancel</pre>
                            <p class="mb-2"><strong><?php echo e(__('Request Body:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">{
  "reason": "Customer requested cancellation",
  "refund_percent": 100
}</pre>
                            <p class="mt-3 text-muted small"><?php echo e(__('Refund percent: 0-100. Platform will process the refund to customer.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('8. Update Service Details')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Update your service information (pricing, title, description, availability).')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">PUT /api/v/services/{type}/{id}</pre>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3" style="font-size:.85rem">curl -X PUT '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/services/hotels/42' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -d '{"title": "Updated Hotel Name", "price": 150, "is_available": true}'</pre>
                            <p class="text-muted small"><?php echo e(__('Fields: title, description, price, is_available, capacity, etc.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('9. Get Customer Profile')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Get detailed information about a customer.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/customers/{id}</pre>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/customers/789' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Name, email, phone, address, booking history, reviews given.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card mb-4">
                        <div class="card-header fw-semibold"><?php echo e(__('10. Payment & Invoices')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Get your earnings, invoices, and payment history.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoints:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/payments
GET /api/v/invoices
GET /api/v/payouts</pre>
                            <p class="mb-2"><strong><?php echo e(__('Get Payments Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/payments?status=completed' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Amount, date, booking ID, customer, status. Filter by date range or status.')); ?></p>
                        </div>
                    </div>

                    
                    <div class="card">
                        <div class="card-header fw-semibold"><?php echo e(__('11. Analytics & Dashboard Stats')); ?></div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo e(__('Get quick metrics about your business performance.')); ?></p>
                            <p class="mb-2"><strong><?php echo e(__('Endpoint:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">GET /api/v/analytics/dashboard</pre>
                            <p class="mb-2"><strong><?php echo e(__('Query Parameters:')); ?></strong></p>
                            <ul class="small mb-3">
                                <li><code>period</code> — today, week, month, year</li>
                                <li><code>service_type</code> — filter by service (optional)</li>
                            </ul>
                            <p class="mb-2"><strong><?php echo e(__('Example:')); ?></strong></p>
                            <pre class="bg-light p-2 rounded border mb-3">curl -X GET '<?php echo e(rtrim(config('app.url'),'/')); ?>/api/v/analytics/dashboard?period=month' \
  -H 'Authorization: Bearer YOUR_API_KEY'</pre>
                            <p class="text-muted small"><?php echo e(__('Returns: Total revenue, bookings count, occupancy rate, avg rating, new enquiries, cancellations.')); ?></p>
                        </div>
                    </div>
                </div>
                
            </div>

            
            
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo e(__('Allowed Origins')); ?> <small class="text-muted fw-normal fs-6"><?php echo e(__('(for browser calls from your website)')); ?></small></h5>
            </div>
            <form method="POST" action="<?php echo e(route('vendor.api_keys.origins.store')); ?>" class="d-flex gap-2 mb-3">
                <?php echo csrf_field(); ?>
                <input type="text" name="origin" class="form-control" style="max-width:360px"
                       placeholder="https://yourwebsite.com" pattern="https://.*">
                <button class="btn btn-outline-primary"><?php echo e(__('Add Origin')); ?></button>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th><?php echo e(__('Origin')); ?></th><th></th></tr></thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $origins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $origin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($origin->origin); ?></code></td>
                            <td class="text-end">
                                <form method="POST" action="<?php echo e(route('vendor.api_keys.origins.destroy', $origin->id)); ?>">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')"><?php echo e(__('Remove')); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="2" class="text-muted text-center"><?php echo e(__('No origins added. Server-to-server calls work without this.')); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo e(__('Webhooks')); ?> <small class="text-muted fw-normal fs-6"><?php echo e(__('(push booking events to your website)')); ?></small></h5>
            </div>
            <form method="POST" action="<?php echo e(route('vendor.api_keys.webhooks.store')); ?>" class="card p-3 mb-3">
                <?php echo csrf_field(); ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small"><?php echo e(__('Endpoint URL')); ?></label>
                        <input type="url" name="url" class="form-control" placeholder="https://yoursite.com/webhooks/tsoka" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small"><?php echo e(__('Events')); ?></label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $__currentLoopData = \Modules\Vendor\Models\VendorWebhook::$supportedEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $evt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="events[]" value="<?php echo e($evt); ?>" id="evt-<?php echo e($evt); ?>">
                                <label class="form-check-label small" for="evt-<?php echo e($evt); ?>"><?php echo e($evt); ?></label>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100"><?php echo e(__('Register')); ?></button>
                    </div>
                </div>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo e(__('URL')); ?></th>
                            <th><?php echo e(__('Events')); ?></th>
                            <th><?php echo e(__('Status')); ?></th>
                            <th><?php echo e(__('Deliveries')); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $webhooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($wh->url); ?></code></td>
                            <td><?php echo e(implode(', ', $wh->events)); ?></td>
                            <td>
                                <?php if($wh->active): ?>
                                    <span class="badge bg-success"><?php echo e(__('Active')); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo e(__('Inactive')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($wh->deliveries_count); ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?php echo e(route('vendor.api_keys.webhooks.destroy', $wh->id)); ?>">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><?php echo e(__('Delete')); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-muted text-center"><?php echo e(__('No webhooks registered.')); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>


<div class="modal fade" id="modal-new-key" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('vendor.api_keys.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo e(__('Generate API Key')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo e(__('Key name')); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="<?php echo e(__('e.g. dare2travel website')); ?>" required maxlength="100">
                        <small class="text-muted"><?php echo e(__('A label so you remember what this key is for.')); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo e(__('Website domain')); ?></label>
                        <input type="text" name="domain" class="form-control" placeholder="dare2travel.com">
                        <small class="text-muted"><?php echo e(__('Your website domain. Browser calls from this domain will be allowed automatically (CORS). Leave blank for server-to-server use only.')); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo e(__('Annual request limit')); ?></label>
                        <input type="number" name="rate_limit" class="form-control" value="100000" min="0" max="10000000">
                        <small class="text-muted"><?php echo e(__('Maximum API calls per year. 0 = unlimited.')); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo e(__('Generate Key')); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/api-keys/index.blade.php ENDPATH**/ ?>