#!/bin/bash

###############################################################################
# Database Sync Script - Local to Production
# Syncs zones, experiences, and location data from local gotrip to production tsoka_portal
###############################################################################

set -e

echo "============================================================"
echo "  Database Sync: Local → Production"
echo "============================================================"
echo ""

# Local database
LOCAL_DB="gotrip"
LOCAL_HOST="127.0.0.1"

# Remote database
REMOTE_DB="tsoka_portal"
REMOTE_HOST="169.239.182.80"
REMOTE_USER="root"

# Tables to sync
TABLES_TO_SYNC=(
    "bc_locations"
    "bc_location_translations"
    "location_category"
    "location_category_translations"
)

echo "→ Step 1: Exporting local database tables..."
mkdir -p /tmp/db-sync

for table in "${TABLES_TO_SYNC[@]}"; do
    echo "  Exporting $table..."
    mysqldump -h $LOCAL_HOST $LOCAL_DB $table > /tmp/db-sync/$table.sql
    echo "    ✓ $table exported"
done

echo ""
echo "→ Step 2: Uploading to production server..."

for table in "${TABLES_TO_SYNC[@]}"; do
    echo "  Uploading $table..."
    scp /tmp/db-sync/$table.sql ${REMOTE_USER}@${REMOTE_HOST}:/tmp/
    echo "    ✓ $table uploaded"
done

echo ""
echo "→ Step 3: Importing to production database..."

ssh ${REMOTE_USER}@${REMOTE_HOST} "
    for table in ${TABLES_TO_SYNC[@]}; do
        echo \"  Importing \$table...\"
        # Drop existing table to ensure clean sync
        mysql $REMOTE_DB -e \"DROP TABLE IF EXISTS \\\`\$table\\\`;\"
        # Import the new data
        mysql $REMOTE_DB < /tmp/\$table.sql
        echo \"    ✓ \$table imported\"
    done
    echo \"  Cleaning up...\"
    rm -f /tmp/bc_locations.sql /tmp/bc_location_translations.sql /tmp/location_category.sql /tmp/location_category_translations.sql
"

echo ""
echo "============================================================"
echo "  ✓ Database sync complete!"
echo "============================================================"
echo ""
echo "  Tables synced:"
for table in "${TABLES_TO_SYNC[@]}"; do
    echo "    ✓ $table"
done
echo ""
echo "  Portal: https://portal.tsokatravel.com"
echo ""
