# Tsoka Portal - Production Runbook

## Overview
This runbook covers deployment, monitoring, incident response, and operational procedures for the Tsoka Portal in production.

---

## 1. Pre-Deployment Checklist

- [ ] All tests passing: `php artisan test`
- [ ] Load tests completed and P95 < 1000ms
- [ ] Environment variables configured in `.env`
- [ ] Database backups tested and restorable
- [ ] SSL certificates valid and renewed
- [ ] Rate limiting configured and tested
- [ ] Monitoring (Sentry) integrated
- [ ] Email (Mailtrap) configured and tested
- [ ] Redis configured and running
- [ ] Queue worker running on `trips` queue

---

## 2. Deployment Steps

### Initial Deployment

```bash
# 1. Clone repository
git clone <repo> /var/www/tsoka-portal
cd /var/www/tsoka-portal

# 2. Install dependencies
composer install --no-dev
npm install && npm run build

# 3. Generate app key
php artisan key:generate

# 4. Run migrations
php artisan migrate --force

# 5. Seed initial data
php artisan db:seed --class=General

# 6. Set permissions
chown -R www-data:www-data /var/www/tsoka-portal
chmod -R 755 storage bootstrap/cache

# 7. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Start queue workers
supervisorctl start tsoka:*
```

### Rolling Deployment (Zero-Downtime)

```bash
# 1. Deploy to secondary instance
git pull origin main
composer install --no-dev
php artisan migrate --force

# 2. Switch load balancer to new instance
# Update load balancer pool

# 3. Verify new instance
curl https://portal.tsokatravel.com/health

# 4. Keep old instance as fallback for 1 hour
sleep 3600

# 5. Deploy to old instance
git pull origin main
composer install --no-dev
php artisan migrate --force
supervisorctl restart tsoka:*
```

---

## 3. Monitoring & Alerts

### Key Metrics to Monitor

| Metric | Threshold | Action |
|--------|-----------|--------|
| Error Rate | >5% | Page on-call |
| API Response Time P95 | >2000ms | Scale up / investigate |
| Queue Backlog | >100 jobs | Add queue workers |
| Database Connections | >80% | Review slow queries |
| Disk Space | >80% | Cleanup logs / backups |
| Redis Memory | >80% | Increase memory / evict |
| Failed Backups | Any | Immediate investigation |

### Sentry Dashboard
- **URL**: https://sentry.io/projects/tsoka-portal/
- **Alert**: Email on critical errors
- **Traces**: Review slow transactions

### Grafana Dashboards (if available)
- Trip generation performance
- API latency
- Queue depth
- Database metrics

---

## 4. Common Issues & Solutions

### Issue: Trip Generation Timeout (504)

**Symptoms**: Users see "Gateway Timeout" when generating trips

**Diagnosis**:
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Check Redis connection
redis-cli ping  # Should return PONG

# Check PHP-FPM workers
ps aux | grep php-fpm
```

**Solution**:
1. Ensure Redis is running: `systemctl status redis-server`
2. Restart queue workers: `supervisorctl restart tsoka:*`
3. Increase PHP-FPM workers in `/etc/php/8.4/fpm/pool.d/tsoka.conf`
4. Check slow logs: `php artisan queue:monitor`

### Issue: Email Notifications Not Sending

**Diagnosis**:
```bash
# Test Mailtrap connection
php artisan tinker
> config('mail')  // Should show Mailtrap settings
> Mail::raw('Test', fn($m) => $m->to('test@example.com'))->send()
```

**Solution**:
1. Verify Mailtrap credentials in `.env`:
   ```
   MAIL_HOST=live.smtp.mailtrap.io
   MAIL_PORT=587
   MAIL_USERNAME=<your-username>
   MAIL_PASSWORD=<your-password>
   ```
2. Check failed email queue: `php artisan queue:failed`
3. Retry failed jobs: `php artisan queue:retry all`

### Issue: High Database Load

**Diagnosis**:
```bash
# Show slow queries
SHOW VARIABLES LIKE 'long_query_time';
SHOW VARIABLES LIKE 'log_queries_not_using_indexes';

# Top 10 slow queries
SELECT query_time, query FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;
```

**Solution**:
1. Add indexes for frequently queried fields
2. Check `bc_tanova_trips` table size: `SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb FROM information_schema.tables WHERE table_schema = 'tsoka_portal' ORDER BY size_mb DESC;`
3. Archive old trips to separate table
4. Implement query pagination

### Issue: Rate Limiting Too Strict

**Current Limits**:
- `tanova/generate`: 30/min per API key
- `tanova/trips`: 100/min per API key
- `bookings`: 100/min per API key
- `enquiries`: 50/min per API key

**Solution**:
1. Edit rate limits in `app/Http/Middleware/ApiRateLimiting.php`
2. Reload: `php artisan config:cache && php artisan cache:clear`
3. For whitelisting specific vendors: Add check in middleware

---

## 5. Backup & Disaster Recovery

### Daily Backups

Automated daily backups run at 2 AM via cron:
```bash
# Check cron job
crontab -l | grep backup

# Manual backup
/var/www/tsoka-portal/scripts/backup-database.sh

# Verify backup in S3
aws s3 ls s3://tsoka-backups/daily/
```

### Restore from Backup

```bash
# 1. Download backup from S3
aws s3 cp s3://tsoka-backups/daily/tsoka_20260531_020000.sql.gz /tmp/

# 2. Decompress
gunzip /tmp/tsoka_20260531_020000.sql.gz

# 3. Restore (with caution!)
mysql -u root -p tsoka_portal < /tmp/tsoka_20260531_020000.sql

# 4. Verify restore
php artisan tinker
> \DB::table('users')->count()  // Should match pre-backup state
```

### Weekly Backups (Archived)

Weekly backups (Sundays) are stored in Glacier for long-term retention.

---

## 6. Incident Response

### Severity Levels

| Level | Response Time | Action |
|-------|---------------|--------|
| Critical | <15 min | Page on-call immediately, post in #incidents |
| High | <1 hour | Create incident ticket, notify team |
| Medium | <4 hours | Plan fix, deploy with next release |
| Low | <1 day | Add to backlog |

### Response Template

```
[INCIDENT] <Service> - <Brief Description>
Status: INVESTIGATING
Severity: CRITICAL
Timeline:
  - <time>: Issue detected via <Sentry/monitoring>
  - <time>: Root cause identified: <cause>
  - <time>: Fix deployed
  - <time>: Verified resolved
Impact:
  - Affected users: ~<number>
  - Duration: <X minutes>
  - Data loss: <yes/no>
Resolution:
  - <What was fixed>
Post-Incident:
  - [ ] Runbook updated
  - [ ] Monitoring alert added
  - [ ] Fix to prevent recurrence deployed
```

### Escalation Path

1. On-call engineer investigates (5 min)
2. If unresolved: Notify lead engineer (15 min)
3. If still unresolved: Notify CTO (30 min)
4. If critical: Prepare rollback plan

---

## 7. Scaling Guide

### When to Scale

**Add Queue Workers**:
- Queue backlog > 100 jobs
- Trip generation P95 > 1500ms

**Add API Servers**:
- API response time P95 > 2000ms
- CPU usage > 80%
- Request rate > 500/sec

**Database Scaling**:
- Connection pool > 80%
- Query latency increasing
- Disk I/O at capacity

### Scaling Commands

```bash
# Add queue worker
supervisorctl start tsoka:worker_3

# Increase pool size
# Edit: /etc/supervisor/conf.d/tsoka.conf
# Increase numprocs=5, restart supervisor

# Monitor during scale
watch -n 1 'ps aux | grep php-fpm | wc -l'
```

---

## 8. Security Procedures

### Regular Tasks

- [ ] Weekly: Review Sentry for security issues
- [ ] Weekly: Check rate limiting statistics
- [ ] Monthly: Rotate API keys for inactive vendors
- [ ] Monthly: Review database access logs
- [ ] Quarterly: Security audit of API endpoints

### API Key Rotation

```bash
php artisan tinker
> $vendor = \Modules\Vendor\Models\Vendor::find(1);
> $oldKey = $vendor->apiKeys()->first();
> $oldKey->update(['active' => false]);
> $newKey = $vendor->apiKeys()->create(['name' => 'rotated']);
> $newKey->key  // Share with vendor
```

### Emergency Security Lockdown

```bash
# Disable all API access
php artisan tinker
> \DB::table('bc_vendor_api_keys')->update(['active' => false]);

# Re-enable specific vendor
> \DB::table('bc_vendor_api_keys')->where('vendor_id', 1)->update(['active' => true]);
```

---

## 9. Maintenance Windows

### Scheduled Maintenance

- **When**: Sundays 2 AM - 3 AM UTC
- **Duration**: 1 hour maximum
- **Communications**: 24-hour notice via status page

### Maintenance Procedure

```bash
# 1. Announce maintenance
echo "MAINTENANCE" > /var/www/tsoka-portal/MAINTENANCE

# 2. Wait for queue to clear
watch -n 5 'php artisan queue:monitor'

# 3. Perform maintenance (DB migrations, etc.)
php artisan migrate

# 4. Restart services
supervisorctl restart tsoka:*
systemctl restart php8.4-fpm
systemctl restart nginx

# 5. Verify health
curl https://portal.tsokatravel.com/health

# 6. Remove maintenance flag
rm /var/www/tsoka-portal/MAINTENANCE
```

---

## 10. Contact & Escalation

**On-Call Engineer**: Check PagerDuty schedule
**Lead Engineer**: <email>
**CTO**: <email>
**Status Page**: https://status.portal.tsokatravel.com/

---

## Appendix A: Useful Commands

```bash
# View logs
tail -f /var/log/tsoka/laravel.log

# Monitor queue
php artisan queue:monitor

# Clear cache
php artisan cache:clear

# Check database
mysql -u root -p tsoka_portal
> SELECT COUNT(*) FROM bc_tanova_trips WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);

# Redis CLI
redis-cli
> INFO memory
> DBSIZE
> FLUSHDB  # Only in emergencies!

# Check supervisor
supervisorctl status
supervisorctl restart tsoka:worker_1
```

---

**Last Updated**: 2026-05-31
**Version**: 1.0
**Owner**: Tsoka DevOps Team
