# phpBMS - Architecture

**Date:** 2026-03-17

## Architectural Summary

phpBMS is a **server-rendered PHP Multi-Page Application (MPA)** with a flat, filesystem-based routing model. There is no framework, no dependency injection container, and no build tooling. Every URL maps directly to a PHP file on disk. The application is now containerized in Docker running PHP 8.3 and MySQL 8.0.

## Technology Stack

| Layer | Technology | Version |
|---|---|---|
| Runtime | PHP | 8.3 (Docker: `php:8.3-apache`) |
| Web server | Apache | 2.4 (bundled in PHP image) |
| Database | MySQL | 8.0 (`mysql:8.0` Docker image) |
| DB driver | mysqli | PHP built-in extension |
| PDF | FPDF | 1.x (bundled in `fpdf/`) |
| JavaScript | MooTools / Prototype.lite | 1.x (bundled in `common/javascript/moo/`) |
| CSS | Hand-authored | No preprocessor |
| Containerization | Docker Compose | 3 services: web, db, phpmyadmin |

## Request Lifecycle

```
Browser Request
    │
    ▼
Apache (mod_php)
    │
    ▼
modules/bms/invoices_addedit.php   ← URL maps directly to file
    │
    ├── require_once("include/session.php")
    │       ├── Parse settings.php → DB credentials
    │       ├── new db() → mysqli_connect()
    │       ├── phpbmsSession::loadDBSettings() → load settings table into constants
    │       ├── session_start()
    │       └── Verify login → redirect to index.php if not authenticated
    │
    ├── include table class + module class
    ├── $thetable = new invoices($db, $tabledefid)
    ├── $therecord = $thetable->processAddEditPage()
    │       ├── GET request: getRecord($id) or getDefaults()
    │       └── POST request: insertRecord($_POST) or updateRecord($_POST)
    │
    ├── include("header.php")  → renders <html>, <head>, top menu
    ├── render form HTML
    └── include("footer.php")  → closes page
```

## Core Subsystems

### 1. Database Layer (`include/db.php`)

The `db` class wraps mysqli with a simplified interface:

```
db
├── connect()         — mysqli_connect with optional persistent connection
├── query()           — Execute SQL, return result or false
├── fetchArray()      — mysqli_fetch_assoc wrapper
├── escape()          — mysqli_real_escape_string wrapper
├── tableInfo()       — SHOW COLUMNS → returns field metadata array
│                       Used by phpbmsTable to discover columns at runtime
├── _normalizeFieldType() — Maps MySQL types to simplified strings
│                           Handles MySQL 8 "int unsigned" (strips " unsigned")
└── _parseFieldFlags()    — Extracts not_null, primary_key, auto_increment flags
```

**MySQL 8 Compatibility Note:** MySQL 8 omits display widths from integer types, returning `int unsigned` instead of `int(10) unsigned`. `_normalizeFieldType()` strips the ` unsigned` suffix before the type switch to ensure correct handling.

### 2. Session & Auth (`include/session.php`)

```
session.php (included by every page)
├── Define appError class       — Error formatting + DB logging
├── Define phpbmsLog class      — Audit log → `log` table
├── Define phpbmsSession class  — Settings + session management
├── mysql_real_escape_string()  — Polyfill using global $db (PHP 8 compatibility)
├── Connect to DB (new db())
├── Load settings from `settings` table into PHP constants
├── session_start()
└── Verify $_SESSION["userinfo"] → redirect if not set
```

**Password Authentication:**
- New: `password_verify($input, $hash)` against bcrypt hash stored in `users.password`
- Old (migrated away from): MySQL `ENCODE()` — removed in MySQL 8
- Migration: `migrate_passwords.php` converts all users in one pass

### 3. ORM Layer (`include/tables.php`)

`phpbmsTable` is the base class for all data entities:

```
phpbmsTable
├── __construct($db, $tabledefid)
│       └── Loads table definition from `tabledefs` table
│           Calls db->tableInfo($maintable) to get live column metadata
│
├── getRecord($id)         — SELECT by PK
├── getDefaults()          — Returns default values for new record form
├── insertRecord($vars)    — INSERT; calls prepareFieldForSQL() per column
├── updateRecord($vars)    — UPDATE by id; calls prepareFieldForSQL() per column
├── deleteRecord($id)      — DELETE by PK
├── processAddEditPage()   — Dispatch: GET→getRecord/getDefaults, POST→insert/update
└── prepareFieldForSQL($value, $type, $flags)
        — Formats value for SQL: quotes strings, casts ints/floats,
          formats dates, handles NULL for nullable fields
        — "password" type → password_hash()
```

Module classes override `insertRecord()`, `updateRecord()`, `getDefaults()`, and `prepareVariables()` for custom logic (e.g., `receipts` converts currency format before save, updates AR items after save).

### 4. Form System (`include/fields.php`)

```
phpbmsForm
├── addField($input)      — Register an input
├── jsMerge()             — Merge JS validation arrays into $phpbms->bottomJS
└── showField($name)      — Render the HTML input element

Input classes:
├── inputField            — <input type="text">
├── inputTextarea         — <textarea>
├── inputBasicList        — <select> from PHP array
├── inputCheckBox         — <input type="checkbox">
├── inputDatePicker       — Text + calendar widget (datepicker.js)
├── inputTimePicker       — Text + time widget (timepicker.js)
├── inputSmartSearch      — Autocomplete search (smartsearch.js + smartsearch.php)
├── inputCurrency         — Currency-formatted number field
└── inputChoiceList       — <select> from `choices` database table
```

Client-side validation arrays (`requiredArray`, `integerArray`, `emailArray`, etc.) are built in JS and checked on form submit.

### 5. Search System (`include/search_class.php`)

`displayTable` renders dynamic list/search views driven entirely by database metadata:

- `tabledefs` — which table, which PHP file handles add/edit
- `tablecolumns` — which columns to display and how
- `tablegroupings` — result grouping
- `tablefindoptions` — quick-filter options
- `tablesearchablefields` — fields available for advanced search
- `tableoptions` — custom action buttons

### 6. Module System

```
modules/
├── base/        — Core: users, roles, notes, menu, scheduler, admin settings
├── bms/         — Business: clients, invoices, products, receipts, AR, discounts
├── recurringinvoices/ — Scheduled recurring invoice generation
└── sample/      — Template for building new modules
```

Each module has:
- `include/` — PHP classes extending `phpbmsTable`
- `javascript/` — Module-specific JS
- `report/` — Report scripts
- `install/` — `createtables.sql`, `version.php`, update SQL

### 7. Report System

```
report/
├── report_class.php     — phpbmsReport base: SQL assembly, where clause, sort
├── pdfreport_class.php  — pdfColumn, pdfColor, pdfFont, pdfStyle helpers
├── general_tableprint.php — HTML table report renderer
├── general_export.php   — CSV/Excel export
└── general_sql.php      — Generic SQL query report
```

PDF reports use FPDF (`fpdf/fpdf.php`), bundled with Helvetica, Times, Courier fonts.

## Dual Output Modes

| Mode | Files | Output |
|---|---|---|
| Full page | `*_addedit.php`, `search.php` | Full HTML with header/footer |
| AJAX | `*_ajax.php` | JSON or plain text fragment |
| API | `api_*.php` | HTTP POST with credentials; JSON response |
| Report | `report/*.php` | HTML table or PDF download |

## Data Flow: Creating an Invoice

```
1. GET /modules/bms/invoices_addedit.php
   → invoices->getDefaults() → blank form

2. User selects client (AJAX)
   → invoices_client_ajax.php → returns client defaults (tax area, address, discount)

3. User adds line item (AJAX)
   → invoices_lineitem_ajax.php → returns product details, price, recalculates totals

4. POST /modules/bms/invoices_addedit.php
   → invoices->insertRecord($_POST)
     → prepareVariables() (calculate totals, tax)
     → phpbmsTable->insertRecord() → INSERT INTO invoices
     → lineitems->set() → INSERT line items
     → aritems->createFromInvoice() → create AR item
```

## Docker Architecture

```
┌─────────────────────────────────────────────┐
│  Docker Compose                             │
│                                             │
│  ┌──────────────┐    ┌───────────────────┐  │
│  │  web (8080)  │    │    db (3307)      │  │
│  │  PHP 8.3     │───▶│    MySQL 8.0      │  │
│  │  Apache 2.4  │    │    phpbms DB      │  │
│  │              │    └───────────────────┘  │
│  │  Volume:     │                           │
│  │  ./:/var/www │    ┌───────────────────┐  │
│  │  /html       │    │ phpmyadmin (8081) │  │
│  └──────────────┘    └───────────────────┘  │
└─────────────────────────────────────────────┘

Host mounts: ./accounting → /var/www/html (live reload)
DB seed: docker/init/dump.sql → auto-loaded into phpbms on first start
Mail: fakesendmail.sh → mail_capture/*.eml
Errors: docker_error.log (display_errors Off via .htaccess)
```

## Role-Based Access Control

Every protected resource has a `roleid`. The `hasRights($roleid)` function (in `session.php`) checks if the current user has the required role. Admin users (`users.admin = 1`) bypass all role checks.

```
users ←──── rolestousers ────→ roles
                                  ↑
tabledefs.editroleid ─────────────┘
tabledefs.addroleid
tabledefs.searchroleid
tablecolumns.roleid
tableoptions.roleid
menu.roleid
```

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
