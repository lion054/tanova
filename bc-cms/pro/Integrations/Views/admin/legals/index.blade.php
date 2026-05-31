@extends('layouts.user')
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb20">
        <div>
            <h1 class="title-bar">Legals</h1>
            <p class="text-muted">Manage your platform's legal documents. Changes take effect immediately on the live site.</p>
        </div>
        <a href="{{ route('admin.integrations.hub') }}" class="btn btn-outline-secondary btn-sm">← Integrations</a>
    </div>

    @include('admin.message')

    <div class="row g-4">
        @foreach($docs as $key => $meta)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="d-flex align-items-center justify-content-center rounded"
                             style="width:42px;height:42px;background:#f5f5f5;font-size:1.2rem;flex-shrink:0">
                            <i class="{{ $meta['icon'] }}"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $meta['title'] }}</h5>
                            <p class="text-muted small mb-0">{{ $meta['desc'] }}</p>
                        </div>
                    </div>

                    @php
                        $hasContent = !empty(setting_item("legal_{$key}_content"));
                        $updatedAt  = setting_item("legal_{$key}_updated_at");
                    @endphp

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div>
                            @if($hasContent)
                                <span class="badge bg-success">Published</span>
                                @if($updatedAt)
                                <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($updatedAt)->diffForHumans() }}</small>
                                @endif
                            @else
                                <span class="badge bg-secondary">Empty</span>
                            @endif
                        </div>
                        <a href="{{ route('admin.integrations.legals.edit', $key) }}" class="btn btn-sm btn-primary">
                            {{ $hasContent ? 'Edit' : 'Create' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
