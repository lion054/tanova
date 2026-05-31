@extends('layouts.user')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">{{__('Choose a Plan')}}</h2>
            <p class="text-muted mb-4">
                {{__('Each plan includes a platform subscription fee and a commission rate on every booking you receive. Higher plans carry lower commission rates.')}}
            </p>

            @if(!empty($currentSubscription) && $currentSubscription->isActive())
                <div class="alert alert-info">
                    {{__('You are currently on the :plan plan, expiring :date.', [
                        'plan' => $currentSubscription->plan->name,
                        'date' => display_date($currentSubscription->ends_at),
                    ])}}
                    {{__('To change your plan, please contact the platform administrator.')}}
                </div>
            @endif

            <div class="row">
                @foreach($plans as $plan)
                    @php
                        $isCurrent = !empty($currentSubscription) && $currentSubscription->plan_id == $plan->id && $currentSubscription->isActive();
                        $metas = $plan->meta->keyBy('post_type');
                    @endphp
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 {{$isCurrent ? 'border-success shadow' : ''}}">
                            @if($isCurrent)
                                <div class="card-header bg-success text-white text-center">
                                    <strong>{{__('Your Current Plan')}}</strong>
                                </div>
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h4 class="card-title">{{$plan->name}}</h4>

                                {{-- Pricing --}}
                                <div class="mb-3">
                                    <span class="h3 font-weight-bold">{{format_money($plan->price)}}</span>
                                    <span class="text-muted">/{{__('month')}}</span>
                                    @if($plan->price_annual)
                                        @php $saving = round((1 - $plan->price_annual / ($plan->price * 12)) * 100) @endphp
                                        <div class="text-success small mt-1">
                                            {{format_money($plan->price_annual)}}/{{__('year')}}
                                            <span class="badge badge-success">{{__('Save :p%', ['p' => $saving])}}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Commission highlight --}}
                                <div class="alert alert-light border mb-3 py-2">
                                    <strong>{{$plan->base_commission}}%</strong> {{__('platform commission per booking')}}
                                </div>

                                {{-- Feature list --}}
                                <ul class="list-unstyled flex-grow-1">
                                    @foreach($metas as $type => $meta)
                                        @if($meta->enable)
                                        <li class="mb-1">
                                            <i class="fa fa-check text-success mr-1"></i>
                                            <strong>{{ucfirst($type)}}</strong>:
                                            @if($meta->maximum_create)
                                                {{__('up to :n listings', ['n' => $meta->maximum_create])}}
                                            @else
                                                {{__('unlimited listings')}}
                                            @endif
                                            @if($meta->auto_publish)
                                                <span class="badge badge-info badge-sm">{{__('auto-publish')}}</span>
                                            @endif
                                            @if($meta->commission && $meta->commission != $plan->base_commission)
                                                <span class="text-muted small">({{$meta->commission}}% commission)</span>
                                            @endif
                                        </li>
                                        @else
                                        <li class="mb-1 text-muted">
                                            <i class="fa fa-times text-danger mr-1"></i>
                                            {{ucfirst($type)}}
                                        </li>
                                        @endif
                                    @endforeach
                                </ul>

                                {{-- CTA --}}
                                <div class="mt-3">
                                    @if($isCurrent)
                                        <button class="btn btn-success btn-block" disabled>{{__('Current Plan')}}</button>
                                    @else
                                        <div class="alert alert-light border text-center small py-2">
                                            {{__('To subscribe or upgrade, contact the platform administrator or email')}}
                                            <a href="mailto:{{setting_item('admin_email', 'admin@tsokatravel.com')}}">
                                                {{setting_item('admin_email', 'admin@tsokatravel.com')}}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($plans->isEmpty())
                <div class="alert alert-info">{{__('No plans are available at the moment. Please check back soon.')}}</div>
            @endif
        </div>
    </div>
</div>
@endsection
