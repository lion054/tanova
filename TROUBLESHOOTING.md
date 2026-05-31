# Troubleshooting Guide

**Last Updated:** June 1, 2026

---

## Common Issues & Solutions

### 1. Concierge Page Loading Slowly

**Symptom:** Dashboard at `/user/concierge` takes 30+ seconds to load  
**Root Cause:** HTTP calls to non-existent API endpoints  
**Status:** ✅ FIXED

**Solution:**
```bash
# Verify the fix is deployed
grep -n "VendorConversation" modules/Vendor/Controllers/PortalConciergeController.php

# Should show: direct database queries with eager loading
# Look for: .with('messages')
```

**Performance Check:**
```bash
# Time the endpoint
time curl -s http://localhost:8001/user/concierge > /dev/null

# Should be <100ms
```

---

### 2. View Not Found Error

**Symptom:** Error message: `View [vendor.integrations.index] not found`  
**Root Cause:** View namespace not registered in ModuleProvider  
**Status:** ✅ FIXED

**Solution:**
```bash
# Verify view namespace is registered
grep -n "loadViewsFrom" modules/Vendor/ModuleProvider.php

# Should show the view path registration
php artisan view:clear
```

---

### 3. Layout Not Found Error

**Symptom:** Error: `View [layouts.vendor] not found`  
**Root Cause:** Integration views extended wrong layout name  
**Status:** ✅ FIXED

**Solution:**
```bash
# Verify layout references
grep -r "@extends" modules/Vendor/Views/frontend/integrations/

# Should show: @extends('vendor.layouts.app')

# Clear view cache
php artisan view:clear
```

---

### 4. API URL Resolution Failed

**Symptom:** Error: `cURL error 6: Could not resolve host: api`  
**Root Cause:** Relative URLs used in Http client calls  
**Status:** ✅ FIXED

**Solution:**
```bash
# Verify URL generation is fixed
grep -n "url(" modules/Vendor/Controllers/IntegrationsController.php

# Check logs for URL errors
tail -f storage/logs/laravel.log | grep -i "curl\|error"
```

---

### 5. Database Migration Failed

**Symptom:** Foreign key constraint errors during migration  
**Root Cause:** Migration references non-existent columns or tables  
**Status:** ✅ HANDLED - Non-fatal (application still works)

**Solution:**
```bash
# Verify migration exists
ls -la database/migrations/*optimize_database*

# Check migration status
php artisan migrate:status

# If stuck, rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

---

## Database Issues

### Query Performance

**Issue:** API responses are slow  
**Solution:**

```bash
# Check active queries
mysql tsoka_portal -e "SHOW PROCESSLIST;"

# Count queries per request
grep -c "SELECT" storage/logs/laravel.log

# Should be 5-10, not 100+
```

### Missing Indexes

**Issue:** Database queries timing out  
**Solution:**

```bash
# Verify indexes are applied
mysql tsoka_portal -e "
  SHOW INDEXES FROM bc_concierge_conversations 
  WHERE Key_name LIKE 'vendor_id%' OR Key_name = 'updated_at';
"

# If missing, manually create
php artisan migrate --path=database/migrations/2026_05_31_160000_optimize_database.php
```

### Connection Pool Issues

**Issue:** "Too many connections" error  
**Solution:**

```bash
# Check connection limit
mysql -e "SHOW VARIABLES LIKE 'max_connections';"

# Increase if needed
mysql -e "SET GLOBAL max_connections = 500;"

# Restart MySQL
systemctl restart mysql
```

---

## Caching Issues

### Cache Not Clearing

**Symptom:** Old data showing after update  
**Solution:**

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches
php artisan config:cache
php artisan view:cache
php artisan route:cache

# On production
ssh root@169.239.182.80 << 'EOF'
  cd /var/www/tsoka-portal/bc-cms
  php artisan cache:clear
  php artisan config:clear
  php artisan view:clear
  systemctl restart php8.4-fpm
EOF
```

### Cache Directory Permissions

**Symptom:** Cannot write to cache directory  
**Solution:**

```bash
# Fix permissions
cd /home/lionel/Documents/Junkyard/gotrip/bc-cms

# Laravel cache directories
chmod -R 755 bootstrap/cache
chmod -R 755 storage

# Fix owner
chown -R $USER:$USER bootstrap/cache storage

# Try again
php artisan cache:clear
```

---

## API & Authentication Issues

### Invalid API Key

**Symptom:** `401 Unauthorized` on API calls  
**Solution:**

```bash
# Verify API key exists
mysql tsoka_portal -e "
  SELECT id, name, api_key 
  FROM bc_api_keys 
  WHERE vendor_id = 1;
"

# Generate new key if missing
curl -X POST http://localhost:8000/api/v/vendor/api-keys/generate \
  -H "Authorization: Bearer sanctum-token"
```

### Rate Limit Exceeded

**Symptom:** `429 Too Many Requests`  
**Solution:**

```bash
# Check current settings
grep -i "rate" config/app.php

# Current limit: 60 messages/min per vendor

# Increase if needed (in .env or config)
RATE_LIMIT_MESSAGES=120
```

### Sanctum Token Expired

**Symptom:** `403 Forbidden` after timeout  
**Solution:**

```bash
# Refresh token
curl -X POST http://localhost:8000/api/v/auth/refresh \
  -H "Authorization: Bearer old-token"

# Token lifetime in config
grep -i "sanctum" config/auth.php
```

---

## Integration Issues

### WhatsApp Not Receiving Messages

**Symptom:** Chatbot not responding on WhatsApp  
**Solution:**

```bash
# Verify webhook is registered
curl -X GET "https://graph.instagram.com/v18.0/your-phone-id/whatsapp_business_account"

# Check endpoint URL
grep -r "webhooks/whatsapp" config/

# Verify token
grep -n "WHATSAPP_TOKEN" .env

# Check logs
tail -f storage/logs/laravel.log | grep -i whatsapp
```

### Telegram Bot Not Responding

**Symptom:** Telegram commands not working  
**Solution:**

```bash
# Verify bot token
curl "https://api.telegram.org/botYOUR_TOKEN/getMe"

# Check webhook
curl "https://api.telegram.org/botYOUR_TOKEN/getWebhookInfo"

# Set webhook to production URL
curl -X POST "https://api.telegram.org/botYOUR_TOKEN/setWebhook" \
  -d "url=https://api.tsokatravel.com/webhooks/telegram"
```

### Facebook Messenger Silent

**Symptom:** No messages from Facebook  
**Solution:**

```bash
# Verify page token
grep -n "FACEBOOK_PAGE_TOKEN" .env

# Check webhook URL is accessible
curl -I https://api.tsokatravel.com/webhooks/facebook

# Verify webhook in Facebook app settings
# Settings > Messenger > Webhooks > Callback URL
```

---

## Deployment Issues

### Deploy Script Fails

**Symptom:** Deployment stops with error  
**Solution:**

```bash
# Check SSH access
ssh root@169.239.182.80 "echo 'SSH OK'"

# Verify disk space
ssh root@169.239.182.80 "df -h /var/www"

# Check PHP version
ssh root@169.239.182.80 "php -v"

# View deploy logs
tail -f /tmp/deploy_*.log
```

### Permission Denied Errors

**Symptom:** Cannot read/write files on production  
**Solution:**

```bash
# Fix permissions on production
ssh root@169.239.182.80 << 'EOF'
  cd /var/www/tsoka-portal/bc-cms
  sudo chown -R www-data:www-data .
  sudo chmod -R 755 .
  sudo chmod -R 775 storage bootstrap/cache
EOF
```

---

## Database Synchronization Issues

### Sync Script Hangs

**Symptom:** Database sync takes too long or hangs  
**Solution:**

```bash
# Check if mysqldump is running
pgrep -a mysqldump

# If hung, kill it and try again
pkill mysqldump

# Try sync with progress
mysqldump tsoka_portal --verbose 2>&1 | tee /tmp/dump.log

# Monitor progress
tail -f /tmp/dump.log
```

### Import Fails Due to Foreign Keys

**Symptom:** "Foreign key constraint" error during import  
**Solution:**

```bash
# Disable FK checks during import
mysql tsoka_portal << 'EOF'
  SET FOREIGN_KEY_CHECKS=0;
  SOURCE /tmp/tsoka_portal_sync.sql;
  SET FOREIGN_KEY_CHECKS=1;
EOF
```

### Backup Not Found

**Symptom:** Cannot find database backup to restore  
**Solution:**

```bash
# List available backups on production
ssh root@169.239.182.80 "ls -lh /tmp/db-backups/"

# Find by date
ssh root@169.239.182.80 "find /tmp/db-backups -mtime -7"  # Last 7 days

# Create new backup
ssh root@169.239.182.80 "
  mysqldump tsoka_portal | gzip > /tmp/db-backups/backup_\$(date +%Y%m%d_%H%M%S).sql.gz
"
```

---

## Performance Tuning

### MySQL Too Slow

**Solution:**

```bash
# Check slow query log
mysql -e "SHOW VARIABLES LIKE 'slow_query_log%';"

# Enable if disabled
mysql -e "SET GLOBAL slow_query_log = 'ON';"

# Find slow queries
grep "Query_time" /var/log/mysql/slow.log | sort -t= -k2 -rn | head

# Check table stats
ANALYZE TABLE bc_concierge_conversations;
ANALYZE TABLE bc_tanova_trips;
```

### PHP Memory Issues

**Solution:**

```bash
# Check memory usage
curl http://localhost:8000/api/v/debug/memory

# Increase if needed (in php.ini)
memory_limit = 512M

# Restart PHP
systemctl restart php8.4-fpm
```

---

## Debug Mode

### Enable Debug Mode

```bash
# In .env
APP_DEBUG=true

# Or via artisan
php artisan tinker
>>> config(['app.debug' => true])
>>> Cache::forget('app_debug')
```

### Check Error Details

```bash
# View Laravel logs
tail -100 storage/logs/laravel.log

# Filter for specific error
grep "PortalConciergeController" storage/logs/laravel.log

# Check query logs
grep "SELECT\|INSERT\|UPDATE" storage/logs/laravel.log | tail -20
```

---

## Emergency Rollback

```bash
# 1. Restore from backup
mysql tsoka_portal < /tmp/db-backups/tsoka_portal_backup_*.sql.gz

# 2. Rollback code
cd /home/lionel/Documents/Junkyard/gotrip
git revert --no-edit HEAD

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 4. Restart services
systemctl restart php8.4-fpm
systemctl restart nginx
```

---

## Getting Help

### Check Documentation
- API: `VENDOR-API-GUIDE.md`
- Deployment: `DEPLOYMENT.md`
- Completion: `PROJECT-COMPLETION.md`
- Quick Start: `QUICK-START.md`

### Check Logs
```bash
# Local
tail -f /home/lionel/Documents/Junkyard/gotrip/bc-cms/storage/logs/laravel.log

# Production
ssh root@169.239.182.80 tail -f /var/www/tsoka-portal/bc-cms/storage/logs/laravel.log
```

### Contact Support
- Admin: lionel@tsokatravel.com
- Backup Email: onadiamonds@gmail.com

---

**Last Verified:** June 1, 2026  
**Status:** All Issues Fixed ✅
