@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__('Plan orders')}}</h1>
        <div class="title-actions">
            @foreach(['pending' => __('Waiting for payment'), 'paid' => __('Paid'), 'cancelled' => __('Cancelled'), 'all' => __('All')] as $k => $label)
                <a href="{{route('vendor.admin.plan_orders.index', ['status' => $k])}}" class="btn btn-{{$status === $k ? 'primary' : 'outline-secondary'}} btn-sm">{{$label}}</a>
            @endforeach
        </div>
    </div>
    @include('admin.message')
    <p class="text-muted">{{__('A company asks for a plan or an extra OS and pays you directly using the instructions in Vendor settings. When the money is in, confirm the order: the plan starts (or extends) and the company is emailed.')}}</p>
    <div class="panel"><div class="panel-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>{{__('Reference')}}</th><th>{{__('Company')}}</th><th>{{__('For')}}</th><th>{{__('Amount')}}</th><th>{{__('Placed')}}</th><th>{{__('Status')}}</th><th width="360px"></th></tr></thead>
                <tbody>
                @forelse($rows as $o)
                    <tr>
                        <td><code>{{$o->reference}}</code></td>
                        <td><a href="{{route('user.admin.detail', ['id' => $o->vendor_id])}}">{{$o->vendor->business_name ?: $o->vendor->email}}</a><small class="text-muted d-block">{{$o->vendor->email}}</small></td>
                        <td>@if($o->kind === 'addon'){{__('Extra OS')}}: {{\Modules\Vendor\Services\CompanyOs::names((array) $o->os_keys)}}@else<strong>{{$o->plan->name}}</strong> · {{$o->billing_cycle === 'yearly' ? __('yearly') : __('monthly')}}@if(!empty($o->os_keys))<small class="text-muted d-block">{{\Modules\Vendor\Services\CompanyOs::names((array) $o->os_keys)}}</small>@endif @endif</td>
                        <td>{{\Modules\Vendor\Services\PlanBilling::money($o->amount, $o->currency)}}</td>
                        <td>{{display_date($o->created_at)}}</td>
                        <td><span class="badge badge-{{$o->status === 'paid' ? 'success' : ($o->status === 'pending' ? 'warning' : 'secondary')}}">{{ucfirst($o->status)}}</span>@if($o->paid_at)<small class="text-muted d-block">{{display_date($o->paid_at)}}{{$o->payment_method ? ' · ' . $o->payment_method : ''}}</small>@endif</td>
                        <td>
                            @if($o->isPending())
                                <form method="post" action="{{route('vendor.admin.plan_orders.confirm', $o->id)}}" class="form-inline d-inline-flex" style="gap:6px">
                                    @csrf
                                    <input type="text" name="payment_method" class="form-control form-control-sm" placeholder="{{__('How paid (bank, EcoCash...)')}}" style="width:170px">
                                    <input type="text" name="payment_note" class="form-control form-control-sm" placeholder="{{__('Note')}}" style="width:110px">
                                    <button class="btn btn-success btn-sm" type="submit" onclick="return confirm('{{__('Money received? This starts the plan.')}}')">{{__('Confirm paid')}}</button>
                                </form>
                                <form method="post" action="{{route('vendor.admin.plan_orders.cancel', $o->id)}}" class="d-inline">@csrf<button class="btn btn-outline-danger btn-sm" type="submit">{{__('Cancel')}}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">{{__('Nothing here.')}}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{$rows->links()}}
    </div></div>
</div>
@endsection
