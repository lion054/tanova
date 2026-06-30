#!/bin/bash
# ============================================================
#  tsokaportal — Deployment to LIVE server
#  Server:  13.60.79.78 (AWS)  |  User: ubuntu  |  Key: peachpy.pem
#  Folder:  /home/ubuntu/apps/tsokaportal
#  Stack:   PHP / Laravel (GoTrip CMS) served by PM2
#           (php artisan serve --host=127.0.0.1 --port=3002) behind nginx
#
#  Pushes bc-cms code, runs migrations, clears caches, restarts PM2.
#  Does NOT touch the database contents (use sync-missing-to-live.sh for data).
#  Additive rsync (no --delete) so server-only files are never removed.
# ============================================================

set -euo pipefail

# ── Config ──────────────────────────────────────────────────
SERVER_IP="13.60.79.78"
SERVER_USER="ubuntu"
SSH_KEY="/home/lionel/Documents/tsokatravel/peachpy.pem"
REMOTE_ROOT="/home/ubuntu/apps/tsokaportal"
REMOTE_CMS="${REMOTE_ROOT}/bc-cms"
PM2_APP="tsokaportal"
APP_PORT="3002"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_CMS="${SCRIPT_DIR}/bc-cms"

SSH="ssh -o ConnectTimeout=12 -o StrictHostKeyChecking=accept-new -i ${SSH_KEY} ${SERVER_USER}@${SERVER_IP}"
RSH="ssh -o StrictHostKeyChecking=accept-new -i ${SSH_KEY}"

echo "============================================================"
echo "  tsokaportal deploy → ${SERVER_USER}@${SERVER_IP}:${REMOTE_CMS}"
echo "============================================================"

# ── Pre-flight ───────────────────────────────────────────────
[ -d "$LOCAL_CMS" ] || { echo "ERROR: bc-cms not found at $LOCAL_CMS"; exit 1; }
[ -f "$SSH_KEY" ]   || { echo "ERROR: ssh key not found at $SSH_KEY"; exit 1; }

echo "→ Testing SSH..."
$SSH "test -d ${REMOTE_CMS} && echo '  ✓ SSH OK, remote bc-cms present'" || {
    echo "ERROR: SSH failed or remote path missing"; exit 1
}

# ── Step 1: Backup remote bc-cms (code only, fast) ──────────
echo "→ Step 1: Backing up remote code..."
$SSH "
    cd ${REMOTE_ROOT}
    ts=\$(date +%Y%m%d_%H%M%S)
    tar --exclude=bc-cms/vendor --exclude=bc-cms/node_modules \
        --exclude='bc-cms/storage/*' \
        -czf /home/ubuntu/tsokaportal-code-backup-\${ts}.tar.gz bc-cms 2>/dev/null || true
    echo \"  ✓ backup → /home/ubuntu/tsokaportal-code-backup-\${ts}.tar.gz\"
"

# ── Step 2: Sync bc-cms (additive; preserves .env, vendor, storage) ─
echo "→ Step 2: Syncing bc-cms code..."
rsync -az --info=stats1 -e "$RSH" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='storage/framework/sessions/' \
    --exclude='storage/framework/cache/' \
    --exclude='storage/framework/views/' \
    --exclude='storage/debugbar/' \
    --exclude='storage/logs/' \
    "$LOCAL_CMS/" "${SERVER_USER}@${SERVER_IP}:${REMOTE_CMS}/"
echo "  ✓ code synced"

# ── Step 3: Artisan: migrate + clear caches ─────────────────
echo "→ Step 3: Migrations + cache clear..."
$SSH "
    cd ${REMOTE_CMS}
    php artisan migrate --force
    php artisan optimize:clear
    echo '  ✓ migrated + caches cleared'
"

# ── Step 4: Restart PM2 process ──────────────────────────────
echo "→ Step 4: Restarting PM2 app '${PM2_APP}'..."
$SSH "pm2 restart ${PM2_APP} --update-env && echo '  ✓ restarted'"

# ── Step 5: Smoke test ───────────────────────────────────────
echo "→ Step 5: Smoke test (localhost:${APP_PORT})..."
sleep 2
$SSH "curl -s -o /dev/null -w '  HTTP %{http_code}\n' http://127.0.0.1:${APP_PORT}/ || echo '  ⚠ no response yet'"

echo ""
echo "============================================================"
echo "  ✓ Deploy complete"
echo "============================================================"
echo "  Restore code if needed:"
echo "    ssh -i ${SSH_KEY} ${SERVER_USER}@${SERVER_IP}"
echo "    cd ${REMOTE_ROOT} && tar xzf /home/ubuntu/tsokaportal-code-backup-<ts>.tar.gz"
