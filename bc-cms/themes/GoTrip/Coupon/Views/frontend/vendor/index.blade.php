@extends('layouts.user')
@section('content')
@php($cPill = fn($s) => $s==='publish' ? 'tnv-b--pos' : ($s==='pending' ? 'tnv-b--gold' : 'tnv-b--neutral'))
<div class="tnv-page">

    <div class="tnv-ph">
        <div>
            <div class="tnv-ph__crumb">{{ __('Marketing') }}</div>
            <h1 class="tnv-ph__title">{{ __('Manage Coupons') }}</h1>
            <div class="tnv-ph__sub">{{ __('Smart promotions, AI-timed. Offers that convert browsers into bookers.') }}</div>
        </div>
        @if(Auth::user()->hasPermission('coupon_create') && empty($recovery))
        <div class="tnv-ph__actions">
            <a href="{{ route('coupon.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Coupon') }}</a>
        </div>
        @endif
    </div>

    @include('admin.message')

    <div class="tnv-c">
        <div class="tnv-c__h"><h3>{{ __('Coupons') }}</h3><span class="tnv-b tnv-b--neutral">{{ $rows->total() }}</span></div>
        <div class="tnv-c__b tnv-c__b--flush">
            @if($rows->total() > 0)
                <div style="overflow-x:auto">
                <table class="tnv-tbl">
                    <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Discount Type') }}</th><th>{{ __('End Date') }}</th><th>{{ __('Status') }}</th><th class="right">{{ __('Action') }}</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td><strong>{{ $row->code }}</strong></td>
                                <td>{{ $row->name }}</td>
                                <td class="num">{{ $row->amount }}</td>
                                <td>{{ $row->discount_type == 'percent' ? __('Percent') : __('Amount') }}</td>
                                <td class="tnv-muted">{{ $row->end_date }}</td>
                                <td><span class="tnv-b {{ $cPill($row->status) }}">{{ ucfirst($row->status) }}</span></td>
                                <td class="right" style="white-space:nowrap">
                                    <a href="{{ route('coupon.vendor.edit',['id'=>$row->id]) }}" class="tnv-btn tnv-btn--ghost tnv-btn--sm">{{ __('Edit') }}</a>
                                    <a href="{{ route('coupon.vendor.delete',['id'=>$row->id]) }}" class="tnv-btn tnv-btn--danger tnv-btn--sm">{{ __('Delete') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div style="padding:14px 20px">{{ $rows->appends(request()->query())->links() }}</div>
            @else
                <div class="tnv-empty">
                    <div class="tnv-empty__ic"><i class="icofont-sale-discount"></i></div>
                    <div class="tnv-empty__t">{{ __('No coupons yet') }}</div>
                    <div class="tnv-empty__s">{{ __('Create a promotion to boost conversions.') }}</div>
                    @if(Auth::user()->hasPermission('coupon_create') && empty($recovery))
                        <div style="margin-top:14px"><a href="{{ route('coupon.vendor.create') }}" class="tnv-btn tnv-btn--gold"><i class="icofont-plus"></i> {{ __('Add Coupon') }}</a></div>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
