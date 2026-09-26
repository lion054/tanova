@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__("Assign / Renew Vendor Plan")}}</h1>
    </div>
    @include('admin.message')

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="panel">
                <div class="panel-title"><strong>{{__('Subscription Details')}}</strong></div>
                <div class="panel-body">
                    <form action="{{route('vendor.admin.subscription.doAssign')}}" method="post">
                        @csrf

                        <div class="form-group">
                            <label>{{__('Vendor')}} <span class="text-danger">*</span></label>
                            <?php
                            \App\Helpers\AdminForm::select2('vendor_id', [
                                'configs' => [
                                    'ajax'        => ['url' => route('user.admin.getForSelect2'), 'dataType' => 'json'],
                                    'allowClear'  => false,
                                    'placeholder' => __('Search vendor by name or email...'),
                                ]
                            ], !empty($vendor) ? [$vendor->id, $vendor->getDisplayName() . ' (#' . $vendor->id . ')'] : false);
                            ?>
                            @error('vendor_id')<span class="text-danger">{{$message}}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('Plan')}} <span class="text-danger">*</span></label>
                            <select name="plan_id" class="form-control" id="planSelect" required>
                                <option value="">{{__('-- Select Plan --')}}</option>
                                @foreach($plans as $plan)
                                    <option value="{{$plan->id}}"
                                        data-price-monthly="{{$plan->price}}"
                                        data-price-annual="{{$plan->price_annual}}"
                                        data-commission="{{$plan->base_commission}}"
                                        {{old('plan_id') == $plan->id ? 'selected' : ''}}>
                                        {{$plan->name}} — {{format_money($plan->price)}}/mo
                                        @if($plan->price_annual) ({{format_money($plan->price_annual)}}/yr) @endif
                                        — {{$plan->base_commission}}% commission
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')<span class="text-danger">{{$message}}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('Billing Cycle')}} <span class="text-danger">*</span></label>
                            <div>
                                <label class="mr-4">
                                    <input type="radio" name="billing_cycle" value="monthly"
                                        {{old('billing_cycle','monthly') === 'monthly' ? 'checked' : ''}}
                                        onchange="updatePrice()">
                                    {{__('Monthly')}}
                                </label>
                                <label>
                                    <input type="radio" name="billing_cycle" value="yearly"
                                        {{old('billing_cycle') === 'yearly' ? 'checked' : ''}}
                                        onchange="updatePrice()">
                                    {{__('Yearly')}}
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{__('Tanova OS')}} <small class="text-muted">({{__('for plans that cover a set number of OS; the company keeps any extras it has')}})</small></label>
                            <div>
                                @foreach(\Modules\Vendor\Services\CompanyOs::all() as $osKey => $osDef)
                                    <label class="mr-3"><input type="checkbox" name="os[]" value="{{$osKey}}" {{in_array($osKey, (array) old('os', $vendor ? \Modules\Vendor\Services\CompanyOs::chosen($vendor) : [])) ? 'checked' : ''}}> {{$osDef['name']}}</label>
                                @endforeach
                            </div>
                            @error('os')<span class="text-danger">{{$message}}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('Start Date')}} <span class="text-danger">*</span></label>
                            <input type="date" name="starts_at" class="form-control"
                                value="{{old('starts_at', now()->toDateString())}}" required>
                            @error('starts_at')<span class="text-danger">{{$message}}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('Amount Charged')}} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{setting_item('currency_symbol','$')}}</span>
                                </div>
                                <input type="number" name="amount_paid" id="amountInput" class="form-control"
                                    value="{{old('amount_paid', 0)}}" min="0" step="0.01" required>
                            </div>
                            <small class="text-muted" id="priceHint"></small>
                            @error('amount_paid')<span class="text-danger">{{$message}}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{__('Internal Notes')}} <small class="text-muted">({{__('not shown to vendor')}})</small></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{__('e.g. Payment received via bank transfer ref #12345')}}">{{old('notes')}}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{route('vendor.admin.subscription.index')}}" class="btn btn-secondary">{{__('Cancel')}}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check"></i> {{__('Assign Plan & Activate')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
    var planData = @json($plans->keyBy('id')->map(fn($p) => ['monthly' => $p->price, 'yearly' => $p->price_annual]));

    function updatePrice() {
        var planId = document.getElementById('planSelect').value;
        var cycle  = document.querySelector('input[name=billing_cycle]:checked')?.value ?? 'monthly';
        var hint   = document.getElementById('priceHint');
        var input  = document.getElementById('amountInput');

        if (!planId || !planData[planId]) { hint.textContent = ''; return; }

        var price = cycle === 'yearly' ? planData[planId].yearly : planData[planId].monthly;
        if (!price && cycle === 'yearly') {
            hint.textContent = '{{ __("No annual price set for this plan — using monthly.") }}';
            price = planData[planId].monthly;
        } else {
            hint.textContent = '';
        }
        if (price !== null && price !== undefined) {
            input.value = parseFloat(price).toFixed(2);
        }
    }

    document.getElementById('planSelect').addEventListener('change', updatePrice);
    document.querySelectorAll('input[name=billing_cycle]').forEach(el => el.addEventListener('change', updatePrice));
</script>
@endpush
