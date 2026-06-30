@extends('layouts.user')
@section('content')
<div class="tnv-page">

    <div class="tnv-ph">
        <div>
            <div class="tnv-ph__crumb">{{ __('Catalog') }} &middot; {{ __('Access') }}</div>
            <h1 class="tnv-ph__title">{{ !empty($recovery) ? __('Recovery Visa Services') : __('Manage Visa Services') }}</h1>
            <div class="tnv-ph__sub">{{ __('Manage your visa service listings, pricing, and applications.') }}</div>
        </div>
        @if(Auth::user()->hasPermission('visa_create') && empty($recovery))
        <div class="tnv-ph__actions">
            <a href="{{ route('visa.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Visa Service') }}</a>
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
                            <div class="col-md-12">@include('Visa::frontend.manageVisa.loop-list')</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="tnv-c__h" style="border-top:1px solid var(--c-line);border-bottom:0">
                <span class="tnv-muted" style="font-size:12.5px">{{ __('Showing :from - :to of :total Visa Services', ['from'=>$rows->firstItem(),'to'=>$rows->lastItem(),'total'=>$rows->total()]) }}</span>
                <div>{{ $rows->appends(request()->query())->links() }}</div>
            </div>
        </div>
    @else
        <div class="tnv-c"><div class="tnv-empty">
            <div class="tnv-empty__ic"><i class="icofont-id-card"></i></div>
            <div class="tnv-empty__t">{{ __('No visa services yet') }}</div>
            <div class="tnv-empty__s">{{ __('Add a visa service to start taking applications.') }}</div>
            @if(Auth::user()->hasPermission('visa_create') && empty($recovery))
                <div style="margin-top:14px"><a href="{{ route('visa.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Visa Service') }}</a></div>
            @endif
        </div></div>
    @endif

</div>
@endsection
