@extends('layouts.user')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            @include('admin.message')

            {{-- Active subscription card --}}
            @if(!empty($subscription) && $subscription->isActive())
                @php
                    $daysLeft = (int) now()->diffInDays($subscription->ends_at, false);
                    $isExpiringSoon = $daysLeft <= 7;
                @endphp
                <div class="alert alert-{{$isExpiringSoon ? 'warning' : 'success'}}">
                    @if($isExpiringSoon)
                        <strong>{{__('Your plan expires in :d day(s).', ['d' => $daysLeft])}}</strong>
                        {{__('Contact the platform admin to renew before your access is suspended.')}}
                    @else
                        <strong>{{__('Active subscription')}}</strong> — {{__('your plan is current.')}}
                    @endif
                </div>

                <div class="panel">
                    <div class="panel-title"><strong>{{__('Current Plan')}}</strong></div>
                    <div class="panel-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="200px">{{__('Plan')}}</th>
                                <td><strong>{{$subscription->plan->name}}</strong></td>
                            </tr>
                            <tr>
                                <th>{{__('Platform Commission')}}</th>
                                <td>{{$subscription->plan->base_commission}}% {{__('per booking')}}</td>
                            </tr>
                            <tr>
                                <th>{{__('Billing Cycle')}}</th>
                                <td>{{$subscription->billing_cycle_label}}</td>
                            </tr>
                            <tr>
                                <th>{{__('Amount Paid')}}</th>
                                <td>{{format_money($subscription->amount_paid)}}</td>
                            </tr>
                            <tr>
                                <th>{{__('Started')}}</th>
                                <td>{{display_date($subscription->starts_at)}}</td>
                            </tr>
                            <tr>
                                <th>{{__('Expires')}}</th>
                                <td>
                                    {{display_date($subscription->ends_at)}}
                                    <span class="text-muted">({{$daysLeft}} {{__('days remaining')}})</span>
                                </td>
                            </tr>
                        </table>

                        <a href="{{route('vendor.subscription.plans')}}" class="btn btn-outline-primary btn-sm">
                            {{__('View All Plans')}}
                        </a>
                    </div>
                </div>

            @else
                <div class="alert alert-warning">
                    <strong>{{__('No active subscription.')}}</strong>
                    {{__('You need an active plan to manage your listings on this platform.')}}
                    <a href="{{route('vendor.subscription.plans')}}" class="alert-link">{{__('View Plans')}}</a>
                </div>
            @endif

            {{-- Subscription history --}}
            @if($history->total() > 0)
            <div class="panel mt-4">
                <div class="panel-title"><strong>{{__('Subscription History')}}</strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>{{__('Plan')}}</th>
                                <th>{{__('Cycle')}}</th>
                                <th>{{__('Amount')}}</th>
                                <th>{{__('Start')}}</th>
                                <th>{{__('End')}}</th>
                                <th>{{__('Status')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($history as $sub)
                            <tr>
                                <td>{{$sub->plan->name}}</td>
                                <td>{{$sub->billing_cycle_label}}</td>
                                <td>{{format_money($sub->amount_paid)}}</td>
                                <td>{{display_date($sub->starts_at)}}</td>
                                <td>{{display_date($sub->ends_at)}}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $sub->status === 'active'     ? 'success'   :
                                        ($sub->status === 'expired'   ? 'warning'   :
                                        ($sub->status === 'cancelled' ? 'danger'    : 'secondary'))
                                    }}">
                                        {{\Modules\Vendor\Models\VendorSubscription::getAllStatuses()[$sub->status] ?? $sub->status}}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{$history->links()}}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
