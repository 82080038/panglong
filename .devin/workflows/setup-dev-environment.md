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
- Database SQLite already contains 87 tables with seed data
- No migration or seeder needed for frontend
- Frontend uses PHP Native + PDO SQLite + jQuery AJAX
- Use XAMPP PHP (`/opt/lampp/bin/php` 8.2.12) for all PHP CLI tasks, NOT system PHP
- System PHP (8.3.6) does NOT have pdo_sqlite extension
- Apache user is `daemon` — database file must be `chmod 666` and dir `chmod 777`
- 7 user roles: owner, manager, kasir, gudang, accounting, supervisor, super_admin
- 58+ AJAX endpoints in `frontend/ajax.php` (3905 lines)
- 51 frontend PHP pages, 26 E2E test specs (~88 test cases)
- 39 migrations, 63 models, 32 controllers, 20 services (Laravel backend — unused by frontend)
- Git remote: https://github.com/82080038/panglong.git (branch: master)
- Sync from GitHub: `git pull origin master`
