#!/bin/bash

# Database Backup Script for Tsoka Portal
# Backs up MySQL database and stores in S3 with daily/weekly retention

set -e

# Configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD}"
DB_NAME="${DB_NAME:-tsoka_portal}"
BACKUP_DIR="/var/backups/tsoka"
S3_BUCKET="${S3_BUCKET:-tsoka-backups}"
S3_REGION="${AWS_DEFAULT_REGION:-us-east-1}"
RETENTION_DAYS=30
WEEKLY_RETENTION_DAYS=90

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate backup filename
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DATE=$(date +%Y%m%d)
BACKUP_FILE="$BACKUP_DIR/tsoka_${TIMESTAMP}.sql.gz"
BACKUP_FILENAME="tsoka_${TIMESTAMP}.sql.gz"

# Perform backup
echo "[$(date)] Starting database backup..."

if [ -z "$DB_PASSWORD" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" | gzip > "$BACKUP_FILE"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip > "$BACKUP_FILE"
fi

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "[$(date)] Backup completed: $BACKUP_SIZE"

# Upload to S3
echo "[$(date)] Uploading to S3..."
aws s3 cp "$BACKUP_FILE" "s3://$S3_BUCKET/daily/$BACKUP_FILENAME" \
    --region "$S3_REGION" \
    --storage-class STANDARD_IA \
    --metadata "date=$DATE,size=$BACKUP_SIZE,host=$DB_HOST"

# Weekly backup (Sunday)
if [ "$(date +%A)" = "Sunday" ]; then
    WEEKLY_FILE="tsoka_${DATE}_weekly.sql.gz"
    cp "$BACKUP_FILE" "$BACKUP_DIR/$WEEKLY_FILE"
    aws s3 cp "$BACKUP_DIR/$WEEKLY_FILE" "s3://$S3_BUCKET/weekly/$WEEKLY_FILE" \
        --region "$S3_REGION" \
        --storage-class GLACIER
    echo "[$(date)] Weekly backup uploaded"
fi

# Clean local old backups (keep 7 days locally)
find "$BACKUP_DIR" -name "tsoka_*.sql.gz" -mtime +7 -delete

echo "[$(date)] Backup script completed successfully"
exit 0
