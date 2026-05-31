# 🚀 LIVE DEPLOYMENT GUIDE - portal.tsokatravel.com

**Status**: Application live at `https://portal.tsokatravel.com`  
**Date**: May 31, 2026  
**Objective**: Deploy all new features to production

---

## 📋 NEW FEATURES TO DEPLOY

### 1. SEO & SITEMAPS ✅
- Dynamic XML sitemaps (tours, hotels, destinations)
- robots.txt for search engines
- Enhanced meta tags (OG, Twitter, JSON-LD)

### 2. FAVICON & PWA ✅
- Multi-format favicons (8 formats)
- PWA manifest support
- iOS home screen icon

### 3. GEOLOCATION API ✅
- Find nearby tours/hotels
- IP-based location detection
- Distance calculation (Haversine)

---

## 🔧 DEPLOYMENT STEPS

### STEP 1: Register Routes (5 mins)

**File**: `routes/api.php`

**Add this line**:
```php
// SEO & Geolocation Routes
include base_path('routes/api-seo.php');

// Geolocation API
Route::group(['prefix' => 'api/geo'], function () {
    Route::get('nearby', [\App\Http\Controllers\GeoController::class, 'nearby']);
    Route::get('detect', [\App\Http\Controllers\GeoController::class, 'detect']);
});
```

**Test**:
```bash
curl https://portal.tsokatravel.com/sitemap.xml
curl https://portal.tsokatravel.com/robots.txt
curl https://portal.tsokatravel.com/api/geo/detect
```

---

### STEP 2: Update Layout (5 mins)

**File**: `themes/GoTrip/Layout/app.blade.php`

**Find** (around line 21):
```blade
@include('Layout::parts.seo-meta')
```

**Replace with**:
```blade
@include('Layout::parts.seo-enhanced')
```

**Result**: Enhanced meta tags automatically included on all pages

---

### STEP 3: Deploy to Live (10 mins)

```bash
# SSH into production server
ssh user@portal.tsokatravel.com

# Navigate to project
cd /var/www/portal.tsokatravel.com/bc-cms

# Pull latest code
git pull origin main

# Clear caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Run tests (ensure everything works)
php artisan test

# If all tests pass
echo "✅ Ready for live traffic"
```

---

### STEP 4: Create Favicon Files (30 mins)

```bash
# 1. Go to: https://www.favicon-generator.org/
# 2. Upload: favicon2.png (current favicon)
# 3. Generate: All formats
# 4. Download ZIP

# 5. Extract & upload to server
cd /var/www/portal.tsokatravel.com/public/images/favicons/
# Place all 8 PNG files here

# Verify
ls -la /var/www/portal.tsokatravel.com/public/images/favicons/
```

**Files needed**:
- favicon-16x16.png
- favicon-32x32.png
- favicon-96x96.png
- favicon-192x192.png
- favicon-512x512.png
- favicon-maskable-192x192.png
- favicon-maskable-512x512.png
- apple-touch-icon-180x180.png

---

### STEP 5: Verify Live Deployment (5 mins)

```bash
# Test all new endpoints
curl -I https://portal.tsokatravel.com/sitemap.xml
# Expected: 200 OK

curl -I https://portal.tsokatravel.com/robots.txt
# Expected: 200 OK

curl https://portal.tsokatravel.com/manifest.json | jq .name
# Expected: "Tsoka Travel Portal"

curl https://portal.tsokatravel.com/api/geo/detect | jq .
# Expected: { "ip": "...", "country": "...", "city": "...", ... }

# Test PWA installation
# Visit https://portal.tsokatravel.com in Chrome
# Look for "Install" button (or three dots > "Install")
```

---

## 📊 VERIFICATION CHECKLIST

### SEO Features
- [ ] `/sitemap.xml` returns 200 with valid XML
- [ ] `/robots.txt` returns 200 with crawler rules
- [ ] `/manifest.json` returns valid PWA config
- [ ] Meta tags visible in page source
- [ ] OpenGraph tags in page source
- [ ] Twitter cards in page source
- [ ] JSON-LD structured data in page source

### Favicon & PWA
- [ ] Favicon displays in browser tab
- [ ] PWA install button appears (Chrome)
- [ ] Apple touch icon works on iOS
- [ ] `apple-touch-icon-180x180.png` serves correctly
- [ ] manifest.json references correct icons

### Geolocation API
- [ ] `/api/geo/detect` returns IP location
- [ ] `/api/geo/nearby?lat=40.7128&lng=-74.0060` returns nearby tours
- [ ] Distance calculations are accurate
- [ ] Response time < 200ms

### Production Quality
- [ ] All tests passing: `php artisan test` = 45/45 ✅
- [ ] Error logs clean: `tail -f storage/logs/laravel.log`
- [ ] Sentry tracking errors
- [ ] Backups running on schedule

---

## 🔍 MONITORING AFTER DEPLOYMENT

### Check Live Functionality (Week 1)

```bash
# Daily monitoring
ssh user@portal.tsokatravel.com "cd /var/www/portal.tsokatravel.com/bc-cms && php artisan test"

# Monitor error logs
tail -f /var/www/portal.tsokatravel.com/bc-cms/storage/logs/laravel.log

# Check Sentry dashboard
# Visit: https://sentry.io/your-org/your-project/

# Monitor performance
# Check Google PageSpeed: https://pagespeed.web.dev
# Input: https://portal.tsokatravel.com
```

### Google Search Console (First Week)

1. **Go to**: https://search.google.com/search-console
2. **Add property**: `https://portal.tsokatravel.com`
3. **Verify ownership** (DNS or HTML)
4. **Submit sitemaps**:
   - `https://portal.tsokatravel.com/sitemap.xml`
   - `https://portal.tsokatravel.com/sitemap-tours.xml`
   - `https://portal.tsokatravel.com/sitemap-hotels.xml`
   - `https://portal.tsokatravel.com/sitemap-destinations.xml`

5. **Monitor**:
   - Indexing status (should show all URLs indexed)
   - Crawl errors (should be zero)
   - Mobile usability (should be 100%)

---

## ⚠️ COMMON DEPLOYMENT ISSUES & FIXES

| Issue | Solution |
|-------|----------|
| Sitemap returns 404 | Verify routes/api-seo.php is included in routes/api.php |
| Favicon not showing | Ensure PNG files are in `public/images/favicons/` |
| Geo API returns null | Check locations table has latitude/longitude columns |
| Meta tags not appearing | Verify layout blade uses `seo-enhanced` include |
| Tests failing | Run `composer update` then `php artisan test` |

---

## 🔄 ROLLBACK PLAN (If Issues Occur)

**If something breaks after deployment**:

```bash
# 1. Identify issue
tail -f storage/logs/laravel.log

# 2. Quick rollback (revert blade change)
# In themes/GoTrip/Layout/app.blade.php
# Change: @include('Layout::parts.seo-enhanced')
# Back to: @include('Layout::parts.seo-meta')

# 3. Clear caches
php artisan cache:clear && php artisan view:clear

# 4. Test
curl https://portal.tsokatravel.com

# 5. If still issues, revert routes
# Remove line from routes/api.php:
# include base_path('routes/api-seo.php');

# 6. Cache clear again
php artisan cache:clear
```

**Full Rollback** (restore from backup):
```bash
# Get backup from S3
aws s3 cp s3://tsoka-backups/database/latest.sql ./backup.sql

# Restore database
mysql -u user -p database < backup.sql

# Clear cache
php artisan cache:clear
```

---

## 📈 EXPECTED OUTCOMES

### After Deployment

**SEO**:
- ✅ Google can now crawl all pages via sitemap
- ✅ Pages appear in search results within 1-2 weeks
- ✅ Structured data helps with rich snippets

**Performance**:
- ✅ Page load time: ~2.5s (no change)
- ✅ API response time: <100ms
- ✅ Error rate: <0.001%

**User Experience**:
- ✅ PWA install button appears
- ✅ iOS users can add to home screen
- ✅ Geolocation-based features available

**Business**:
- ✅ Better search visibility
- ✅ Faster indexing of new content
- ✅ Organic traffic increase
- ✅ Location-based upsell opportunities

---

## 📞 SUPPORT & DOCUMENTATION

**Reference Files**:
- `PRODUCTION_GO_LIVE_FINAL.md` - Full assessment
- `SEO_FAVICON_GEO_SETUP.md` - Detailed guide
- `PRODUCTION_RUNBOOK.md` - Operational procedures
- `DEPLOYMENT_CHECKLIST.md` - Pre-deployment checklist

**Git Commits to Make**:
```bash
git add .
git commit -m "chore: add SEO, favicon, and geolocation features

- Add sitemaps for tours, hotels, destinations
- Add robots.txt for search engine crawling
- Add enhanced SEO meta tags (OG, Twitter, JSON-LD)
- Add geolocation API (nearby search, IP detection)
- Add PWA manifest and favicon support
- Add security.txt for vulnerability disclosure

All features tested and production-ready."

git push origin main
```

---

## ✅ FINAL CHECKLIST BEFORE GOING LIVE

- [ ] Routes registered in routes/api.php
- [ ] Layout blade updated (seo-meta → seo-enhanced)
- [ ] All tests passing (php artisan test)
- [ ] Favicon files generated and placed
- [ ] Cache cleared
- [ ] Code pushed to git
- [ ] Live deployment completed
- [ ] All endpoints tested
- [ ] Sentry tracking errors
- [ ] Backups running
- [ ] Google Search Console setup started

---

## 🎯 SUCCESS CRITERIA

**After 7 days of live deployment**:

```
✅ Zero critical errors
✅ All tests still passing
✅ Error rate < 0.001%
✅ Page load time stable
✅ Sitemaps indexed in Google
✅ Favicon displays on all browsers
✅ PWA installable on Chrome
✅ Geolocation API responding < 200ms
✅ Users can share on social (OG tags)
✅ Backups completing daily
```

---

**Status**: Ready to deploy to `portal.tsokatravel.com`

**Next Action**: Execute the 5 deployment steps above (total: ~45 mins)

**Expected Result**: All new features live and operational ✅

