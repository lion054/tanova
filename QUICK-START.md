# Tsoka Portal - Quick Start Guide

**Last Updated:** June 1, 2026  
**Status:** Production Ready ✅

---

## 🌐 Access & URLs

### Production Environment
- **Portal:** https://portal.tsokatravel.com
- **Admin Email:** lionel@tsokatravel.com
- **Database:** tsoka_portal (169.239.182.80)

### Local Development
- **API Server:** http://localhost:8000
- **Portal Server:** http://localhost:8001
- **Database:** tsoka_portal (127.0.0.1)

---

## 🔑 Key Features

### 1. AI Concierge Chatbot
- **Endpoint:** `/api/v/concierge/message`
- **Multi-channel:** Web, WhatsApp, Facebook, Telegram
- **State Machine:** Destination → Dates → Travelers → Budget → Confirm → Generate
- **Rate Limit:** 60 messages/min per vendor

### 2. Tanova Trip Planner
- **Endpoint:** `/api/v/tanova/trips`
- **Duration Matching:** Shows pre-built tours 70%-100% of requested days
- **Status:** ✅ Fully operational

### 3. Vendor API Keys
- **Endpoint:** `/vendor/api-keys`
- **Purpose:** Manage integrations and authentication
- **Security:** Bearer token via Sanctum

### 4. Integrations
- **WhatsApp Business API**
- **Facebook Messenger**
- **Telegram Bot API**
- **Web Widget**

---

## 📱 Quick Integration Examples

### Web Widget
```html
<!-- Embed in vendor website -->
<div id="tsoka-chatbot"></div>
<script>
  const config = {
    apiKey: 'your-vendor-api-key',
    color: '#0066cc',
    position: 'bottom-right'
  };
  
  // Load widget
  fetch('/api/v/concierge/widget-embed', {
    headers: { 'Authorization': `Bearer ${config.apiKey}` }
  })
  .then(r => r.text())
  .then(html => document.getElementById('tsoka-chatbot').innerHTML = html);
</script>
```

### WhatsApp Integration
```bash
# Setup via portal
1. Go to https://portal.tsokatravel.com/user/integrations/whatsapp
2. Add your WhatsApp Business Phone Number
3. Add Webhook URL: https://api.tsokatravel.com/webhooks/whatsapp
4. Verify token and save
```

### Facebook Messenger Integration
```bash
# Setup via portal
1. Go to https://portal.tsokatravel.com/user/integrations/facebook
2. Add your Facebook Page ID
3. Add Page Access Token
4. Save configuration
```

### Telegram Integration
```bash
# Setup via portal
1. Go to https://portal.tsokatravel.com/user/integrations/telegram
2. Add your Telegram Bot Token (from @BotFather)
3. Add Webhook URL: https://api.tsokatravel.com/webhooks/telegram
4. Save configuration
```

---

## 🗄️ Database Tables

### Core Tables
- `bc_users` - User accounts
- `bc_concierge_conversations` - Chat conversations (vendor-isolated)
- `bc_concierge_messages` - Chat messages
- `bc_tours` - Activities/tours
- `bc_locations` - Destinations

### Tanova Tables
- `bc_tanova_trips` - Pre-built trip packages
- `bc_tanova_accommodations` - Hotels/stays
- `bc_tanova_daily_itineraries` - Day-by-day plans

### Indexes
- `vendor_id + status` on conversations
- `vendor_id + channel` on conversations
- `conversation_id + created_at` on messages
- `location_id + status` on tours
- `is_package` on tours

---

## 🚀 Deployment

### Local Development
```bash
cd /home/lionel/Documents/Junkyard/gotrip/bc-cms
php artisan serve --port=8000
php artisan serve --port=8001
```

### Remote Deployment
```bash
cd /home/lionel/Documents/Junkyard/gotrip
chmod +x deploy-portal.sh
./deploy-portal.sh
```

### Database Sync
```bash
# Sync zones/experiences only
./sync-db.sh

# Full database sync
./sync-full-db.sh
```

---

## 🔒 Security

### API Authentication
```bash
# Get vendor API key
curl -H "Authorization: Bearer your-sanctum-token" \
  https://portal.tsokatravel.com/api/v/vendor/api-keys

# Use in requests
curl -H "Authorization: Bearer your-api-key" \
  https://api.tsokatravel.com/api/v/concierge/message \
  -d '{"message": "Book a trip to Paris"}'
```

### Multi-Tenancy
- All queries filtered by `vendor_id`
- Each vendor isolated from others
- Credentials stored encrypted
- Rate limiting per vendor

---

## 🛠️ Troubleshooting

### Concierge Dashboard Slow
✅ **Fixed** - Now uses direct database queries (~100ms)

### View Not Found Errors
✅ **Fixed** - Views properly namespaced in ModuleProvider

### API Timeout Errors
✅ **Fixed** - Removed HTTP calls to non-existent endpoints

### Tanova Integration Issues
✅ **Preserved** - Duration matching with `is_package` column

---

## 📊 Performance

- **Concierge Load:** <100ms (was 30s)
- **API Response:** <200ms typical
- **DB Queries:** 5-10 per request (was 100+)
- **Cache Hit Rate:** >90%

---

## 📞 Support

### Documentation Files
- `VENDOR-API-GUIDE.md` - Vendor API documentation
- `DEPLOYMENT.md` - Deployment procedures
- `DEPLOYMENT-SUMMARY.md` - Performance metrics
- `PROJECT-COMPLETION.md` - Project overview

### Contact
- **Admin:** lionel@tsokatravel.com
- **User Email:** onadiamonds@gmail.com

---

## 📋 Monitoring

### Key Metrics to Watch
```bash
# Check response times
curl -i https://portal.tsokatravel.com/user/concierge

# Monitor database
mysql tsoka_portal -e "SHOW PROCESSLIST;"

# Check logs
tail -f /var/www/tsoka-portal/bc-cms/storage/logs/laravel.log
```

### Cache Status
```bash
# On production server
ssh root@169.239.182.80

# Check caches
ls -la /var/www/tsoka-portal/bc-cms/bootstrap/cache/

# Clear if needed
cd /var/www/tsoka-portal/bc-cms
php artisan cache:clear
php artisan view:clear
```

---

## ✅ Checklist

### Before Going Live
- [x] Database deployed
- [x] Migrations applied
- [x] Indexes created
- [x] Caches built
- [x] Environment configured
- [x] API tested
- [x] Portal verified

### Daily Checks
- [ ] Error logs clean
- [ ] API responding <200ms
- [ ] Database queries <10 per request
- [ ] All integrations working
- [ ] Vendor conversations flowing

---

**🎉 Ready for production use!**

For detailed information, see:
- API Integration → VENDOR-API-GUIDE.md
- Deployment Steps → DEPLOYMENT.md  
- Performance Metrics → DEPLOYMENT-SUMMARY.md
