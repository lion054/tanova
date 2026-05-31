@extends('layouts.user')
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb20">
        <div>
            <h1 class="title-bar">{{ $meta['title'] }}</h1>
            @if($updatedAt)
            <small class="text-muted">Last saved {{ \Carbon\Carbon::parse($updatedAt)->format('d M Y H:i') }}</small>
            @endif
        </div>
        <a href="{{ route('admin.integrations.legals') }}" class="btn btn-outline-secondary btn-sm">← All Legals</a>
    </div>

    @include('admin.message')

    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.integrations.legals.save', $doc) }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-semibold">{{ $meta['title'] }} Content</label>
                    <p class="text-muted small mb-2">{{ $meta['desc'] }}. Supports basic HTML formatting.</p>

                    {{-- Try to use the existing TinyMCE/CKEditor if loaded, fallback to textarea --}}
                    @php
                        $editorLoaded = false;
                    @endphp

                    @if(function_exists('has_action') && has_action('EDITOR_JS_STACK'))
                        @php $editorLoaded = true; @endphp
                        <textarea name="content" id="legal-content-editor" class="form-control editor-content"
                                  rows="30">{{ old('content', $content) }}</textarea>
                    @else
                        <textarea name="content" class="form-control" rows="30"
                                  style="font-family: monospace; font-size: 13px;"
                                  placeholder="Paste your {{ $meta['title'] }} content here (HTML supported)...">{{ old('content', $content) }}</textarea>
                    @endif
                </div>

                {{-- Jump to other legal docs --}}
                <div class="mb-4">
                    <p class="small text-muted mb-2">Other legal documents:</p>
                    @php
                        $allLegals = [
                            'tos'     => 'Terms of Service',
                            'privacy' => 'Privacy Policy',
                            'dpa'     => 'Data Processing Agreement',
                            'cookie'  => 'Cookie Policy',
                            'refund'  => 'Cancellation & Refund Policy',
                            'vendor'  => 'Vendor / Host Agreement',
                        ];
                    @endphp
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($allLegals as $k => $label)
                        @if($k !== $doc)
                        <a href="{{ route('admin.integrations.legals.edit', $k) }}"
                           class="btn btn-xs btn-outline-secondary">{{ $label }}</a>
                        @endif
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save {{ $meta['title'] }}</button>
                    <a href="{{ route('admin.integrations.legals') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Live preview --}}
    @if($content)
    <div class="panel mt-4">
        <div class="panel-title">Preview</div>
        <div class="panel-body" style="max-height:400px;overflow-y:auto;font-size:14px;line-height:1.7">
            {!! $content !!}
        </div>
    </div>
    @endif

</div>
@endsection
