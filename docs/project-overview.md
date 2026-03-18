# phpBMS - Project Overview

**Date:** 2026-03-17
**Type:** Legacy PHP Web Application (Multi-Page Application) — Modernized
**Architecture:** Server-rendered MPA with module-based organization

## Executive Summary

phpBMS (PHP Business Management System) is an open-source business management and accounting application originally developed by Kreotek LLC (2004–2009). It covers the full order-to-cash cycle: prospecting and client management, product catalog, quote/order/invoice lifecycle, tax and shipping calculation, payment receipt, accounts receivable aging, and PDF report generation.

The codebase has been modernized to run on **PHP 8.3 + MySQL 8.0** inside Docker, replacing all deprecated `mysql_*` calls with `mysqli`, migrating password hashing from MySQL's removed `ENCODE()` to PHP's `password_hash()`, and containerizing the full stack for local development.

## Project Classification

- **Repository Type:** Single-part monolith
- **Project Type:** Web application — server-rendered PHP MPA
- **Primary Language:** PHP (218+ PHP files), with JavaScript (48 files) and CSS (63 files)
- **Architecture Pattern:** Module-based MPA; each page is a standalone PHP script that includes shared session/db/form libraries

## Technology Stack Summary

| Layer | Technology | Notes |
|---|---|---|
| Language | PHP 8.3 | Procedural + OOP (PHP 4 class syntax); running in Docker |
| Database | MySQL 8.0 | `mysqli` extension; MyISAM tables; `NO_ENGINE_SUBSTITUTION` mode |
| Password hashing | PHP `password_hash()` | bcrypt via PASSWORD_DEFAULT; migrated from MySQL `ENCODE()` |
| PDF generation | FPDF 1.x | Bundled in `fpdf/` directory |
| JavaScript | MooTools 1.x / Prototype.lite | Bundled in `common/javascript/moo/` |
| CSS | Hand-authored | 63 files under `common/stylesheet/mozilla/` |
| Session | PHP native sessions | Session name derived from application name |
| Deployment | Docker (PHP 8.3-apache + MySQL 8.0) | `docker-compose.yml` with web, db, phpmyadmin services |
| Mail (dev) | fakesendmail.sh | Captures outgoing mail to `mail_capture/*.eml`; viewable at `/mail_viewer.php` |
| Install | Raw SQL scripts | `docker/init/dump.sql` seeds database on container start |

## Key Features

- **Client & Prospect Management** — Full contact records with addresses, phone numbers, email, web address, lead source, category, and credit limit. Prospects can be promoted to clients.
- **Invoice Lifecycle** — Documents move through four types: Quote → Order → Invoice → VOID. Each document has a configurable status workflow with history tracking and assigned-to user.
- **Line Items** — Each invoice has unlimited line items linked to product records, with quantity, unit price, unit cost, unit weight, and per-item taxable flag.
- **Product Catalog** — Products with part number, category, pricing, weight, images (stored as BLOBs), web-enabled flag, and product type.
- **Tax Engine** — Multiple tax rates grouped into tax areas. Taxable amounts computed per line item.
- **Shipping** — Multiple shipping methods, optional UPS rate estimation.
- **Discounts** — Named discount rules (percent or fixed amount) assignable to clients and invoices.
- **Payment Methods** — Configurable payment types with optional online processing scripts. Supports credit card fields, bank/check fields, and transaction IDs.
- **Accounts Receivable** — AR items track open and closed balances per client. Receipts applied to AR items via `receiptitems`. Aging report with configurable aging bands.
- **Reports** — HTML table reports and PDF reports via FPDF.
- **Role-Based Access Control** — Every menu item, table search, add, and edit screen is gated by a `roleid`.
- **Recurring Invoices** — Scheduled auto-generation of invoices on daily/weekly/monthly/yearly schedules.
- **File Attachments & Notes** — Generic attachment and notes system linkable to any record type.
- **Smart Search / Advanced Search** — Saved search queries per user; advanced multi-criteria search.
- **Scheduler** — Background task runner for recurring invoices and other periodic jobs.
- **Mail Capture (Dev)** — All outgoing email captured to files; viewable in browser at `/mail_viewer.php`.

## Architecture Highlights

- **No framework** — Filesystem-based routing: URL `/modules/bms/invoices_addedit.php?id=123` maps directly to a PHP file.
- **Include-based bootstrapping** — Every page starts with `require_once("include/session.php")`, which connects to MySQL, loads settings, starts the PHP session, and verifies login.
- **`phpbmsTable` base class** — The ORM layer. Reads MySQL column metadata at runtime (`SHOW COLUMNS`), then provides `getRecord()`, `insertRecord()`, `updateRecord()`, and `processAddEditPage()`.
- **Table definitions in the database** — The `tabledefs` table stores metadata about each searchable/listable entity.
- **Module system** — Modules live under `modules/`. Installed modules are registered in the `modules` database table.
- **Dual output modes** — Pages render full HTML for browser use. AJAX endpoints (`*_ajax.php`) return plain text or JSON.

## Modernization Changes (2026)

| Area | Old | New |
|---|---|---|
| PHP version | 4.3+ | 8.3 |
| MySQL driver | `mysql_*` (removed PHP 7) | `mysqli` |
| Password hashing | MySQL `ENCODE()` (removed MySQL 8) | PHP `password_hash()` bcrypt |
| Deployment | Apache + mod_php bare metal | Docker (PHP 8.3-apache + MySQL 8.0) |
| Email (dev) | Real sendmail | fakesendmail → `mail_capture/` files |
| Type normalization | `int(10) unsigned` (MySQL 5 format) | `int unsigned` (MySQL 8 format) — fixed in `_normalizeFieldType()` |

## Repository Structure

```
/home/bart/accounting/
├── index.php               # Login page
├── header.php / footer.php # Shared HTML header/footer
├── settings.php            # Active DB config (symlink to docker or live)
├── settings.docker.php     # Docker DB credentials
├── settings.live.php       # Production DB credentials
├── defaultsettings.php     # Default application settings template
├── Dockerfile              # PHP 8.3-apache container build
├── docker-compose.yml      # Docker services: web (8080), db (3307), phpmyadmin (8081)
├── docker/                 # Docker support: fakesendmail, errors.ini, init/dump.sql
├── .htaccess               # PHP error config (display_errors Off, log to file)
├── .user.ini               # PHP ini overrides
├── include/                # Core PHP library (session, db, form, table, search classes)
├── common/                 # Shared static assets (JavaScript, CSS, HTML fragments)
├── modules/                # Feature modules (base, bms, recurringinvoices, sample)
├── report/                 # Report base classes
├── fpdf/                   # FPDF PDF generation library
├── install-dd/ / installDD/# Installation wizard
├── mail_capture/           # Captured outgoing emails (Docker dev only)
├── mail_viewer.php         # Browser-based mail capture viewer
├── migrate_passwords.php   # One-time password migration script (ENCODE → bcrypt)
└── docs/                   # This documentation
```

## Documentation Map

- [index.md](./index.md) — Master documentation index
- [architecture.md](./architecture.md) — Detailed architecture
- [source-tree-analysis.md](./source-tree-analysis.md) — Directory structure
- [development-guide.md](./development-guide.md) — Development workflow
- [deployment-guide.md](./deployment-guide.md) — Docker deployment
- [data-models.md](./data-models.md) — Database schema
- [component-inventory.md](./component-inventory.md) — PHP classes and JS components

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
