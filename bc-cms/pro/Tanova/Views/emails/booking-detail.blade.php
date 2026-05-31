<table class="b-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="label">{{__('Booking Number')}}</td>
        <td class="val">#{{$booking->id}}</td>
    </tr>
    <tr>
        <td class="label">{{__('Status')}}</td>
        <td class="val">{{$booking->statusName}}</td>
    </tr>
    @if($booking->gatewayObj)
    <tr>
        <td class="label">{{__('Payment Method')}}</td>
        <td class="val">{{$booking->gatewayObj->getOption('name')}}</td>
    </tr>
    @endif
    <tr>
        <td class="label">{{__('Trip')}}</td>
        <td class="val">{{$service->title ?? '—'}}</td>
    </tr>
    <tr>
        <td class="label">{{__('Destination')}}</td>
        <td class="val">{{$service->destination ?? '—'}}</td>
    </tr>
    @if($booking->start_date)
    <tr>
        <td class="label">{{__('Check-in')}}</td>
        <td class="val">{{display_date($booking->start_date)}}</td>
    </tr>
    @endif
    @if($booking->end_date)
    <tr>
        <td class="label">{{__('Check-out')}}</td>
        <td class="val">{{display_date($booking->end_date)}}</td>
    </tr>
    @endif
    @if($service && $service->guests)
    <tr>
        <td class="label">{{__('Guests')}}</td>
        <td class="val">{{$service->guests}}</td>
    </tr>
    @endif
    <tr>
        <td class="label" style="font-size:18px">{{__('Total')}}</td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636">{{format_money($booking->total)}}</strong></td>
    </tr>
    <tr>
        <td class="label" style="font-size:18px">{{__('Paid')}}</td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636">{{format_money($booking->paid)}}</strong></td>
    </tr>
    @if($booking->total > $booking->paid)
    <tr>
        <td class="label" style="font-size:18px">{{__('Remaining')}}</td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636">{{format_money($booking->total - $booking->paid)}}</strong></td>
    </tr>
    @endif
</table>
<div class="text-center mt20">
    <a href="{{ route('admin.tanova.show', $service) }}" target="_blank" class="btn btn-primary manage-booking-btn">
        {{__('View Itinerary')}}
    </a>
</div>
