#!/bin/bash

# Setup cron jobs for automated backups
# Usage: sudo ./setup_cron.sh

PROJECT_DIR="/opt/lampp/htdocs/panglong"
BACKUP_SCRIPT="$PROJECT_DIR/scripts/backup_database.sh"

echo "Setting up cron jobs for Panglong ERP backups..."

# Add daily backup at 2:00 AM
(crontab -l 2>/dev/null; echo "0 2 * * * $BACKUP_SCRIPT daily >> $PROJECT_DIR/backups/backup.log 2>&1") | crontab -

# Add weekly backup on Sunday at 3:00 AM
(crontab -l 2>/dev/null; echo "0 3 * * 0 $BACKUP_SCRIPT weekly >> $PROJECT_DIR/backups/backup.log 2>&1") | crontab -

# Add monthly backup on 1st of month at 4:00 AM
(crontab -l 2>/dev/null; echo "0 4 1 * * $BACKUP_SCRIPT monthly >> $PROJECT_DIR/backups/backup.log 2>&1") | crontab -

echo "Cron jobs configured:"
echo "  - Daily backup: 2:00 AM"
echo "  - Weekly backup: Sunday 3:00 AM"
echo "  - Monthly backup: 1st of month 4:00 AM"
echo ""
echo "View cron jobs: crontab -l"
echo "View backup log: tail -f $PROJECT_DIR/backups/backup.log"
