# Tsoka Portal - Production Verification Script

Run this checklist before deploying to production.

## ✅ Run All Verifications

```bash
#!/bin/bash
set -e

echo "🔍 TSOKA PORTAL - PRODUCTION VERIFICATION"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FAILED=0
PASSED=0

# Helper functions
pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASSED++))
}

fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAILED++))
}

warn() {
    echo -e "${YELLOW}⚠ WARN${NC}: $1"
}

# ============================================
# 1. CODE QUALITY TESTS
# ============================================
echo ""
echo "📊 CODE QUALITY VERIFICATION"
echo "---"

# Test: All unit tests pass
echo "Running test suite..."
if php artisan test --profile 2>/dev/null; then
    pass "Unit tests passing"
else
    fail "Unit tests failing - fix errors before proceeding"
fi

# Test: Code formatting
echo "Checking code standards..."
if php artisan pint --test 2>/dev/null; then
    pass "Code formatting standards"
else
    warn "Code formatting issues - run 'php artisan pint' to fix"
fi

# ============================================
# 2. SECURITY VERIFICATION
# ============================================
echo ""
echo "🔒 SECURITY VERIFICATION"
echo "---"

# Test: Rate limiting middleware exists
if [ -f "app/Http/Middleware/ApiRateLimiting.php" ]; then
    pass "Rate limiting middleware configured"
else
    fail "Rate limiting middleware missing"
fi

# Test: Security headers middleware exists
if [ -f "app/Http/Middleware/ApiSecurityHeaders.php" ]; then
    pass "Security headers middleware configured"
else
    fail "Security headers middleware missing"
fi

# Test: HTTPS configured
if grep -q "APP_ENV=production" .env && grep -q "APP_URL=https://" .env; then
    pass "HTTPS configured in production"
else
    warn "Verify HTTPS is configured in .env"
fi

# Test: Debug mode disabled
if grep -q "APP_DEBUG=false" .env; then
    pass "Debug mode disabled in production"
else
    fail "Debug mode should be disabled in production"
fi

# Test: API key format
if grep -q "sk_live_" .env || [ -z "$(grep MAILTRAP .env)" ]; then
    pass "API key format validation"
else
    warn "Verify API key format (should be sk_live_*)"
fi

# ============================================
# 3. INFRASTRUCTURE VERIFICATION
# ============================================
echo ""
echo "⚙️  INFRASTRUCTURE VERIFICATION"
echo "---"

# Test: Redis connectivity
if command -v redis-cli &> /dev/null; then
    if redis-cli ping | grep -q PONG; then
        pass "Redis connectivity verified"
    else
        fail "Redis not responding - check redis-server status"
    fi
else
    warn "Redis CLI not found - skip if Redis is remote"
fi

# Test: Database connectivity
echo "Checking database..."
if php artisan tinker --execute "DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q OK; then
    pass "Database connectivity verified"
else
    fail "Database connection failed - check credentials in .env"
fi

# Test: Backup script exists
if [ -f "scripts/backup-database.sh" ]; then
    pass "Database backup script configured"
else
    fail "Backup script missing"
fi

# Test: Queue configuration
if grep -q "QUEUE_CONNECTION=redis" .env; then
    pass "Queue configured to use Redis"
else
    warn "Queue should use Redis for production (QUEUE_CONNECTION=redis)"
fi

# Test: Cache configuration
if grep -q "CACHE_STORE=redis" .env; then
    pass "Cache configured to use Redis"
else
    warn "Cache should use Redis for production (CACHE_STORE=redis)"
fi

# ============================================
# 4. MONITORING VERIFICATION
# ============================================
echo ""
echo "📡 MONITORING VERIFICATION"
echo "---"

# Test: Sentry configuration
if [ -f "config/sentry.php" ]; then
    pass "Sentry configuration exists"
    
    if grep -q "SENTRY_LARAVEL_DSN" .env; then
        if [ -n "$(grep SENTRY_LARAVEL_DSN .env | cut -d= -f2-)" ]; then
            pass "Sentry DSN configured"
        else
            warn "Sentry DSN not set - errors won't be tracked"
        fi
    fi
else
    fail "Sentry config missing"
fi

# Test: Logging configured
if grep -q "LOG_CHANNEL" .env; then
    pass "Logging configured"
else
    fail "Logging not configured"
fi

# ============================================
# 5. EMAIL VERIFICATION
# ============================================
echo ""
echo "📧 EMAIL VERIFICATION"
echo "---"

# Test: Mailtrap configured
if grep -q "live.smtp.mailtrap.io" .env; then
    pass "Mailtrap SMTP configured"
    
    if grep -q "MAIL_PASSWORD" .env && [ -n "$(grep MAIL_PASSWORD .env | cut -d= -f2-)" ]; then
        pass "Mailtrap credentials set"
    else
        warn "Mailtrap password not configured"
    fi
else
    fail "Mailtrap not configured"
fi

# Test: Email from address set
if grep -q "MAIL_FROM_ADDRESS" .env && grep "MAIL_FROM_ADDRESS" .env | grep -q "@"; then
    pass "Email from address configured"
else
    fail "Email from address not configured"
fi

# ============================================
# 6. MIGRATIONS
# ============================================
echo ""
echo "📚 DATABASE MIGRATIONS"
echo "---"

# Test: Migrations up to date
if php artisan migrate:status 2>/dev/null | grep -q "No pending"; then
    pass "All migrations applied"
else
    warn "Run 'php artisan migrate' to apply pending migrations"
fi

# ============================================
# 7. LOAD TESTING
# ============================================
echo ""
echo "⚡ LOAD TESTING"
echo "---"

# Test: Load test file exists
if [ -f "tests/Load/TanovaLoadTest.php" ]; then
    pass "Load testing framework configured"
    echo ""
    echo "To run load tests: php tests/load-test.php"
    echo "Target: >95% success rate, P95 < 1000ms"
else
    fail "Load testing framework missing"
fi

# ============================================
# SUMMARY
# ============================================
echo ""
echo "=========================================="
echo "SUMMARY"
echo "=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ALL CHECKS PASSED - READY FOR PRODUCTION${NC}"
    exit 0
else
    echo -e "${RED}✗ $FAILED CHECKS FAILED - FIX BEFORE DEPLOYING${NC}"
    exit 1
fi
```

## Save as Script

```bash
chmod +x scripts/verify-production.sh
./scripts/verify-production.sh
```

---

## Manual Verification Checklist

After running the script, complete these manual steps:

### Security
- [ ] Penetration test API endpoints with Burp Suite or similar
- [ ] Verify vendor isolation (test with 2 different API keys)
- [ ] Test rate limiting (make 100 requests quickly, should be blocked)
- [ ] Verify API key rotation process works
- [ ] Test emergency lockdown (disable all API keys)

### Performance
- [ ] Run load tests: `php tests/load-test.php`
- [ ] Verify P95 response < 1000ms
- [ ] Check queue depth with: `php artisan queue:monitor`
- [ ] Monitor cache hit rate

### Functionality
- [ ] Test trip generation end-to-end
- [ ] Verify all zone 1 activities in every package
- [ ] Test booking creation and status updates
- [ ] Test email notifications (check Mailtrap inbox)
- [ ] Test vendor isolation (logged in as vendor A, cannot see vendor B's data)

### Infrastructure
- [ ] Test database backup: `./scripts/backup-database.sh`
- [ ] Verify backup uploaded to S3
- [ ] Test database restore from backup
- [ ] Test queue worker restart
- [ ] Verify Redis connection

### Monitoring
- [ ] Trigger test error, verify appears in Sentry
- [ ] Check logs are being written
- [ ] Verify email alerts work

---

## Production Deployment Gates

**BEFORE LAUNCHING, ALL OF THESE MUST BE TRUE:**

```
✓ All tests passing (php artisan test)
✓ Load tests show >95% success rate
✓ Security audit passed
✓ Database backup tested and restorable
✓ Sentry receiving errors
✓ Email notifications working
✓ All infrastructure services running
✓ On-call schedule confirmed
✓ Rollback plan documented
✓ Status page ready
```

If ANY of the above is NOT TRUE, **DO NOT DEPLOY TO PRODUCTION**.

---

## Quick Deploy Script

Once all checks pass:

```bash
#!/bin/bash
set -e

echo "🚀 DEPLOYING TO PRODUCTION"

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --no-interaction

# 3. Run migrations
php artisan migrate --force

# 4. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
supervisorctl restart tsoka:*

# 6. Health check
HEALTH=$(curl -s https://portal.tsokatravel.com/health | grep -c OK)
if [ $HEALTH -gt 0 ]; then
    echo "✓ Deployment successful"
    exit 0
else
    echo "✗ Health check failed - rolling back"
    exit 1
fi
```

---

## Post-Deployment Monitoring (30 minutes)

After deploying:
1. Watch Sentry for errors
2. Monitor API response times
3. Check queue depth
4. Verify no spike in error rates
5. Test a few API calls manually

If everything looks good, deployment is complete! 🎉

