#!/bin/bash
###############################################################################
# sync-missing-to-live.sh
# Push tables that have DATA locally but are MISSING or EMPTY on the LIVE server.
#
# LIVE: ubuntu@13.60.79.78 (peachpy.pem) :: /home/ubuntu/apps/tsokaportal
#       DB tsoka_portal on the server's local MySQL.
#
# SAFETY: A live table that already has >0 rows is NEVER touched.
#         Only missing/empty live tables are (re)created and filled.
#         DB passwords are read from each side's .env (never hardcoded).
#
# Usage:
#   bash sync-missing-to-live.sh         # dry-run: show what WOULD sync
#   bash sync-missing-to-live.sh apply   # actually push the missing/empty tables
###############################################################################
set -euo pipefail

# ── Local DB ─────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_ENV="${SCRIPT_DIR}/bc-cms/.env"
LOCAL_DB="tsoka_portal"
LOCAL_HOST="127.0.0.1"
LOCAL_USER="$(grep -E '^DB_USERNAME=' "$LOCAL_ENV" | cut -d= -f2-)"
LOCAL_PASS="$(grep -E '^DB_PASSWORD=' "$LOCAL_ENV" | cut -d= -f2-)"

# ── Live server ──────────────────────────────────────────────
SSH_KEY="/home/lionel/Documents/tsokatravel/peachpy.pem"
REMOTE_HOST="13.60.79.78"
REMOTE_USER="ubuntu"
REMOTE_CMS="/home/ubuntu/apps/tsokaportal/bc-cms"
REMOTE_DB="tsoka_portal"
SSH="ssh -o ConnectTimeout=12 -o StrictHostKeyChecking=accept-new -i ${SSH_KEY} ${REMOTE_USER}@${REMOTE_HOST}"

MODE="${1:-plan}"
WORK="/tmp/sync-missing-$(date +%s)"
mkdir -p "$WORK"

ml() { MYSQL_PWD="$LOCAL_PASS" mysql -h "$LOCAL_HOST" -u "$LOCAL_USER" -N -s "$@"; }

echo "============================================================"
echo "  Sync missing/empty tables: LOCAL → LIVE (${REMOTE_HOST})   [mode: $MODE]"
echo "============================================================"

# 1) Local tables that actually have rows (exact count)
echo "→ Scanning local tables with data..."
LOCAL_TABLES=$(ml -e "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES \
    WHERE TABLE_SCHEMA='$LOCAL_DB' AND TABLE_TYPE='BASE TABLE'")
CANDIDATES=()
for t in $LOCAL_TABLES; do
    c=$(ml "$LOCAL_DB" -e "SELECT COUNT(*) FROM \`$t\`")
    [ "$c" -gt 0 ] && CANDIDATES+=("$t:$c")
done
echo "  ${#CANDIDATES[@]} local tables have data."

# 2) Check those tables on the live server (one ssh session; reads remote .env pw)
echo "→ Checking those tables on LIVE (ssh)..."
CAND_NAMES=$(printf '%s\n' "${CANDIDATES[@]}" | cut -d: -f1 | tr '\n' ' ')
PROD_REPORT=$($SSH "
    PW=\$(grep -E '^DB_PASSWORD=' ${REMOTE_CMS}/.env | cut -d= -f2-)
    DB='$REMOTE_DB'
    for t in $CAND_NAMES; do
        if MYSQL_PWD=\"\$PW\" mysql -u $LOCAL_USER -N -s -e \"SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='\$DB' AND TABLE_NAME='\$t'\" | grep -q 1; then
            n=\$(MYSQL_PWD=\"\$PW\" mysql -u $LOCAL_USER -N -s \$DB -e \"SELECT COUNT(*) FROM \\\`\$t\\\`\"); echo \"\$t \$n\";
        else echo \"\$t MISSING\"; fi
    done")

# 3) Build sync set = missing OR empty(0) on live
SYNC=()
echo ""
printf "  %-42s %10s %12s\n" "TABLE" "LOCAL" "LIVE"
printf "  %-42s %10s %12s\n" "-----" "-----" "----"
for entry in "${CANDIDATES[@]}"; do
    t="${entry%%:*}"; lc="${entry##*:}"
    pstat=$(echo "$PROD_REPORT" | awk -v T="$t" '$1==T{print $2}')
    if [ "$pstat" = "MISSING" ]; then
        printf "  %-42s %10s %12s  <= sync (new)\n"   "$t" "$lc" "missing"; SYNC+=("$t")
    elif [ "$pstat" = "0" ]; then
        printf "  %-42s %10s %12s  <= sync (empty)\n" "$t" "$lc" "0";       SYNC+=("$t")
    else
        printf "  %-42s %10s %12s  skip (has data)\n" "$t" "$lc" "${pstat:-?}"
    fi
done

echo ""
echo "  ${#SYNC[@]} table(s) to sync (missing/empty on live)."
[ "${#SYNC[@]}" -eq 0 ] && { echo "  Nothing to do."; rm -rf "$WORK"; exit 0; }

if [ "$MODE" != "apply" ]; then
    echo ""
    echo "  DRY RUN. Re-run with 'apply' to push these:"
    echo "    bash $(basename "$0") apply"
    rm -rf "$WORK"; exit 0
fi

# 4) APPLY: dump the sync set from local, upload, import with FK checks off
echo ""
echo "→ Dumping ${#SYNC[@]} table(s) from local..."
DUMP="$WORK/missing.sql"
{
  echo "SET FOREIGN_KEY_CHECKS=0;"
  MYSQL_PWD="$LOCAL_PASS" mysqldump -h "$LOCAL_HOST" -u "$LOCAL_USER" \
      --single-transaction --quick --skip-lock-tables \
      "$LOCAL_DB" "${SYNC[@]}"
  echo "SET FOREIGN_KEY_CHECKS=1;"
} > "$DUMP"
gzip -f "$DUMP"
echo "  $(du -h "$DUMP.gz" | cut -f1) dump ready."

echo "→ Uploading + importing on LIVE..."
scp -o StrictHostKeyChecking=accept-new -i "$SSH_KEY" "$DUMP.gz" "${REMOTE_USER}@${REMOTE_HOST}:/tmp/"
$SSH "
    gunzip -f /tmp/$(basename "$DUMP.gz")
    PW=\$(grep -E '^DB_PASSWORD=' ${REMOTE_CMS}/.env | cut -d= -f2-)
    MYSQL_PWD=\"\$PW\" mysql -u $LOCAL_USER $REMOTE_DB < /tmp/$(basename "$DUMP")
    rm -f /tmp/$(basename "$DUMP")
    cd ${REMOTE_CMS} && php artisan cache:clear >/dev/null 2>&1 || true
    echo '  ✓ imported and cache cleared'
"
rm -rf "$WORK"
echo ""
echo "============================================================"
echo "  ✓ Sync complete — missing/empty tables filled on LIVE."
echo "============================================================"
