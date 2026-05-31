@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{!empty($row->id) ? __("Edit Plan: :name", ['name' => $row->name]) : __("Create Plan")}}</h1>
    </div>
    @include('admin.message')

    <form action="" method="post">
        @csrf
        <div class="row">
            {{-- Left column: main fields --}}
            <div class="col-md-8">

                {{-- Identity --}}
                <div class="panel">
                    <div class="panel-title"><strong>{{__('Plan Details')}}</strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>{{__('Plan Name')}} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{old('name', $row->name)}}" required>
                        </div>
                        <div class="form-group">
                            <label>{{__('Base Commission Rate (%)')}} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="base_commission" class="form-control"
                                    value="{{old('base_commission', $row->base_commission ?? 10)}}"
                                    min="0" max="100" step="1" required>
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                            <small class="form-text text-muted">{{__('Platform commission on each booking for vendors on this plan. Lower rate = more attractive plan.')}}</small>
                        </div>
                    </div>
                </div>

                {{-- Subscription Pricing --}}
                <div class="panel">
                    <div class="panel-title"><strong>{{__('Subscription Pricing')}}</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('Monthly Price')}} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{setting_item('currency_symbol','$')}}</span></div>
                                        <input type="number" name="price" class="form-control"
                                            value="{{old('price', $row->price ?? 0)}}"
                                            min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{__('Annual Price')}} <small class="text-muted">{{__('(optional — leave blank to disable annual billing)')}}</small></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{setting_item('currency_symbol','$')}}</span></div>
                                        <input type="number" name="price_annual" class="form-control"
                                            value="{{old('price_annual', $row->price_annual)}}"
                                            min="0" step="0.01">
                                    </div>
                                    @if(!empty($row->price) && !empty($row->price_annual))
                                        @php $saving = round((1 - $row->price_annual / ($row->price * 12)) * 100) @endphp
                                        <small class="text-success">{{__(':p% saving vs monthly', ['p' => $saving])}}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Per-service feature matrix --}}
                <div class="panel">
                    <div class="panel-title"><strong>{{__('Service Permissions')}}</strong></div>
                    <div class="panel-body">
                        <p class="text-muted">{{__('Control which service types vendors on this plan can create, and set per-service commission overrides.')}}</p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>{{__('Service')}}</th>
                                    <th width="80px">{{__('Enable')}}</th>
                                    <th width="130px">{{__('Max Listings')}}</th>
                                    <th width="100px">{{__('Auto-Publish')}}</th>
                                    <th width="120px">{{__('Commission %')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($service_types as $type => $label)
                                    @php $meta = !empty($row->id) ? $row->meta->firstWhere('post_type', $type) : null; @endphp
                                    <tr>
                                        <td><strong>{{$label}}</strong>
                                            <input type="hidden" name="services_options[{{$type}}][post_type]" value="{{$type}}">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="services_options[{{$type}}][enable]" value="1"
                                                {{!empty($meta->enable) ? 'checked' : ''}}>
                                        </td>
                                        <td>
                                            <input type="number" name="services_options[{{$type}}][maximum_create]"
                                                class="form-control form-control-sm"
                                                value="{{$meta->maximum_create ?? ''}}"
                                                placeholder="{{__('Unlimited')}}" min="0">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="services_options[{{$type}}][auto_publish]" value="1"
                                                {{!empty($meta->auto_publish) ? 'checked' : ''}}>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="services_options[{{$type}}][commission]"
                                                    class="form-control"
                                                    value="{{$meta->commission ?? ''}}"
                                                    placeholder="{{__('Use base')}}" min="0" max="100" step="1">
                                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">{{__('Max Listings: leave blank for unlimited. Commission: leave blank to use the base commission rate above.')}}</small>
                    </div>
                </div>
            </div>

            {{-- Right column: status + save --}}
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-title"><strong>{{__('Publish')}}</strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>{{__('Status')}}</label>
                            <select name="status" class="form-control">
                                <option value="publish" {{old('status', $row->status) == 'publish' ? 'selected' : ''}}>{{__('Published')}}</option>
                                <option value="draft"   {{old('status', $row->status) == 'draft'   ? 'selected' : ''}}>{{__('Draft')}}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa fa-save"></i> {{!empty($row->id) ? __('Update Plan') : __('Create Plan')}}
                        </button>
                        @if(!empty($row->id))
                        <a href="{{route('vendor.admin.plan.index')}}" class="btn btn-secondary btn-block mt-2">
                            {{__('Cancel')}}
                        </a>
                        @endif
                    </div>
                </div>

                @if(!empty($row->id))
                <div class="panel">
                    <div class="panel-title"><strong>{{__('Vendors on this Plan')}}</strong></div>
                    <div class="panel-body">
                        @php $vendorCount = \App\User::where('vendor_plan_id', $row->id)->count(); @endphp
                        <p>
                            <strong>{{$vendorCount}}</strong> {{__('active vendor(s)')}}
                        </p>
                        <a href="{{route('vendor.admin.subscription.index')}}?plan_id={{$row->id}}" class="btn btn-sm btn-outline-info">
                            {{__('View Subscriptions')}}
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection
