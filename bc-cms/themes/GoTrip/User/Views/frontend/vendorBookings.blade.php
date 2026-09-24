@extends('layouts.user')

@push('css')
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
@endpush

@section('content')
<div class="portal-header" style="margin-bottom:28px">
    <div>
        <p class="portal-eyebrow">{{ __("Vendor") }}</p>
        <h1 class="portal-h1">{{ __("Bookings") }}</h1>
    </div>
    @if(auth()->user()->hasPermission('dashboard_access'))
    <a href="{{ route('booking.admin.index') }}" class="tp-btn-ghost" style="margin-top:auto">
        <i class="ion ion-ios-settings"></i> Admin View
    </a>
    @endif
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Total Bookings</div>
            <div class="bk-stat-val">{{ $rows->total() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Revenue</div>
            <div class="bk-stat-val">{{ format_money_main($total_rev) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Collected</div>
            <div class="bk-stat-val" style="color:#16a34a">{{ format_money_main($total_paid) }}</div>
            <div class="bk-stat-sub">{{ format_money_main($total_rev - $total_paid) }} remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bk-stat">
            <div class="bk-stat-label">Processing</div>
            <div class="bk-stat-val" style="color:#d97706">{{ $processing }}</div>
            <div class="bk-stat-sub">awaiting action</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="tp-card"><div style="padding:14px 16px 6px">@include('vendor.partials.filterbar', ['fb' => $fb])</div></div>

{{-- Table --}}
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
            @forelse($rows as $row)
                <tr>
                    <td style="color:#aaa;font-size:12px">#{{ $row->id }}</td>
                    <td>
                        @if($row->object_model === 'tanova_trip')
                            @php $trip = \Pro\Tanova\Models\TanovaTrip::find($row->object_id); @endphp
                            <span class="bk-name" style="cursor:default">
                                {{ Str::limit($trip->title ?? "Trip #{$row->object_id}", 50) }}
                            </span>
                            <div class="bk-sub">
                                <span style="background:#eff6ff;color:#2563eb;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:700">Tanova</span>
                                @if($trip?->destination) · {{ $trip->destination }}@endif
                            </div>
                        @elseif($service = $row->service)
                            <a href="{{ $service->getDetailUrl() }}" target="_blank" class="bk-name">
                                {{ Str::limit($service->title ?? '', 50) }}
                            </a>
                        @else
                            <span style="color:#aaa;font-size:12px">[Deleted]</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:13px;font-weight:600;color:#0a0a0a">{{ $row->first_name }} {{ $row->last_name }}</div>
                        <div class="bk-sub">
                            @if($row->email)<a href="mailto:{{ $row->email }}" style="color:#2563eb">{{ $row->email }}</a>@endif
                            @if($row->phone) · {{ $row->phone }}@endif
                        </div>
                    </td>
                    <td>
                        <div class="bk-amount">{{ format_money_main($row->total) }}</div>
                        <div class="bk-amount-sub">
                            Paid {{ format_money_main($row->paid) }}
                            @if($row->total > $row->paid)
                                · <span style="color:#d97706">{{ format_money_main($row->total - $row->paid) }} due</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @php
                            $sc = match($row->status) {
                                'paid'       => 'bk-paid',
                                'completed'  => 'bk-completed',
                                'cancelled'  => 'bk-cancelled',
                                'processing' => 'bk-processing',
                                default      => 'bk-unpaid',
                            };
                        @endphp
                        <span class="tp-badge {{ $sc }}">{{ $row->statusName }}</span>
                    </td>
                    <td style="font-size:12px;color:#aaa;white-space:nowrap">{{ display_datetime($row->updated_at) }}</td>
                    <td>
                        @php
                            $unpaidStatuses = ['processing', 'unpaid', 'partial_payment', 'confirmed'];
                            $isUnpaid = in_array($row->status, $unpaidStatuses);
                            $existingInvoice = null;
                            if ($row->object_model === 'tanova_trip') {
                                $existingInvoice = \Modules\TourPay\Models\Invoice::where('tanova_trip_id', $row->object_id)
                                    ->where('author_id', Auth::id())
                                    ->first();
                            }
                            $guestName = trim($row->first_name . ' ' . $row->last_name);
                        @endphp
                        <div class="dropdown">
                            <button class="tp-btn-ghost dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    style="padding:5px 10px;font-size:11px">
                                Actions
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" style="min-width:185px;border:1px solid #ebebeb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:4px">

                                {{-- Detail --}}
                                <a class="dropdown-item btn-detail-booking"
                                   href="#bk-modal-detail"
                                   data-ajax="{{ route('booking.modal', ['booking' => $row]) }}"
                                   data-bs-toggle="modal" data-id="{{ $row->id }}" data-bs-target="#bk-modal-detail"
                                   style="font-size:13px;padding:8px 14px;border-radius:6px">
                                    <i class="ion ion-ios-eye" style="width:18px"></i> Detail
                                </a>

                                @if($isUnpaid)
                                <div style="border-top:1px solid #f0f0f0;margin:4px 0"></div>

                                {{-- Invoice --}}
                                @if($existingInvoice)
                                    <a class="dropdown-item" href="{{ route('tourpay.vendor.view', $existingInvoice->id) }}"
                                       style="font-size:13px;padding:8px 14px;border-radius:6px">
                                        <i class="ion ion-ios-document" style="width:18px"></i> View Invoice
                                    </a>
                                @elseif($row->object_model === 'tanova_trip')
                                    <form method="POST" action="{{ route('admin.tanova.createInvoice', $row->object_id) }}" style="display:contents">
                                        @csrf
                                        <button type="submit" class="dropdown-item" style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                            <i class="ion ion-ios-document" style="width:18px"></i> Create Invoice
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tourpay.vendor.from-booking', $row->id) }}" style="display:contents">
                                        @csrf
                                        <button type="submit" class="dropdown-item" style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                            <i class="ion ion-ios-document" style="width:18px"></i> Create Invoice
                                        </button>
                                    </form>
                                @endif

                                {{-- WhatsApp --}}
                                @if($row->phone)
                                    <button type="button" class="dropdown-item bk-send-wa"
                                        data-booking="{{ $row->id }}"
                                        data-phone="{{ $row->phone }}"
                                        data-name="{{ e($guestName) }}"
                                        data-total="{{ format_money_main($row->total) }}"
                                        data-status="{{ booking_status_to_text($row->status) }}"
                                        data-url="{{ route('user.booking.send-whatsapp', $row->id) }}"
                                        style="font-size:13px;padding:8px 14px;border-radius:6px;color:#16a34a;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                        <i class="ion ion-logo-whatsapp" style="width:18px"></i> WhatsApp
                                    </button>
                                @endif

                                {{-- Email --}}
                                @if($row->email)
                                    <button type="button" class="dropdown-item bk-send-email"
                                        data-booking="{{ $row->id }}"
                                        data-email="{{ $row->email }}"
                                        data-name="{{ e($guestName) }}"
                                        data-total="{{ format_money_main($row->total) }}"
                                        data-status="{{ booking_status_to_text($row->status) }}"
                                        data-url="{{ route('user.booking.send-email', $row->id) }}"
                                        style="font-size:13px;padding:8px 14px;border-radius:6px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                        <i class="ion ion-ios-mail" style="width:18px"></i> Email
                                    </button>
                                @endif
                                @endif {{-- end $isUnpaid --}}

                                @if(auth()->user()->hasPermission('dashboard_access'))
                                <div style="border-top:1px solid #f0f0f0;margin:4px 0"></div>
                                <a class="dropdown-item"
                                   href="{{ route('booking.admin.edit', ['id' => $row->id]) }}"
                                   style="font-size:13px;padding:8px 14px;border-radius:6px">
                                    <i class="ion ion-ios-create" style="width:18px"></i> Edit
                                </a>
                                @endif

                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:60px 20px;color:#aaa;font-size:13px">
                        <i class="ion ion-ios-calendar" style="font-size:32px;display:block;margin-bottom:10px;color:#e0e0e0"></i>
                        No bookings found.
                        @if(request('s') || request('status'))
                            <br><a href="{{ route('user.vendor.bookings') }}" style="color:#2563eb;font-size:12px">Clear filters</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end">{{ $rows->links() }}</div>

{{-- Detail modal --}}
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

{{-- WhatsApp modal --}}
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

{{-- Email modal --}}
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
@endsection

@push('js')
<script>
var _bkCsrf    = '{{ csrf_token() }}';
var _bkCompany = '{{ addslashes(auth()->user()->business_name ?: auth()->user()->name) }}';

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
@endpush
