@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{ $key->name }} — <small class="text-muted">{{ $key->vendor?->email }}</small></h1>
        @if($key->active)
        <form method="POST" action="{{ route('vendor.admin.api-keys.revoke', $key->id) }}"
              onsubmit="return confirm('Revoke this key?')">
            @csrf
            <button class="btn btn-danger">Revoke Key</button>
        </form>
        @endif
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0">{{ number_format($monthly_count) }}</div>
                <small class="text-muted">Requests this month</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0">{{ number_format($key->rate_limit) }}</div>
                <small class="text-muted">Monthly limit</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0">{{ $key->active ? 'Active' : 'Revoked' }}</div>
                <small class="text-muted">Status</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0">{{ $key->last_used_at?->diffForHumans() ?? '—' }}</div>
                <small class="text-muted">Last used</small>
            </div>
        </div>
    </div>

    {{-- 30-day usage chart (simple table — swap for Chart.js if desired) --}}
    <div class="panel mb-4">
        <div class="panel-heading"><h3 class="panel-title">Daily usage — last 30 days</h3></div>
        <div class="panel-body">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Requests</th><th>Errors</th><th>Avg ms</th></tr></thead>
                <tbody>
                @forelse($usage_by_day as $day)
                <tr>
                    <td>{{ $day->date }}</td>
                    <td>{{ $day->total }}</td>
                    <td class="{{ $day->errors > 0 ? 'text-danger' : '' }}">{{ $day->errors }}</td>
                    <td>{{ round($day->avg_ms) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No usage in the last 30 days.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top endpoints --}}
    <div class="panel">
        <div class="panel-heading"><h3 class="panel-title">Top endpoints (this month)</h3></div>
        <div class="panel-body">
            <table class="table table-sm">
                <thead><tr><th>Method</th><th>Endpoint</th><th>Calls</th><th>Avg ms</th></tr></thead>
                <tbody>
                @forelse($top_endpoints as $ep)
                <tr>
                    <td><span class="badge badge-secondary">{{ $ep->method }}</span></td>
                    <td><code>{{ $ep->endpoint }}</code></td>
                    <td>{{ $ep->total }}</td>
                    <td>{{ round($ep->avg_ms) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
