@extends('vendor.layouts.app')

@section('title', 'Conversation Details')

@section('content')
<div class="conversation-detail">
    <div class="header">
        <a href="{{ route('user.concierge.index') }}" class="btn btn-light">← Back</a>
        <h1>Conversation with {{ $conversation['guest_name'] ?? 'Guest' }}</h1>
        <span class="badge badge-{{ $conversation['status'] }}">{{ ucfirst($conversation['status']) }}</span>
    </div>

    <div class="conversation-container">
        <!-- Conversation Info Sidebar -->
        <div class="sidebar">
            <div class="info-card">
                <h4>Guest Information</h4>
                <div class="info-item">
                    <label>Name</label>
                    <p>{{ $conversation['guest_name'] ?? '-' }}</p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p>{{ $conversation['guest_email'] ?? '-' }}</p>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <p>{{ $conversation['guest_phone'] ?? '-' }}</p>
                </div>
                <div class="info-item">
                    <label>Channel</label>
                    <p><span class="badge badge-channel badge-{{ $conversation['channel'] }}">
                        {{ ucfirst($conversation['channel']) }}
                    </span></p>
                </div>
                <div class="info-item">
                    <label>Started</label>
                    <p>{{ \Carbon\Carbon::parse($conversation['created_at'])->format('M d, Y H:i') }}</p>
                </div>
                <div class="info-item">
                    <label>Last Message</label>
                    <p>{{ \Carbon\Carbon::parse($conversation['updated_at'])->diffForHumans() }}</p>
                </div>
            </div>

            <div class="info-card">
                <h4>Conversation Data</h4>
                <div class="info-item">
                    <label>Step</label>
                    <p>{{ ucfirst($conversation['current_step'] ?? '-') }}</p>
                </div>
                <div class="info-item">
                    <label>Messages</label>
                    <p>{{ $conversation['message_count'] ?? 0 }}</p>
                </div>
                @if(isset($conversation['search_data']) && !empty($conversation['search_data']))
                    <div class="info-item">
                        <label>Search Data</label>
                        <pre>{{ json_encode($conversation['search_data'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>

            @if($conversation['status'] !== 'closed')
                <form method="POST" action="{{ route('user.concierge.close', $conversation['id']) }}" class="mt-3">
                    @csrf
                    <div class="form-group">
                        <label>Close Conversation</label>
                        <textarea name="reason" class="form-control form-control-sm" placeholder="Reason..." rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100">Close</button>
                </form>
            @endif
        </div>

        <!-- Messages -->
        <div class="messages-section">
            <div class="messages-list">
                @forelse($messages as $message)
                    <div class="message {{ $message['role'] === 'user' ? 'message-user' : 'message-ai' }}">
                        <div class="message-bubble">
                            <p>{{ $message['content'] }}</p>
                            <small class="message-time">
                                {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="empty-messages">
                        <p>No messages in this conversation</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if(isset($pagination) && $pagination['last_page'] > 1)
                <div class="pagination-container">
                    @if($pagination['current_page'] > 1)
                        <a href="?page=1" class="btn btn-sm btn-light">First</a>
                        <a href="?page={{ $pagination['current_page'] - 1 }}" class="btn btn-sm btn-light">Previous</a>
                    @endif

                    <span class="pagination-info">
                        Page {{ $pagination['current_page'] }} of {{ $pagination['last_page'] }}
                    </span>

                    @if($pagination['current_page'] < $pagination['last_page'])
                        <a href="?page={{ $pagination['current_page'] + 1 }}" class="btn btn-sm btn-light">Next</a>
                        <a href="?page={{ $pagination['last_page'] }}" class="btn btn-sm btn-light">Last</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.conversation-detail {
    padding: 20px;
}

.header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

.header h1 {
    margin: 0;
    flex: 1;
}

.conversation-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-card h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
}

.info-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-item label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 5px;
    display: block;
}

.info-item p {
    margin: 0;
    color: #333;
    font-size: 14px;
}

.info-item pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    font-size: 11px;
    overflow-x: auto;
    margin: 0;
}

.messages-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.messages-list {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    max-height: 600px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message {
    display: flex;
    margin-bottom: 10px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-user {
    justify-content: flex-end;
}

.message-ai {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 8px;
    word-wrap: break-word;
    line-height: 1.4;
}

.message-user .message-bubble {
    background: #FF6B35;
    color: white;
    border-radius: 8px 2px 8px 8px;
}

.message-ai .message-bubble {
    background: #f0f0f0;
    color: #333;
    border-radius: 2px 8px 8px 8px;
}

.message-bubble p {
    margin: 0 0 5px 0;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    display: block;
    margin-top: 5px;
}

.message-user .message-time {
    color: rgba(255,255,255,0.8);
}

.empty-messages {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.pagination-container {
    border-top: 1px solid #f0f0f0;
    padding: 15px 20px;
    display: flex;
    justify-content: center;
    gap: 10px;
    align-items: center;
}

.pagination-info {
    font-size: 13px;
    color: #666;
    padding: 0 10px;
}

.badge-channel {
    font-size: 11px;
    padding: 4px 8px;
}

.badge-web { background: #2196F3; }
.badge-whatsapp { background: #25D366; }
.badge-facebook { background: #1877F2; }
.badge-telegram { background: #0088cc; }

.badge-active { background: #4CAF50; color: white; }
.badge-escalated { background: #FF9800; color: white; }
.badge-closed { background: #9E9E9E; color: white; }

@media (max-width: 1024px) {
    .conversation-container {
        grid-template-columns: 1fr;
    }

    .sidebar {
        order: 2;
    }

    .messages-section {
        order: 1;
    }
}
</style>
@endsection
