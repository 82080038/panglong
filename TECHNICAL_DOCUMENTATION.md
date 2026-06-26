# TECHNICAL DOCUMENTATION STRUCTURE

# PANGLONG ERP - PHASE 1 MVP

## Version: 2.0 (Updated 2026-06-26)
## Last Updated: 2026-06-26
## Status: ALL SPRINTS (1-12) + GAP FEATURES + UI/UX COMPLETED

> **ARSITEKTUR AKTUAL (Updated 2026-06-26):** Frontend menggunakan PHP Native
> + PDO SQLite + jQuery AJAX. `frontend/ajax.php` adalah single endpoint (1940 lines, 48 endpoints) untuk
> semua CRUD operations. Laravel backend API ada di repo tetapi TIDAK digunakan
> oleh frontend. Database: SQLite (`database/database.sqlite`, 78 tables).
> Frontend: 45 PHP pages, 19 Playwright E2E specs (50 tests, ALL PASSING).
> UI: RBAC nav per role, dark mode, eye-care mode, fullscreen toggle, responsive design.
> Lihat PROJECT_STATUS.md dan DEVELOPMENT_ROADMAP.md untuk detail.

---

# AKTUAL ARCHITECTURE SUMMARY

## Yang Berjalan Saat Ini

```
[Browser]
  ↓
[PHP Server-Side Rendering] — frontend/*.php (45 pages)
  ├── Direct PDO SQLite queries untuk initial page load
  └── jQuery 3.6 $.ajax() → frontend/ajax.php (1940 lines, 48 endpoints) → PDO SQLite
  ↓
[database/database.sqlite] — 78 tables
```

## Komponen Utama

| Komponen | File | Fungsi |
|----------|------|--------|
| DB Connection | `frontend/db.php` | PDO SQLite singleton, `PRAGMA foreign_keys = ON` |
| Auth | `frontend/auth.php` | Session-based, `login()`, `logout()`, `hasPermission()` |
| Config | `frontend/config.php` | Session timeout 30 min, RBAC navbar, dark mode, fullscreen, CDN |
| AJAX Endpoint | `frontend/ajax.php` | 1940 lines, 48 endpoints, all CRUD operations |
| Login | `frontend/login.php` | Login page with quick login buttons |
| PWA | `frontend/manifest.json`, `frontend/sw.js` | Offline-first service worker |

## Frontend Pages (45 total)

### Core (5)
`db.php`, `auth.php`, `config.php`, `ajax.php`, `login.php`

### Business Pages (40)
`index.php` (Dashboard), `products.php`, `product_detail.php`, `customers.php`, `customer_detail.php`,
`sales.php`, `sale_detail.php`, `deliveries.php`, `stock.php`, `stock_opname.php`, `stock_transfers.php`,
`suppliers.php`, `purchase-orders.php`, `reports.php`, `settings.php`, `users.php`, `print_nota.php`,
`accounting.php`, `cashbook.php`, `fixed_assets.php`, `cash_flow.php`, `closing.php`,
`warehouses.php`, `reorder.php`, `ai_insights.php`, `saas.php`, `marketplace.php`, `iot.php`,
`quotations.php`, `sales_orders.php`, `returns.php`, `pricing.php`,
`fleet.php`, `routes.php`, `whatsapp.php`, `e_faktur.php`,
`landed_cost.php`, `batches.php`, `salesman_app.php`, `logout.php`

## RBAC Navigation per Role

| Role | Menu Count | Key Menus |
|------|-----------|-----------|
| Owner | 35 | All menus + Pengguna, Pengaturan, SaaS |
| Manager | 34 | All except SaaS |
| Kasir | 9 | Beranda, Pelanggan, Penjualan, SO, Penawaran, Pengiriman, Retur, WhatsApp, Salesman |
| Gudang | 15 | Beranda, Produk, Stok, Opname, Mutasi, Supplier, PO, Gudang, Landed Cost, Batch/FIFO, IoT, Kendaraan, Rute, Pengiriman, Retur |
| Accounting | 11 | Beranda, Pelanggan, Laporan, Akuntansi, Kas Buku, Aset Tetap, Arus Kas, Tutup Buku, e-Faktur |
| Supervisor | 3 | Beranda, Laporan |

## UI/UX Features

- **Dark mode**: `data-bs-theme="dark"`, session-based toggle
- **Eye-care mode**: `data-bs-theme="eyecare"`, sepia theme for 24-hour usage
- **Fullscreen toggle**: Fullscreen API, auto-hides navbar
- **Responsive**: Mobile (<576px), Tablet (768px), Desktop (1200px+), Ultra-wide (1900px+)
- **Professional UI**: Gradient navbar, card shadows, Bootstrap 5.3 + Bootstrap Icons

---

# DOCUMENTATION OVERVIEW

This document outlines the complete technical documentation structure for the Panglong ERP MVP project. All documentation should be maintained in the `/docs` directory.

---

# DOCUMENTATION DIRECTORY STRUCTURE

```
/docs
├── 00-getting-started/
│   ├── README.md
│   ├── installation.md
│   ├── requirements.md
│   └── quick-start.md
├── 01-architecture/
│   ├── overview.md
│   ├── system-design.md
│   ├── database-design.md
│   └── api-design.md
├── 02-development/
│   ├── coding-standards.md
│   ├── git-workflow.md
│   ├── branch-strategy.md
│   └── code-review.md
├── 03-api-documentation/
│   ├── authentication.md
│   ├── sales-api.md
│   ├── products-api.md
│   ├── customers-api.md
│   ├── inventory-api.md
│   └── reports-api.md
├── 04-database/
│   ├── schema.md
│   ├── migrations.md
│   ├── seeders.md
│   └── queries.md
├── 05-testing/
│   ├── testing-guide.md
│   ├── unit-testing.md
│   ├── feature-testing.md
│   └── coverage-report.md
├── 06-deployment/
│   ├── environment-setup.md
│   ├── deployment-checklist.md
│   ├── backup-strategy.md
│   └── monitoring.md
├── 07-user-guides/
│   ├── user-manual.md
│   ├── admin-guide.md
│   ├── troubleshooting.md
│   └── faq.md
├── 08-maintenance/
│   ├── update-guide.md
│   ├── data-migration.md
│   └── performance-tuning.md
└── 09-appendix/
    ├── glossary.md
    ├── changelog.md
    └── references.md
```

---

# DOCUMENTATION TEMPLATES

Each documentation file should follow this template:

```markdown
# [Document Title]

## Version: X.X
## Last Updated: YYYY-MM-DD
## Author: [Author Name]

---

# Table of Contents

1. [Section 1](#section-1)
2. [Section 2](#section-2)
3. [Section 3](#section-3)

---

# Section 1

[Content]

---

# Section 2

[Content]

---

# Section 3

[Content]
```

---

# DOCUMENTATION CONTENT

## 00-GETTING-STARTED

### README.md
- Project overview
- Features list
- Technology stack
- Links to other documentation

### installation.md
- System requirements
- Installation steps
- Configuration
- Verification steps

### requirements.md
- Hardware requirements
- Software requirements
- PHP version requirements
- Database requirements
- Browser requirements

### quick-start.md
- 5-minute setup guide
- Basic configuration
- First login
- Creating first sale

---

## 01-ARCHITECTURE

### overview.md
- High-level architecture
- System components
- Technology choices rationale
- Architecture diagrams

### system-design.md
- Design patterns used
- Service layer pattern
- Repository pattern
- Dependency injection
- Event-driven architecture

### database-design.md
- Database schema overview
- ERD diagrams
- Relationship definitions
- Indexing strategy
- Partitioning strategy

### api-design.md
- RESTful principles
- API versioning strategy
- Authentication mechanism
- Rate limiting
- Error handling

---

## 02-DEVELOPMENT

### coding-standards.md
- PSR standards compliance
- Code formatting rules
- Naming conventions
- Comment standards
- Documentation requirements

### git-workflow.md
- Git conventions
- Commit message format
- Branch naming
- Merge strategies
- Tagging releases

### branch-strategy.md
- Main branch
- Develop branch
- Feature branches
- Hotfix branches
- Release branches

### code-review.md
- Review checklist
- Approval process
- Review guidelines
- Common issues to check

---

## 03-API-DOCUMENTATION

### authentication.md
- Login endpoint
- Token management
- Permission system
- Session handling
- Security best practices

### sales-api.md
- Sales endpoints
- Request/response formats
- Error codes
- Examples
- Use cases

### products-api.md
- Product endpoints
- Search functionality
- Barcode scanning
- Multi-unit support
- Examples

### customers-api.md
- Customer endpoints
- Credit management
- Group management
- Examples

### inventory-api.md
- Stock management
- Adjustments
- Stock opname
- Movement history
- Examples

### reports-api.md
- Report endpoints
- Filtering options
- Export formats
- Scheduling
- Examples

---

## 04-DATABASE

### schema.md
- Complete table definitions
- Column descriptions
- Data types
- Constraints
- Relationships

### migrations.md
- Migration files
- Rollback procedures
- Migration order
- Version history

### seeders.md
- Seed data
- Development data
- Test data
- Production data

### queries.md
- Common queries
- Performance queries
- Reporting queries
- Maintenance queries

---

## 05-TESTING

### testing-guide.md
- Testing philosophy
- Test types
- Testing tools
- Running tests
- CI/CD integration

### unit-testing.md
- Unit test principles
- Service testing
- Repository testing
- Helper testing
- Examples

### feature-testing.md
- API testing
- Integration testing
- End-to-end testing
- Examples

### coverage-report.md
- Coverage goals
- Current coverage
- Coverage by module
- Improvement plan

---

## 06-DEPLOYMENT

### environment-setup.md
- Development environment
- Staging environment
- Production environment
- Environment variables

### deployment-checklist.md
- Pre-deployment checks
- Deployment steps
- Post-deployment verification
- Rollback procedures

### backup-strategy.md
- Database backup
- File backup
- Backup schedule
- Restoration procedures

### monitoring.md
- Application monitoring
- Error tracking
- Performance monitoring
- Log management
- Alerts setup

---

## 07-USER-GUIDES

### user-manual.md
- User roles
- POS operations
- Inventory management
- Sales management
- Reports

### admin-guide.md
- System administration
- User management
- Configuration
- Maintenance tasks
- Troubleshooting

### troubleshooting.md
- Common issues
- Error messages
- Solutions
- Support contacts

### faq.md
- Frequently asked questions
- How-to guides
- Tips and tricks
- Best practices

---

## 08-MAINTENANCE

### update-guide.md
- Update procedures
- Version compatibility
- Migration steps
- Testing requirements

### data-migration.md
- Data export
- Data import
- Format requirements
- Validation procedures

### performance-tuning.md
- Database optimization
- Query optimization
- Caching strategies
- Load balancing

---

## 09-APPENDIX

### glossary.md
- Technical terms
- Business terms
- Acronyms
- Definitions

### changelog.md
- Version history
- New features
- Bug fixes
- Breaking changes

### references.md
- External documentation
- Libraries used
- Standards referenced
- Useful links

---

# DOCUMENTATION MAINTENANCE

## Review Schedule
- **Monthly**: Review and update user guides
- **Quarterly**: Review and update technical documentation
- **Per Release**: Update changelog and version notes

## Update Process
1. Create branch for documentation update
2. Make changes to documentation files
3. Update version numbers and dates
4. Submit pull request
5. Review and approve
6. Merge to main branch

## Documentation Quality Checklist
- [ ] All sections are complete
- [ ] Code examples are accurate
- [ ] Screenshots are up-to-date
- [ ] Links are working
- [ ] Version number is updated
- [ ] Last updated date is current
- [ ] Author is credited
- [ ] Table of contents is accurate

---

# DOCUMENTATION TOOLS

## Recommended Tools
- **Markdown Editor**: VS Code with Markdown All in One extension
- **Diagrams**: Draw.io, Mermaid
- **API Documentation**: Postman, Swagger/OpenAPI
- **Screen Capture**: Snagit, Lightshot
- **Version Control**: Git

## Documentation Generation
- API docs can be auto-generated from code comments
- Database schema can be auto-generated from migrations
- Class diagrams can be generated from code

---

# DOCUMENTATION STANDARDS

## Writing Style
- Use clear, concise language
- Avoid jargon where possible
- Define technical terms
- Use active voice
- Keep paragraphs short
- Use lists for clarity

## Code Examples
- Include complete, runnable examples
- Add comments explaining key points
- Use realistic data
- Show both success and error cases
- Include expected output

## Screenshots
- Use high-resolution screenshots
- Highlight important areas
- Add captions
- Keep images small and optimized
- Use consistent styling

## Diagrams
- Use consistent notation
- Include legends
- Keep diagrams simple
- Use appropriate tools
- Export in multiple formats

---

# DOCUMENTATION ACCESS

## Internal Access
- All documentation available in `/docs` directory
- Accessible via Git repository
- Can be viewed in IDE
- Can be published to internal wiki

## External Access
- User guides can be published to public website
- API documentation can be published to API portal
- Technical docs restricted to development team

---

# DOCUMENTATION VERSIONING

## Version Numbers
- Follow semantic versioning: MAJOR.MINOR.PATCH
- MAJOR: Major changes, breaking changes
- MINOR: New features, backward compatible
- PATCH: Bug fixes, minor updates

## Changelog Format
```markdown
## [1.0.0] - 2024-01-15

### Added
- New feature A
- New feature B

### Changed
- Updated feature C
- Modified API endpoint D

### Fixed
- Fixed bug E
- Fixed issue F

### Removed
- Deprecated feature G
```

---

# DOCUMENTATION TEMPLATES

## API Endpoint Template

```markdown
## [Endpoint Name]

### [HTTP Method] /api/v1/[path]

**Description**
[Brief description of what this endpoint does]

**Authentication**
Required: Yes/No
Permission: [permission_name]

**Request Headers**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Parameters**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| param1 | string | Yes | Description |
| param2 | integer | No | Description |

**Request Body**
```json
{
  "field1": "value1",
  "field2": "value2"
}
```

**Response (200 OK)**
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

**Error Responses**
- 400 Bad Request
- 401 Unauthorized
- 404 Not Found
- 422 Validation Error

**Example**
```bash
curl -X POST http://localhost:8000/api/v1/endpoint \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"field1": "value1"}'
```
```

## Database Table Template

```markdown
## [Table Name]

**Description**
[Brief description of what this table stores]

**Schema**
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| field1 | VARCHAR(255) | No | - | Description |
| field2 | DECIMAL(10,2) | Yes | NULL | Description |

**Indexes**
- PRIMARY KEY (id)
- INDEX idx_field1 (field1)
- UNIQUE INDEX uk_field2 (field2)

**Relationships**
- BelongsTo: [RelatedTable] via [foreign_key]
- HasMany: [RelatedTable] via [foreign_key]

**Sample Data**
```sql
INSERT INTO table_name (field1, field2) VALUES
('value1', 100.00),
('value2', 200.00);
```
```

---

# DOCUMENTATION BEST PRACTICES

## 1. Keep It Current
- Update documentation alongside code changes
- Review documentation regularly
- Remove outdated information
- Archive old versions

## 2. Make It Findable
- Use clear titles
- Include table of contents
- Use descriptive headings
- Add tags/labels

## 3. Make It Useful
- Focus on user needs
- Provide practical examples
- Include troubleshooting tips
- Add context and rationale

## 4. Make It Maintainable
- Use templates
- Follow standards
- Keep it organized
- Use version control

## 5. Make It Accessible
- Use clear language
- Provide multiple formats
- Include visual aids
- Support multiple devices

---

# DOCUMENTATION REVIEW PROCESS

## Review Checklist
- [ ] Content is accurate
- [ ] Content is complete
- [ ] Content is up-to-date
- [ ] Code examples work
- [ ] Screenshots are current
- [ ] Links are valid
- [ ] Formatting is consistent
- [ ] Language is clear

## Approval Process
1. Author submits documentation for review
2. Technical review by senior developer
3. User experience review by product owner
4. Final approval by project lead
5. Merge to main branch

---

# DOCUMENTATION METRICS

## Track These Metrics
- Documentation coverage (% of features documented)
- Documentation age (average age of documentation)
- Documentation usage (page views, downloads)
- Documentation accuracy (reported errors)
- Documentation completeness (missing sections)

## Targets
- 100% feature documentation coverage
- Documentation updated within 1 week of code changes
- Zero critical documentation errors
- All API endpoints documented
- All database tables documented

---

# DOCUMENTATION BACKUP

## Backup Strategy
- Documentation backed up with code repository
- Export to PDF for offline access
- Mirror to internal wiki
- Archive old versions

## Recovery
- Restore from Git repository
- Use Git history for rollback
- Maintain backup copies in separate location

---

# DOCUMENTATION TRAINING

## New Developer Onboarding
- Review documentation structure
- Read getting started guide
- Review architecture documentation
- Review coding standards
- Complete documentation exercises

## Ongoing Training
- Quarterly documentation reviews
- Documentation writing workshops
- Best practices sharing
- Tool training

---

# DOCUMENTATION TOOLS CONFIGURATION

## VS Code Extensions
- Markdown All in One
- Markdown Preview Enhanced
- Markdown PDF
- Draw.io Integration
- Code Spell Checker

## Git Hooks
- Pre-commit: Check documentation formatting
- Pre-push: Verify documentation completeness
- Post-merge: Update documentation index

## CI/CD Integration
- Build documentation on every commit
- Deploy documentation to staging
- Run documentation linters
- Generate API documentation

---

# DOCUMENTATION INDEX

Create a central index file (`docs/INDEX.md`) that lists all documentation:

```markdown
# Documentation Index

## Getting Started
- [Installation](./00-getting-started/installation.md)
- [Requirements](./00-getting-started/requirements.md)
- [Quick Start](./00-getting-started/quick-start.md)

## Architecture
- [Overview](./01-architecture/overview.md)
- [System Design](./01-architecture/system-design.md)
- [Database Design](./01-architecture/database-design.md)
- [API Design](./01-architecture/api-design.md)

## Development
- [Coding Standards](./02-development/coding-standards.md)
- [Git Workflow](./02-development/git-workflow.md)
- [Branch Strategy](./02-development/branch-strategy.md)
- [Code Review](./02-development/code-review.md)

## API Documentation
- [Authentication](./03-api-documentation/authentication.md)
- [Sales API](./03-api-documentation/sales-api.md)
- [Products API](./03-api-documentation/products-api.md)
- [Customers API](./03-api-documentation/customers-api.md)
- [Inventory API](./03-api-documentation/inventory-api.md)
- [Reports API](./03-api-documentation/reports-api.md)

## Database
- [Schema](./04-database/schema.md)
- [Migrations](./04-database/migrations.md)
- [Seeders](./04-database/seeders.md)
- [Queries](./04-database/queries.md)

## Testing
- [Testing Guide](./05-testing/testing-guide.md)
- [Unit Testing](./05-testing/unit-testing.md)
- [Feature Testing](./05-testing/feature-testing.md)
- [Coverage Report](./05-testing/coverage-report.md)

## Deployment
- [Environment Setup](./06-deployment/environment-setup.md)
- [Deployment Checklist](./06-deployment/deployment-checklist.md)
- [Backup Strategy](./06-deployment/backup-strategy.md)
- [Monitoring](./06-deployment/monitoring.md)

## User Guides
- [User Manual](./07-user-guides/user-manual.md)
- [Admin Guide](./07-user-guides/admin-guide.md)
- [Troubleshooting](./07-user-guides/troubleshooting.md)
- [FAQ](./07-user-guides/faq.md)

## Maintenance
- [Update Guide](./08-maintenance/update-guide.md)
- [Data Migration](./08-maintenance/data-migration.md)
- [Performance Tuning](./08-maintenance/performance-tuning.md)

## Appendix
- [Glossary](./09-appendix/glossary.md)
- [Changelog](./09-appendix/changelog.md)
- [References](./09-appendix/references.md)
```

---

# NEXT STEPS

1. Create `/docs` directory structure
2. Create template files for each section
3. Populate critical documentation first:
   - Installation guide
   - API documentation
   - Database schema
   - User manual
4. Set up documentation build process
5. Configure documentation deployment
6. Train team on documentation standards
7. Establish documentation review process
8. Set up documentation metrics tracking
