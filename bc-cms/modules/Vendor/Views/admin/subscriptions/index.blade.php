@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__("Vendor Subscriptions")}}</h1>
        <div class="title-actions">
            <a href="{{route('vendor.admin.subscription.assign')}}" class="btn btn-primary">
                <i class="fa fa-plus"></i> {{__("Assign Plan")}}
            </a>
        </div>
    </div>
    @include('admin.message')

    {{-- Filters --}}
    <div class="filter-div d-flex justify-content-end mb-3">
        <form method="get" action="{{route('vendor.admin.subscription.index')}}" class="d-flex flex-wrap" style="gap:8px">
            <?php
            $filterVendor = !empty(request('vendor_id')) ? \App\User::find(request('vendor_id')) : false;
            \App\Helpers\AdminForm::select2('vendor_id', [
                'configs' => [
                    'ajax'        => ['url' => route('user.admin.getForSelect2'), 'dataType' => 'json'],
                    'allowClear'  => true,
                    'placeholder' => __('-- All Vendors --'),
                ]
            ], !empty($filterVendor) ? [$filterVendor->id, $filterVendor->getDisplayName() . ' (#' . $filterVendor->id . ')'] : false);
            ?>
            <select name="plan_id" class="form-control" style="width:180px">
                <option value="">{{__('-- All Plans --')}}</option>
                @foreach($plans as $plan)
                    <option value="{{$plan->id}}" {{request('plan_id') == $plan->id ? 'selected' : ''}}>{{$plan->name}}</option>
                @endforeach
            </select>
            <select name="status" class="form-control" style="width:150px">
                <option value="">{{__('-- All Statuses --')}}</option>
                @foreach($statuses as $key => $label)
                    <option value="{{$key}}" {{request('status') == $key ? 'selected' : ''}}>{{$label}}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-info">{{__('Filter')}}</button>
        </form>
    </div>

    <div class="text-right mb-2">
        <i>{{__('Found :total subscriptions', ['total' => $rows->total()])}}</i>
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th width="70px">{{__('#')}}</th>
                        <th>{{__('Vendor')}}</th>
                        <th>{{__('Plan')}}</th>
                        <th width="110px">{{__('Cycle')}}</th>
                        <th width="120px">{{__('Amount Paid')}}</th>
                        <th width="140px">{{__('Starts')}}</th>
                        <th width="140px">{{__('Expires')}}</th>
                        <th width="90px">{{__('Status')}}</th>
                        <th width="100px">{{__('Actions')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $sub)
                        <tr class="status-{{$sub->status}}">
                            <td>#{{$sub->id}}</td>
                            <td>
                                <a href="{{route('user.admin.detail', ['id' => $sub->vendor_id])}}">
                                    {{$sub->vendor->getDisplayName()}}
                                </a>
                                <small class="text-muted d-block">{{$sub->vendor->email}}</small>
                            </td>
                            <td>
                                <a href="{{route('vendor.admin.plan.edit', ['id' => $sub->plan_id])}}">
                                    {{$sub->plan->name}}
                                </a>
                                <small class="text-muted d-block">{{$sub->plan->base_commission}}% commission</small>
                            </td>
                            <td>{{$sub->billing_cycle_label}}</td>
                            <td>{{format_money($sub->amount_paid)}}</td>
                            <td>{{display_date($sub->starts_at)}}</td>
                            <td>
                                {{display_date($sub->ends_at)}}
                                @if($sub->status === 'active' && $sub->ends_at)
                                    @php $daysLeft = (int) now()->diffInDays($sub->ends_at, false) @endphp
                                    @if($daysLeft <= 7 && $daysLeft >= 0)
                                        <span class="badge badge-warning">{{__(':d days left', ['d' => $daysLeft])}}</span>
                                    @elseif($daysLeft < 0)
                                        <span class="badge badge-danger">{{__('Overdue')}}</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{
                                    $sub->status === 'active'    ? 'success' :
                                    ($sub->status === 'expired'  ? 'warning' :
                                    ($sub->status === 'cancelled'? 'danger'  : 'secondary'))
                                }}">
                                    {{$statuses[$sub->status] ?? $sub->status}}
                                </span>
                            </td>
                            <td>
                                @if($sub->status === 'active')
                                    <form action="{{route('vendor.admin.subscription.cancel', $sub->id)}}" method="post"
                                          onsubmit="return confirm(@json(__('Cancel this subscription?')))"
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">{{__('Cancel')}}</button>
                                    </form>
                                @else
                                    <a href="{{route('vendor.admin.subscription.assign')}}?vendor_id={{$sub->vendor_id}}"
                                       class="btn btn-sm btn-info">{{__('Renew')}}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">{{__('No subscriptions found.')}}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{$rows->appends(request()->query())->links()}}
        </div>
    </div>
</div>
@endsection
