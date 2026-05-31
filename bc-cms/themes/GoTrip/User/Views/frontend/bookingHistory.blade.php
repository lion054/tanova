@extends('layouts.user')
@section('content')

    {{-- Page header --}}
    <div class="portal-header">
        <div>
            <p class="portal-eyebrow">{{ __("Account") }}</p>
            <h1 class="portal-h1">{{ __("Booking History") }}</h1>
        </div>
    </div>

    @include('admin.message')

    <div class="portal-card booking-history-manager">
        <div class="tabs -underline-2 js-tabs">

            {{-- Status filter tabs --}}
            <div class="tabs__controls row x-gap-32 y-gap-10 js-tabs-controls"
                 style="border-bottom:1px solid #e8e8e8;padding-bottom:0;margin-bottom:24px;">
                <?php $status_type = Request::query('status'); ?>
                <div class="col-auto">
                    <a href="{{ route('user.booking_history') }}"
                       class="tabs__button fw-500 pb-5 @if(empty($status_type)) is-tab-el-active @endif">
                        {{ __("All") }}
                    </a>
                </div>
                @if(!empty($statues))
                    @foreach($statues as $status)
                    <div class="col-auto">
                        <a href="{{ route('user.booking_history', ['status' => $status]) }}"
                           class="tabs__button fw-500 pb-5 @if(!empty($status_type) && $status_type == $status) is-tab-el-active @endif">
                            {{ booking_status_to_text($status) }}
                        </a>
                    </div>
                    @endforeach
                @endif
            </div>

            {{-- Table --}}
            <div class="tabs__content js-tabs-content">
                <div class="tabs__pane -tab-item-1 is-tab-el-active">
                    <div class="overflow-scroll scroll-bar-1">
                        @if(!empty($bookings) && $bookings->total() > 0)
                            <table class="table-3 -border-bottom col-12">
                                <thead class="bg-light-2">
                                    <tr>
                                        <th>{{ __("Type") }}</th>
                                        <th>{{ __("Title") }}</th>
                                        <th class="a-hidden">{{ __("Order Date") }}</th>
                                        <th class="a-hidden">{{ __("Dates") }}</th>
                                        <th>{{ __("Total") }}</th>
                                        <th>{{ __("Paid") }}</th>
                                        <th>{{ __("Remain") }}</th>
                                        <th class="a-hidden">{{ __("Status") }}</th>
                                        <th>{{ __("Action") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bookings as $key => $booking)
                                        @include(ucfirst($booking->object_model).'::frontend.bookingHistory.loop', ['key' => $key])
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="bc-pagination pt-30">
                                {{ $bookings->appends(request()->query())->links() }}
                            </div>
                        @else
                            <div class="portal-empty">
                                <i class="fa fa-clock-o" style="font-size:28px;color:#d0d0d0;display:block;margin-bottom:10px;"></i>
                                {{ __("No bookings found") }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('js')
<script>
$('.btn-info-booking').on('click', function (e) {
    var btn   = $(this);
    var modal = $('#modal_booking_detail');
    modal.find('.user_id').html(btn.data('id'));
    modal.find('.modal-body').html('<div class="d-flex justify-content-center">{{ __("Loading...") }}</div>');
    $.get(btn.data('ajax'), function (html) {
        modal.find('.modal-body').html(html);
    });
});
</script>
@endpush
