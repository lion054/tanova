@extends ('admin.layouts.app')
@push('css')
<style>
.tp-stat { background:#fff; border:1px solid #ebebeb; border-radius:10px; padding:18px 20px; }
.tp-stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:#aaa; margin-bottom:6px; }
.tp-stat-amount { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; line-height:1; }
.tp-stat-sub { font-size:11px; color:#bbb; margin-top:4px; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-card-header { padding:14px 20px; border-bottom:1px solid #f0f0f0; font-size:13px; font-weight:700; color:#0a0a0a; display:flex; align-items:center; gap:8px; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:100px; font-size:11px; font-weight:600; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.tp-badge-paid       { background:#f0fdf4; color:#16a34a; }  .tp-badge-paid::before       { background:#16a34a; }
.tp-badge-processing { background:#fffbeb; color:#d97706; }  .tp-badge-processing::before { background:#d97706; }
.tp-badge-completed  { background:#eff6ff; color:#2563eb; }  .tp-badge-completed::before  { background:#2563eb; }
.tp-badge-cancelled  { background:#fff1f2; color:#e11d48; }  .tp-badge-cancelled::before  { background:#e11d48; }
.tp-badge-unpaid     { background:#f4f4f5; color:#71717a; }  .tp-badge-unpaid::before     { background:#71717a; }

.tp-table { width:100%; border-collapse:collapse; }
.tp-table thead { border-bottom:1px solid #f0f0f0; background:#fafafa; }
.tp-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#bbb; white-space:nowrap; }
.tp-table td { padding:14px 16px; font-size:13px; color:#222; border-bottom:1px solid #f7f7f7; vertical-align:middle; }
.tp-table tbody tr:hover { background:#fafafa; }
.tp-table tbody tr:last-child td { border-bottom:none; }

.tp-filter-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:14px 20px; border-bottom:1px solid #f0f0f0; background:#fafafa; }
.tp-filter-field { display:flex; align-items:center; gap:6px; border:1.5px solid #e4e4e4; border-radius:8px; height:38px; padding:0 12px; background:#fff; }
.tp-filter-field:focus-within { border-color:#0a0a0a; }
.tp-filter-field input, .tp-filter-field select { border:none; background:transparent; font-size:13px; color:#333; outline:none; height:100%; }
.tp-filter-field select { cursor:pointer; }

.tp-ab-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 13px; border-radius:6px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn-primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn-primary:hover { background:#333; }
.tp-ab-btn-outline { background:#fff; border:1.5px solid #e0e0e0; color:#0a0a0a !important; }
.tp-ab-btn-outline:hover { border-color:#0a0a0a; }
.tp-ab-btn-danger { background:#fff1f2; border:1.5px solid #fecdd3; color:#e11d48 !important; }
.tp-ab-btn-danger:hover { background:#ffe4e6; }

.bk-service-name { font-size:13px; font-weight:600; color:#0a0a0a; text-decoration:none; }
.bk-service-name:hover { text-decoration:underline; }
.bk-meta { font-size:11px; color:#aaa; margin-top:2px; }
.bk-customer-name { font-size:13px; font-weight:600; color:#0a0a0a; }
.bk-customer-meta { font-size:11px; color:#aaa; line-height:1.6; }
.bk-money-total { font-size:14px; font-weight:800; color:#0a0a0a; letter-spacing:-.02em; }
.bk-money-sub { font-size:11px; color:#aaa; margin-top:1px; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0; }
</style>
@endpush
@section ('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Booking Management</p>
            <h1 class="portal-h1">All Bookings</h1>
        </div>
        @if(!empty($booking_update))
        <form method="post" action="{{route('booking.admin.bulkEdit')}}" id="bulk-form" style="display:flex;gap:8px;align-items:center">
            @csrf
            <select name="action" class="tp-filter-field" style="height:38px;padding:0 12px;border:1.5px solid #e4e4e4;border-radius:8px;font-size:13px;color:#333;background:#fff;outline:none">
                <option value="">-- Bulk Actions --</option>
                @if(!empty($statues))
                    @foreach($statues as $status)
                        <option value="{{$status}}">Mark as: {{booking_status_to_text($status)}}</option>
                    @endforeach
                @endif
                <option value="delete">DELETE selected</option>
            </select>
            <button data-confirm="{{__('Do you want to delete?')}}" class="tp-ab-btn tp-ab-btn-outline dungdt-apply-form-btn" type="button">Apply</button>
        </form>
        @endif
    </div>

    @include('admin.message')

    {{-- Stats --}}
    @php
        $allRows = \Modules\Booking\Models\Booking::where('status','!=','draft')
            ->when(!auth()->user()->hasPermission('booking_manage_others'), fn($q) => $q->where('vendor_id', auth()->id()))
            ->whereIn('object_model', array_keys(get_bookable_services()));
        $totalCount  = (clone $allRows)->count();
        $totalRev    = (clone $allRows)->sum('total');
        $totalPaid   = (clone $allRows)->sum('paid');
        $processing  = (clone $allRows)->where('status', 'processing')->count();
        $cancelled   = (clone $allRows)->where('status', 'cancelled')->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="tp-stat">
                <div class="tp-stat-label">Total Bookings</div>
                <div class="tp-stat-amount">{{ $totalCount }}</div>
                <div class="tp-stat-sub">{{ $cancelled }} cancelled</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tp-stat">
                <div class="tp-stat-label">Total Revenue</div>
                <div class="tp-stat-amount">{{ format_money_main($totalRev) }}</div>
                <div class="tp-stat-sub">gross value</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tp-stat">
                <div class="tp-stat-label">Collected</div>
                <div class="tp-stat-amount" style="color:#16a34a">{{ format_money_main($totalPaid) }}</div>
                <div class="tp-stat-sub">{{ format_money_main($totalRev - $totalPaid) }} remaining</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="tp-stat">
                <div class="tp-stat-label">Awaiting Action</div>
                <div class="tp-stat-amount" style="color:#d97706">{{ $processing }}</div>
                <div class="tp-stat-sub">processing status</div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="get" action="" id="filter-form">
        <div class="tp-card mb-4">
            <div class="tp-filter-bar">
                @if(!empty($booking_manage_others))
                    @php
                        $vendor = !empty(Request()->vendor_id) ? App\User::find(Request()->vendor_id) : false;
                        \App\Helpers\AdminForm::select2('vendor_id', [
                            'configs' => [
                                'ajax'        => ['url' => route('user.admin.getForSelect2'), 'dataType' => 'json'],
                                'allowClear'  => true,
                                'placeholder' => '-- All Vendors --'
                            ]
                        ], !empty($vendor->id) ? [$vendor->id, $vendor->name_or_email . ' (#' . $vendor->id . ')'] : false)
                    @endphp
                @endif
                <div class="tp-filter-field flex-grow-1" style="max-width:320px">
                    <i class="ion ion-ios-search" style="color:#aaa;flex-shrink:0"></i>
                    <input type="text" name="s" value="{{ Request()->s }}" placeholder="Search by name, email or ID…">
                </div>
                <button type="submit" class="tp-ab-btn tp-ab-btn-primary">
                    <i class="ion ion-ios-funnel"></i> Filter
                </button>
                @if(Request()->s || Request()->vendor_id)
                    <a href="{{ route('booking.admin.index') }}" class="tp-ab-btn tp-ab-btn-outline">Clear</a>
                @endif
                <span style="margin-left:auto;font-size:12px;color:#aaa;align-self:center">
                    <i>{{ $rows->total() }} booking(s) found</i>
                </span>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="tp-card mb-4">
        <div class="tp-card-header">
            <i class="ion ion-ios-list" style="color:#2563eb"></i>
            Bookings
        </div>
        <form action="" class="bc-form-item" id="items-form">
            <div style="overflow-x:auto">
                <table class="tp-table">
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="check-all"></th>
                            <th style="width:50px">#</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php $booking = $row; @endphp
                        <tr>
                            <td><input type="checkbox" class="check-item" name="ids[]" value="{{$row->id}}"></td>
                            <td style="color:#aaa;font-size:12px">#{{$row->id}}</td>
                            <td>
                                @if($service = $row->service)
                                    <a href="{{$service->getDetailUrl()}}" target="_blank" class="bk-service-name">
                                        {{ Str::limit($service->title ?? '', 45) }}
                                    </a>
                                    @if($row->vendor)
                                    <div class="bk-meta">
                                        by <a href="{{route('user.admin.detail',['id'=>$row->vendor_id])}}" style="color:#2563eb" target="_blank">
                                            {{$row->vendor->name_or_email}}
                                        </a>
                                    </div>
                                    @endif
                                @else
                                    <span style="color:#aaa;font-size:12px">[Deleted]</span>
                                @endif
                            </td>
                            <td>
                                <div class="bk-customer-name">{{$row->first_name}} {{$row->last_name}}</div>
                                <div class="bk-customer-meta">
                                    @if($row->email)<a href="mailto:{{$row->email}}" style="color:#2563eb">{{$row->email}}</a><br>@endif
                                    @if($row->phone){{$row->phone}}@endif
                                </div>
                            </td>
                            <td>
                                <div class="bk-money-total">{{format_money_main($row->total)}}</div>
                                <div class="bk-money-sub">
                                    Paid {{ format_money_main($row->paid) }}
                                    @if($row->total > $row->paid)
                                        · <span style="color:#d97706">{{ format_money_main($row->total - $row->paid) }} due</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $sc = match($row->status) {
                                        'paid'       => 'paid',
                                        'completed'  => 'completed',
                                        'cancelled'  => 'cancelled',
                                        'processing' => 'processing',
                                        default      => 'unpaid',
                                    };
                                @endphp
                                <span class="tp-badge tp-badge-{{$sc}}">{{$row->statusName}}</span>
                            </td>
                            <td style="font-size:12px;color:#555">
                                {{$row->gatewayObj ? $row->gatewayObj->getDisplayName() : '—'}}
                            </td>
                            <td style="font-size:12px;color:#aaa;white-space:nowrap">
                                {{display_datetime($row->updated_at)}}
                            </td>
                            <td>
                                @if($service = $row->service)
                                <div class="dropdown">
                                    <button class="tp-ab-btn tp-ab-btn-outline dropdown-toggle" type="button"
                                            data-toggle="dropdown" style="padding:5px 10px;font-size:11px">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" style="min-width:160px;border:1px solid #ebebeb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:4px">
                                        <a class="dropdown-item btn-detail-booking"
                                           href="#modal_booking_detail"
                                           data-ajax="{{route('booking.modal',['booking'=>$booking])}}"
                                           data-toggle="modal"
                                           data-id="{{$booking->id}}"
                                           data-target="#modal_booking_detail"
                                           style="font-size:13px;padding:8px 14px;border-radius:6px">
                                            <i class="ion ion-ios-eye" style="width:18px"></i> Detail
                                        </a>
                                        <a class="dropdown-item" href="#"
                                           data-toggle="modal" data-target="#modal-paid-{{$row->id}}"
                                           style="font-size:13px;padding:8px 14px;border-radius:6px">
                                            <i class="ion ion-ios-card" style="width:18px"></i> Set Paid
                                        </a>
                                        <a class="dropdown-item"
                                           href="{{route('booking.admin.email_preview',['id'=>$row->id])}}"
                                           target="_blank"
                                           style="font-size:13px;padding:8px 14px;border-radius:6px">
                                            <i class="ion ion-ios-mail" style="width:18px"></i> Email Preview
                                        </a>
                                        @if(auth()->user()->hasPermission('booking_update'))
                                        <a class="dropdown-item"
                                           href="{{route('booking.admin.edit',['id'=>$row->id])}}"
                                           style="font-size:13px;padding:8px 14px;border-radius:6px">
                                            <i class="ion ion-ios-create" style="width:18px"></i> Edit Booking
                                        </a>
                                        @endif
                                    </div>
                                </div>
                                @include ($service->set_paid_modal_file ?? '')
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:60px 20px;color:#aaa;font-size:13px">
                                <i class="ion ion-ios-calendar" style="font-size:32px;display:block;margin-bottom:10px;color:#e0e0e0"></i>
                                No bookings found.
                                @if(Request()->s) Try a different search term. @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-end">{{$rows->links()}}</div>
</div>

{{-- Detail modal --}}
<div class="modal fade" tabindex="-1" id="modal_booking_detail">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #ebebeb;border-radius:12px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.15)">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:16px 20px">
                <h5 class="modal-title" style="font-size:15px;font-weight:700;color:#0a0a0a">
                    Booking ID: <span class="user_id" style="color:#2563eb"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:20px">
                <div class="d-flex justify-content-center" style="color:#aaa;padding:40px 0">Loading…</div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 20px">
                <button type="button" class="tp-ab-btn tp-ab-btn-outline" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).on('click', '#set_paid_btn', function (e) {
    var id = $(this).data('id');
    $.ajax({
        url: bookingCore.url + '/booking/setPaidAmount',
        data: { id: id, remain: $('#modal-paid-' + id + ' #set_paid_input').val() },
        dataType: 'json',
        type: 'post',
        success: function(res) { alert(res.message); window.location.reload(); }
    });
});

$('.btn-detail-booking').on('click', function() {
    var btn = $(this);
    var modal = $('#modal_booking_detail');
    modal.find('.user_id').html(btn.data('id'));
    modal.find('.modal-body').html('<div class="d-flex justify-content-center" style="color:#aaa;padding:40px 0">Loading…</div>');
    $.get(btn.data('ajax'), function(html){ modal.find('.modal-body').html(html); });
});

$(document).on('click', '.btn-submit-note-vendor', function () {
    var parent = $(this).closest('.bc-note-form');
    var id = $(this).data('id');
    parent.find('.icon-loading').removeClass('d-none');
    $.ajax({
        url: bookingCore.url + '/booking/storeNoteBooking',
        method: 'post',
        data: parent.find('input,select,textarea').serialize(),
        dataType: 'json',
        success: function(res) {
            parent.find('.icon-loading').addClass('d-none');
            if (res.errors) {
                res.message = '';
                for (var k in res.errors) res.message += res.errors[k].join('<br>') + '<br>';
            }
            if (res.message) {
                parent.find('.message_box').html(
                    '<div class="text text-' + (res.status ? 'success' : 'danger') + '">' + res.message + '</div>'
                );
            }
        },
        error: function(e) { console.log(e); parent.find('.icon-loading').addClass('d-none'); }
    });
});

// Wire bulk form check-all / check-item to the bulk-form
$('.check-all').on('change', function() {
    $('.check-item').prop('checked', this.checked);
});
$('.check-item').on('change', function() {
    if (!this.checked) $('.check-all').prop('checked', false);
});
$('.dungdt-apply-form-btn').on('click', function() {
    var action = $('[name="action"]').val();
    if (!action) { alert('Please select an action.'); return; }
    if (action === 'delete' && !confirm('Delete selected bookings? This cannot be undone.')) return;
    var ids = $('.check-item:checked').map(function(){ return $(this).val(); }).get();
    if (!ids.length) { alert('No bookings selected.'); return; }
    // Copy checked ids into bulk-form
    $('#bulk-form').find('input[name="ids[]"]').remove();
    ids.forEach(function(id){ $('#bulk-form').append('<input type="hidden" name="ids[]" value="'+id+'">'); });
    $('#bulk-form').submit();
});
</script>
@endpush
