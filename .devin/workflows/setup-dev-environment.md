---
description: Setup development environment for Panglong ERP
---

# Setup Development Environment Workflow

## Prerequisites
- XAMPP installed with PHP 8.2+ and pdo_sqlite extension
- Git installed
- Web browser (Chrome/Firefox)

## Steps

1. **Clone repository**
   ```bash
   cd /opt/lampp/htdocs
   git clone <repo-url> panglong
   cd panglong
   ```

2. **Start XAMPP**
   ```bash
   sudo /opt/lampp/lampp start
   ```

3. **Set database permissions** (Linux/macOS only)
   ```bash
   chmod 666 database/database.sqlite
   chmod 777 database/
   ```

4. **Verify database connection**
   ```bash
   /opt/lampp/bin/php -r "new PDO('sqlite:database/database.sqlite'); echo 'SQLite OK';"
   ```

5. **Access frontend**
   Open browser: http://localhost/panglong/frontend/login.php

6. **Login with default credentials**
   - Username: admin
   - Password: password123

## Notes
- Database SQLite already contains 78 tables with seed data
- No migration or seeder needed for frontend
- Laravel backend is scaffolded but not used by frontend
- Frontend uses PHP Native + PDO SQLite + jQuery AJAX
