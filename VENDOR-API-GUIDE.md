# Tsoka Vendor API Guide

**Complete API for independent travel vendors to integrate AI trip planning, intelligent chatbot, and service catalogs on their own website.**

---

## 📋 Overview

Your vendor API key provides access to three core features:

### 1. **AI Trip Planner (Tanova)**
Generate customized travel itineraries with 8 package options based on:
- Destination
- Travel dates  
- Number of guests
- Budget tier (budget/mid-range/luxury)

Includes real-time weather, activity selection, accommodation, and pricing.

### 2. **AI Chatbot Concierge**
Multi-step conversational AI that:
- Guides customers through trip planning (destination → dates → travelers → budget)
- Automatically parses natural language ("June 3-10", "5 days", "next month")
- Answers general travel questions with Claude AI
- Integrates your services into the itinerary

**Multi-channel:** Web widget, WhatsApp, Facebook Messenger, Telegram

### 3. **Service Catalog & Bookings**
- List your hotels, tours, activities, accommodations
- Accept customer enquiries and bookings
- Manage inventory and availability

---

## 🔑 Getting Your API Key

1. Log in to your vendor portal at `https://portal.tsokatravel.com`
2. Go to **Dashboard → API Keys**
3. Copy your **Live API Key** (starts with `sk_live_`)
4. Add your domain to **CORS Whitelist** for browser-based requests

---

## 🚀 Quick Start: Embed the Chatbot on Your Website

### Step 1: Get Embed Code
```bash
curl -X GET "https://api.tsoka.travel/concierge/widget-embed?format=script" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE"
```

### Step 2: Copy-Paste on Your Website
Add this one line before the closing `</body>` tag:

```html
<script src="https://widget.tsoka.travel/chatbot.js" data-api-key="sk_live_YOUR_KEY_HERE"></script>
```

That's it! Your chatbot widget appears in the bottom-right corner. ✨

---

## ⚙️ Customize the Chatbot

### Branding: Set Your Own Name & Colors

```bash
curl -X PUT "https://api.tsoka.travel/concierge/config" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "chatbot_name": "Safari - Your Adventure Guide",
    "welcome_message": "Hi! I'\''m Safari. Ready to plan your next safari adventure?",
    "primary_color": "#2E7D32",
    "secondary_color": "#FF9800",
    "position": "bottom-right"
  }'
```

**Result:** Your chatbot now has your branding, name, colors, and position! 🎨

---

## 💬 Integrate the Chatbot API (Advanced)

### Send a Message to the Chatbot

```bash
curl -X POST "https://api.tsoka.travel/concierge/message" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "guest_email": "customer@example.com",
    "guest_name": "John Doe",
    "message": "I want to plan a 5-day safari trip for 2 people with $8000 budget",
    "channel": "web"
  }'
```

### Response
```json
{
  "conversation_id": 42,
  "user_message": {
    "role": "user",
    "content": "I want to plan a 5-day safari trip for 2 people with $8000 budget"
  },
  "assistant_message": {
    "role": "assistant",
    "content": "Great! I'll create a 5-day safari itinerary for 2 guests within your $8000 budget. Let me fetch the best safari packages and accommodations for you..."
  },
  "next_step": "confirm",
  "trip_ready": true
}
```

The chatbot automatically:
✅ Parses destination, dates, travelers, budget from natural language  
✅ Confirms understanding and asks clarifying questions  
✅ Generates trip when all info is collected  
✅ Answers off-topic questions about travel, weather, attractions  

---

## 🎯 Generate Trip Itineraries (Tanova)

Once the chatbot collects destination, dates, travelers, and budget, generate 8 customized packages:

```bash
curl -X POST "https://api.tsoka.travel/tanova/generate" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "destination": "Victoria Falls",
    "start_date": "2026-06-15",
    "end_date": "2026-06-20",
    "guests": 2,
    "budget": "mid-range"
  }'
```

### Response Includes:
- **8 Itinerary Packages** (daily breakdown)
- **Weather Forecast** (for each day)
- **Activities** (with times, costs, duration)
- **Accommodations** (matched to budget tier)
- **Meals** (breakfast, lunch, dinner availability)
- **Total Estimated Cost** (with currency)

---

## 🛎️ Customer Enquiries & Bookings

### Customers Interested in Your Services?

```bash
curl -X POST "https://api.tsoka.travel/enquiries" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 42,
    "service_type": "hotel",
    "customer_name": "Jane Smith",
    "customer_email": "jane@example.com",
    "customer_phone": "+1234567890",
    "check_in": "2026-07-01",
    "check_out": "2026-07-05",
    "guests": 2,
    "message": "Are rooms available for early check-in?"
  }'
```

### Track Bookings

```bash
curl -X GET "https://api.tsoka.travel/bookings?status=confirmed&per_page=25" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE"
```

---

## 📱 Multi-Channel Support

The chatbot works across multiple channels. Set up WhatsApp, Facebook, or Telegram:

### Enable WhatsApp
```bash
curl -X PUT "https://api.tsoka.travel/concierge/config" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{"whatsapp_enabled": true}'
```

Customers can now chat via:
- **Web**: Your website widget  
- **WhatsApp**: Direct to your business number
- **Facebook**: Your Facebook Page Messenger
- **Telegram**: Your Telegram Bot

---

## 🔐 Security & Rate Limits

### Authentication
All requests use Bearer token authentication:
```
Authorization: Bearer sk_live_YOUR_API_KEY
```

### Rate Limits
- **Chatbot Messages:** 60 messages/minute per vendor
- **Trip Generation:** 10 requests/minute
- **Other Endpoints:** 100 requests/minute

### Data Privacy
✅ Only your vendor's data is accessible  
✅ Customer conversations encrypted at rest  
✅ API key never exposed in browser (use server-side requests)  
✅ CORS configured for your registered domains only

---

## 📊 Manage Your Conversations

### List All Conversations
```bash
curl -X GET "https://api.tsoka.travel/concierge/conversations?status=active" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE"
```

### Get Full Conversation with Messages
```bash
curl -X GET "https://api.tsoka.travel/concierge/conversations/42" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE"
```

### View Statistics
```bash
curl -X GET "https://api.tsoka.travel/concierge/statistics?period=30days" \
  -H "Authorization: Bearer sk_live_YOUR_KEY_HERE"
```

Returns:
- Total conversations this month
- Messages by channel (web, WhatsApp, Facebook, Telegram)
- Conversations → Bookings conversion rate

---

## 🛠️ Integration Examples

### React Component (Chatbot)
```javascript
import { useState } from 'react';

export default function TsokaChatbot() {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');

  const sendMessage = async () => {
    const response = await fetch('https://api.tsoka.travel/concierge/message', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer sk_live_YOUR_KEY',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        guest_email: 'customer@example.com',
        message: input,
        channel: 'web'
      })
    });
    
    const data = await response.json();
    setMessages([...messages, 
      { role: 'user', content: input },
      { role: 'assistant', content: data.assistant_message.content }
    ]);
    setInput('');
  };

  return (
    <div>
      {messages.map((msg, i) => (
        <p key={i}><strong>{msg.role}:</strong> {msg.content}</p>
      ))}
      <input value={input} onChange={(e) => setInput(e.target.value)} />
      <button onClick={sendMessage}>Send</button>
    </div>
  );
}
```

### HTML/JavaScript (Embed Widget)
```html
<script src="https://widget.tsoka.travel/chatbot.js" 
  data-api-key="sk_live_YOUR_KEY"
  data-position="bottom-right"
  data-color="#FF6B35">
</script>
```

---

## 📖 Complete API Reference

For detailed endpoint documentation, see **`tsoka-api-swagger.json`**

Import into Postman, Swagger UI, or ReDoc:
```
https://api.tsoka.travel/openapi.json
```

---

## ❓ Common Questions

**Q: Can I use my own chatbot name?**  
A: Yes! Set `chatbot_name` in `/concierge/config`. Customers see your name, not "Tsoka".

**Q: Does the widget work on mobile?**  
A: Yes! Widget is fully responsive. Full-screen on mobile, floating bubble on desktop.

**Q: Can I customize the trip itinerary?**  
A: The Tanova engine is deterministic (reproducible packages), but you can extend with webhooks for custom business logic.

**Q: How do customers find my chatbot?**  
A: Embed the widget on your website. Or integrate WhatsApp/Telegram links so they chat on their preferred platform.

**Q: What happens to conversation history?**  
A: Stored securely in your vendor account. You can download or export via the portal.

---

## 🤝 Support

- **Email:** developer@tsoka.travel
- **Portal:** https://portal.tsokatravel.com/help
- **Status Page:** https://status.tsoka.travel

---

**Version:** 2.0.0 | **Last Updated:** 2026-05-31 | **License:** Proprietary
