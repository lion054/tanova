@extends('layouts.user')

@push('css')
<style>
.tp-ab-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn--primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn--primary:hover { background:#333; }
.tp-ab-btn--ghost  { background:transparent; color:#555 !important; border:1.5px solid #e4e4e4; }
.tp-ab-btn--ghost:hover  { border-color:#0a0a0a; color:#0a0a0a !important; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-card-header { padding:16px 24px; border-bottom:1px solid #f0f0f0; font-size:13px; font-weight:700; color:#0a0a0a; display:flex; align-items:center; gap:8px; }
.tp-card-body { padding:24px; }

.tp-field { margin-bottom:16px; }
.tp-field label { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#777; margin-bottom:5px; }
.tp-field input, .tp-field select, .tp-field textarea { width:100%; border:1.5px solid #e8e8e8; border-radius:7px; padding:9px 12px; font-size:13px; color:#222; background:#fff; outline:none; transition:border-color .12s; }
.tp-field input:focus, .tp-field select:focus, .tp-field textarea:focus { border-color:#0a0a0a; }
.tp-field .hint { font-size:11px; color:#aaa; margin-top:4px; }

.tp-divider { height:1px; background:#f0f0f0; margin:20px 0; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0 0 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="max-width:640px">

    <nav class="mb-3"><ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.concierge.index') }}">Concierge</a></li>
        <li class="breadcrumb-item active">New Conversation</li>
    </ol></nav>

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">AI Concierge</p>
            <h1 class="portal-h1">New Conversation</h1>
        </div>
        <a href="{{ route('admin.concierge.index') }}" class="tp-ab-btn tp-ab-btn--ghost">
            <i class="ion ion-ios-arrow-back"></i> Back
        </a>
    </div>

    @include('admin.message')

    <div class="tp-card">
        <div class="tp-card-header">
            <i class="ion ion-ios-chatbubble" style="color:#25D366"></i>
            Guest & Channel Details
        </div>
        <div class="tp-card-body">
            <form method="POST" action="{{ route('admin.concierge.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="tp-field">
                            <label>Guest Name <span style="color:#e11d48">*</span></label>
                            <input type="text" name="guest_name" value="{{ old('guest_name') }}"
                                   placeholder="e.g. Sarah Johnson" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tp-field">
                            <label>Guest Email</label>
                            <input type="email" name="guest_email" value="{{ old('guest_email') }}"
                                   placeholder="guest@example.com">
                        </div>
                    </div>
                </div>

                <div class="tp-field">
                    <label>Channel <span style="color:#e11d48">*</span></label>
                    <select name="channel">
                        <option value="web"      @selected(old('channel')==='web')>🌐 Web (Portal)</option>
                        <option value="whatsapp" @selected(old('channel')==='whatsapp')>💬 WhatsApp</option>
                        <option value="email"    @selected(old('channel')==='email')>✉️ Email</option>
                        <option value="phone"    @selected(old('channel')==='phone')>📞 Phone</option>
                    </select>
                    <div class="hint">Where did the guest reach out from?</div>
                </div>

                <div class="tp-divider"></div>

                <div class="tp-field">
                    <label>First Message <span style="color:#e11d48">*</span></label>
                    <textarea name="message" rows="5" required
                              placeholder="What did the guest say? Paste their message here…">{{ old('message') }}</textarea>
                    <div class="hint">This becomes the first message in the conversation thread.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="tp-ab-btn tp-ab-btn--primary">
                        <i class="ion ion-ios-add-circle"></i> Create Conversation
                    </button>
                    <a href="{{ route('admin.concierge.index') }}" class="tp-ab-btn tp-ab-btn--ghost">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
