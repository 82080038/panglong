# DEVIN MASTER PROMPT — Panglong ERP

## Version: 2.0 (2026-06-30)

> Updated setelah Cycle 6. Aplikasi sudah stabil dengan 90/90 Playwright tests passing.
> File ini berisi prompt master untuk AI-assisted development cycle.
> Copy-paste isi "PROMPT MASTER" ke AI tool pilihan Anda.
> Gunakan "PROMPT LANJUTAN" untuk iterasi berikutnya.

---

## Rekomendasi Model GRATIS

| Model | Platform | Konteks | Kelebihan | Kekurangan |
|-------|----------|---------|-----------|------------|
| **DeepSeek V3** | deepseek.com (free) | 128K | Coding terbaik gratis, bagus untuk PHP/SQL | Rate limit cukup ketat |
| **DeepSeek R1** | deepseek.com (free) | 128K | Reasoning kuat, cocok untuk debug kompleks | Lambat (thinking mode) |
| **Qwen 2.5 Coder 32B** | HuggingFace / Groq (free) | 128K | Spesialis coding, open source | Kurang konteks bahasa Indonesia |
| **Google Gemini 2.0 Flash** | aistudio.google.com (free) | 1M | Konteks terbesar, bisa baca seluruh codebase sekaligus | Kadang halusinasi |
| **Llama 3.3 70B** | Groq.com (free) | 128K | Cepat (Groq), kompeten coding | Rate limit harian |
| **Claude 3.5 Sonnet** | claude.ai (free tier) | 200K | Sangat akurat untuk PHP | Limit pesan harian (~30-50) |

### Urutan Prioritas untuk Panglong ERP

1. **DeepSeek V3** — Best overall untuk coding PHP/SQL, gratis tanpa batas waktu
2. **Google Gemini 2.0 Flash** — Best untuk analisa seluruh codebase sekaligus (konteks 1M)
3. **Claude 3.5 Sonnet (free)** — Best akurasi, tapi terbatas jumlah pesan
4. **Qwen 2.5 Coder** — Alternatif jika DeepSeek rate-limited

### Strategi Multi-Model (GRATIS)

```
Gunakan kombinasi untuk menghemat kuota:

- DeepSeek V3  → Bug fix, feature add, coding harian
- Gemini 2.0   → Analisa seluruh codebase, review arsitektur
- Claude 3.5   → Task kompleks yang butuh akurasi tinggi (limited pesan)
- DeepSeek R1  → Debug bug sulit yang butuh reasoning mendalam
```

---

## PROMPT MASTER (Copy-paste ke AI)

```
You are working on the Panglong ERP project at /opt/lampp/htdocs/panglong.

## PROJECT CONTEXT
- Architecture: 100% PHP Native Procedural + PDO SQLite + jQuery AJAX + Bootstrap 5.3
- NO Laravel, NO Composer, NO framework
- Database: database/database.sqlite (84 tables, multi-tenant)
- AJAX: single endpoint frontend/ajax.php (?endpoint=xxx)
- Testing: Playwright E2E (26 specs, 90 tests)
- Completed Cycles: 6
  - Cycle 1-3: Stability, bug fixes, stock opname workflow
  - Cycle 4: FE-BE integration testing
  - Cycle 5: Page & data consistency audit
  - Cycle 6: Reorder to Purchase Order integration
- PHP: /opt/lampp/bin/php (8.2.12 with pdo_sqlite)
- Frontend URL: http://localhost/panglong/frontend/
- SUDO password: 8208
- Default login: admin / password123

## MANDATORY RULES
1. Use db() singleton — never new PDO()
2. Use API_URL constant — never hardcode 'ajax.php'
3. Use prepared statements for all SQL
4. Filter tenant: (tenant_id = ? OR tenant_id IS NULL) for products
5. Check permission: requireLogin(), requirePermission('slug')
6. No fn() arrow functions — use foreach loops (PHP 8.2 compat)
7. No DATE() in SQL with JOINs — use direct comparison
8. Composite UNIQUE (column, tenant_id) — never single-column for tenant tables
9. Always chmod 666 database/database.sqlite after git operations
10. Use test_mode=true in Playwright tests to bypass CSRF
11. Never delete or weaken existing tests
12. Never break backward compatibility
13. Prioritize stability over new features

## YOUR TASK
Execute the AI Development Cycle (see .devin/workflows/ai-development-cycle.md):

### Phase 1: ANALYZE
- Run: npx playwright test --reporter=list --workers=1
- Run: echo "8208" | sudo -S tail -50 /opt/lampp/logs/error_log
- Run: for f in frontend/*.php; do /opt/lampp/bin/php -l "$f"; done
- Check all pages return HTTP 200:
  curl -s -c /tmp/c.txt -L -X POST http://localhost/panglong/frontend/login.php -d "username=admin&password=password123" -o /dev/null
  for page in index products customers suppliers warehouses sales sales_orders quotations deliveries purchase-orders stock stock_opname stock_transfers batches reorder iot fleet routes accounting cashbook cash_flow fixed_assets e_faktur closing reports ai_insights marketplace landed_cost pricing settings saas users tenants returns whatsapp salesman_app; do
    code=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/c.txt "http://localhost/panglong/frontend/$page.php")
    echo "$page: $code"
  done
- Identify: bugs, missing features, UX issues, performance bottlenecks
- Document findings in docs/development-iteration-{N}.md

### Phase 2: FIX
- Fix all PHP syntax errors first (highest priority)
- Fix all test failures
- Fix all HTTP 500 errors
- Fix all error_log entries
- For each fix: search for similar error patterns in ALL frontend/*.php files
- Run syntax check after each fix: /opt/lampp/bin/php -l [file]
- Common error patterns to check:
  - PHP arrow functions (fn()) → Replace with foreach loops
  - SQL DATE() with JOINs → Simplify to direct comparison
  - Date comparison → Use field >= ? AND field <= ? with ' 00:00:00' and ' 23:59:59'
  - Undefined property access → Use null coalescing (??)

### Phase 3: TEST
- Run: npx playwright test --reporter=list --workers=1
- If any test fails → go back to Phase 2
- If all pass → proceed to Phase 4

### Phase 4: IMPROVE
- Review gap features from docs/DEVELOPMENT_ROADMAP.md:
  - SaaS management (subscriptions, invoices, payment workflow)
  - Super admin dashboard (tenants, revenue, system stats)
  - Multi-tenant tenant isolation audit
  - Warehouse locations & IoT sensor integration
  - Advanced reports & analytics
  - Mobile/PWA salesman app
- Review security checklist from .devin/workflows/deploy.md
- Implement improvements ONE AT A TIME
- Test after each improvement

### Phase 5: VERIFY
- All 90 Playwright tests must pass
- Zero PHP errors in error_log
- All pages return HTTP 200
- No JavaScript console errors
- Page load < 2 seconds
- AJAX response < 500ms

### EXIT CRITERIA
1. Zero critical bugs (no crashes, data loss, security vulnerabilities)
2. Zero high-priority issues (all important features work correctly)
3. All tests passing (100% pass rate)
4. Code quality meets standards (PSR-12, proper error handling, input validation)
5. Performance targets met (page < 2s, AJAX < 500ms)
6. Documentation complete (all features documented)
7. All gap features implemented

## ITERATION RULES
- Work in focused iterations — one area per iteration
- After each iteration: git commit with descriptive message
- Update docs/PROGRESS.md after each iteration
- If stuck on a bug for >3 attempts: document it and move on
- Never delete or weaken existing tests
- Never break backward compatibility
- Prioritize stability over new features

## START NOW
1. Read .devin/workflows/ai-development-cycle.md
2. Read docs/PROMPTING_GUIDE.md
3. Read docs/DEVELOPMENT_HANDOFF.md
4. Run all tests to get baseline: npx playwright test --reporter=list --workers=1
5. Check error log: echo "8208" | sudo -S tail -50 /opt/lampp/logs/error_log
6. Begin Phase 1 analysis
```

---

## PROMPT LANJUTAN (Untuk iterasi berikutnya — BATCH & AUTONOMOUS)

```
Continue the AI Development Cycle for Panglong ERP at /opt/lampp/htdocs/panglong.

Objective: Work AUTONOMOUSLY and in BATCH to complete remaining gap features.

1. Read docs/PROGRESS.md to see completed cycles
2. Read docs/development-iteration-{N}.md for latest findings
3. Run all tests to verify current state:
   npx playwright test --reporter=list --workers=1
4. Check error log:
   echo "8208" | sudo -S tail -50 /opt/lampp/logs/error_log
5. Check all pages return 200 (batch):
   curl -s -c /tmp/c.txt -L -X POST http://localhost/panglong/frontend/login.php -d "username=admin&password=password123" -o /dev/null
   for page in index products customers suppliers warehouses sales sales_orders quotations deliveries purchase-orders stock stock_opname stock_transfers batches reorder iot fleet routes accounting cashbook cash_flow fixed_assets e_faktur closing reports ai_insights marketplace landed_cost pricing settings saas users tenants returns whatsapp salesman_app; do
     code=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/c.txt "http://localhost/panglong/frontend/$page.php")
     [ "$code" != "200" ] && echo "FAIL: $page = $code"
   done
6. Check data consistency (batch):
   /opt/lampp/bin/php scripts/db_consistency_check.php
7. Check all pages for errors (batch):
   /opt/lampp/bin/php scripts/page_audit.php
8. Identify remaining issues from the exit criteria
9. Pick the highest priority unfinished item and implement it
10. Run tests again → fix → repeat until all pass
11. Update docs/PROGRESS.md and create docs/development-iteration-{N}.md
12. Commit: git add -A && git commit -m "Cycle N: [description]"
13. If exit criteria not all met → repeat from step 3
14. If all exit criteria met → report completion summary

MANDATORY RULES:
- Use db() singleton, never new PDO()
- Use API_URL constant, never hardcode 'ajax.php'
- No fn() arrow functions (PHP 8.2 compat)
- No DATE() in SQL with JOINs
- Composite UNIQUE (column, tenant_id) for tenant tables
- chmod 666 database/database.sqlite after git operations
- Never delete or weaken tests
- Never break backward compatibility
- Always run full Playwright suite before commit
- Always check error log after tests
```

---

## PROMPT BATCH AUTONOMOUS — SELESAIKAN SEMUA CYCLE

```
Lanjutkan pengerjaan Panglong ERP di /opt/lampp/htdocs/panglong secara BATCH dan AUTONOMOUS.

Tujuan: Selesaikan semua gap feature yang tersisa tanpa menunggu instruksi per item.

Mode kerja:
- Jalankan semua check dalam batch (page audit, db consistency, tests, error log)
- Identifikasi semua issue sekaligus
- Kerjakan issue secara batch: fix → test → commit
- Jika satu cycle selesai, LANJUT otomatis ke cycle berikutnya
- Update progress setiap cycle

Workflow per cycle:
1. Baca docs/PROGRESS.md dan iteration terakhir
2. Jalankan batch audit:
   /opt/lampp/bin/php scripts/page_audit.php
   /opt/lampp/bin/php scripts/db_consistency_check.php
   npx playwright test --reporter=list --workers=1
   echo "8208" | sudo -S tail -50 /opt/lampp/logs/error_log
3. Catat semua issue dalam docs/development-iteration-{N}.md
4. Perbaiki semua issue yang ditemukan
5. Jalankan batch audit lagi
6. Jika semua passing → update docs/PROGRESS.md → commit
7. Pilih gap feature berikutnya dari roadmap → kembali ke langkah 1

Gap features yang tersisa (prioritas):
1. SaaS subscription & invoice payment workflow
2. Super admin dashboard (tenants, revenue, stats)
3. Multi-tenant tenant isolation audit & fix
4. Warehouse locations & IoT sensor integration
5. Advanced reports & analytics
6. Mobile/PWA salesman app

RULES:
- Gunakan db() singleton, API_URL, prepared statements
- No fn() arrow functions, no DATE() in SQL JOINs
- Jangan hapus atau lemahkan test
- Jangan break backward compatibility
- Setiap perubahan harus diikuti test
- Selalu commit di akhir cycle
- Jika stuck >3 kali: dokumentasikan, lalu pindah ke fitur berikutnya
```

---

## PROMPT BUG FIX SPESIFIK

```
Fix bug in [FILE]: [ERROR DESCRIPTION]

Project: /opt/lampp/htdocs/panglong (PHP Native + PDO SQLite + jQuery)

Steps:
1. Check error log: echo "8208" | sudo -S tail -20 /opt/lampp/logs/error_log
2. Reproduce with curl:
   curl -s -c /tmp/c.txt -L -X POST http://localhost/panglong/frontend/login.php -d "username=admin&password=password123" -o /dev/null
   curl -s -b /tmp/c.txt -X POST "http://localhost/panglong/frontend/ajax.php?endpoint=XXX&test_mode=true" -H "Content-Type: application/json" -d '{...}'
3. Fix root cause, not symptom
4. Search for same error pattern in ALL frontend/*.php files:
   grep -rn "PATTERN" frontend/
5. Fix all occurrences found
6. Syntax check: /opt/lampp/bin/php -l [file]
7. Run related test: npx playwright test tests/e2e/[spec].js --reporter=list --workers=1
8. Run full test suite: npx playwright test --reporter=list --workers=1
9. If all pass: git add -A && git commit -m "fix: [description]"
10. Update docs/PROGRESS.md

RULES:
- Use db() singleton, API_URL constant, prepared statements
- No fn() arrow functions, no DATE() in SQL JOINs
- Check tenant_id filter where applicable
```

---

## PROMPT TAMBAH FITUR

```
Add feature [NAME] to [PAGE] in Panglong ERP at /opt/lampp/htdocs/panglong.

Architecture: PHP Native + PDO SQLite + jQuery AJAX + Bootstrap 5.3

Steps:
1. Read existing pattern from frontend/ajax.php (find similar endpoint)
2. Add new endpoint in ajax.php (follow existing pattern):
   - Check permission with requirePermission()
   - Use prepared statements
   - Filter by tenant_id where applicable
   - Return JSON response
3. Add endpoint to endpointRoles map in ajax.php
4. Add UI to frontend/[page].php:
   - Use Bootstrap 5.3 components
   - Use fetch(API_URL + '?endpoint=...') pattern
   - Follow existing page layout (renderHead, renderNav, renderFoot)
5. Syntax check: /opt/lampp/bin/php -l frontend/ajax.php && /opt/lampp/bin/php -l frontend/[page].php
6. Test with curl:
   curl -s -c /tmp/c.txt -L -X POST http://localhost/panglong/frontend/login.php -d "username=admin&password=password123" -o /dev/null
   curl -s -b /tmp/c.txt -X POST "http://localhost/panglong/frontend/ajax.php?endpoint=NEW_ENDPOINT&test_mode=true" -H "Content-Type: application/json" -d '{...}'
7. Run tests: npx playwright test --reporter=list --workers=1
8. Update docs/API_SPECIFICATION.md
9. Commit: git add -A && git commit -m "feat: [description]"

RULES:
- Use db() singleton, API_URL constant, prepared statements
- No fn() arrow functions, no DATE() in SQL JOINs
- Composite UNIQUE (column, tenant_id) for tenant tables
- Never break existing functionality
```

---

## CATATAN PENGGUNAAN

### Untuk DeepSeek V3 (deepseek.com, gratis)
- Upload file `docs/DEVELOPMENT_HANDOFF.md` dan `docs/PROMPTING_GUIDE.md` sebagai konteks
- Copy PROMPT MASTER di atas
- Rate limit: ~50 pesan/hari, cukup untuk 2-3 iterasi

### Untuk Gemini 2.0 Flash (aistudio.google.com, gratis)
- Upload seluruh folder `frontend/` atau file-file kunci
- Konteks 1M token = bisa baca ajax.php (4000+ lines) sekaligus
- Best untuk analisa menyeluruh

### Untuk Claude 3.5 Sonnet (claude.ai, free tier)
- Limited ~30-50 pesan/hari
- Gunakan untuk task yang butuh akurasi tinggi saja
- Copy prompt + attach file yang relevan

### Untuk Windsurf/Cascade (IDE ini)
- Sudah punya akses langsung ke codebase
- Gunakan slash command: `/ai-development-cycle`
- Model: bisa pilih model gratis yang tersedia

### Strategi Menghemat Kuota Gratis
1. **Analisa besar** → Gemini 2.0 Flash (konteks 1M, sekali baca semua)
2. **Coding harian** → DeepSeek V3 (gratis, kompeten)
3. **Bug sulit** → DeepSeek R1 (reasoning mode)
4. **Review akhir** → Claude 3.5 Sonnet (akurasi, limited pesan)
5. **Eksekusi langsung di IDE** → Windsurf/Cascade (sudah terhubung codebase)
