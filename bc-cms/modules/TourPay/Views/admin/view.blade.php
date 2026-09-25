@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{ $row->invoice_number }} <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $row->type)) }} · {{ ucfirst(str_replace('_', ' ', $row->status)) }}</small></h1>
        <a href="{{ route('tourpay.admin.index') }}" class="btn btn-default">&larr; {{ __('All documents') }}</a>
    </div>
    @include('admin.message')
    <div class="row">
        <div class="col-md-8">
            <div class="panel"><div class="panel-title"><strong>{{ __('Items') }}</strong></div><div class="panel-body table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('Item') }}</th><th class="text-right">{{ __('Qty') }}</th><th class="text-right">{{ __('Unit price') }}</th><th class="text-right">{{ __('Total') }}</th></tr></thead>
                    <tbody>@foreach($row->items as $it)<tr><td>{{ $it->name }}@if($it->description)<div class="text-muted" style="font-size:12px;">{{ $it->description }}</div>@endif</td><td class="text-right">{{ $it->quantity + 0 }}</td><td class="text-right">{{ number_format((float) $it->unit_price, 2) }}</td><td class="text-right">{{ number_format((float) $it->total, 2) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><th colspan="3" class="text-right">{{ __('Total') }}</th><th class="text-right">{{ $row->currency }} {{ number_format((float) $row->total, 2) }}</th></tr></tfoot>
                </table>
            </div></div>
            <div class="panel"><div class="panel-title"><strong>{{ __('Payments') }}</strong></div><div class="panel-body table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Status') }}</th><th class="text-right">{{ __('Amount') }}</th></tr></thead>
                    <tbody>@forelse($payments as $p)<tr><td>{{ optional($p->paid_at)->format('d M Y') }}</td><td>{{ ucfirst((string) $p->method) }}</td><td>{{ $p->reference ?: $p->gateway_ref }}</td><td>{{ ucfirst((string) $p->status) }}</td><td class="text-right">{{ number_format((float) $p->amount, 2) }}</td></tr>@empty<tr><td colspan="5" class="text-muted text-center">{{ __('No payments.') }}</td></tr>@endforelse</tbody>
                </table>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="panel"><div class="panel-title"><strong>{{ __('Details') }}</strong></div><div class="panel-body">
                <p><span class="text-muted">{{ __('Business') }}</span><br>{{ $row->vendorOwner->business_name ?: $row->vendorOwner->name }}<br><span class="text-muted">{{ $row->vendorOwner->email }}</span></p>
                <p><span class="text-muted">{{ __('Client') }}</span><br>{{ $row->client_name }}<br><span class="text-muted">{{ $row->client_email }}</span></p>
                <p><span class="text-muted">{{ __('Issued') }}</span> {{ optional($row->issue_date)->format('d M Y') }}<br><span class="text-muted">{{ __('Due') }}</span> {{ optional($row->due_date)->format('d M Y') ?: '—' }}</p>
                <p><span class="text-muted">{{ __('Paid') }}</span> {{ $row->currency }} {{ number_format((float) $row->amount_paid, 2) }}<br><span class="text-muted">{{ __('Balance') }}</span> {{ $row->currency }} {{ number_format($row->balance(), 2) }}</p>
            </div></div>
        </div>
    </div>
</div>
@endsection
