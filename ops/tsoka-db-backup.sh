#!/bin/bash
# Nightly MySQL backup. Installed at /usr/local/bin/tsoka-db-backup.sh, run by root's cron at 02:00 server time (UTC).
#
# - One compressed dump per database, into /var/backups/tsoka-db (root only).
# - Keeps the newest KEEP dumps of each database and deletes the older ones, but ONLY after tonight's dump has
#   been written and verified, so a failing job never eats the backups that still work.
# - Any problem is logged, exits non-zero, and (if set) does not ping the heartbeat, so a missed night is noticed.
#
# Optional /etc/tsoka-backup.env:
#   HEALTHCHECK_URL=https://hc-ping.com/xxxx      pinged only on full success (/fail on problems)
#   OFFSITE_S3_URI=s3://my-bucket/tsoka-db/       copy every verified dump off this server (needs the aws cli; use a write-only key)
#   AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION   for that copy
#   (an off-site copy that fails counts as a failed night, so it is noticed)
#
# Weekly restore test:  tsoka-db-backup.sh --restore-test   (loads the newest dump into a scratch database, checks it, drops it)
set -u
umask 077

DBS=(tsoka_portal)
DIR=/var/backups/tsoka-db
KEEP=7
LOG=/var/log/tsoka-db-backup.log
LOCK=/var/lock/tsoka-db-backup.lock
[ -f /etc/tsoka-backup.env ] && . /etc/tsoka-backup.env

log() { echo "$(date -Is) $*" >> "$LOG"; }
mkdir -p "$DIR"; chmod 700 "$DIR"

if [ "${1:-}" = "--restore-test" ]; then
    db=${DBS[0]}; scratch="restore_test_$$"; last=$(ls -1t "$DIR/${db}"-*.sql.gz 2>/dev/null | head -1)
    [ -n "$last" ] || { log "restore-test: no dump found"; exit 1; }
    mysql -e "CREATE DATABASE \`$scratch\` CHARACTER SET utf8mb4" || { log "restore-test: cannot create scratch database"; exit 1; }
    if zcat "$last" | mysql "$scratch" 2>>"$LOG"; then
        tables=$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$scratch'")
        vendors=$(mysql -N -e "SELECT COUNT(*) FROM \`$scratch\`.users" 2>/dev/null || echo 0)
        mysql -e "DROP DATABASE \`$scratch\`"
        if [ "${tables:-0}" -gt 50 ] && [ "${vendors:-0}" -gt 0 ]; then
            log "restore-test: ok $(basename "$last") -> ${tables} tables, ${vendors} users"
            [ -n "${HEALTHCHECK_URL:-}" ] && curl -fsS -m 10 --retry 3 "${HEALTHCHECK_URL}" >/dev/null 2>&1
            exit 0
        fi
        log "restore-test: FAILED, restored but only ${tables} tables / ${vendors} users"
    else
        mysql -e "DROP DATABASE \`$scratch\`"; log "restore-test: FAILED, the dump would not load"
    fi
    [ -n "${HEALTHCHECK_URL:-}" ] && curl -fsS -m 10 --retry 3 "${HEALTHCHECK_URL}/fail" >/dev/null 2>&1
    exit 1
fi

exec 9>"$LOCK"
flock -n 9 || { log "another backup is still running, skipping"; exit 1; }

fail=0
stamp=$(date +%Y%m%d-%H%M%S)

for db in "${DBS[@]}"; do
    tmp="$DIR/.${db}-${stamp}.sql.gz.part"
    final="$DIR/${db}-${stamp}.sql.gz"

    # Room for it? Need at least twice the size of the last dump.
    last=$(ls -1t "$DIR/${db}"-*.sql.gz 2>/dev/null | head -1)
    need=$(( $( [ -n "$last" ] && stat -c %s "$last" || echo 0 ) * 2 / 1024 + 10240 ))
    free=$(df --output=avail -k "$DIR" | tail -1)
    if [ "$free" -lt "$need" ]; then log "$db: NOT ENOUGH DISK (${free}K free, need ${need}K)"; fail=1; continue; fi

    if ! mysqldump --single-transaction --quick --routines --triggers --events --hex-blob \
            --default-character-set=utf8mb4 --set-gtid-purged=OFF "$db" 2>>"$LOG" | gzip -6 > "$tmp"; then
        log "$db: mysqldump FAILED"; rm -f "$tmp"; fail=1; continue
    fi
    # PIPESTATUS is not available after the if; verify the file itself.
    if ! gzip -t "$tmp" 2>>"$LOG" || ! zcat "$tmp" | tail -c 400 | grep -q "Dump completed"; then
        log "$db: dump is incomplete or corrupt, discarded"; rm -f "$tmp"; fail=1; continue
    fi
    size=$(stat -c %s "$tmp")
    if [ -n "$last" ]; then
        prev=$(stat -c %s "$last")
        if [ "$size" -lt $(( prev / 2 )) ]; then log "$db: WARNING new dump ${size}B is less than half of the last (${prev}B); kept, please check"; fail=1; fi
    fi
    mv "$tmp" "$final"; chmod 600 "$final"
    log "$db: ok ${size} bytes -> $(basename "$final")"

    if [ -n "${OFFSITE_S3_URI:-}" ]; then
        if command -v aws >/dev/null 2>&1 && aws s3 cp --only-show-errors "$final" "${OFFSITE_S3_URI%/}/$(basename "$final")" 2>>"$LOG"; then
            log "$db: copied off-site to ${OFFSITE_S3_URI}"
        else
            log "$db: OFF-SITE COPY FAILED"; fail=1
        fi
    fi

    # Rotate: keep the newest $KEEP, delete the rest (only reached after a verified new dump).
    ls -1t "$DIR/${db}"-*.sql.gz 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r old; do
        rm -f -- "$old" && log "$db: removed old $(basename "$old")"
    done
done

if [ "$fail" -eq 0 ]; then
    [ -n "${HEALTHCHECK_URL:-}" ] && curl -fsS -m 10 --retry 3 "$HEALTHCHECK_URL" >/dev/null 2>&1
    log "backup finished: ok"
    exit 0
fi
[ -n "${HEALTHCHECK_URL:-}" ] && curl -fsS -m 10 --retry 3 "${HEALTHCHECK_URL}/fail" >/dev/null 2>&1
log "backup finished: WITH PROBLEMS"
exit 1
