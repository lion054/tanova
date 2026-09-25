@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{ __('TourPay: all businesses') }}</h1>
    </div>
    @include('admin.message')
    <p class="text-muted">{{ __('Read-only staff view. Each business creates and changes its own invoices in its own portal.') }}</p>

    <div class="row mb-3">
        @forelse($totals as $t)
        <div class="col-md-3"><div class="panel"><div class="panel-body">
            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;">{{ $t->currency }} · {{ $t->n }} {{ __('invoices') }}</div>
            <div style="font-size:20px;font-weight:800;">{{ number_format((float) $t->billed, 2) }}</div>
            <div class="text-muted" style="font-size:12px;">{{ __('paid') }} {{ number_format((float) $t->paid, 2) }}</div>
        </div></div></div>
        @empty
        <div class="col-12"><div class="panel"><div class="panel-body text-muted">{{ __('No invoices yet.') }}</div></div></div>
        @endforelse
    </div>

    <form method="GET" class="form-inline mb-3" style="gap:8px;">
        <input type="text" name="s" value="{{ request('s') }}" class="form-control" placeholder="{{ __('Number, client or e-mail') }}">
        <select name="status" class="form-control"><option value="">{{ __('Any status') }}</option>@foreach($statuses as $st)<option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>@endforeach</select>
        <select name="type" class="form-control"><option value="">{{ __('Any type') }}</option>@foreach(['invoice', 'quotation', 'credit_note'] as $ty)<option value="{{ $ty }}" {{ request('type') === $ty ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $ty)) }}</option>@endforeach</select>
        <button class="btn btn-primary">{{ __('Filter') }}</button>
    </form>

    <div class="panel"><div class="panel-body table-responsive">
        <table class="table table-hover">
            <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Business') }}</th><th>{{ __('Client') }}</th><th>{{ __('Type') }}</th><th>{{ __('Status') }}</th><th class="text-right">{{ __('Total') }}</th><th class="text-right">{{ __('Paid') }}</th><th>{{ __('Due') }}</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('tourpay.admin.view', $r->id) }}">{{ $r->invoice_number }}</a></td>
                    <td>{{ $r->vendorOwner->business_name ?: $r->vendorOwner->name }}</td>
                    <td>{{ $r->client_name }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->type)) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                    <td class="text-right">{{ $r->currency }} {{ number_format((float) $r->total, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $r->amount_paid, 2) }}</td>
                    <td>{{ optional($r->due_date)->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('Nothing matches.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
    <p class="text-muted" style="font-size:12px;">{{ $businesses }} {{ __('businesses have TourPay documents;') }} {{ number_format($ledgerRows) }} {{ __('money ledger entries.') }}</p>
</div>
@endsection
