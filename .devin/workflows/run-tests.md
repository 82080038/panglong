---
description: Run tests for Panglong ERP
---

# Run Tests Workflow

## Playwright E2E Tests

1. **Install Playwright dependencies** (first time only)
   ```bash
   npx playwright install
   ```

2. **Run all E2E tests**
   ```bash
   npx playwright test
   ```

3. **Run tests with UI**
   ```bash
   npx playwright test --headed
   ```

4. **Run specific test file**
   ```bash
   npx playwright test tests/e2e/login.spec.js
   ```

## PHP Syntax Check

1. **Check single file**
   ```bash
   /opt/lampp/bin/php -l frontend/file.php
   ```

2. **Check all frontend PHP files**
   ```bash
   for f in frontend/*.php; do /opt/lampp/bin/php -l "$f"; done
   ```

## Page HTTP Status Check

1. **Login and save cookies**
   ```bash
   curl -s -c /tmp/test_cookies.txt -d 'username=admin&password=password123' http://localhost/panglong/frontend/login.php
   ```

2. **Check all pages return 200**
   ```bash
   for page in index products customers suppliers warehouses sales sales_orders quotations deliveries purchase-orders stock stock_opname stock_transfers batches reorder iot fleet routes accounting cashbook cash_flow fixed_assets e_faktur closing reports ai_insights marketplace landed_cost pricing settings saas users tenants returns whatsapp salesman_app; do
     code=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/test_cookies.txt "http://localhost/panglong/frontend/$page.php")
     echo "$page: $code"
   done
   ```

## Notes
- Playwright tests: ~55 tests across 23 specs (all passing)
- Use XAMPP PHP (`/opt/lampp/bin/php`) for syntax checks, NOT system PHP
- System PHP (8.3.6) does NOT have pdo_sqlite extension
- Laravel backend is scaffolded but NOT used by frontend
