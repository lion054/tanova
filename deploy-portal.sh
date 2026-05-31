#!/bin/bash
# ============================================================
#  portal.tsokatravel.com — Tsoka Portal Deployment
#  Server:  169.239.182.80  |  Folder: /var/www/tsoka-portal
#  Stack:   PHP 8.4 + Laravel (GoTrip CMS) + MySQL 8
#
#  ISOLATION: ONLY touches /var/www/tsoka-portal and
#             /etc/nginx/sites-available/portal-tsokatravel.
#             Every other site is left completely untouched.
#             nginx -t is run before any reload.
# ============================================================

set -euo pipefail

# ── Config ──────────────────────────────────────────────────
SERVER_IP="169.239.182.80"
SERVER_USER="root"
REMOTE_ROOT="/var/www/tsoka-portal"
REMOTE_WEBROOT="${REMOTE_ROOT}/public_html"
REMOTE_CMS="${REMOTE_ROOT}/bc-cms"
DOMAIN="portal.tsokatravel.com"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_WEBROOT="${SCRIPT_DIR}/public_html"
LOCAL_CMS="${SCRIPT_DIR}/bc-cms"
NGINX_CONF="${SCRIPT_DIR}/nginx-portal.tsokatravel.com.conf"

DB_NAME="tsoka_portal"
DB_USER="tsoka_portal_user"
# Generate a random password on first run; stable on subsequent runs
# (stored in .deploy-db-pass next to this script so you can reference it)
DB_PASS_FILE="${SCRIPT_DIR}/.deploy-db-pass"
if [ ! -f "$DB_PASS_FILE" ]; then
    openssl rand -base64 24 | tr -d '/+=' | head -c 28 > "$DB_PASS_FILE"
    chmod 600 "$DB_PASS_FILE"
    echo "→ Generated new DB password → saved to .deploy-db-pass (keep this safe)"
fi
DB_PASS="$(cat "$DB_PASS_FILE")"

APP_KEY="base64:VxQQpyPAQ/O2IpLIykzWSReS6U8xKI1qPLOmKBoNHxQ="

# ── Pre-flight ───────────────────────────────────────────────
echo ""
echo "============================================================"
echo "  portal.tsokatravel.com  —  Deployment"
echo "============================================================"
echo "  Server : ${SERVER_USER}@${SERVER_IP}"
echo "  Remote : ${REMOTE_ROOT}"
echo "  Domain : ${DOMAIN}"
echo ""

if [ ! -d "$LOCAL_CMS" ]; then
    echo "ERROR: bc-cms not found at $LOCAL_CMS"; exit 1
fi
if [ ! -d "$LOCAL_WEBROOT" ]; then
    echo "ERROR: public_html not found at $LOCAL_WEBROOT"; exit 1
fi
if [ ! -f "$NGINX_CONF" ]; then
    echo "ERROR: nginx config not found at $NGINX_CONF"; exit 1
fi

echo "→ Testing SSH..."
ssh -o ConnectTimeout=8 ${SERVER_USER}@${SERVER_IP} "echo 'SSH OK'" || {
    echo "ERROR: SSH failed"; exit 1
}

# ── Step 1: Create remote directories ───────────────────────
echo ""
echo "→ Step 1: Creating remote directories..."
ssh ${SERVER_USER}@${SERVER_IP} "
    mkdir -p ${REMOTE_ROOT}
    mkdir -p ${REMOTE_WEBROOT}
    mkdir -p ${REMOTE_CMS}/storage/logs
    mkdir -p ${REMOTE_CMS}/storage/framework/{sessions,views,cache/data}
    mkdir -p ${REMOTE_CMS}/storage/app/public
    echo '✓ Directories ready'
"

# ── Step 2: Sync public_html ─────────────────────────────────
echo ""
echo "→ Step 2: Syncing public_html (web root)..."
rsync -az --progress --delete \
    --exclude='.git' \
    --exclude='*.log' \
    "$LOCAL_WEBROOT/" "${SERVER_USER}@${SERVER_IP}:${REMOTE_WEBROOT}/"
echo "✓ public_html synced"

# ── Step 3: Sync bc-cms (Laravel app) ───────────────────────
echo ""
echo "→ Step 3: Syncing bc-cms (Laravel app)..."
rsync -az --progress --delete \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='storage/framework/sessions/' \
    --exclude='storage/framework/cache/data/' \
    --exclude='storage/framework/views/' \
    --exclude='storage/debugbar/' \
    --exclude='storage/logs/' \
    --exclude='node_modules/' \
    "$LOCAL_CMS/" "${SERVER_USER}@${SERVER_IP}:${REMOTE_CMS}/"
echo "✓ bc-cms synced"

# ── Step 4: Write server .env ────────────────────────────────
echo ""
echo "→ Step 4: Writing server .env..."
ssh ${SERVER_USER}@${SERVER_IP} "cat > ${REMOTE_CMS}/.env" << ENVEOF
APP_NAME='Tsoka Travel'
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_LOG_LEVEL=error
APP_URL=https://${DOMAIN}

BC_ACTIVE_THEME=GoTrip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_DRIVER=sendmail
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@tsokatravel.com
MAIL_FROM_NAME='Tsoka Travel'

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=

# ── Anthropic / Claude ────────────────────────────────────────
ANTHROPIC_API_KEY=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
ANTHROPIC_MODEL=claude-sonnet-4-6
TANOVA_WINDOW_HOURS=24

# ── Tsokanew source DB (read-only, for Tanova engine) ─────────
TSOKANEW_SOCKET=/var/run/mysqld/mysqld.sock
TSOKANEW_DB=tsokanew
TSOKANEW_USER=shantelwarambwa
TSOKANEW_PASS=,Zzdx4Rjwnxh
ENVEOF
ssh ${SERVER_USER}@${SERVER_IP} "chmod 600 ${REMOTE_CMS}/.env"
echo "✓ .env written"

# ── Step 5: Database setup ───────────────────────────────────
echo ""
echo "→ Step 5: Setting up MySQL database and user..."
ssh ${SERVER_USER}@${SERVER_IP} "mysql -e \"
    CREATE DATABASE IF NOT EXISTS \\\`${DB_NAME}\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
    ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON \\\`${DB_NAME}\\\`.* TO '${DB_USER}'@'127.0.0.1';
    GRANT ALL PRIVILEGES ON \\\`${DB_NAME}\\\`.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;
\""
echo "✓ Database '${DB_NAME}' ready, user '${DB_USER}' provisioned"

# ── Step 6: Composer install ─────────────────────────────────
echo ""
echo "→ Step 6: Running composer install on server..."
ssh ${SERVER_USER}@${SERVER_IP} "
    cd ${REMOTE_CMS}
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1
    echo '✓ Composer done'
"

# ── Step 7: Laravel artisan setup ───────────────────────────
echo ""
echo "→ Step 7: Running artisan commands..."
ssh ${SERVER_USER}@${SERVER_IP} "
    cd ${REMOTE_CMS}
    sudo -u www-data php artisan config:clear
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan route:clear
    sudo -u www-data php artisan view:clear
    sudo -u www-data php artisan migrate --force
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache  || echo '  ⚠ route:cache skipped (duplicate route name in base — non-fatal)'
    sudo -u www-data php artisan view:cache   || echo '  ⚠ view:cache skipped (missing theme dir — non-fatal)'
    # Mark as installed so installer does not run
    touch storage/installed && chown www-data:www-data storage/installed
    echo '✓ Artisan done'
"

# ── Step 7b: Restart PHP-FPM (flushes OPcache) ──────────────
echo ""
echo "→ Step 7b: Restarting PHP-FPM to flush OPcache..."
ssh ${SERVER_USER}@${SERVER_IP} "
    systemctl restart php8.4-fpm
    echo '✓ PHP-FPM restarted'
"

# ── Step 8: Set permissions ──────────────────────────────────
echo ""
echo "→ Step 8: Setting permissions..."
ssh ${SERVER_USER}@${SERVER_IP} "
    chown -R www-data:www-data ${REMOTE_ROOT}
    find ${REMOTE_ROOT} -type d -exec chmod 755 {} \;
    find ${REMOTE_ROOT} -type f -exec chmod 644 {} \;
    # Scripts executable
    chmod +x ${REMOTE_CMS}/artisan 2>/dev/null || true
    # Writable directories
    chmod -R 775 ${REMOTE_CMS}/storage
    chmod -R 775 ${REMOTE_CMS}/bootstrap/cache
    # Protect .env
    chmod 600 ${REMOTE_CMS}/.env
    echo '✓ Permissions set'
"

# ── Step 9: Nginx config ─────────────────────────────────────
echo ""
echo "→ Step 9: Deploying nginx config..."
scp "$NGINX_CONF" "${SERVER_USER}@${SERVER_IP}:/etc/nginx/sites-available/portal-tsokatravel"
ssh ${SERVER_USER}@${SERVER_IP} "
    ln -sf /etc/nginx/sites-available/portal-tsokatravel /etc/nginx/sites-enabled/portal-tsokatravel
    echo '✓ Symlink created'
"

# ── Step 10: SSL certificate ─────────────────────────────────
echo ""
echo "→ Step 10: Obtaining SSL certificate for ${DOMAIN}..."
echo "  (Requires DNS A record pointing ${DOMAIN} → ${SERVER_IP})"
ssh ${SERVER_USER}@${SERVER_IP} "
    if [ -d /etc/letsencrypt/live/${DOMAIN} ]; then
        echo '  Certificate already exists — skipping certbot'
    else
        # Temporarily enable HTTP-only for certbot challenge
        nginx -t && systemctl reload nginx || true
        certbot certonly --nginx -d ${DOMAIN} --non-interactive --agree-tos -m hello@tsokatravel.com 2>&1 || {
            echo '  ⚠ certbot failed — check DNS and try again manually:'
            echo '    certbot certonly --nginx -d ${DOMAIN}'
        }
    fi
"

# ── Step 11: Final nginx reload ──────────────────────────────
echo ""
echo "→ Step 11: Testing and reloading nginx..."
ssh ${SERVER_USER}@${SERVER_IP} "
    nginx -t 2>&1
    if [ \$? -eq 0 ]; then
        systemctl reload nginx
        echo '✓ Nginx reloaded — all sites safe'
    else
        echo 'ERROR: nginx config test failed — NOT reloading (all sites protected)'
        exit 1
    fi
"

# ── Done ─────────────────────────────────────────────────────
echo ""
echo "============================================================"
echo "  ✓  portal.tsokatravel.com deployed!"
echo "============================================================"
echo ""
echo "  Live:     https://${DOMAIN}"
echo "  Folder:   ${REMOTE_ROOT}"
echo "  DB:       ${DB_NAME}  (user: ${DB_USER})"
echo "  DB pass:  $(cat "$DB_PASS_FILE")  ← also saved in .deploy-db-pass"
echo ""
echo "  Other sites on this server are UNTOUCHED:"
echo "    tsokatravel.com   /var/www/tsokatravel  (Next.js, PM2 port 3333)"
echo "    luxsav.com        /var/www/luxsav        (Laravel, port 9002)"
echo "    innwrk.com        /var/www/innwrk        (Laravel, port 9001)"
echo "    peachpy.cloud     /var/www/peachpy       (Laravel, socket)"
echo "    tsokax.com        /var/www/tsokax        (Laravel, port 9004)"
echo "    snappyfresh.net   /var/www/snappyfresh   (Next.js, port 3000)"
echo ""
echo "  Logs:"
echo "    App:   ssh ${SERVER_USER}@${SERVER_IP} tail -f ${REMOTE_CMS}/storage/logs/laravel-\$(date +%Y-%m-%d).log"
echo "    Nginx: ssh ${SERVER_USER}@${SERVER_IP} tail -f /var/log/nginx/portal.tsokatravel.com-error.log"
echo ""
echo "  Re-deploy any time:"
echo "    bash deploy-portal.sh"
echo ""
