@extends('layouts.user')
@section('content')
<div class="tnv-page">

    <div class="tnv-ph">
        <div>
            <div class="tnv-ph__crumb">{{ __('Catalog') }} &middot; {{ __('Transport') }}</div>
            <h1 class="tnv-ph__title">{{ !empty($recovery) ? __('Recovery Boats') : __('Manage Boats') }}</h1>
            <div class="tnv-ph__sub">{{ __('AI-native boat and charter management. Fleet availability, bookings, and dispatch.') }}</div>
        </div>
        @if(Auth::user()->hasPermission('boat_create') && empty($recovery))
        <div class="tnv-ph__actions">
            <a href="{{ route('boat.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Boat') }}</a>
        </div>
        @endif
    </div>

    @include('admin.message')

    @if($rows->total() > 0)
        <div class="tnv-c">
            <div class="tnv-c__b">
                <div class="list-item mt-0">
                    <div class="row">
                        @foreach($rows as $row)
                            <div class="col-md-12">@include('Boat::frontend.manageBoat.loop-list')</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="tnv-c__h" style="border-top:1px solid var(--c-line);border-bottom:0">
                <span class="tnv-muted" style="font-size:12.5px">{{ __('Showing :from - :to of :total Boats', ['from'=>$rows->firstItem(),'to'=>$rows->lastItem(),'total'=>$rows->total()]) }}</span>
                <div>{{ $rows->appends(request()->query())->links() }}</div>
            </div>
        </div>
    @else
        <div class="tnv-c"><div class="tnv-empty">
            <div class="tnv-empty__ic"><i class="icofont-ship"></i></div>
            <div class="tnv-empty__t">{{ __('No boats yet') }}</div>
            <div class="tnv-empty__s">{{ __('Add a boat or charter to start taking bookings.') }}</div>
            @if(Auth::user()->hasPermission('boat_create') && empty($recovery))
                <div style="margin-top:14px"><a href="{{ route('boat.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Boat') }}</a></div>
            @endif
        </div></div>
    @endif

</div>
@endsection
