<?php $__env->startPush('css'); ?>
<style>
.bk-stat { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:18px 20px; }
.bk-stat-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#aaa; margin-bottom:6px; }
.bk-stat-val { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; line-height:1.1; }
.bk-stat-sub { font-size:11px; color:#bbb; margin-top:4px; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:100px; font-size:11px; font-weight:600; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; opacity:.7; flex-shrink:0; border-radius:50%; }
.bk-paid       { background:#f0fdf4; color:#16a34a; }
.bk-processing { background:#fffbeb; color:#d97706; }
.bk-completed  { background:#eff6ff; color:#2563eb; }
.bk-cancelled  { background:#fff1f2; color:#e11d48; }
.bk-unpaid     { background:#f4f4f5; color:#71717a; }

.bk-table { width:100%; border-collapse:collapse; }
.bk-table thead { background:#fafafa; border-bottom:1px solid #ebebeb; }
.bk-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#bbb; white-space:nowrap; text-align:left; }
.bk-table td { padding:14px 16px; font-size:13px; color:#222; border-bottom:1px solid #f7f7f7; vertical-align:middle; }
.bk-table tbody tr:hover { background:#fafafa; }
.bk-table tbody tr:last-child td { border-bottom:none; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; margin-bottom:20px; }
.tp-filter-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:14px 20px; background:#fafafa; border-bottom:1px solid #f0f0f0; }
.tp-input-wrap { display:flex; align-items:center; gap:6px; border:1.5px solid #e4e4e4; border-radius:8px; height:38px; padding:0 12px; background:#fff; flex:1; max-width:340px; }
.tp-input-wrap:focus-within { border-color:#0a0a0a; }
.tp-input-wrap input, .tp-input-wrap select { border:none; background:transparent; font-size:13px; color:#333; outline:none; width:100%; }
.tp-select-wrap { display:flex; align-items:center; border:1.5px solid #e4e4e4; border-radius:8px; height:38px; padding:0 12px; background:#fff; }
.tp-select-wrap select { border:none; background:transparent; font-size:13px; color:#333; outline:none; cursor:pointer; }
.tp-btn-prim { display:inline-flex; align-items:center; gap:5px; padding:8px 16px; border-radius:7px; font-size:12px; font-weight:700; background:#0a0a0a; color:#fff; border:none; cursor:pointer; text-decoration:none !important; }
.tp-btn-prim:hover { background:#333; color:#fff !important; }
.tp-btn-ghost { display:inline-flex; align-items:center; gap:5px; padding:8px 16px; border-radius:7px; font-size:12px; font-weight:600; background:#fff; color:#0a0a0a !important; border:1.5px solid #e4e4e4; cursor:pointer; text-decoration:none !important; }
.tp-btn-ghost:hover { border-color:#0a0a0a; }

.bk-name { font-size:13px; font-weight:600; color:#0a0a0a; text-decoration:none; }
.bk-name:hover { text-decoration:underline; }
.bk-sub { font-size:11px; color:#aaa; margin-top:2px; line-height:1.5; }
.bk-amount { font-size:14px; font-weight:800; color:#0a0a0a; letter-spacing:-.02em; }
.bk-amount-sub { font-size:11px; color:#aaa; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="portal-header" style="margin-bottom:28px">
    <div>
        <p class="portal-eyebrow"><?php echo e(__("Vendor")); ?></p>
        <h1 class="portal-h1"><?php echo e(__("Bookings")); ?></h1>
    </div>
    <?php if(auth()->user()->hasPermission('dashboard_access')): ?>
    <a href="<?php echo e(route('booking.admin.index')); ?>" class="tp-btn-ghost" style="margin-top:auto">
        <i class="ion ion-ios-settings"></i> Admin View
    </a>
    <?php endif; ?>
</div>


<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Total Bookings</div>
            <div class="bk-stat-val"><?php echo e($rows->total()); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Revenue</div>
            <div class="bk-stat-val"><?php echo e(format_money_main($total_rev)); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Collected</div>
            <div class="bk-stat-val" style="color:#16a34a"><?php echo e(format_money_main($total_paid)); ?></div>
            <div class="bk-stat-sub"><?php echo e(format_money_main($total_rev - $total_paid)); ?> remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Processing</div>
            <div class="bk-stat-val" style="color:#d97706"><?php echo e($processing); ?></div>
            <div class="bk-stat-sub">awaiting action</div>
        </div>
    </div>
</div>


<form method="get" action="">
    <div class="tp-card">
        <div class="tp-filter-bar">
            <div class="tp-input-wrap">
                <i class="ion ion-ios-search" style="color:#aaa;flex-shrink:0"></i>
                <input type="text" name="s" value="<?php echo e(request('s')); ?>" placeholder="Search name, email or #ID…">
            </div>
            <div class="tp-select-wrap">
                <select name="status" onchange="this.closest('form').submit()">
                    <option value="">All Statuses</option>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($st); ?>" <?php echo e(request('status') == $st ? 'selected' : ''); ?>>
                            <?php echo e(booking_status_to_text($st)); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="tp-btn-prim">
                <i class="ion ion-ios-funnel"></i> Filter
            </button>
            <?php if(request('s') || request('status')): ?>
                <a href="<?php echo e(route('user.vendor.bookings')); ?>" class="tp-btn-ghost">Clear</a>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:12px;color:#aaa"><?php echo e($rows->total()); ?> found</span>
        </div>
    </div>
</form>


<div class="tp-card">
    <div style="overflow-x:auto">
        <table class="bk-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Service</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td style="color:#aaa;font-size:12px">#<?php echo e($row->id); ?></td>
                    <td>
                        <?php if($row->object_model === 'tanova_trip'): ?>
                            <?php $trip = \Pro\Tanova\Models\TanovaTrip::find($row->object_id); ?>
                            <span class="bk-name" style="cursor:default">
                                <?php echo e(Str::limit($trip->title ?? "Trip #{$row->object_id}", 50)); ?>

                            </span>
                            <div class="bk-sub">
                                <span style="background:#eff6ff;color:#2563eb;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:700">Tanova</span>
                                <?php if($trip?->destination): ?> · <?php echo e($trip->destination); ?><?php endif; ?>
                            </div>
                        <?php elseif($service = $row->service): ?>
                            <a href="<?php echo e($service->getDetailUrl()); ?>" target="_blank" class="bk-name">
                                <?php echo e(Str::limit($service->title ?? '', 50)); ?>

                            </a>
                        <?php else: ?>
                            <span style="color:#aaa;font-size:12px">[Deleted]</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size:13px;font-weight:600;color:#0a0a0a"><?php echo e($row->first_name); ?> <?php echo e($row->last_name); ?></div>
                        <div class="bk-sub">
                            <?php if($row->email): ?><a href="mailto:<?php echo e($row->email); ?>" style="color:#2563eb"><?php echo e($row->email); ?></a><?php endif; ?>
                            <?php if($row->phone): ?> · <?php echo e($row->phone); ?><?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="bk-amount"><?php echo e(format_money_main($row->total)); ?></div>
                        <div class="bk-amount-sub">
                            Paid <?php echo e(format_money_main($row->paid)); ?>

                            <?php if($row->total > $row->paid): ?>
                                · <span style="color:#d97706"><?php echo e(format_money_main($row->total - $row->paid)); ?> due</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php
                            $sc = match($row->status) {
                                'paid'       => 'bk-paid',
                                'completed'  => 'bk-completed',
                                'cancelled'  => 'bk-cancelled',
                                'processing' => 'bk-processing',
                                default      => 'bk-unpaid',
                            };
                        ?>
                        <span class="tp-badge <?php echo e($sc); ?>"><?php echo e($row->statusName); ?></span>
                    </td>
                    <td style="font-size:12px;color:#aaa;white-space:nowrap"><?php echo e(display_datetime($row->updated_at)); ?></td>
                    <td>
                        <?php
                            $unpaidStatuses = ['processing', 'unpaid', 'partial_payment', 'confirmed'];
                            $isUnpaid = in_array($row->status, $unpaidStatuses);
                            $existingInvoice = null;
                            if ($row->object_model === 'tanova_trip') {
                                $existingInvoice = \Modules\TourPay\Models\Invoice::where('tanova_trip_id', $row->object_id)
                                    ->where('author_id', Auth::id())
                                    ->first();
                            }
                            $guestName = trim($row->first_name . ' ' . $row->last_name);
                        ?>
                        <div class="dropdown">
                            <button class="tp-btn-ghost dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    style="padding:5px 10px;font-size:11px">
                                Actions
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" style="min-width:185px;border:1px solid #ebebeb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:4px">

                                
                                <a class="dropdown-item btn-detail-booking"
                                   href="#bk-modal-detail"
                                   data-ajax="<?php echo e(route('booking.modal', ['booking' => $row])); ?>"
                                   data-bs-toggle="modal" data-id="<?php echo e($row->id); ?>" data-bs-target="#bk-modal-detail"
                                   style="font-size:13px;padding:8px 14px;border-radius:6px">
                                    <i class="ion ion-ios-eye" style="width:18px"></i> Detail
                                </a>

                                <?php if($isUnpaid): ?>
                                <div style="border-top:1px solid #f0f0f0;margin:4px 0"></div>

                                
                                <?php if($existingInvoice): ?>
                                    <a class="dropdown-item" href="<?php echo e(route('tourpay.vendor.view', $existingInvoice->id)); ?>"
                                       style="font-size:13px;padding:8px 14px;border-radius:6px">
                                        <i class="ion ion-ios-document" style="width:18px"></i> View Invoice
                                    </a>
                                <?php elseif($row->object_model === 'tanova_trip'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.tanova.createInvoice', $row->object_id)); ?>" style="display:contents">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="dropdown-item" style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                            <i class="ion ion-ios-document" style="width:18px"></i> Create Invoice
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?php echo e(route('tourpay.vendor.from-booking', $row->id)); ?>" style="display:contents">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="dropdown-item" style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                            <i class="ion ion-ios-document" style="width:18px"></i> Create Invoice
                                        </button>
                                    </form>
                                <?php endif; ?>

                                
                                <?php if($row->phone): ?>
                                    <button type="button" class="dropdown-item bk-send-wa"
                                        data-booking="<?php echo e($row->id); ?>"
                                        data-phone="<?php echo e($row->phone); ?>"
                                        data-name="<?php echo e(e($guestName)); ?>"
                                        data-total="<?php echo e(format_money_main($row->total)); ?>"
                                        data-status="<?php echo e(booking_status_to_text($row->status)); ?>"
                                        data-url="<?php echo e(route('user.booking.send-whatsapp', $row->id)); ?>"
                                        style="font-size:13px;padding:8px 14px;border-radius:6px;color:#16a34a;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                        <i class="ion ion-logo-whatsapp" style="width:18px"></i> WhatsApp
                                    </button>
                                <?php endif; ?>

                                
                                <?php if($row->email): ?>
                                    <button type="button" class="dropdown-item bk-send-email"
                                        data-booking="<?php echo e($row->id); ?>"
                                        data-email="<?php echo e($row->email); ?>"
                                        data-name="<?php echo e(e($guestName)); ?>"
                                        data-total="<?php echo e(format_money_main($row->total)); ?>"
                                        data-status="<?php echo e(booking_status_to_text($row->status)); ?>"
                                        data-url="<?php echo e(route('user.booking.send-email', $row->id)); ?>"
                                        style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                        <i class="ion ion-ios-mail" style="width:18px"></i> Email
                                    </button>
                                <?php endif; ?>
                                <?php endif; ?> 

                                <?php if(auth()->user()->hasPermission('dashboard_access')): ?>
                                <div style="border-top:1px solid #f0f0f0;margin:4px 0"></div>
                                <a class="dropdown-item"
                                   href="<?php echo e(route('booking.admin.edit', ['id' => $row->id])); ?>"
                                   style="font-size:13px;padding:8px 14px;border-radius:6px">
                                    <i class="ion ion-ios-create" style="width:18px"></i> Edit
                                </a>
                                <?php endif; ?>

                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:60px 20px;color:#aaa;font-size:13px">
                        <i class="ion ion-ios-calendar" style="font-size:32px;display:block;margin-bottom:10px;color:#e0e0e0"></i>
                        No bookings found.
                        <?php if(request('s') || request('status')): ?>
                            <br><a href="<?php echo e(route('user.vendor.bookings')); ?>" style="color:#2563eb;font-size:12px">Clear filters</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end"><?php echo e($rows->links()); ?></div>


<div class="modal fade" tabindex="-1" id="bk-modal-detail">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #ebebeb;border-radius:12px;overflow:hidden">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:16px 20px">
                <h5 class="modal-title" style="font-size:15px;font-weight:700;color:#0a0a0a">
                    Booking <span class="bk-modal-id" style="color:#2563eb"></span>
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bk-modal-body" style="padding:20px">
                <div style="text-align:center;color:#aaa;padding:40px 0">Loading…</div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px">
                <button type="button" class="tp-btn-ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" tabindex="-1" id="bk-modal-wa">
    <div class="modal-dialog" style="max-width:440px">
        <div class="modal-content" style="border:1px solid #ebebeb;border-radius:12px;overflow:hidden">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:16px 20px">
                <h5 class="modal-title" style="font-size:15px;font-weight:700;color:#16a34a">
                    <i class="ion ion-logo-whatsapp"></i> Send WhatsApp
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="padding:20px">
                <div class="mb-3">
                    <label style="font-size:12px;font-weight:700;color:#555;margin-bottom:4px;display:block">Phone Number</label>
                    <input type="text" id="bk-wa-phone" class="form-control"
                           style="border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;padding:9px 12px">
                    <small style="color:#aaa;font-size:11px">Include country code, digits only (e.g. 263777123456)</small>
                </div>
                <div class="mb-3">
                    <label style="font-size:12px;font-weight:700;color:#555;margin-bottom:4px;display:block">Message</label>
                    <textarea id="bk-wa-message" rows="5" class="form-control"
                              style="border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;padding:9px 12px;resize:vertical"></textarea>
                </div>
                <div id="bk-wa-feedback"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px;gap:8px">
                <button type="button" class="tp-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="bk-wa-submit" class="tp-btn-prim" style="background:#16a34a">
                    <i class="ion ion-logo-whatsapp"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" tabindex="-1" id="bk-modal-email">
    <div class="modal-dialog" style="max-width:520px">
        <div class="modal-content" style="border:1px solid #ebebeb;border-radius:12px;overflow:hidden">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:16px 20px">
                <h5 class="modal-title" style="font-size:15px;font-weight:700;color:#0a0a0a">
                    <i class="ion ion-ios-mail"></i> Send Email
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="padding:20px">
                <div class="mb-3">
                    <label style="font-size:12px;font-weight:700;color:#555;margin-bottom:4px;display:block">To</label>
                    <input type="email" id="bk-email-addr" class="form-control"
                           style="border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;padding:9px 12px">
                </div>
                <div class="mb-3">
                    <label style="font-size:12px;font-weight:700;color:#555;margin-bottom:4px;display:block">Subject</label>
                    <input type="text" id="bk-email-subject" class="form-control"
                           style="border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;padding:9px 12px">
                </div>
                <div class="mb-3">
                    <label style="font-size:12px;font-weight:700;color:#555;margin-bottom:4px;display:block">Message</label>
                    <textarea id="bk-email-body" rows="6" class="form-control"
                              style="border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;padding:9px 12px;resize:vertical"></textarea>
                </div>
                <div id="bk-email-feedback"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px;gap:8px">
                <button type="button" class="tp-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="bk-email-submit" class="tp-btn-prim">
                    <i class="ion ion-ios-send"></i> Send Email
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
var _bkCsrf    = '<?php echo e(csrf_token()); ?>';
var _bkCompany = '<?php echo e(addslashes(auth()->user()->business_name ?: auth()->user()->name)); ?>';

// ── Detail modal ──────────────────────────────────────────────────────────────
$(document).on('click', '.btn-detail-booking', function() {
    var btn = $(this);
    var modal = $('#bk-modal-detail');
    modal.find('.bk-modal-id').text('#' + btn.data('id'));
    modal.find('.bk-modal-body').html('<div style="text-align:center;color:#aaa;padding:40px 0">Loading…</div>');
    $.get(btn.data('ajax'), function(html){ modal.find('.bk-modal-body').html(html); });
});

// ── Fixed-position dropdowns (escape overflow containers) ─────────────────────
document.querySelectorAll('.bk-table [data-bs-toggle="dropdown"]').forEach(function(el) {
    var inst = bootstrap.Dropdown.getInstance(el);
    if (inst) inst.dispose();
    new bootstrap.Dropdown(el, {
        popperConfig: function(cfg) { cfg.strategy = 'fixed'; return cfg; }
    });
});

// ── WhatsApp modal ────────────────────────────────────────────────────────────
var _waUrl  = '';
var _waModal = new bootstrap.Modal(document.getElementById('bk-modal-wa'));

$(document).on('click', '.bk-send-wa', function() {
    var btn = $(this);
    _waUrl = btn.data('url');
    var phone = (btn.data('phone') + '').replace(/[^0-9]/g, '');
    var msg   = 'Hi ' + btn.data('name') + ', your booking of ' + btn.data('total') +
                ' is *' + btn.data('status') + '*.\n\nThank you, ' + _bkCompany;
    $('#bk-wa-phone').val(phone);
    $('#bk-wa-message').val(msg);
    $('#bk-wa-feedback').html('');
    $('#bk-wa-submit').prop('disabled', false).html('<i class="ion ion-logo-whatsapp"></i> Send');
    _waModal.show();
});

$('#bk-wa-submit').on('click', function() {
    var btn = $(this).prop('disabled', true).text('Sending…');
    $.ajax({
        url: _waUrl, method: 'POST',
        data: { _token: _bkCsrf, phone: $('#bk-wa-phone').val(), message: $('#bk-wa-message').val() },
        success: function() {
            $('#bk-wa-feedback').html('<div class="alert alert-success py-2 mt-2" style="font-size:13px">Sent successfully!</div>');
            setTimeout(function() { _waModal.hide(); }, 1500);
        },
        error: function(xhr) {
            var err = (xhr.responseJSON || {}).error || 'Failed to send.';
            $('#bk-wa-feedback').html('<div class="alert alert-danger py-2 mt-2" style="font-size:13px">' + err + '</div>');
            btn.prop('disabled', false).html('<i class="ion ion-logo-whatsapp"></i> Send');
        }
    });
});

// ── Email modal ───────────────────────────────────────────────────────────────
var _emailUrl   = '';
var _emailModal = new bootstrap.Modal(document.getElementById('bk-modal-email'));

$(document).on('click', '.bk-send-email', function() {
    var btn = $(this);
    _emailUrl = btn.data('url');
    var subject = 'Your booking with ' + _bkCompany;
    var body    = 'Hi ' + btn.data('name') + ',\n\nYour booking of ' + btn.data('total') +
                  ' is currently ' + btn.data('status') + '.\n\nPlease let us know if you have any questions.' +
                  '\n\nKind regards,\n' + _bkCompany;
    $('#bk-email-addr').val(btn.data('email'));
    $('#bk-email-subject').val(subject);
    $('#bk-email-body').val(body);
    $('#bk-email-feedback').html('');
    $('#bk-email-submit').prop('disabled', false).html('<i class="ion ion-ios-send"></i> Send Email');
    _emailModal.show();
});

$('#bk-email-submit').on('click', function() {
    var btn = $(this).prop('disabled', true).text('Sending…');
    $.ajax({
        url: _emailUrl, method: 'POST',
        data: { _token: _bkCsrf, email: $('#bk-email-addr').val(), subject: $('#bk-email-subject').val(), message: $('#bk-email-body').val() },
        success: function() {
            $('#bk-email-feedback').html('<div class="alert alert-success py-2 mt-2" style="font-size:13px">Email sent!</div>');
            setTimeout(function() { _emailModal.hide(); }, 1500);
        },
        error: function(xhr) {
            var err = (xhr.responseJSON || {}).error || 'Failed to send.';
            $('#bk-email-feedback').html('<div class="alert alert-danger py-2 mt-2" style="font-size:13px">' + err + '</div>');
            btn.prop('disabled', false).html('<i class="ion ion-ios-send"></i> Send Email');
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/vendorBookings.blade.php ENDPATH**/ ?>