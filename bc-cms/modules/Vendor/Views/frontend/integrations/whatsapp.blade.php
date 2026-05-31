@extends('vendor.layouts.app')

@section('title', 'Setup WhatsApp Business')

@section('content')
<div class="setup-page">
    <div class="setup-header">
        <a href="{{ route('user.integrations.index') }}" class="btn btn-light">← Back</a>
        <h1>WhatsApp Business Setup</h1>
    </div>

    <div class="setup-content">
        <div class="setup-info">
            <div class="info-section">
                <h3>What You Need</h3>
                <ul>
                    <li>WhatsApp Business Account (free)</li>
                    <li>Meta App with WhatsApp product</li>
                    <li>Business phone number</li>
                    <li>Access token & account ID from Meta</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Setup Steps</h3>
                <ol>
                    <li><strong>Go to Meta Business</strong> → <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                    <li><strong>Create or select business account</strong></li>
                    <li><strong>Create App</strong> (type: Business)</li>
                    <li><strong>Add WhatsApp product</strong> to your app</li>
                    <li><strong>Get credentials:</strong>
                        <ul>
                            <li>Access Token</li>
                            <li>Phone Number ID</li>
                            <li>Business Account ID</li>
                        </ul>
                    </li>
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

            <form method="POST" action="{{ route('user.integrations.whatsapp') }}" class="setup-form">
                @csrf

                <div class="form-section">
                    <h4>Your WhatsApp Credentials</h4>

                    <div class="form-group mb-3">
                        <label for="whatsapp_phone">Business Phone Number *</label>
                        <input type="text"
                               id="whatsapp_phone"
                               name="whatsapp_phone"
                               class="form-control @error('whatsapp_phone') is-invalid @enderror"
                               placeholder="+1234567890"
                               value="{{ old('whatsapp_phone', Auth::user()->whatsapp_phone) }}"
                               required>
                        <small class="text-muted">Format: +1234567890</small>
                        @error('whatsapp_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="whatsapp_phone_number_id">Phone Number ID *</label>
                        <input type="text"
                               id="whatsapp_phone_number_id"
                               name="whatsapp_phone_number_id"
                               class="form-control @error('whatsapp_phone_number_id') is-invalid @enderror"
                               placeholder="123456789012345"
                               value="{{ old('whatsapp_phone_number_id') }}"
                               required>
                        <small class="text-muted">Found in Meta App Dashboard → WhatsApp Settings</small>
                        @error('whatsapp_phone_number_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="whatsapp_business_account_id">Business Account ID *</label>
                        <input type="text"
                               id="whatsapp_business_account_id"
                               name="whatsapp_business_account_id"
                               class="form-control @error('whatsapp_business_account_id') is-invalid @enderror"
                               placeholder="123456789012345"
                               value="{{ old('whatsapp_business_account_id') }}"
                               required>
                        <small class="text-muted">Found in Meta Business Account settings</small>
                        @error('whatsapp_business_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="whatsapp_access_token">Access Token *</label>
                        <input type="password"
                               id="whatsapp_access_token"
                               name="whatsapp_access_token"
                               class="form-control @error('whatsapp_access_token') is-invalid @enderror"
                               placeholder="EAAxxxxxxx..."
                               value="{{ old('whatsapp_access_token') }}"
                               required>
                        <small class="text-muted">🔒 Your token is encrypted and stored securely</small>
                        @error('whatsapp_access_token')
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
                    <strong>💡 Tip:</strong> Test your connection before saving to make sure all credentials are correct.
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
    border-left: 4px solid #FF6B35;
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

.info-box strong {
    display: block;
    margin-bottom: 5px;
}

@media (max-width: 1024px) {
    .setup-content {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
