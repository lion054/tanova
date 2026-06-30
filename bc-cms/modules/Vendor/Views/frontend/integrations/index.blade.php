@extends('layouts.user')

@section('title', 'Integrations & Channels')

@section('content')
<div class="integrations-hub">
    <!-- Header -->
    <div class="hub-header">
        <div>
            <p class="hub-eyebrow">Tsoka Platform</p>
            <h1 class="hub-title">Integrations <em>& Channels</em></h1>
            <p class="hub-subtitle">Connect your business channels to the AI Concierge</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats -->
    @php
        $all_channels = ['whatsapp', 'facebook', 'telegram', 'pms', 'pss'];
        $connected_count = count(array_filter($all_channels, function($ch) use ($integrations) {
            return ($integrations[$ch]['connected'] ?? false);
        }));
    @endphp
    <div class="hub-stats">
        <div class="stat-card stat-green">
            <div class="stat-label">✓ Connected</div>
            <div class="stat-value">{{ $connected_count }}</div>
            <div class="stat-sub">of {{ count($all_channels) }} channels</div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-label">🔧 Available</div>
            <div class="stat-value">{{ count($all_channels) }}</div>
            <div class="stat-sub">integration channels</div>
        </div>
        <div class="stat-card stat-amber">
            <div class="stat-label">⚙️ Channels</div>
            <div class="stat-value">{{ count($all_channels) - $connected_count }}</div>
            <div class="stat-sub">{{ count($all_channels) - $connected_count > 1 ? 'channels' : 'channel' }} available to setup</div>
        </div>
    </div>

    <!-- Communication Channels Section -->
    <div class="hub-section">
        <h2 class="section-label">Communication Channels</h2>
        <div class="channels-grid">
            @foreach(['whatsapp' => ['icon' => '💬', 'name' => 'WhatsApp Business', 'desc' => 'Chat with customers on WhatsApp'],
                      'facebook' => ['icon' => '👥', 'name' => 'Facebook Messenger', 'desc' => 'Chat with customers on Facebook'],
                      'telegram' => ['icon' => '🤖', 'name' => 'Telegram Bot', 'desc' => 'Chat with customers on Telegram']] as $channel => $meta)
                @php
                    $integration = $integrations[$channel] ?? [];
                    $connected = $integration['connected'] ?? false;
                    $hasRoute = Route::has('user.integrations.' . $channel);
                @endphp
                <div class="channel-card {{ $connected ? 'connected' : '' }}">
                    <div class="card-icon">{{ $meta['icon'] }}</div>
                    <div class="card-info">
                        <h3>{{ $meta['name'] }}</h3>
                        <p>{{ $meta['desc'] }}</p>
                    </div>
                    <div class="card-status">
                        @if($connected)
                            <span class="badge badge-success">✓ Connected</span>
                        @else
                            <span class="badge badge-pending">Not Connected</span>
                        @endif
                    </div>
                    <div class="card-actions">
                        @if($hasRoute)
                            @if(!$connected)
                                <a href="{{ route('user.integrations.' . $channel) }}" class="btn btn-primary btn-sm">Setup</a>
                            @else
                                <button class="btn btn-outline-secondary btn-sm" onclick="testConnection('{{ $channel }}')">Test</button>
                                <a href="{{ route('user.integrations.' . $channel) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                <button class="btn btn-outline-danger btn-sm" onclick="disconnectChannel('{{ $channel }}')">Disconnect</button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Property Management Section -->
    <div class="hub-section">
        <h2 class="section-label">Property Management Systems</h2>
        <div class="systems-grid">
            @foreach(['pms' => ['icon' => '🏨', 'name' => 'PMS Integration', 'desc' => 'Connect your Property Management System'],
                      'pss' => ['icon' => '🔧', 'name' => 'PSS Integration', 'desc' => 'Connect your Property Service System']] as $channel => $meta)
                @php
                    $integration = $integrations[$channel] ?? [];
                    $connected = $integration['connected'] ?? false;
                    $hasRoute = Route::has('user.integrations.' . $channel);
                @endphp
                <div class="system-card {{ $connected ? 'connected' : '' }}">
                    <div class="card-icon">{{ $meta['icon'] }}</div>
                    <div class="card-info">
                        <h3>{{ $meta['name'] }}</h3>
                        <p>{{ $meta['desc'] }}</p>
                    </div>
                    <div class="card-status">
                        @if($connected)
                            <span class="badge badge-success">✓ Connected</span>
                        @else
                            <span class="badge badge-pending">Not Connected</span>
                        @endif
                    </div>
                    <div class="card-actions">
                        @if($hasRoute)
                            @if(!$connected)
                                <a href="{{ route('user.integrations.' . $channel) }}" class="btn btn-primary btn-sm">Setup</a>
                            @else
                                <button class="btn btn-outline-secondary btn-sm" onclick="testConnection('{{ $channel }}')">Test</button>
                                <a href="{{ route('user.integrations.' . $channel) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                <button class="btn btn-outline-danger btn-sm" onclick="disconnectChannel('{{ $channel }}')">Disconnect</button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Setup Guides -->
    <div class="hub-section">
        <h2 class="section-label">Setup Guides</h2>
        <div class="guides-grid">
            @if(Route::has('user.integrations.whatsapp'))
            <div class="guide-card">
                <h4>💬 WhatsApp Business</h4>
                <p>Connect your WhatsApp Business account to receive messages from customers</p>
                <ol>
                    <li>Go to <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                    <li>Create WhatsApp Business app</li>
                    <li>Get your credentials (API Key, Phone ID)</li>
                    <li>Click "Setup" button above to configure</li>
                </ol>
                <a href="{{ route('user.integrations.whatsapp') }}" class="btn-guide">View Full Guide →</a>
            </div>
            @endif

            @if(Route::has('user.integrations.facebook'))
            <div class="guide-card">
                <h4>👥 Facebook Messenger</h4>
                <p>Connect your Facebook Page to chat with customers in Messenger</p>
                <ol>
                    <li>Go to <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
                    <li>Create a new App</li>
                    <li>Connect your Facebook Page</li>
                    <li>Click "Setup" button above to configure</li>
                </ol>
                <a href="{{ route('user.integrations.facebook') }}" class="btn-guide">View Full Guide →</a>
            </div>
            @endif

            @if(Route::has('user.integrations.telegram'))
            <div class="guide-card">
                <h4>🤖 Telegram Bot</h4>
                <p>Create a Telegram bot to chat with customers on Telegram</p>
                <ol>
                    <li>Open Telegram</li>
                    <li>Search for @BotFather</li>
                    <li>Create your bot with /newbot</li>
                    <li>Click "Setup" button above to configure</li>
                </ol>
                <a href="{{ route('user.integrations.telegram') }}" class="btn-guide">View Full Guide →</a>
            </div>
            @endif

            @if(Route::has('user.integrations.pms'))
            <div class="guide-card">
                <h4>🏨 PMS Integration</h4>
                <p>Connect your Property Management System to manage guest communications</p>
                <ol>
                    <li>Log into your PMS account</li>
                    <li>Navigate to API/Integration settings</li>
                    <li>Generate your API credentials</li>
                    <li>Click "Setup" button above to configure</li>
                </ol>
                <a href="{{ route('user.integrations.pms') }}" class="btn-guide">View Full Guide →</a>
            </div>
            @endif

            @if(Route::has('user.integrations.pss'))
            <div class="guide-card">
                <h4>🔧 PSS Integration</h4>
                <p>Connect your Property Service System for seamless service management</p>
                <ol>
                    <li>Access your PSS dashboard</li>
                    <li>Go to Integrations section</li>
                    <li>Request API access from your PSS provider</li>
                    <li>Click "Setup" button above to configure</li>
                </ol>
                <a href="{{ route('user.integrations.pss') }}" class="btn-guide">View Full Guide →</a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Disconnect Modal -->
<div class="modal fade" id="disconnectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Disconnect Channel?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure? You won't receive messages from this channel anymore.</p>
                <p><strong>Existing conversations will be preserved</strong> in your Concierge dashboard.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="disconnectForm" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="channel" id="disconnectChannel">
                    <button type="submit" class="btn btn-danger">Disconnect</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.integrations-hub {
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.hub-header {
    margin-bottom: 40px;
}

.hub-eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #aaa;
    margin-bottom: 8px;
}

.hub-title {
    font-size: 36px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
    letter-spacing: -0.02em;
}

.hub-title em {
    font-style: italic;
    color: #FF6B35;
}

.hub-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
}

/* Stats */
.hub-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .hub-stats {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 24px;
    position: relative;
    overflow: hidden;
}

.stat-card::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    opacity: 0.1;
}

.stat-card.stat-green {
    border-color: #4CAF50;
}

.stat-card.stat-green::after {
    background: #4CAF50;
}

.stat-card.stat-blue {
    border-color: #2196F3;
}

.stat-card.stat-blue::after {
    background: #2196F3;
}

.stat-card.stat-amber {
    border-color: #FF9800;
}

.stat-card.stat-amber::after {
    background: #FF9800;
}

.stat-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #aaa;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: #333;
    line-height: 1;
    margin-bottom: 6px;
}

.stat-sub {
    font-size: 11px;
    color: #999;
}

/* Sections */
.hub-section {
    margin-bottom: 50px;
}

.section-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #aaa;
    margin-bottom: 20px;
}

/* Grids */
.channels-grid,
.systems-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}

.guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

/* Cards */
.channel-card,
.system-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.channel-card:hover,
.system-card:hover {
    border-color: #FF6B35;
    box-shadow: 0 8px 24px rgba(255, 107, 53, 0.15);
    transform: translateY(-2px);
}

.channel-card.connected,
.system-card.connected {
    border-color: #4CAF50;
    background: #f1f8f5;
}

.card-icon {
    font-size: 36px;
    margin-bottom: 16px;
}

.card-info {
    flex: 1;
    margin-bottom: 16px;
}

.card-info h3 {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
}

.card-info p {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.5;
}

.card-status {
    margin-bottom: 16px;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background: #4CAF50;
    color: white;
}

.badge-pending {
    background: #e0e0e0;
    color: #666;
}

.card-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-sm {
    font-size: 12px;
    padding: 8px 12px;
}

/* Guide Cards */
.guide-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 24px;
}

.guide-card h4 {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin: 0 0 12px 0;
}

.guide-card p {
    font-size: 13px;
    color: #666;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

.guide-card ol {
    margin: 0 0 16px 20px;
    padding: 0;
    color: #666;
    font-size: 13px;
}

.guide-card li {
    margin-bottom: 6px;
}

.guide-card a {
    color: #FF6B35;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}

.guide-card a:hover {
    text-decoration: underline;
}

.btn-guide {
    display: inline-block;
    margin-top: 4px;
}

.btn-primary {
    background: #FF6B35;
    border-color: #FF6B35;
    color: white;
}

.btn-primary:hover {
    background: #E55A24;
    border-color: #E55A24;
}

.btn-outline-secondary,
.btn-outline-primary,
.btn-outline-danger {
    border: 1px solid;
    background: transparent;
    font-weight: 600;
}

.btn-outline-secondary {
    border-color: #999;
    color: #666;
}

.btn-outline-secondary:hover {
    background: #f5f5f5;
}

.btn-outline-primary {
    border-color: #FF6B35;
    color: #FF6B35;
}

.btn-outline-primary:hover {
    background: #FFF5F0;
}

.btn-outline-danger {
    border-color: #d32f2f;
    color: #d32f2f;
}

.btn-outline-danger:hover {
    background: #FFEBEE;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-success {
    background: #f1f8f5;
    border: 1px solid #d1fae5;
    color: #065f46;
}

.alert-danger {
    background: #fef2f2;
    border: 1px solid #fee2e2;
    color: #7f1d1d;
}

@media (max-width: 640px) {
    .integrations-hub {
        padding: 16px;
    }

    .hub-title {
        font-size: 24px;
    }

    .channels-grid,
    .systems-grid,
    .guides-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function disconnectChannel(channel) {
    document.getElementById('disconnectChannel').value = channel;
    const modal = new bootstrap.Modal(document.getElementById('disconnectModal'));
    modal.show();
}

function testConnection(channel) {
    const btn = event.target;
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Testing...';

    fetch('{{ route("user.integrations.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ channel: channel })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;

        const alertClass = data.success ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.innerHTML = `
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const page = document.querySelector('.integrations-hub');
        page.insertBefore(alert, page.firstChild);

        setTimeout(() => alert.remove(), 5000);
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error('Error:', error);
    });
}

document.getElementById('disconnectForm').addEventListener('submit', function(e) {
    const channel = document.getElementById('disconnectChannel').value;
    this.action = `/user/integrations/${channel}/disconnect`;
});
</script>
@endsection
