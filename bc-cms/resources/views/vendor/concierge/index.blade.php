@extends('layouts.user')

@section('title', 'Concierge & Support')

@section('content')
<div class="concierge-dashboard">
    <div class="header">
        <h1>Concierge & Support</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#newConversationModal">
            + New Conversation
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_conversations'] ?? 0 }}</div>
            <div class="stat-label">Total Conversations</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #4CAF50;">{{ $stats['open_conversations'] ?? 0 }}</div>
            <div class="stat-label">Open</div>
            <small>Waiting response</small>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #FF9800;">{{ $stats['escalated_conversations'] ?? 0 }}</div>
            <div class="stat-label">Escalated</div>
            <small>Needs urgent attention</small>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #2196F3;">{{ $stats['resolved_conversations'] ?? 0 }}</div>
            <div class="stat-label">Resolved</div>
            <small>Closed conversations</small>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search guest name or email..." class="form-control">
            </div>

            <div class="filter-group">
                <label>Channel</label>
                <select name="channel" class="form-control">
                    <option value="">All Channels</option>
                    <option value="web" {{ $filters['channel'] === 'web' ? 'selected' : '' }}>Website</option>
                    <option value="whatsapp" {{ $filters['channel'] === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="facebook" {{ $filters['channel'] === 'facebook' ? 'selected' : '' }}>Facebook</option>
                    <option value="telegram" {{ $filters['channel'] === 'telegram' ? 'selected' : '' }}>Telegram</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="escalated" {{ $filters['status'] === 'escalated' ? 'selected' : '' }}>Escalated</option>
                    <option value="closed" {{ $filters['status'] === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>

    <!-- Conversations Table -->
    @if(count($conversations) > 0)
        <div class="conversations-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Channel</th>
                        <th>Last Message</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversations as $conv)
                        <tr class="conversation-row" data-id="{{ $conv['id'] }}">
                            <td>
                                <div class="guest-info">
                                    <div class="guest-name">{{ $conv['guest_name'] ?? 'Guest' }}</div>
                                    <div class="guest-email">{{ $conv['guest_email'] ?? '' }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-channel badge-{{ $conv['channel'] }}">
                                    {{ ucfirst($conv['channel']) }}
                                </span>
                            </td>
                            <td>
                                <div class="last-message">
                                    {{ Str::limit($conv['last_message'] ?? '', 50) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-status badge-{{ $conv['status'] }}">
                                    {{ ucfirst($conv['status']) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $conv['updated_at'] ? \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans() : '-' }}</small>
                            </td>
                            <td>
                                <a href="{{ route('user.concierge.show', $conv['id']) }}" class="btn btn-sm btn-info">
                                    View
                                </a>
                                @if($conv['status'] !== 'closed')
                                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#closeModal" data-id="{{ $conv['id'] }}">
                                        Close
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">💬</div>
            <p>No conversations yet.</p>
            <p class="text-muted">Start by embedding the chatbot on your website or setting up WhatsApp.</p>
            <a href="{{ route('user.concierge.setup') }}" class="btn btn-primary">
                Setup Chatbot
            </a>
        </div>
    @endif
</div>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('user.concierge.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Guest Name</label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Guest Email</label>
                        <input type="email" name="guest_email" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Guest Phone (Optional)</label>
                        <input type="text" name="guest_phone" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Conversation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Close Conversation Modal -->
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Close Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="closeForm" action="#">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason for closing</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Close Conversation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-target="#closeModal"]').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    const form = document.getElementById('closeForm');
    form.action = '{{ route("user.concierge.close", ":id") }}'.replace(':id', id);
  });
});
</script>

<style>
.concierge-dashboard {
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #FF6B35;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: flex-end;
}

.filter-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    font-size: 13px;
}

.conversations-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.guest-info {
    padding: 10px 0;
}

.guest-name {
    font-weight: 500;
    color: #333;
}

.guest-email {
    font-size: 12px;
    color: #999;
}

.badge-channel {
    font-size: 11px;
    padding: 4px 8px;
}

.badge-web { background: #2196F3; }
.badge-whatsapp { background: #25D366; }
.badge-facebook { background: #1877F2; }
.badge-telegram { background: #0088cc; }

.badge-status {
    font-size: 11px;
    padding: 4px 8px;
}

.badge-active { background: #4CAF50; }
.badge-escalated { background: #FF9800; }
.badge-closed { background: #9E9E9E; }

.last-message {
    color: #666;
    font-size: 13px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.empty-state p {
    margin: 10px 0;
    color: #666;
}

.table-hover tbody tr:hover {
    background-color: #f9f9f9;
}
</style>
@endsection
