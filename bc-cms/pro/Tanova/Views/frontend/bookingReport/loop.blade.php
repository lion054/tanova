<tr>
    <td class="booking-history-type">
        <i class="icofont-compass-alt"></i>
        <small>{{ __('Tanova Trip') }}</small>
    </td>
    <td>
        @if($service = $booking->service)
            <strong>{{ $service->title }}</strong>
            @if($service->destination)
                <small><div>{{ $service->destination }}</div></small>
            @endif
            @if($service->start_date && $service->end_date)
                <small>
                    <div>{{ display_date($service->start_date) }} &mdash; {{ display_date($service->end_date) }}</div>
                </small>
            @endif
            @php
                $pkgIndex  = max(0, ($service->booked_package ?? 1) - 1);
                $packages  = $service->itinerary ?? [];
                $chosen    = $packages[$pkgIndex] ?? ($packages[0] ?? null);
                $hotel     = $chosen['hotel'] ?? null;
            @endphp
            <small>
                <div>{{ $service->guests }} {{ __('guest(s)') }}
                    @if($hotel) &middot; {{ $hotel['name'] ?? '' }} ({{ $hotel['type'] ?? '' }})@endif
                </div>
                <div>{{ __("Customer Info") }}</div>
                <div>{{ __("First Name") }}: {{ $booking->first_name }}</div>
                <div>{{ __("Last Name") }}: {{ $booking->last_name }}</div>
            </small>
        @else
            {{ __("[Deleted]") }}
        @endif
    </td>
    <td class="a-hidden">{{ display_date($booking->created_at) }}</td>
    <td class="a-hidden">
        @if($service && $service->start_date)
            {{ display_date($service->start_date) }}
            @if($service->end_date)
                &mdash; {{ display_date($service->end_date) }}
            @endif
        @else
            &mdash;
        @endif
    </td>
    <td>
        <div>{{ __("Total") }}: {{ format_money_main($booking->total) }}</div>
        <div>{{ __("Paid") }}: {{ format_money_main($booking->paid) }}</div>
        <div>{{ __("Remain") }}: {{ format_money($booking->total - $booking->paid) }}</div>
    </td>
    <td>{{ format_money($booking->commission) }}</td>
    <td class="{{ $booking->status }} a-hidden">{{ $booking->statusName }}</td>
    <td width="2%">
        {{-- Details: open the Tanova trip page with full itinerary --}}
        @if($service)
        <a href="{{ route('admin.tanova.show', $service->id) }}"
           class="btn btn-xs btn-primary btn-info-booking"
           target="_blank">
            <i class="fa fa-info-circle"></i>{{ __("Details") }}
        </a>
        @endif

        {{-- Invoice: link to existing TourPay invoice, or create from selected itinerary --}}
        @php
            $tInvoice = $service
                ? \Modules\TourPay\Models\Invoice::where('tanova_trip_id', $service->id)
                    ->where('author_id', auth()->id())
                    ->first()
                : null;
        @endphp
        @if($tInvoice)
            <a href="{{ route('tourpay.vendor.view', $tInvoice->id) }}"
               class="btn btn-xs btn-success btn-info-booking open-new-window mt-1"
               target="_blank">
                <i class="fa fa-file-text-o"></i>{{ __("Invoice") }}
            </a>
        @elseif($service)
            <form method="POST" action="{{ route('admin.tanova.createInvoice', $service->id) }}" style="display:inline">
                @csrf
                <input type="hidden" name="package" value="{{ $service->booked_package ?? 1 }}">
                <button type="submit" class="btn btn-xs btn-warning mt-1">
                    <i class="fa fa-plus"></i>{{ __("Create Invoice") }}
                </button>
            </form>
        @endif
    </td>
</tr>
