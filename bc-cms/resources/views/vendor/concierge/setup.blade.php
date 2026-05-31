@extends('vendor.layouts.app')

@section('title', 'Chatbot Setup Guide')

@section('content')
<div class="setup-guide">
    <div class="header">
        <h1>Chatbot Setup & Integration</h1>
        <p>Get Tanova Chatbot running on your business in minutes</p>
    </div>

    <div class="setup-content">
        <!-- Your API Key -->
        <section class="setup-section">
            <h2>Your API Key</h2>
            <p>Use this key to authenticate chatbot requests:</p>
            <div class="api-key-box">
                <code id="apiKey">{{ $apiKey }}</code>
                <button onclick="copyToClipboard('apiKey')" class="btn btn-sm btn-outline-primary">
                    Copy
                </button>
            </div>
            <p class="text-muted small">
                🔒 Keep this key secret. Don't commit to GitHub or share publicly.
            </p>
        </section>

        <!-- Website Widget Setup -->
        <section class="setup-section">
            <h2>1. Website Widget Setup (5 Minutes)</h2>
            <p>Add the chatbot to your website with just 2 lines of code.</p>

            <h4>Step 1: Add Script Tag</h4>
            <p>Copy and paste this into your website's <code>&lt;head&gt;</code> or before <code>&lt;/body&gt;</code>:</p>
            <div class="code-block">
                <pre><code>&lt;script src="https://portal.tsokatravel.com/chatbot-widget.js"&gt;&lt;/script&gt;</code></pre>
                <button onclick="copyCode(this)" class="btn btn-sm btn-outline-primary">Copy</button>
            </div>

            <h4>Step 2: Initialize</h4>
            <p>Add this configuration script right after the script tag:</p>
            <div class="code-block">
                <pre><code>&lt;script&gt;
  window.TsokaChatbot.init({
    apiKey: '<span class="highlight">{{ $apiKey }}</span>',
    color: '#FF6B35',
    position: 'bottom-right',
    title: 'Chat with Tanova',
    subtitle: 'Ask me about your trip'
  });
&lt;/script&gt;</code></pre>
                <button onclick="copyCode(this)" class="btn btn-sm btn-outline-primary">Copy</button>
            </div>

            <h4>Step 3: Test</h4>
            <ul>
                <li>Refresh your website</li>
                <li>Look for the 💬 button in the bottom-right</li>
                <li>Click and test the chatbot</li>
                <li>Send a message like "Plan my safari"</li>
            </ul>

            <div class="alert alert-info">
                <strong>Note:</strong> Conversations will appear in your Concierge dashboard within seconds.
            </div>
        </section>

        <!-- WhatsApp Setup -->
        <section class="setup-section">
            <h2>2. WhatsApp Business Setup (Optional)</h2>
            <p>Let customers chat with you via WhatsApp.</p>

            <h4>Requirements:</h4>
            <ul>
                <li>WhatsApp Business Account (free)</li>
                <li>Meta App with WhatsApp product</li>
                <li>Business phone number</li>
                <li>Access token from Meta</li>
            </ul>

            <h4>Steps:</h4>
            <ol>
                <li>Go to <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                <li>Create or select your business account</li>
                <li>Add WhatsApp product</li>
                <li>Get your Phone Number ID, Business Account ID, and Access Token</li>
                <li>Contact support@tsokatravel.com with these details</li>
                <li>We'll activate WhatsApp on your account</li>
            </ol>

            <div class="alert alert-success">
                <strong>Coming Soon:</strong> Self-service WhatsApp setup in portal settings.
            </div>
        </section>

        <!-- Facebook Setup -->
        <section class="setup-section">
            <h2>3. Facebook Messenger Setup (Optional)</h2>
            <p>Chat with customers via Facebook Messenger.</p>

            <h4>Requirements:</h4>
            <ul>
                <li>Facebook Page</li>
                <li>Meta App with Messenger product</li>
                <li>Page Access Token</li>
            </ul>

            <h4>Steps:</h4>
            <ol>
                <li>Go to <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
                <li>Create a new App (type: Business)</li>
                <li>Add Messenger product</li>
                <li>Connect your Facebook Page</li>
                <li>Generate Page Access Token</li>
                <li>Contact support@tsokatravel.com with your Page ID and token</li>
                <li>We'll activate Facebook on your account</li>
            </ol>

            <div class="alert alert-success">
                <strong>Coming Soon:</strong> Self-service Facebook setup in portal settings.
            </div>
        </section>

        <!-- Telegram Setup -->
        <section class="setup-section">
            <h2>4. Telegram Bot Setup (Optional)</h2>
            <p>Create a Telegram chatbot for your customers.</p>

            <h4>Requirements:</h4>
            <ul>
                <li>Telegram account</li>
                <li>Bot token from @BotFather</li>
            </ul>

            <h4>Steps:</h4>
            <ol>
                <li>Open Telegram</li>
                <li>Search for @BotFather</li>
                <li>Send <code>/newbot</code></li>
                <li>Follow prompts to create your bot</li>
                <li>Copy the API token (looks like <code>123456:ABCDEFxxx</code>)</li>
                <li>Contact support@tsokatravel.com with your token</li>
                <li>We'll activate Telegram on your account</li>
            </ol>

            <div class="alert alert-success">
                <strong>Coming Soon:</strong> Self-service Telegram setup in portal settings.
            </div>
        </section>

        <!-- Configuration Options -->
        <section class="setup-section">
            <h2>Widget Configuration Options</h2>
            <p>Customize the widget to match your brand:</p>

            <div class="config-table">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Option</th>
                            <th>Type</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>apiKey</code></td>
                            <td>string</td>
                            <td>-</td>
                            <td>Your API key (required)</td>
                        </tr>
                        <tr>
                            <td><code>color</code></td>
                            <td>hex color</td>
                            <td>#FF6B35</td>
                            <td>Primary button color</td>
                        </tr>
                        <tr>
                            <td><code>position</code></td>
                            <td>string</td>
                            <td>bottom-right</td>
                            <td>bottom-right, bottom-left, top-right, top-left</td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td>string</td>
                            <td>Tanova</td>
                            <td>Chat header title</td>
                        </tr>
                        <tr>
                            <td><code>subtitle</code></td>
                            <td>string</td>
                            <td>AI travel expert</td>
                            <td>Chat header subtitle</td>
                        </tr>
                        <tr>
                            <td><code>guestEmail</code></td>
                            <td>string</td>
                            <td>null</td>
                            <td>Pre-fill guest email</td>
                        </tr>
                        <tr>
                            <td><code>autoOpen</code></td>
                            <td>boolean</td>
                            <td>false</td>
                            <td>Auto-open chat on page load</td>
                        </tr>
                        <tr>
                            <td><code>autoOpenDelay</code></td>
                            <td>number</td>
                            <td>5000</td>
                            <td>Delay before auto-open (ms)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Example: Custom Configuration</h4>
            <div class="code-block">
                <pre><code>window.TsokaChatbot.init({
  apiKey: '<span class="highlight">{{ $apiKey }}</span>',
  color: '#004E89',
  position: 'bottom-left',
  title: 'Contact Our Experts',
  subtitle: 'We reply in minutes',
  guestEmail: customer@example.com,
  autoOpen: true,
  autoOpenDelay: 3000,
  onConversationStart: function(conversationId) {
    console.log('Chat started:', conversationId);
  },
  onMessage: function(message) {
    console.log('Message:', message);
  }
});</code></pre>
                <button onclick="copyCode(this)" class="btn btn-sm btn-outline-primary">Copy</button>
            </div>
        </section>

        <!-- Analytics -->
        <section class="setup-section">
            <h2>View Analytics</h2>
            <p>Track all conversations in your Concierge dashboard:</p>

            <ul>
                <li><a href="{{ route('user.concierge.index') }}">View all conversations</a></li>
                <li>Filter by channel (website, WhatsApp, Facebook, Telegram)</li>
                <li>Filter by status (open, escalated, resolved)</li>
                <li>See guest information and full message history</li>
                <li>Close conversations and track metrics</li>
            </ul>

            <div class="alert alert-info">
                <strong>Pro Tip:</strong> Check the Concierge tab regularly to see customer inquiries and respond quickly.
            </div>
        </section>

        <!-- Troubleshooting -->
        <section class="setup-section">
            <h2>Troubleshooting</h2>

            <h4>Widget doesn't appear</h4>
            <ul>
                <li>Check your API key is correct</li>
                <li>Open browser console (F12) to see errors</li>
                <li>Make sure script is loaded: Check Network tab</li>
                <li>Check if page is HTTPS (not HTTP)</li>
            </ul>

            <h4>Messages not sending</h4>
            <ul>
                <li>Check API key is valid</li>
                <li>Check Claude AI service is accessible</li>
                <li>Look for error messages in browser console</li>
                <li>Check rate limiting (60 messages/minute)</li>
            </ul>

            <h4>WhatsApp not working</h4>
            <ul>
                <li>Contact support@tsokatravel.com</li>
                <li>Provide your WhatsApp phone number</li>
                <li>We'll verify your setup</li>
            </ul>

            <h4>Still having issues?</h4>
            <ul>
                <li>Email: support@tsokatravel.com</li>
                <li>Chat: Click the help icon in the portal</li>
                <li>Include screenshots and error messages</li>
            </ul>
        </section>

        <!-- Quick Start Template -->
        <section class="setup-section">
            <h2>Quick Copy-Paste HTML Template</h2>
            <p>Use this complete HTML template to get started immediately:</p>

            <div class="code-block">
                <pre><code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;My Travel Business&lt;/title&gt;
    &lt;meta charset="utf-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1"&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;Welcome to My Travel Business&lt;/h1&gt;
    &lt;p&gt;Chat with Tanova to plan your perfect trip!&lt;/p&gt;

    &lt;!-- Chatbot Widget --&gt;
    &lt;script src="https://portal.tsokatravel.com/chatbot-widget.js"&gt;&lt;/script&gt;
    &lt;script&gt;
      window.TsokaChatbot.init({
        apiKey: '<span class="highlight">{{ $apiKey }}</span>',
        color: '#FF6B35'
      });
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
                <button onclick="copyCode(this)" class="btn btn-sm btn-outline-primary">Copy</button>
            </div>

            <p class="text-muted small">Save this as <code>index.html</code> and open in your browser to test.</p>
        </section>

        <!-- Next Steps -->
        <section class="setup-section final-section">
            <h2>You're All Set! 🎉</h2>
            <p>Your chatbot is ready to use.</p>

            <div class="next-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Add Widget to Website</h4>
                        <p>Copy the code and add to your website</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Test the Chatbot</h4>
                        <p>Chat with it to make sure it works</p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Monitor Conversations</h4>
                        <p><a href="{{ route('user.concierge.index') }}">View in Concierge dashboard</a></p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Customize & Optimize</h4>
                        <p>Adjust colors and messages to match your brand</p>
                    </div>
                </div>
            </div>

            <div class="alert alert-success">
                <strong>Support:</strong> Questions? Email support@tsokatravel.com
            </div>
        </section>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => {
            btn.textContent = originalText;
        }, 2000);
    });
}

function copyCode(button) {
    const code = button.previousElementSibling.textContent;
    navigator.clipboard.writeText(code).then(() => {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        setTimeout(() => {
            button.textContent = originalText;
        }, 2000);
    });
}
</script>

<style>
.setup-guide {
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.header h1 {
    margin: 0 0 10px 0;
}

.header p {
    color: #666;
    font-size: 18px;
    margin: 0;
}

.setup-section {
    background: white;
    padding: 30px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.setup-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 24px;
    border-bottom: 2px solid #FF6B35;
    padding-bottom: 10px;
}

.setup-section h4 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #333;
}

.setup-section p {
    color: #666;
    line-height: 1.6;
}

.setup-section ul, .setup-section ol {
    color: #666;
    line-height: 1.8;
}

.api-key-box {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.api-key-box code {
    font-family: monospace;
    font-size: 14px;
    word-break: break-all;
    flex: 1;
    color: #333;
}

.code-block {
    background: #f5f5f5;
    border-left: 4px solid #FF6B35;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    position: relative;
}

.code-block pre {
    margin: 0;
    overflow-x: auto;
}

.code-block code {
    font-family: monospace;
    font-size: 13px;
    line-height: 1.6;
    color: #333;
}

.code-block .highlight {
    background: #FFE0D0;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
}

.code-block .btn {
    position: absolute;
    top: 10px;
    right: 10px;
}

.config-table {
    margin-bottom: 20px;
}

.config-table code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    border-left: 4px solid;
}

.alert-info {
    background: #E3F2FD;
    border-color: #2196F3;
    color: #1565C0;
}

.alert-success {
    background: #E8F5E9;
    border-color: #4CAF50;
    color: #2E7D32;
}

.alert strong {
    font-weight: 600;
}

.next-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.step {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #eee;
    display: flex;
    gap: 15px;
}

.step-number {
    background: #FF6B35;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.step-content p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.step-content a {
    color: #FF6B35;
    text-decoration: none;
    font-weight: 500;
}

.final-section {
    background: linear-gradient(135deg, #FFF3E0 0%, #F3E5F5 100%);
    border: 2px solid #FF6B35;
    text-align: center;
}

.final-section h2 {
    border-bottom-color: #FF6B35;
}

@media (max-width: 768px) {
    .api-key-box {
        flex-direction: column;
        align-items: flex-start;
    }

    .code-block .btn {
        top: auto;
        right: auto;
        bottom: 10px;
        right: 10px;
    }
}
</style>
@endsection
