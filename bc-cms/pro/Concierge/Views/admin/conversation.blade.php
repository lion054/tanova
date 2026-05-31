@extends('layouts.user')

@push('css')
<style>
.tp-ab-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn--primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn--primary:hover { background:#333; }
.tp-ab-btn--outline { background:#fff; border:1.5px solid #e0e0e0; color:#0a0a0a !important; }
.tp-ab-btn--outline:hover { border-color:#0a0a0a; }
.tp-ab-btn--ghost  { background:transparent; color:#555 !important; border:1.5px solid #e4e4e4; }
.tp-ab-btn--ghost:hover  { border-color:#0a0a0a; color:#0a0a0a !important; }
.tp-ab-btn--danger { background:#fff1f2; color:#e11d48 !important; border:1.5px solid #fecdd3; }
.tp-ab-btn--danger:hover { background:#ffe4e6; }
.tp-ab-btn--success { background:#f0fdf4; color:#16a34a !important; border:1.5px solid #d1fae5; }
.tp-ab-btn--success:hover { background:#dcfce7; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-card-header { padding:14px 20px; border-bottom:1px solid #f0f0f0; font-size:13px; font-weight:700; color:#0a0a0a; display:flex; align-items:center; gap:8px; }
.tp-card-body { padding:20px; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; font-size:11px; font-weight:600; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.tp-badge--green  { background:#f0fdf4; color:#16a34a; } .tp-badge--green::before  { background:#16a34a; }
.tp-badge--red    { background:#fff1f2; color:#e11d48; } .tp-badge--red::before    { background:#e11d48; }
.tp-badge--gray   { background:#f4f4f5; color:#71717a; } .tp-badge--gray::before   { background:#71717a; }
.tp-badge--blue   { background:#eff6ff; color:#2563eb; } .tp-badge--blue::before   { background:#2563eb; }
.tp-badge--purple { background:#f5f3ff; color:#7c3aed; } .tp-badge--purple::before { background:#7c3aed; }
.tp-badge--amber  { background:#fffbeb; color:#d97706; } .tp-badge--amber::before  { background:#d97706; }

/* Chat */
.cc-thread { max-height:480px; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:14px; scroll-behavior:smooth; }
.cc-msg { display:flex; gap:10px; max-width:80%; }
.cc-msg--guest { align-self:flex-start; }
.cc-msg--agent { align-self:flex-end; flex-direction:row-reverse; }
.cc-msg--ai    { align-self:flex-end; flex-direction:row-reverse; }
.cc-msg--system { align-self:center; max-width:100%; }

.cc-avatar { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; flex-shrink:0; margin-top:2px; }
.cc-avatar--guest  { background:#f4f4f5; color:#71717a; }
.cc-avatar--agent  { background:#0a0a0a; color:#fff; }
.cc-avatar--ai     { background:#eff6ff; color:#2563eb; }
.cc-avatar--system { background:#fafafa; color:#aaa; }

.cc-bubble { padding:10px 14px; border-radius:12px; font-size:13px; line-height:1.5; }
.cc-bubble--guest  { background:#f4f4f5; color:#222; border-bottom-left-radius:3px; }
.cc-bubble--agent  { background:#0a0a0a; color:#fff; border-bottom-right-radius:3px; }
.cc-bubble--ai     { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; border-bottom-right-radius:3px; }
.cc-bubble--system { background:#fafafa; color:#aaa; font-size:12px; border:1px solid #f0f0f0; border-radius:8px; padding:6px 12px; }

.cc-meta { font-size:10px; color:#aaa; margin-top:4px; }
.cc-msg--guest .cc-meta { text-align:left; }
.cc-msg--agent .cc-meta, .cc-msg--ai .cc-meta { text-align:right; }

.cc-draft-tag { display:inline-block; font-size:9px; font-weight:700; background:#fde68a; color:#92400e; padding:1px 6px; border-radius:4px; margin-left:6px; text-transform:uppercase; }

/* Reply box */
.cc-reply { border-top:1px solid #f0f0f0; }
.cc-reply textarea { width:100%; border:none; outline:none; resize:none; font-size:13px; color:#222; padding:16px 20px; background:transparent; min-height:80px; }
.cc-reply-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; border-top:1px solid #f0f0f0; background:#fafafa; }

/* AI draft */
.cc-ai-draft { border:1.5px solid #bfdbfe; border-radius:10px; overflow:hidden; margin-top:14px; }
.cc-ai-draft-header { padding:10px 16px; background:#eff6ff; font-size:12px; font-weight:700; color:#2563eb; display:flex; align-items:center; gap:6px; border-bottom:1px solid #bfdbfe; }
.cc-ai-draft-body { padding:14px 16px; }
.cc-ai-draft-body textarea { width:100%; border:1.5px solid #bfdbfe; border-radius:7px; padding:10px 12px; font-size:13px; color:#222; outline:none; resize:none; }
.cc-ai-draft-body textarea:focus { border-color:#2563eb; }

/* Info table */
.cc-info { width:100%; border-collapse:collapse; font-size:13px; }
.cc-info td { padding:9px 0; border-bottom:1px solid #f7f7f7; vertical-align:top; }
.cc-info td:first-child { color:#aaa; width:42%; font-size:12px; }
.cc-info td:last-child  { font-weight:500; color:#0a0a0a; }
.cc-info tr:last-child td { border-bottom:none; }

/* tp-field */
.tp-field label { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#777; margin-bottom:4px; }
.tp-field textarea { width:100%; border:1.5px solid #e8e8e8; border-radius:7px; padding:8px 12px; font-size:13px; color:#222; background:#fff; outline:none; transition:border-color .12s; resize:vertical; }
.tp-field textarea:focus { border-color:#0a0a0a; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0 0 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <nav class="mb-3"><ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.concierge.index') }}">Concierge</a></li>
        <li class="breadcrumb-item active">{{ $conversation->guestDisplayName() }}</li>
    </ol></nav>

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Conversation</p>
            <h1 class="portal-h1">{{ $conversation->guestDisplayName() }}</h1>
            <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap">
                @if($conversation->status === 'open')
                    <span class="tp-badge tp-badge--green">Open</span>
                @elseif($conversation->status === 'escalated')
                    <span class="tp-badge tp-badge--red">Escalated</span>
                @else
                    <span class="tp-badge tp-badge--gray">Resolved</span>
                @endif
                @php
                    $chBadge = match($conversation->channel) {
                        'whatsapp' => 'tp-badge--green',
                        'email'    => 'tp-badge--blue',
                        default    => 'tp-badge--gray',
                    };
                @endphp
                <span class="tp-badge {{ $chBadge }}">{{ ucfirst($conversation->channel) }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.concierge.index') }}" class="tp-ab-btn tp-ab-btn--ghost">
                <i class="ion ion-ios-arrow-back"></i> Back
            </a>
            @if($conversation->status === 'open')
            <form method="POST" action="{{ route('admin.concierge.escalate', $conversation) }}" style="display:inline">
                @csrf
                <button class="tp-ab-btn tp-ab-btn--danger">
                    <i class="ion ion-ios-warning"></i> Escalate
                </button>
            </form>
            <form method="POST" action="{{ route('admin.concierge.resolve', $conversation) }}" style="display:inline">
                @csrf
                <button class="tp-ab-btn tp-ab-btn--success">
                    <i class="ion ion-ios-checkmark-circle"></i> Resolve
                </button>
            </form>
            @endif
        </div>
    </div>

    @include('admin.message')

    <div class="row g-4">

        {{-- Chat column --}}
        <div class="col-md-8">
            <div class="tp-card">
                <div class="tp-card-header">
                    <i class="ion ion-ios-chatbubbles"></i> Messages
                    <span style="font-size:11px;color:#aaa;font-weight:400;margin-left:auto">
                        {{ $conversation->messages->count() }} message{{ $conversation->messages->count() !== 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="cc-thread" id="cc-thread">
                    @forelse($conversation->messages as $msg)
                    @php
                        $type = $msg->sender_type; // guest | agent | ai | system
                    @endphp
                    <div class="cc-msg cc-msg--{{ $type }}">
                        <div class="cc-avatar cc-avatar--{{ $type }}">
                            @if($type === 'guest')  G
                            @elseif($type === 'ai') AI
                            @else                   A
                            @endif
                        </div>
                        <div>
                            <div class="cc-bubble cc-bubble--{{ $type }}">
                                {{ $msg->body }}
                                @if($msg->isAiDraft() && !$msg->approved)
                                <span class="cc-draft-tag">Draft</span>
                                @endif
                            </div>
                            <div class="cc-meta">
                                {{ $msg->created_at?->format('d M H:i') }}
                                @if($type === 'ai') · AI draft @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div style="text-align:center;color:#aaa;font-size:13px;padding:32px 0">
                        No messages yet.
                    </div>
                    @endforelse
                </div>

                {{-- Reply box --}}
                @if($conversation->status === 'open')
                <div class="cc-reply">
                    <form method="POST" action="{{ route('admin.concierge.reply', $conversation) }}">
                        @csrf
                        <textarea name="body" placeholder="Type your reply to {{ $conversation->guestDisplayName() }}…"
                                  required></textarea>
                        <div class="cc-reply-bar">
                            <span style="font-size:11px;color:#aaa">Replying as Agent</span>
                            <button type="submit" class="tp-ab-btn tp-ab-btn--primary">
                                <i class="ion ion-ios-send"></i> Send Reply
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div style="padding:14px 20px;background:#fafafa;border-top:1px solid #f0f0f0;font-size:12px;color:#aaa;text-align:center">
                    Conversation {{ $conversation->status }}. Reopen to reply.
                </div>
                @endif
            </div>

            {{-- AI Draft --}}
            @if(isset($aiDraft) && $aiDraft && $conversation->status === 'open')
            <div class="cc-ai-draft">
                <div class="cc-ai-draft-header">
                    <i class="ion ion-ios-flash"></i> AI Suggested Reply
                </div>
                <div class="cc-ai-draft-body">
                    <form method="POST" action="{{ route('admin.concierge.approve-ai', $conversation) }}">
                        @csrf
                        <textarea name="body" rows="4">{{ $aiDraft }}</textarea>
                        <div style="display:flex;gap:8px;margin-top:10px">
                            <button type="submit" class="tp-ab-btn tp-ab-btn--primary">
                                <i class="ion ion-ios-checkmark"></i> Approve & Send
                            </button>
                            <span style="font-size:11px;color:#aaa;align-self:center">Edit before sending if needed</span>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            <div class="tp-card" style="margin-bottom:14px">
                <div class="tp-card-header"><i class="ion ion-ios-person"></i> Guest Info</div>
                <div class="tp-card-body" style="padding:16px 20px">
                    <table class="cc-info">
                        <tr><td>Name</td><td>{{ $conversation->guestDisplayName() }}</td></tr>
                        <tr><td>Email</td><td>{{ $conversation->guest_email ?: '—' }}</td></tr>
                        <tr><td>Channel</td><td>{{ ucfirst($conversation->channel) }}</td></tr>
                        <tr><td>Status</td><td>
                            @if($conversation->status === 'open')
                                <span class="tp-badge tp-badge--green">Open</span>
                            @elseif($conversation->status === 'escalated')
                                <span class="tp-badge tp-badge--red">Escalated</span>
                            @else
                                <span class="tp-badge tp-badge--gray">Resolved</span>
                            @endif
                        </td></tr>
                        <tr><td>Started</td><td>{{ $conversation->created_at?->format('d M Y H:i') }}</td></tr>
                        @if($conversation->booking_id)
                        <tr><td>Booking</td><td>
                            <a href="{{ route('admin.booking.detail', $conversation->booking_id) }}"
                               style="color:#2563eb;font-weight:600">#{{ $conversation->booking_id }}</a>
                        </td></tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($conversation->status === 'open')
            <div class="tp-card" style="border-color:#fde68a;background:#fffbeb">
                <div class="tp-card-body" style="padding:14px 18px">
                    <div style="font-size:12px;font-weight:700;color:#d97706;margin-bottom:10px">
                        <i class="ion ion-ios-warning"></i> Quick Actions
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <form method="POST" action="{{ route('admin.concierge.escalate', $conversation) }}">
                            @csrf
                            <button class="tp-ab-btn tp-ab-btn--danger" style="width:100%;justify-content:center">
                                <i class="ion ion-ios-warning"></i> Mark as Escalated
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.concierge.resolve', $conversation) }}">
                            @csrf
                            <button class="tp-ab-btn tp-ab-btn--success" style="width:100%;justify-content:center">
                                <i class="ion ion-ios-checkmark-circle"></i> Mark as Resolved
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var thread = document.getElementById('cc-thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
});
</script>
@endpush
