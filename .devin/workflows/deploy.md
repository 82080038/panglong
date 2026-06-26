---
description: Deploy Panglong ERP to production
---

# Deploy Panglong ERP Workflow

## Deployment Options

### Option 1: Docker Deployment

1. **Build Docker image**
   ```bash
   docker build -t panglong-erp .
   ```

2. **Run with Docker Compose**
   ```bash
   docker-compose up -d
   ```

3. **Access application**
   - Frontend: http://localhost
   - Laravel API: http://localhost:8000

### Option 2: XAMPP Deployment

1. **Copy files to production server**
   ```bash
   rsync -avz /opt/lampp/htdocs/panglong/ user@server:/var/www/html/panglong/
   ```

2. **Set permissions**
   ```bash
   chmod 666 database/database.sqlite
   chmod 777 database/
   chmod -R 755 frontend/
   ```

3. **Configure Apache virtual host**
   ```apache
   <VirtualHost *:80>
       ServerName panglong.example.com
       DocumentRoot /var/www/html/panglong/frontend
       <Directory /var/www/html/panglong/frontend>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

### Option 3: Production Database Migration

If migrating from SQLite to MySQL for production:

1. **Export SQLite data**
   ```bash
   /opt/lampp/bin/php database/export_sqlite.php
   ```

2. **Create MySQL database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE panglong_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

3. **Import to MySQL**
   ```bash
   mysql -u root -p panglong_prod < database/mysql_schema_dump.sql
   mysql -u root -p panglong_prod < database/mysql_data_dump.sql
   ```

4. **Update frontend/db.php**
   ```php
   $db = new PDO('mysql:host=localhost;dbname=panglong_prod', 'username', 'password');
   ```

## Security Checklist

- [ ] Change default passwords
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure firewall
- [ ] Set up regular backups
- [ ] Enable audit logging
- [ ] Review file permissions
- [ ] Disable debug mode in production
