#!/bin/bash

# Panglong ERP - Automated Database Backup Script
# Usage: ./backup_database.sh [daily|weekly|monthly]
# Default: daily

BACKUP_TYPE=${1:-daily}
PROJECT_DIR="/opt/lampp/htdocs/panglong"
DATABASE_FILE="$PROJECT_DIR/database/database.sqlite"
BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7
RETENTION_WEEKS=4
RETENTION_MONTHS=12

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR/daily"
mkdir -p "$BACKUP_DIR/weekly"
mkdir -p "$BACKUP_DIR/monthly"

# Backup function
backup() {
    local type=$1
    local retention=$2
    local dest_dir="$BACKUP_DIR/$type"
    local backup_file="$dest_dir/panglong_erp_${type}_${TIMESTAMP}.sqlite"
    
    echo "[$(date)] Starting $type backup..."
    
    # Use sqlite3 .backup for consistent backup (handles WAL mode)
    if command -v sqlite3 &> /dev/null; then
        sqlite3 "$DATABASE_FILE" ".backup '$backup_file'"
    else
        # Fallback: copy the file
        cp "$DATABASE_FILE" "$backup_file"
    fi
    
    # Compress
    gzip "$backup_file"
    
    echo "[$(date)] $type backup completed: panglong_erp_${type}_${TIMESTAMP}.sqlite.gz"
    
    # Clean old backups
    find "$dest_dir" -name "panglong_erp_${type}_*.sqlite.gz" -type f -mtime +$retention -delete
    echo "[$(date)] Cleaned backups older than $retention days"
}

# Execute backup based on type
case $BACKUP_TYPE in
    daily)
        backup "daily" $RETENTION_DAYS
        ;;
    weekly)
        backup "weekly" $((RETENTION_WEEKS * 7))
        ;;
    monthly)
        backup "monthly" $((RETENTION_MONTHS * 30))
        ;;
    *)
        echo "Usage: $0 [daily|weekly|monthly]"
        exit 1
        ;;
esac

echo "[$(date)] Backup process finished"
