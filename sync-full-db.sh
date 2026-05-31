#!/bin/bash

###############################################################################
# Full Database Sync: Local → Production
# Exports entire local gotrip database and imports to production tsoka_portal
###############################################################################

set -e

echo "============================================================"
echo "  FULL DATABASE SYNC"
echo "  Local (gotrip) → Production (tsoka_portal)"
echo "============================================================"
echo ""
echo "⚠️  WARNING: This will REPLACE all data in production!"
echo ""

# Configuration
LOCAL_DB="gotrip"
REMOTE_DB="tsoka_portal"
REMOTE_HOST="169.239.182.80"
REMOTE_USER="root"
BACKUP_DIR="/tmp/db-backups"

mkdir -p $BACKUP_DIR

# Step 1: Create backup of current production DB
echo "→ Step 1: Creating backup of current production database..."
ssh ${REMOTE_USER}@${REMOTE_HOST} "
    echo '  Backing up current database...'
    mkdir -p /tmp/db-backups
    mysqldump ${REMOTE_DB} | gzip > /tmp/db-backups/tsoka_portal_backup_\$(date +%Y%m%d_%H%M%S).sql.gz
    echo '  ✓ Backup created'
    ls -lh /tmp/db-backups/ | tail -1
"

# Step 2: Export entire local database
echo ""
echo "→ Step 2: Exporting entire local database..."
EXPORT_FILE="${BACKUP_DIR}/gotrip_full_$(date +%Y%m%d_%H%M%S).sql"
mysqldump --all-databases --single-transaction --quick --lock-tables=false \
    -h 127.0.0.1 \
    --databases ${LOCAL_DB} > ${EXPORT_FILE} 2>/dev/null || {

    # Try using Laravel/PHP to export if mysqldump fails
    echo "  Using Laravel to export..."
    cd /home/lionel/Documents/Junkyard/gotrip/bc-cms
    php artisan tinker << 'EOF'
$tables = \DB::select('SHOW TABLES');
$sql = '';
foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    $createTable = \DB::selectOne("SHOW CREATE TABLE `$tableName`");
    $createSql = $createTable->{'Create Table'} ?? '';
    $sql .= $createSql . ";\n\n";

    $rows = \DB::table($tableName)->get();
    foreach($rows as $row) {
        $cols = array_keys((array)$row);
        $vals = array_map(fn($v) => is_null($v) ? 'NULL' : "'" . addslashes($v) . "'", array_values((array)$row));
        $sql .= "INSERT INTO `$tableName` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
    }
}
file_put_contents('$EXPORT_FILE', $sql);
echo "✓ Exported all tables\n";
exit();
EOF
}

echo "  ✓ Database exported ($(du -h ${EXPORT_FILE} | cut -f1))"

# Step 3: Compress for transfer
echo ""
echo "→ Step 3: Compressing database dump..."
gzip -f ${EXPORT_FILE}
EXPORT_FILE_GZ="${EXPORT_FILE}.gz"
echo "  ✓ Compressed ($(du -h ${EXPORT_FILE_GZ} | cut -f1))"

# Step 4: Upload to production
echo ""
echo "→ Step 4: Uploading to production server..."
scp ${EXPORT_FILE_GZ} ${REMOTE_USER}@${REMOTE_HOST}:/tmp/
echo "  ✓ Upload complete"

# Step 5: Import on production
echo ""
echo "→ Step 5: Importing database on production..."
ssh ${REMOTE_USER}@${REMOTE_HOST} "
    echo '  Decompressing...'
    gunzip -f /tmp/$(basename ${EXPORT_FILE_GZ})

    IMPORT_FILE='/tmp/$(basename ${EXPORT_FILE})'

    echo '  Dropping old database...'
    mysql -e \"DROP DATABASE IF EXISTS \\\`${REMOTE_DB}\\\`;\"

    echo '  Creating new database...'
    mysql -e \"CREATE DATABASE \\\`${REMOTE_DB}\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"

    echo '  Importing data (this may take a few minutes)...'
    mysql ${REMOTE_DB} < \$IMPORT_FILE

    echo '  ✓ Database imported successfully'

    echo '  Verifying...'
    TABLE_COUNT=\$(mysql ${REMOTE_DB} -sN -e \"SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='${REMOTE_DB}';\")
    echo \"  ✓ \$TABLE_COUNT tables in database\"

    rm -f \$IMPORT_FILE
"

# Step 6: Clear caches
echo ""
echo "→ Step 6: Clearing application caches..."
ssh ${REMOTE_USER}@${REMOTE_HOST} "
    cd /var/www/tsoka-portal/bc-cms
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan config:clear
    sudo -u www-data php artisan view:clear
    echo '  ✓ Caches cleared'
"

# Step 7: Restart PHP-FPM
echo ""
echo "→ Step 7: Restarting PHP-FPM..."
ssh ${REMOTE_USER}@${REMOTE_HOST} "
    systemctl restart php8.4-fpm
    echo '  ✓ PHP-FPM restarted'
"

# Cleanup local
rm -f ${EXPORT_FILE_GZ}

echo ""
echo "============================================================"
echo "  ✓ FULL DATABASE SYNC COMPLETE!"
echo "============================================================"
echo ""
echo "  ✅ Production database updated with local data"
echo "  📍 Portal: https://portal.tsokatravel.com"
echo ""
echo "  Backup location (on production):"
echo "    /tmp/db-backups/"
echo ""
echo "  To restore from backup if needed:"
echo "    ssh ${REMOTE_USER}@${REMOTE_HOST} gunzip -c /tmp/db-backups/tsoka_portal_backup_YYYYMMDD_HHMMSS.sql.gz | mysql"
echo ""
