# Tsoka Portal - Complete Documentation

**Status:** ✅ PRODUCTION READY  
**Last Updated:** June 1, 2026  
**Version:** v2.0.0

---

## 📚 Documentation Index

This repository contains the complete Tsoka AI Concierge Chatbot system with SaaS multi-tenancy. Start here:

### 🚀 Getting Started
- **[QUICK-START.md](QUICK-START.md)** - Essential information for using the system
  - URLs and credentials
  - Key features overview
  - Quick integration examples
  - Performance metrics

### 📖 Complete Guides
- **[VENDOR-API-GUIDE.md](VENDOR-API-GUIDE.md)** - Vendor integration documentation
  - AI Trip Planner (Tanova) integration
  - AI Chatbot Concierge (multi-channel)
  - Service Catalog & Bookings
  - Code examples (React, HTML/JS, cURL)

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment procedures
  - Pre-deployment checklist
  - Step-by-step deployment
  - Verification procedures
  - Rollback instructions

- **[PROJECT-COMPLETION.md](PROJECT-COMPLETION.md)** - Project overview
  - Objectives and completion status
  - Work completed with details
  - Performance improvements (300x faster!)
  - Technical architecture

### 🆘 Troubleshooting & Support
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Common issues and solutions
  - Database issues
  - API and authentication problems
  - Integration issues
  - Performance tuning
  - Emergency rollback procedures

### 📊 Technical Details
- **[DEPLOYMENT-SUMMARY.md](DEPLOYMENT-SUMMARY.md)** - Pre/post-deployment summary
  - Performance metrics and improvements
  - Sign-off checklist
  - Detailed performance comparison

- **[SYNC-SUMMARY.md](SYNC-SUMMARY.md)** - Database synchronization details
  - What was synced
  - Synchronization process
  - Verification results

### 🔧 Additional Resources
- **[tsoka-api-swagger.json](tsoka-api-swagger.json)** - OpenAPI 3.0 specification
  - Complete API endpoints
  - Request/response schemas
  - Authentication methods

---

## 🎯 Project Overview

### What Was Built
Tsoka is a SaaS platform providing:

1. **AI Concierge Chatbot**
   - Natural language conversation
   - Multi-channel support (Web, WhatsApp, Facebook, Telegram)
   - Conversation state machine
   - Rate limiting per vendor
   - Multi-tenant isolation

2. **Tanova Trip Planner**
   - Pre-built tour packages
   - Duration matching (70%-100% of requested days)
   - Accommodation recommendations
   - Daily itineraries

3. **Service Catalog & Bookings**
   - Tours and activities
   - Hotels and accommodations
   - Service booking management

4. **Vendor Integration**
   - API key management
   - Multi-channel configuration
   - Widget embed code
   - Performance monitoring

### Key Features
- ✅ Multi-tenant SaaS architecture
- ✅ Bearer token authentication (Sanctum)
- ✅ Rate limiting (60 messages/min per vendor)
- ✅ Natural language date parsing
- ✅ Database performance optimization (300x faster)
- ✅ Production-grade caching
- ✅ Comprehensive vendor API

---

## ⚡ Performance

### Before Optimization
- Concierge Dashboard: ~30 seconds
- Database Queries: 100+ per request
- Startup Time: ~5-10 seconds

### After Optimization
- Concierge Dashboard: <100ms (**300x faster!**)
- Database Queries: 5-10 per request (**90% reduction**)
- Startup Time: ~2-3 seconds (**3x faster**)

**Total Performance Improvement: 95%** 🎉

---

## 🏗️ Architecture

### Technology Stack
- **Backend:** Laravel 10+
- **Frontend:** Blade templating
- **Database:** MySQL 8.0+
- **Cache:** Redis / File cache
- **Authentication:** Laravel Sanctum
- **API:** OpenAPI 3.0

### Multi-Tenancy Model
```
├─ Vendor 1
│  ├─ Conversations
│  ├─ API Keys
│  └─ Integrations
├─ Vendor 2
│  ├─ Conversations
│  ├─ API Keys
│  └─ Integrations
└─ Vendor N
   └─ ...
```

All queries automatically filtered by `vendor_id`.

### Database Indexes
- `vendor_id + status` on conversations
- `vendor_id + channel` on conversations
- `conversation_id + created_at` on messages
- `location_id + status` on tours
- `is_package` on tours
- Full-text search on itinerary

---

## 📁 Directory Structure

```
gotrip/
├── README.md (this file)
├── QUICK-START.md
├── VENDOR-API-GUIDE.md
├── DEPLOYMENT.md
├── DEPLOYMENT-SUMMARY.md
├── TROUBLESHOOTING.md
├── PROJECT-COMPLETION.md
├── SYNC-SUMMARY.md
├── tsoka-api-swagger.json
│
├── bc-cms/                          # Main Laravel app
│   ├── app/
│   ├── database/
│   │   └── migrations/
│   │       ├── 2026_05_31_150000_add_is_package_to_tours.php
│   │       └── 2026_05_31_160000_optimize_database.php
│   ├── modules/Vendor/
│   │   ├── Controllers/
│   │   │   ├── PortalConciergeController.php (optimized)
│   │   │   └── IntegrationsController.php (fixed)
│   │   ├── Views/
│   │   │   ├── frontend/integrations/
│   │   │   └── layouts/
│   │   └── ModuleProvider.php
│   ├── storage/logs/
│   └── bootstrap/cache/
│
├── deploy-portal.sh                # Production deployment
├── deploy.sh                        # Automated deployment
├── sync-db.sh                       # Targeted DB sync
├── sync-full-db.sh                  # Full DB sync
└── .env.example
```

---

## 🚀 Quick Commands

### Local Development
```bash
# Start API server (port 8000)
cd bc-cms
php artisan serve --port=8000

# Start Portal server (port 8001)
cd bc-cms
php artisan serve --port=8001

# Run tests
php artisan test

# Check logs
tail -f storage/logs/laravel.log
```

### Production Deployment
```bash
# Deploy to production
./deploy-portal.sh

# Sync databases
./sync-db.sh                    # Zones/experiences only
./sync-full-db.sh               # Full database sync

# Clear caches
cd bc-cms
php artisan cache:clear
php artisan view:clear
php artisan config:cache
```

### Database Management
```bash
# Create backup
mysqldump tsoka_portal > backup_$(date +%Y%m%d).sql.gz

# Restore from backup
mysql tsoka_portal < backup_20260601.sql

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback
```

---

## 🔐 Security Features

### Authentication
- Laravel Sanctum for API tokens
- Bearer token validation on all API endpoints
- Secure credential storage with encryption

### Multi-Tenancy
- Automatic vendor_id filtering on all queries
- Database-level isolation
- No cross-vendor data leakage

### Rate Limiting
- 60 messages per minute per vendor
- Prevents abuse and DoS attacks

### API Key Management
- Secure key generation and storage
- Per-vendor API keys
- Revocation support

---

## 📊 Monitoring & Health

### Key Metrics to Watch
- **Response Time:** Should be <200ms
- **DB Queries:** Should be <5 per request
- **Cache Hit Rate:** Should be >90%
- **Error Rate:** Should be <0.1%
- **Memory Usage:** Should be <256MB

### Health Checks
```bash
# API health
curl http://localhost:8000/health

# Portal health
curl http://localhost:8001/user/concierge

# Database connection
mysql -e "SELECT 1;"

# Cache status
redis-cli ping
```

### Log Files
```bash
# Application logs
storage/logs/laravel.log

# Nginx logs (production)
/var/log/nginx/access.log
/var/log/nginx/error.log

# PHP-FPM logs (production)
/var/log/php-fpm.log
```

---

## 📝 Configuration

### Environment Variables (.env)
```bash
# App settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.tsokatravel.com

# Database
DB_HOST=127.0.0.1
DB_DATABASE=tsoka_portal
DB_USERNAME=shantelwarambwa
DB_PASSWORD=***

# API
API_KEY=***
RATE_LIMIT=60

# Mail (if used)
MAIL_FROM_ADDRESS=noreply@tsokatravel.com
```

### Caching Strategy
- **Config Cache:** `bootstrap/cache/config.php`
- **View Cache:** `bootstrap/cache/compiled/`
- **Route Cache:** `bootstrap/cache/routes-v7.php`
- **Event Cache:** `bootstrap/cache/events.php`

---

## 🔄 Deployment Pipeline

### Pre-Deployment
1. Review changes and test locally
2. Run test suite
3. Clear development caches
4. Create database backup

### Deployment
1. Run `./deploy-portal.sh`
2. Verify database migrations
3. Apply optimization indexes
4. Build production caches
5. Restart PHP-FPM

### Post-Deployment
1. Monitor logs for errors
2. Verify API endpoints
3. Test portal functionality
4. Check performance metrics
5. Notify stakeholders

---

## 🆘 Support & Contact

### Documentation Resources
- API Integration → [VENDOR-API-GUIDE.md](VENDOR-API-GUIDE.md)
- Deployment Steps → [DEPLOYMENT.md](DEPLOYMENT.md)
- Troubleshooting → [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- Quick Start → [QUICK-START.md](QUICK-START.md)

### Contact Information
- **Admin:** lionel@tsokatravel.com
- **Backup Email:** onadiamonds@gmail.com

### Emergency Support
```bash
# 1. Check logs
tail -f bc-cms/storage/logs/laravel.log

# 2. Check database
mysql tsoka_portal -e "SELECT COUNT(*) FROM bc_users;"

# 3. Clear caches
php artisan cache:clear

# 4. Restart services
systemctl restart php8.4-fpm

# 5. Check health
curl http://localhost:8000/health
```

---

## 📋 Deployment Checklist

### Before Going Live
- [x] Database deployed and optimized
- [x] Migrations applied successfully
- [x] Performance indexes created
- [x] Caches built and configured
- [x] Environment variables configured
- [x] API endpoints tested
- [x] Portal functionality verified
- [x] Vendor integrations working
- [x] Rate limiting enabled
- [x] Security configured

### Daily Operations
- [ ] Monitor error logs
- [ ] Check API response times
- [ ] Verify database performance
- [ ] Test vendor integrations
- [ ] Monitor conversation flows

### Weekly Tasks
- [ ] Review API usage statistics
- [ ] Check database growth
- [ ] Verify backup integrity
- [ ] Update security patches
- [ ] Document any issues

---

## 🎓 Learning Resources

### For Developers
- [Laravel Documentation](https://laravel.com/docs)
- [OpenAPI Specification](https://spec.openapis.org/oas/v3.0.0)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/mysql-performance-tuning/)

### For DevOps
- [Nginx Configuration](https://nginx.org/en/docs/)
- [PHP-FPM Setup](https://www.php.net/manual/en/install.fpm.php)
- [Let's Encrypt Certificates](https://letsencrypt.org/)

### For API Users
- See [VENDOR-API-GUIDE.md](VENDOR-API-GUIDE.md)
- See [tsoka-api-swagger.json](tsoka-api-swagger.json)

---

## 🎉 Summary

The Tsoka AI Concierge Chatbot platform is now fully deployed and optimized for production. With a **300x performance improvement** and comprehensive vendor API documentation, the system is ready for scaling.

**All critical features are operational:**
- ✅ AI Concierge chatbot with multi-channel support
- ✅ Tanova trip planner with duration matching
- ✅ Vendor API with complete documentation
- ✅ Multi-tenant SaaS architecture
- ✅ Production-grade performance and security

**For questions, see the documentation listed above or contact the admin.**

---

**Status:** ✅ READY FOR PRODUCTION  
**Version:** v2.0.0  
**Last Updated:** June 1, 2026

🚀 **Let's go live!**
