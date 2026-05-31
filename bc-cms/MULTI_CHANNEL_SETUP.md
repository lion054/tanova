# Tsoka Multi-Channel Setup Guide
## WhatsApp, Facebook Messenger, and Telegram Integration

---

## Overview

The Tsoka chatbot now runs on three channels:
1. **Website Widget** (JavaScript) - Vendor websites
2. **WhatsApp Business API** - WhatsApp conversations
3. **Facebook Messenger** - Facebook Page messages
4. **Telegram Bot** - Telegram chatbot

All channels use the same backend (`/api/v/concierge/message`), so:
- Same conversation flow across all channels
- Same conversation state and context
- Same Claude AI integration
- Vendor isolation maintained on all channels

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│           User Messages (4 channels)                    │
├─────────────────────────────────────────────────────────┤
│  Website     WhatsApp      Facebook      Telegram       │
│   Widget      Business      Messenger      Bot          │
│    ↓            ↓             ↓             ↓           │
├─────────────────────────────────────────────────────────┤
│              Adapter Layer (normalizes messages)        │
│  WhatsAppAdapter | FacebookAdapter | TelegramAdapter   │
│    ↓            ↓             ↓             ↓           │
├─────────────────────────────────────────────────────────┤
│            ChatbotService.processMessage()              │
│      (Same conversation logic for all channels)        │
│    ↓            ↓             ↓             ↓           │
├─────────────────────────────────────────────────────────┤
│         Response (sent back to channel)                 │
│      Message + Buttons + Typing Indicators              │
└─────────────────────────────────────────────────────────┘
```

---

## Setup Instructions

### Prerequisites

All channels require:
1. A vendor account in the Tsoka portal
2. API key (already set up for website widget)
3. Access to the external platform (WhatsApp Business, Facebook, Telegram)
4. Webhook URL (provided by Tsoka: `https://portal.tsokatravel.com/webhooks/{channel}`)

---

## 1. WHATSAPP BUSINESS API

### Step 1: Get WhatsApp Business Account

```
1. Go to: Meta Business Suite (business.facebook.com)
2. Create business account (if not already done)
3. Go to: Apps & Assets → Apps
4. Create new app (or use existing one)
5. Add WhatsApp product to the app
6. Get your: Phone Number ID, Business Account ID
```

### Step 2: Generate Access Token

```
1. In App Dashboard → Settings → Basic
2. Generate or get your Access Token
3. Save it (you'll need this for config)
4. Token format: EAAxxxxxxx...
```

### Step 3: Configure Webhook

```
1. In WhatsApp App Dashboard → Configuration
2. Webhook URL: https://portal.tsokatravel.com/webhooks/whatsapp
3. Webhook Token (Verify Token): Create a random string
   Example: my_whatsapp_webhook_token_2024
```

### Step 4: Add Config to Laravel (.env)

```bash
# In /bc-cms/.env
WHATSAPP_ACCESS_TOKEN="EAAxxxxxxx..."
WHATSAPP_PHONE_NUMBER_ID="12345678901234"
WHATSAPP_BUSINESS_ACCOUNT_ID="123456789"
WHATSAPP_VERIFY_TOKEN="my_whatsapp_webhook_token_2024"
```

### Step 5: Update Config (config/services.php)

```php
'whatsapp' => [
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
],
```

### Step 6: Link WhatsApp Account to Vendor

```
Vendor database table needs:
- whatsapp_phone: Phone number receiving messages (e.g., "+255-788-123-456")
- whatsapp_enabled: boolean (true/false)

When user messages this WhatsApp number, adapter looks up vendor by phone number.
```

### Step 7: Test

```bash
# Test webhook verification
curl -X GET "https://portal.tsokatravel.com/webhooks/whatsapp?hub_verify_token=my_whatsapp_webhook_token_2024&hub_challenge=test_challenge"

# Expected response: test_challenge

# Send test message via WhatsApp to your business number
# Should see it appear in Laravel logs
```

### WhatsApp Message Flow

```
User sends: "Plan my safari"
    ↓
WhatsApp API → POST /webhooks/whatsapp
    ↓
WhatsAppAdapter extracts: {from: "+255788123456", text: "Plan my safari"}
    ↓
Looks up vendor by phone number
    ↓
Calls ChatbotService.processMessage(vendorId, "whatsapp", "Plan my safari")
    ↓
Response: "Let's build this. Where in Africa are you headed?"
    ↓
WhatsAppAdapter sends back to user
    ↓
User sees reply in WhatsApp
```

---

## 2. FACEBOOK MESSENGER

### Step 1: Create Facebook Page

```
1. Go to: facebook.com/business
2. Create or use existing Facebook Page
3. Get your Page ID (visible in page URL: facebook.com/pageid)
```

### Step 2: Create Meta App

```
1. Go to: developers.facebook.com/apps
2. Create new app (type: Business)
3. Add Messenger product
4. Add your Facebook Page to the app
```

### Step 3: Generate Page Access Token

```
1. App Dashboard → Messenger → Settings
2. Generate Page Access Token for your page
3. Save it (format: EAAxxxxxxx...)
4. This token allows sending messages as your page
```

### Step 4: Set Webhook

```
1. Messenger Settings → Webhooks
2. Webhook URL: https://portal.tsokatravel.com/webhooks/facebook
3. Verify Token: Create random string (e.g., my_fb_verify_token_2024)
4. Subscribe to events: messages, messaging_postbacks
```

### Step 5: Add Config to Laravel (.env)

```bash
FACEBOOK_PAGE_ID="123456789"
FACEBOOK_PAGE_ACCESS_TOKEN="EAAxxxxxxx..."
FACEBOOK_VERIFY_TOKEN="my_fb_verify_token_2024"
FACEBOOK_APP_ID="your_app_id"
FACEBOOK_APP_SECRET="your_app_secret"
```

### Step 6: Update Config (config/services.php)

```php
'facebook' => [
    'page_id' => env('FACEBOOK_PAGE_ID'),
    'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
    'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
    'app_id' => env('FACEBOOK_APP_ID'),
    'app_secret' => env('FACEBOOK_APP_SECRET'),
],
```

### Step 7: Link Facebook Account to Vendor

```
Vendor database table needs:
- facebook_page_id: Your Facebook Page ID
- facebook_enabled: boolean (true/false)

Adapter looks up vendor by page ID when message arrives.
```

### Step 8: Test

```bash
# Test webhook verification
curl -X GET "https://portal.tsokatravel.com/webhooks/facebook?hub_verify_token=my_fb_verify_token_2024&hub_challenge=test_challenge"

# Expected: test_challenge

# Send message via Facebook Messenger to your page
# Check Laravel logs for incoming message
```

### Facebook Message Flow

```
User sends: "Plan my safari"
    ↓
Facebook Messenger API → POST /webhooks/facebook
    ↓
FacebookMessengerAdapter extracts: {from: userId, text: "Plan my safari"}
    ↓
Gets user profile info from Facebook
    ↓
Looks up vendor by page ID
    ↓
Calls ChatbotService.processMessage(vendorId, "facebook", "Plan my safari")
    ↓
Response + Quick Reply Buttons
    ↓
FacebookMessengerAdapter sends to user
    ↓
User sees reply in Messenger
```

---

## 3. TELEGRAM BOT

### Step 1: Create Telegram Bot

```
1. Open Telegram
2. Search for @BotFather
3. /newbot
4. Follow prompts:
   - Bot name: "Tanova by [Your Company]"
   - Bot username: Something unique (e.g., "tsokatravel_bot")
5. Copy the API token (format: 123456:ABCDEFxxxxxyz)
6. Save it securely
```

### Step 2: Configure Bot Commands

```
With @BotFather:

/setcommands
→ Select your bot
→ Paste these commands:
   start - Start chatting with Tanova
   help - Get help
   reset - Start a new conversation
   settings - Vendor settings (admin only)
```

### Step 3: Set Webhook URL

```
1. Laravel config should point to webhook route
2. Use TelegramAdapter.setWebhook() to register:

In your Laravel tinker:
$adapter = app(TelegramAdapter::class);
$adapter->setWebhook('https://portal.tsokatravel.com/webhooks/telegram');
```

### Step 4: Add Config to Laravel (.env)

```bash
TELEGRAM_BOT_TOKEN="123456:ABCDEFxxxxxyz"
```

### Step 5: Update Config (config/services.php)

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
],
```

### Step 6: Link Telegram Bot to Vendor

```
Vendor database table needs:
- telegram_bot_token: Your bot token
- telegram_enabled: boolean (true/false)

Adapter looks up vendor by bot token.
```

### Step 7: Test

```bash
# Verify webhook is set
curl https://api.telegram.org/bot123456:ABCDEFxxxxxyz/getWebhookInfo

# Should show your webhook URL

# Add bot to chat (search for your bot_username)
# /start
# Should see welcome message
```

### Telegram Message Flow

```
User sends: "Plan my safari"
    ↓
Telegram Bot API → POST /webhooks/telegram
    ↓
TelegramAdapter extracts: {userId: 123456, chatId: 789, text: "Plan my safari"}
    ↓
Looks up vendor by bot token
    ↓
Calls ChatbotService.processMessage(vendorId, "telegram", "Plan my safari")
    ↓
Response + Inline Buttons
    ↓
TelegramAdapter sends to chat
    ↓
User sees reply in Telegram
```

---

## Database Schema Updates

Add these columns to `vendors` table:

```sql
ALTER TABLE vendors ADD COLUMN whatsapp_phone VARCHAR(20) NULLABLE;
ALTER TABLE vendors ADD COLUMN whatsapp_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE vendors ADD COLUMN facebook_page_id VARCHAR(20) NULLABLE;
ALTER TABLE vendors ADD COLUMN facebook_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE vendors ADD COLUMN telegram_bot_token VARCHAR(100) NULLABLE;
ALTER TABLE vendors ADD COLUMN telegram_enabled BOOLEAN DEFAULT FALSE;

CREATE INDEX idx_vendors_whatsapp ON vendors(whatsapp_phone);
CREATE INDEX idx_vendors_facebook ON vendors(facebook_page_id);
CREATE INDEX idx_vendors_telegram ON vendors(telegram_bot_token);
```

---

## API Response Format (All Channels)

All adapters receive this response from ChatbotService:

```json
{
  "conversation_id": "uuid",
  "step": "destination",
  "content": "Let's build this. Where in Africa are you headed?",
  "picks": [
    {
      "id": 1,
      "name": "Serengeti",
      "country_name": "Tanzania"
    }
  ],
  "buttons": [
    {
      "label": "Just me",
      "value": "1"
    }
  ],
  "action": "select_destination",
  "metadata": {
    "channel": "whatsapp|facebook|telegram|web"
  }
}
```

Each adapter formats this appropriately for its platform:
- **WhatsApp**: Text message + Interactive buttons
- **Facebook**: Text message + Quick Replies
- **Telegram**: Text message + Inline keyboard
- **Web**: JavaScript widget updates

---

## Testing All Channels

### 1. Website Widget

```html
<script src="https://portal.tsokatravel.com/chatbot-widget.js"></script>
<script>
  window.TsokaChatbot.init({ apiKey: 'sk_live_...' });
</script>
```

### 2. WhatsApp

```
Save this number as contact: +1 (WhatsApp Business number you configured)
Send: "Plan my safari"
Should get response: "Let's build this..."
```

### 3. Facebook

```
1. Go to your Facebook Page
2. Click "Send Message"
3. Send: "Plan my safari"
Should get response in Messenger
```

### 4. Telegram

```
1. Search for your bot by username
2. /start
3. Send: "Plan my safari"
Should get response + buttons
```

---

## Monitoring & Debugging

### View Incoming Webhooks

```bash
# Laravel logs
tail -f /bc-cms/storage/logs/laravel.log | grep -i whatsapp
tail -f /bc-cms/storage/logs/laravel.log | grep -i facebook
tail -f /bc-cms/storage/logs/laravel.log | grep -i telegram
```

### Check Webhook Delivery Status

**WhatsApp:**
```
App Dashboard → Webhooks → Activity Logs
Shows status of each webhook call (delivered/failed)
```

**Facebook:**
```
App Dashboard → Webhooks → View All
Shows recent webhook events and their status
```

**Telegram:**
```bash
curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo
Shows: last_error_message, last_error_date, pending_update_count
```

### Common Issues

#### WhatsApp: "Webhook failed"
```
- Check verify token in config matches Meta config
- Check webhook URL is publicly accessible
- Check Laravel logs for error details
- Verify API token is valid and not expired
```

#### Facebook: "Messages not delivering"
```
- Check Page Access Token is valid
- Check page is NOT in development mode
- Check webhook is subscribed to 'messages' event
- Verify Facebook app is live (not in dev)
```

#### Telegram: "Bot not responding"
```
- Check bot token is correct
- Run: curl https://api.telegram.org/bot{TOKEN}/getMe
- If error, bot token is invalid
- Check webhook URL is set: getWebhookInfo
- Check Laravel logs for webhook errors
```

---

## Production Checklist

- [ ] All three `.env` variables are set and tested
- [ ] Webhook URLs are publicly accessible (https://)
- [ ] Database migrations run (vendor columns added)
- [ ] Each channel is linked to vendor account
- [ ] All channels have been tested with test messages
- [ ] Rate limiting is enabled (60 msgs/min)
- [ ] Error logging is enabled and monitored
- [ ] Message persistence is working (messages stored in DB)
- [ ] Escalation logic is configured (manual review after N messages)
- [ ] Analytics are tracked (messages per channel, conversation length)

---

## Migration Path

### Day 1 (Website Only)
```
✅ Website widget fully functional
⏳ WhatsApp/Facebook/Telegram: Coming soon
```

### Day 2 (Add WhatsApp)
```
✅ Website widget
✅ WhatsApp Business API
⏳ Facebook/Telegram: Coming soon
```

### Day 3 (Add Facebook)
```
✅ Website widget
✅ WhatsApp Business API
✅ Facebook Messenger
⏳ Telegram: Coming soon
```

### Day 4 (Add Telegram)
```
✅ Website widget
✅ WhatsApp Business API
✅ Facebook Messenger
✅ Telegram Bot
```

**All channels share the same conversation backend**, so enabling each one is just:
1. Get credentials from the platform
2. Add to `.env`
3. Set webhook URL
4. Test

---

## Pricing & Limits

### WhatsApp Business API
```
Per-message pricing model
- Business-initiated: ~$0.01-0.05 per message
- User-initiated (first 24h): Free
- Template messages: Lower cost
See: https://www.whatsapp.com/business/pricing
```

### Facebook Messenger
```
Free for business messages
- Charge only if you use Click-to-Messaging ads
- No per-message fee
See: https://www.facebook.com/business/pages/resources/
```

### Telegram Bot API
```
Completely free
- Unlimited messages
- No rate limits (except Telegram's fair use policy)
- Hosting: You pay for server (we do)
```

### Tsoka Pricing
```
Multi-channel is included with all plans
- Website widget: ✅ Included
- WhatsApp: ✅ Included (you pay WhatsApp fees)
- Facebook: ✅ Included (free)
- Telegram: ✅ Included (free)
```

---

## Support

### Channel Troubleshooting

**WhatsApp:**
- Meta Developers: https://developers.facebook.com/docs/whatsapp
- Business Account Health: https://business.facebook.com/

**Facebook:**
- Messenger Docs: https://developers.facebook.com/docs/messenger-platform
- Developer Community: https://www.facebook.com/groups/fbdevelopers/

**Telegram:**
- Bot API Docs: https://core.telegram.org/bots/api
- TroubleShooting: https://core.telegram.org/bots/faq

### Tsoka Support

```
Email: support@tsokatravel.com
Chat: Click "Tanova" on portal.tsokatravel.com
Docs: https://portal.tsokatravel.com/docs
```

---

## Architecture Summary

| Channel | Authentication | Message Format | Button Style | Rate Limit |
|---------|-----------------|-----------------|--------------|-----------|
| Website | API Key | JSON | Custom CSS | 60/min |
| WhatsApp | Access Token | WhatsApp API | Interactive Buttons | Meta limits |
| Facebook | Page Token | Messenger API | Quick Replies | Meta limits |
| Telegram | Bot Token | Telegram API | Inline Keyboard | Unlimited |

All channels funnel to the same `ChatbotService.processMessage()` endpoint, ensuring:
✅ Consistent conversation flow
✅ Shared conversation history
✅ Same Claude AI integration
✅ Vendor isolation maintained

---

*Setup completed: 2026-05-31*
*Status: Ready for deployment* ✅
