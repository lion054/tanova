# Tsoka Portal - Production Deployment Checklist

**Project**: Tsoka Travel Portal
**Date**: 2026-05-31
**Version**: 1.0.0

---

## ✅ Code Quality & Testing

- [x] **Test Suite Created**
  - [x] Trip generation tests (8 packages, all zone 1 activities)
  - [x] Vendor isolation tests (data access control)
  - [x] Booking and enquiry tests
  - Files: `tests/Feature/Tanova/*`, `tests/Feature/Bookings/*`

- [x] **Load Tests Created**
  - [x] Concurrent request testing (10, 25, 50 users)
  - [x] Sustained load testing (5 req/sec for 5 minutes)
  - File: `tests/Load/TanovaLoadTest.php`
  - **Action**: Run before deployment
    ```bash
    php tests/load-test.php
    # All tests must show >95% success rate and P95 < 1000ms
    ```

- [x] **Code Standards**
  - [ ] Run `php artisan pint` for code formatting
  - [ ] Run `php artisan test` for all tests
  - [ ] Zero errors/failures required before merge

---

## ✅ Infrastructure & Configuration

- [x] **Database**
  - [x] Migrations created (`add_vendor_id_to_tanova_trips`)
  - [x] Backup script configured
  - [ ] Test restore from backup
    ```bash
    /var/www/tsoka-portal/scripts/backup-database.sh
    # Verify backup exists in S3
    ```

- [x] **Caching & Queues**
  - [x] Redis configured for cache (`CACHE_STORE=redis`)
  - [x] Async queue configured (`QUEUE_CONNECTION=redis`)
  - [x] Job created for trip generation (`GenerateTripJob`)
  - [ ] Test queue processing
    ```bash
    php artisan queue:work redis --queue=trips --tries=3
    ```

- [x] **Email Service**
  - [x] Mailtrap configured (`.env` settings)
  - [ ] Test email sending
    ```bash
    php artisan tinker
    > Mail::raw('Test', fn($m) => $m->to('test@example.com'))->send()
    # Check Mailtrap inbox at https://mailtrap.io
    ```

- [x] **API Security**
  - [x] Rate limiting middleware created (30-100 req/min per endpoint)
  - [x] Security headers middleware created (CSP, HSTS, etc.)
  - [ ] Register middleware in `app/Http/Kernel.php`
    ```php
    'api' => [
        \App\Http\Middleware\ApiSecurityHeaders::class,
        \App\Http\Middleware\ApiRateLimiting::class,
    ],
    ```

---

## ✅ Monitoring & Observability

- [x] **Sentry Configuration**
  - [x] Config file created (`config/sentry.php`)
  - [ ] Enable Sentry in production
    ```bash
    SENTRY_LARAVEL_DSN=https://..@sentry.io/...
    php artisan config:cache
    ```
  - [ ] Verify errors are captured
    - Generate test error: trigger 500 in code
    - Check Sentry dashboard: https://sentry.io/

- [x] **Logging**
  - [x] Daily log rotation configured
  - [x] Queue failures logged
  - [x] Trip generation errors logged
  - [ ] Monitor logs in production
    ```bash
    tail -f /var/log/tsoka/laravel.log | grep -i error
    ```

- [ ] **Performance Monitoring**
  - [ ] Set up Grafana/Datadog dashboards (optional)
  - [ ] Define SLOs:
    - API P95 response: < 1000ms
    - Trip generation: < 30s
    - Trip availability: > 99.9%

---

## ✅ Documentation

- [x] **API Documentation**
  - [x] Swagger/OpenAPI specs complete
  - [x] Files: `tsoka-api-swagger.json`, `tsoka-api-swagger.yaml`
  - [ ] Host on docs.portal.tsokatravel.com (optional)

- [x] **Production Runbook**
  - [x] Deployment procedures documented
  - [x] Common issues & solutions documented
  - [x] Incident response procedures documented
  - [x] Backup & recovery procedures documented
  - File: `PRODUCTION_RUNBOOK.md`

- [x] **Environment Setup**
  - [x] `.env.example` updated with all required variables
  - [ ] Before deployment, populate `.env` with:
    ```
    MAIL_USERNAME=<Mailtrap username>
    MAIL_PASSWORD=<Mailtrap password>
    SENTRY_LARAVEL_DSN=<your-sentry-dsn>
    ANTHROPIC_API_KEY=<Claude API key>
    AWS_ACCESS_KEY_ID=<for S3 backups>
    AWS_SECRET_ACCESS_KEY=<for S3 backups>
    ```

---

## ✅ Security Hardening

- [x] **API Security**
  - [x] Authentication via Bearer token (API keys)
  - [x] Rate limiting per endpoint
  - [x] Vendor isolation (queries scoped by vendor_id)
  - [x] Security headers (CSP, HSTS, X-Frame-Options, etc.)

- [ ] **Secrets Management**
  - [ ] Store secrets in environment (never in code)
  - [ ] API keys rotated quarterly
  - [ ] Database credentials in secure vault
  - [ ] .env file not committed to git

- [ ] **SSL/TLS**
  - [ ] SSL certificate installed (Let's Encrypt)
  - [ ] Auto-renewal configured
  - [ ] Redirect HTTP → HTTPS

- [ ] **Database Security**
  - [ ] Strong password for DB user
  - [ ] Bind MySQL to localhost only
  - [ ] Regular backups encrypted
  - [ ] Slow query log enabled

---

## 📋 Pre-Deployment Tasks

### 1 Week Before Launch
- [ ] Final staging deployment
- [ ] Full end-to-end testing
- [ ] Load test results reviewed (P95 < 1000ms)
- [ ] Team trained on runbook
- [ ] On-call schedule confirmed

### 1 Day Before Launch
- [ ] Database backup tested
- [ ] All services running on staging
- [ ] Monitoring alerts configured
- [ ] Incident response contacts updated
- [ ] Rollback plan reviewed

### Day of Launch
- [ ] Final code review & merge
- [ ] Build artifacts created
- [ ] Load balancer ready
- [ ] Status page configured
- [ ] Announcement scheduled

### Launch
```bash
# 1. Stop old instance
supervisorctl stop tsoka:*

# 2. Deploy new code
git pull origin main
composer install --no-dev --no-interaction
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Start services
supervisorctl start tsoka:*

# 6. Health check
curl -I https://portal.tsokatravel.com/health
# Should return 200 OK

# 7. Monitor for 30 minutes
# Watch: Sentry errors, API response times, queue depth
tail -f /var/log/tsoka/laravel.log
redis-cli
> DBSIZE  # Check queue depth
```

---

## 🚨 Rollback Plan

If critical issues occur within 1 hour of launch:

```bash
# 1. Stop current instance
supervisorctl stop tsoka:*

# 2. Restore previous code
git checkout <previous-tag>
composer install --no-dev

# 3. Restore previous database (if needed)
# Only if data corruption detected
# See PRODUCTION_RUNBOOK.md restore section

# 4. Restart services
supervisorctl start tsoka:*

# 5. Verify
curl https://portal.tsokatravel.com/health

# 6. Alert team & communicate status
# Post in #incidents channel
# Update status.portal.tsokatravel.com
```

---

## ✅ Post-Deployment Verification

- [ ] **Functionality Tests**
  - [ ] Trip generation works (POST /api/v/tanova/generate)
  - [ ] Trip list returns results (GET /api/v/tanova/trips)
  - [ ] Booking endpoints accessible
  - [ ] Concierge chat functional
  - [ ] Email notifications sending

- [ ] **Performance Checks**
  - [ ] API response times < 1000ms P95
  - [ ] Queue backlog processing normally
  - [ ] Database queries under 100ms
  - [ ] Cache hit rates > 80%

- [ ] **Monitoring Active**
  - [ ] Sentry receiving errors
  - [ ] Logs being written
  - [ ] Backups running successfully
  - [ ] Rate limits working

---

## 📞 Support & Escalation

**On-Call**: Check PagerDuty schedule
**Status Page**: https://status.portal.tsokatravel.com/
**Runbook**: `/home/lionel/Documents/Junkyard/gotrip/bc-cms/PRODUCTION_RUNBOOK.md`

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Deployment Lead | | | |
| QA Lead | | | |
| CTO/Tech Lead | | | |

---

**Last Updated**: 2026-05-31
**Status**: ✅ Ready for Staging Deployment
**Next Step**: Run load tests and staging E2E verification

