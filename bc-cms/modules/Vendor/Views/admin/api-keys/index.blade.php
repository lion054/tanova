@extends('admin.layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__("Vendor API Keys")}}</h1>
    </div>

    {{-- Platform-wide stats --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0">{{ number_format($total_requests) }}</div>
                <small class="text-muted">Requests this month</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0">{{ $error_rate }}%</div>
                <small class="text-muted">Error rate (4xx/5xx)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0">{{ $rows->total() }}</div>
                <small class="text-muted">Total API keys</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0">{{ $rows->where('active', true)->count() }}</div>
                <small class="text-muted">Active keys</small>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="d-flex gap-2 mb-3">
        <input type="number" name="vendor_id" class="form-control w-auto" placeholder="Vendor ID" value="{{ request('vendor_id') }}">
        <select name="active" class="form-control w-auto">
            <option value="">All status</option>
            <option value="1" @selected(request('active')==='1')>Active</option>
            <option value="0" @selected(request('active')==='0')>Revoked</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel">
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vendor</th>
                        <th>Key name</th>
                        <th>Status</th>
                        <th>Rate limit</th>
                        <th>Requests (this month)</th>
                        <th>Last used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $key)
                    @php $pct = $key->rate_limit > 0 ? round($key->monthly_requests / $key->rate_limit * 100) : 0; @endphp
                    <tr>
                        <td>{{ $key->id }}</td>
                        <td>
                            <a href="{{ route('user.admin.edit', $key->vendor_id) }}">
                                {{ $key->vendor?->name ?? $key->vendor?->email ?? $key->vendor_id }}
                            </a>
                        </td>
                        <td><a href="{{ route('vendor.admin.api-keys.show', $key->id) }}">{{ $key->name }}</a></td>
                        <td>
                            @if($key->active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Revoked</span>
                            @endif
                        </td>
                        <td>{{ number_format($key->rate_limit) }}/mo</td>
                        <td>
                            <div class="progress" style="height:6px; width:100px; display:inline-block; vertical-align:middle;">
                                <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     style="width:{{ min($pct,100) }}%"></div>
                            </div>
                            <small>{{ number_format($key->monthly_requests) }} ({{ $pct }}%)</small>
                        </td>
                        <td>{{ $key->last_used_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <a href="{{ route('vendor.admin.api-keys.show', $key->id) }}" class="btn btn-xs btn-info">View</a>
                            @if($key->active)
                            <form method="POST" action="{{ route('vendor.admin.api-keys.revoke', $key->id) }}" style="display:inline"
                                  onsubmit="return confirm('Revoke this key?')">
                                @csrf
                                <button class="btn btn-xs btn-danger">Revoke</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No API keys found.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $rows->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
