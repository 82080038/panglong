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

## PHPUnit Tests (Laravel Backend)

1. **Run PHPUnit tests**
   ```bash
   /opt/lampp/bin/php artisan test
   ```

2. **Run specific test**
   ```bash
   /opt/lampp/bin/php artisan test --filter AuthServiceTest
   ```

## Notes
- Playwright tests: 50 tests across 19 specs (all passing)
- PHPUnit tests: 14 test files for Laravel API
- Laravel API is scaffolded but not used by frontend
