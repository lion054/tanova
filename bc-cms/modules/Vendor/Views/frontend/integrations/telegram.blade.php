@extends('vendor.layouts.app')

@section('title', 'Setup Telegram Bot')

@section('content')
<div class="setup-page">
    <div class="setup-header">
        <a href="{{ route('user.integrations.index') }}" class="btn btn-light">← Back</a>
        <h1>Telegram Bot Setup</h1>
    </div>

    <div class="setup-content">
        <div class="setup-info">
            <div class="info-section">
                <h3>What You Need</h3>
                <ul>
                    <li>Telegram account (create at <a href="https://telegram.org" target="_blank">telegram.org</a>)</li>
                    <li>Bot token from @BotFather</li>
                    <li>That's it! 🎉</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Setup Steps</h3>
                <ol>
                    <li><strong>Open Telegram</strong></li>
                    <li><strong>Search for @BotFather</strong></li>
                    <li><strong>Send /newbot</strong></li>
                    <li><strong>Follow the prompts:</strong>
                        <ul>
                            <li>Choose a name (e.g., "Tanova Travel Bot")</li>
                            <li>Choose a username (e.g., "tsokatravel_bot")</li>
                        </ul>
                    </li>
                    <li><strong>Copy your bot token</strong></li>
                    <li><strong>Paste below</strong> and click Save</li>
                </ol>
            </div>
        </div>

        <div class="setup-form-container">
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('user.integrations.telegram') }}" class="setup-form">
                @csrf

                <div class="form-section">
                    <h4>Your Telegram Bot Token</h4>

                    <div class="form-group mb-3">
                        <label for="telegram_bot_token">Bot Token *</label>
                        <input type="password"
                               id="telegram_bot_token"
                               name="telegram_bot_token"
                               class="form-control @error('telegram_bot_token') is-invalid @enderror"
                               placeholder="123456789:ABCDefg_hijKlmNOpQrStUVwxYz"
                               value="{{ old('telegram_bot_token') }}"
                               required>
                        <small class="text-muted">Format: <code>123456:ABCDEFxxx</code> from @BotFather</small>
                        <small class="text-muted d-block">🔒 Your token is encrypted and stored securely</small>
                        @error('telegram_bot_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button type="submit"
                                name="test_connection"
                                value="1"
                                class="btn btn-outline-primary">
                            Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save & Connect
                        </button>
                    </div>
                </div>

                <div class="info-box">
                    <strong>💡 Where to find your bot token:</strong><br>
                    After /newbot, @BotFather will give you a message like:<br>
                    <code>Use this token to access the HTTP API: 123456789:ABCDefg...</code><br>
                    Copy everything after "token to access the HTTP API: "
                </div>

                <div class="info-box success">
                    <strong>✅ Benefits of Telegram:</strong><br>
                    • 100% free<br>
                    • No rate limits<br>
                    • Unlimited conversations<br>
                    • Easy bot creation
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.setup-page {
    padding: 20px;
}

.setup-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
}

.setup-header h1 {
    margin: 0;
    flex: 1;
    color: #333;
}

.setup-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    max-width: 1200px;
}

.setup-info {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.info-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0088cc;
}

.info-section h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.info-section ul, .info-section ol {
    margin: 0;
    padding-left: 20px;
    color: #666;
    line-height: 1.8;
}

.info-section li {
    margin-bottom: 10px;
}

.setup-form-container {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 30px;
}

.setup-form {
    display: flex;
    flex-direction: column;
}

.form-section h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
}

.form-group label {
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    display: block;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

.form-group code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.form-actions .btn {
    flex: 1;
}

.info-box {
    background: #E3F2FD;
    border: 1px solid #2196F3;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
    color: #1565C0;
    font-size: 13px;
    line-height: 1.6;
}

.info-box.success {
    background: #E8F5E9;
    border-color: #4CAF50;
    color: #2E7D32;
}

.info-box strong {
    display: block;
    margin-bottom: 5px;
}

.info-box code {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 3px;
    display: block;
    margin-top: 5px;
    font-size: 11px;
    word-break: break-all;
}

@media (max-width: 1024px) {
    .setup-content {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
