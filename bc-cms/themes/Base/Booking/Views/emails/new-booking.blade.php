@extends('Email::layout')
@section('content')

    <div class="b-container">
        <div class="b-panel">
            @switch($to)
                @case ('admin')
                    <h3 class="email-headline"><strong>{{__('Hello Administrator')}}</strong></h3>
                    <p>{{__('New booking has been made')}}</p>
                @break
                @case ('vendor')
                    <h3 class="email-headline"><strong>{{__('Hello :name',['name'=>$booking->vendor->nameOrEmail ?? ''])}}</strong></h3>
                    <p>{{__('Your service has new booking')}}</p>
                @break

                @case ('customer')
                    <h3 class="email-headline"><strong>{{__('Hello :name',['name'=>$booking->first_name ?? ''])}}</strong></h3>
                    <p>{{__('Thank you for booking with us. Here are your booking information:')}}</p>
                @break

            @endswitch

            @if(!empty($service->email_new_booking_file) && view()->exists($service->email_new_booking_file))
                @include($service->email_new_booking_file)
            @else
                <table class="b-table" cellspacing="0" cellpadding="0">
                    <tr><td class="label">{{__('Booking Number')}}</td><td class="val">#{{$booking->id}}</td></tr>
                    <tr><td class="label">{{__('Service')}}</td><td class="val">{{$service->title ?? $booking->object_model ?? '—'}}</td></tr>
                    <tr><td class="label">{{__('Start Date')}}</td><td class="val">{{$booking->start_date ? display_date($booking->start_date) : '—'}}</td></tr>
                    <tr><td class="label">{{__('End Date')}}</td><td class="val">{{$booking->end_date ? display_date($booking->end_date) : '—'}}</td></tr>
                    <tr><td class="label">{{__('Status')}}</td><td class="val">{{$booking->statusName}}</td></tr>
                    <tr><td class="label" style="font-size:18px">{{__('Total')}}</td><td class="val" style="font-size:18px"><strong style="color:#FA5636">{{format_money($booking->total)}}</strong></td></tr>
                    <tr><td class="label" style="font-size:18px">{{__('Paid')}}</td><td class="val" style="font-size:18px"><strong style="color:#FA5636">{{format_money($booking->paid)}}</strong></td></tr>
                </table>
                <div class="text-center mt20">
                    <a href="{{ route('user.booking_history') }}" target="_blank" class="btn btn-primary manage-booking-btn">{{__('Manage Bookings')}}</a>
                </div>
            @endif
        </div>
        @include('Booking::emails.parts.panel-customer')
        @include('Booking::emails.parts.panel-passengers')
    </div>
@endsection
