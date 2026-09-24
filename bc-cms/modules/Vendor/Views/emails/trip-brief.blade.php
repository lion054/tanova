@component('mail::message')
# {{ __('Hello :name,', ['name' => $customerName ?: __('there')]) }}

{{ __('Here is everything for your trip with :vendor.', ['vendor' => $vendorName]) }}

@component('mail::panel')
**{{ $trip['title'] ?? '' }}**
@if(!empty($trip['dates']))<br>{{ $trip['dates'] }}@endif
@if(!empty($trip['guests']))<br>{{ trans_choice(':n guest|:n guests', $trip['guests'], ['n' => $trip['guests']]) }}@endif
<br>{{ __('Booking reference') }}: **{{ $trip['code'] }}**
@endcomponent

@if(trim($note) !== '')
{{ $note }}

@endif
@if(($trip['balance'] ?? 0) > 0)
{{ __('Still to pay: :amount', ['amount' => number_format($trip['balance'], 2) . ' ' . ($trip['currency'] ?? '')]) }}

@endif
@if($guestFormUrl)
{{ __('Please tell us who is travelling. It takes a minute.') }}

@component('mail::button', ['url' => $guestFormUrl])
{{ __('Add traveller details') }}
@endcomponent
@endif

@if(count($documents))
**{{ __('Your documents') }}**

@foreach($documents as $d)
- [{{ $d['name'] }}]({{ $d['url'] }})
@endforeach
@endif

{{ __('Safe travels,') }}<br>
{{ $vendorName }}
@endcomponent
