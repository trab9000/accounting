# phpBMS - Source Tree Analysis

**Date:** 2026-03-17

## Root Directory

```
/home/bart/accounting/
│
├── index.php                    # Login page — application entry point
├── header.php                   # Shared HTML header (menu, CSS/JS includes)
├── footer.php                   # Shared HTML footer (closes HTML, shows JS)
├── logout.php                   # Destroys session + redirects to index.php
│
├── search.php                   # Generic record search/list page (uses displayTable)
├── print.php                    # Print preview coordinator
├── smartsearch.php              # Smart search AJAX endpoint
├── advancedsearch.php           # Advanced multi-criteria search form
├── advancedsort.php             # Advanced sort form
├── loadsearch.php               # Load saved searches
├── checkunique.php              # Check field uniqueness (AJAX)
├── choicelist.php               # Choice list AJAX endpoint
├── datepicker.php               # Date picker AJAX endpoint
├── timepicker.php               # Time picker AJAX endpoint
├── dbgraphic.php                # Database image server (products thumbnail)
├── servefile.php                # File attachment serving
├── noaccess.php                 # Access denied page
├── requirements.php             # Browser requirements checker
├── info.php                     # Application info page
├── phpinfo.php                  # PHP info page (remove in production)
│
├── mail_viewer.php              # Docker dev: view captured emails in browser
├── migrate_passwords.php        # One-time: ENCODE() → password_hash() migration
│
│── settings.php                 # Active DB config (copy of docker or live)
├── settings.docker.php          # Docker credentials (server=db)
├── settings.live.php            # Production credentials
├── settings.php.sample          # Template with instructions
├── defaultsettings.php          # Default application settings (read by session.php)
│
├── Dockerfile                   # PHP 8.3-apache image with gd, mysqli extensions
├── docker-compose.yml           # Services: web:8080, db:3307, phpmyadmin:8081
├── .dockerignore                # Excludes _bmad, .git, docs, etc. from image
├── .htaccess                    # display_errors Off, log_errors On, error_log path
├── .user.ini                    # PHP ini overrides
│
├── ChangeLog                    # Version history (0.62 → 0.96)
├── README                       # Original project readme
├── license.txt                  # BSD license
├── THANKS.txt                   # Credits
├── token                        # Session/API token file
└── accounting.tar.gz            # Backup archive
```

## `include/` — Core PHP Libraries

```
include/
├── session.php          # ★ Entry point for every page
│                        #   - appError class (error display/logging)
│                        #   - phpbmsLog class (audit log → `log` table)
│                        #   - phpbmsSession class (settings, session, auth)
│                        #   - mysql_real_escape_string() polyfill (PHP 8 compat)
│                        #   - Global $db and $phpbms instantiation
│
├── db.php               # Database abstraction (mysqli wrapper)
│                        #   - db class: connect, query, fetchArray, escape, tableInfo
│                        #   - _normalizeFieldType(): MySQL 8 "int unsigned" fix
│
├── common_functions.php # Global utilities
│                        #   - phpbms class: modules, CSS/JS includes, menu
│                        #   - addSlashesToArray() — legacy escaping shim
│                        #   - currencyToNumber(), numberToCurrency()
│                        #   - dateToString(), stringToDate(), sqlDateFromString()
│                        #   - hasRights() — role check
│                        #   - goURL() — redirect helper
│
├── tables.php           # phpbmsTable ORM base class
│                        #   - CRUD: getRecord, insertRecord, updateRecord, deleteRecord
│                        #   - prepareFieldForSQL(): value → SQL literal
│                        #   - processAddEditPage(): GET/POST dispatch
│
├── fields.php           # Form field rendering
│                        #   - phpbmsForm class + all inputXxx classes
│                        #   - Client-side validation JS array generation
│
├── search_class.php     # displayTable: dynamic list/search view renderer
├── print_class.php      # printer class: report/print coordinator
├── menu_class.php       # topMenu class: hierarchical menu from `menu` table
├── login_include.php    # Login form processing (password_verify)
├── jstransport.php      # AJAX data transport helper
├── post_class.php       # POST data wrapper
├── relationships.php    # Data relationship helpers
└── createmodifiedby.php # Created/modified user tracking helper
```

## `common/` — Shared Assets

```
common/
├── javascript/
│   ├── common.js          # Core utility functions (used on all pages)
│   ├── queryfunctions.js  # Search/list view AJAX and sorting
│   ├── fields.js          # Client-side form validation
│   ├── smartsearch.js     # Autocomplete type-ahead search
│   ├── choicelist.js      # Dynamic choice/picklist population
│   ├── datepicker.js      # Calendar date picker widget
│   ├── timepicker.js      # Time picker widget
│   ├── login.js           # Login form behavior
│   ├── menu.js            # Top navigation menu behavior
│   ├── print.js           # Print/report selection behavior
│   └── moo/
│       ├── prototype.lite.js  # Prototype.js lightweight compat layer
│       ├── moo.fx.js          # MooTools effects library
│       └── moo.fx.pack.js     # Packed/minified version
│
├── stylesheet/
│   └── mozilla/           # Default theme (63 CSS files)
│       ├── base.css        # Base styles
│       ├── pages/          # Per-page CSS (invoices.css, receipts.css, etc.)
│       └── [60+ more CSS files]
│
├── image/                 # UI icons and images
└── html/                  # HTML fragment templates
```

## `modules/` — Feature Modules

### `modules/base/` — Core Module

```
modules/base/
├── include/
│   ├── users.php          # users extends phpbmsTable — auth, roles
│   ├── roles.php          # roles extends phpbmsTable — RBAC
│   ├── notes.php          # notes class — NT/TS/EV/SY note types
│   ├── menu.php           # Menu item management
│   ├── scheduler.php      # Task scheduler management
│   ├── tabledefs.php      # Table definition management
│   ├── attachments.php    # File attachment handling
│   ├── files.php          # File management
│   ├── adminsettings_include.php  # Admin settings UI
│   ├── myaccount.php      # User self-service account
│   ├── usersearches.php   # Saved searches management
│   ├── reports.php        # Report definition management
│   ├── snapshot_include.php # Dashboard snapshot
│   ├── tablegroupings.php # Table grouping config
│   └── [7 more tabledefs_*.php files]
│
├── javascript/
│   ├── users.js, roles.js, notes.js  # UI behaviors
│   └── [10+ more JS files]
│
├── report/
│   ├── tabledefs_sqlexport.php  # Export table definition as SQL
│   └── notes_summary.php        # Notes summary report
│
├── install/
│   ├── createtables.sql   # Base schema (users, roles, notes, menus, etc.)
│   ├── menu.sql, roles.sql, settings.sql, tabledefs.sql  # Seed data
│   └── version.php        # Module version: 0.96
│
├── notes_ajax.php          # AJAX: note CRUD + email notifications
├── snapshot_ajax.php       # AJAX: dashboard snapshot data
├── adminsettings.php       # Admin settings page
├── cron.php                # Background scheduler runner
├── ical.php                # iCal export endpoint
└── [20+ *_addedit.php pages]
```

### `modules/bms/` — Business Management Module

```
modules/bms/
├── include/
│   ├── clients.php         # clients extends phpbmsTable
│   ├── clients_addresses.php # Client address management
│   ├── clients_credit.php  # Client credit management
│   ├── invoices.php        # invoices extends phpbmsTable — core invoice logic
│   ├── products.php        # products extends phpbmsTable
│   ├── receipts.php        # receipts + receiptitems classes
│   ├── aritems.php         # aritems — AR receivable items
│   ├── discounts.php       # discounts extends phpbmsTable
│   └── addresses.php / addresstorecord.php
│
├── javascript/
│   ├── invoice.js          # Invoice line item management, total recalc
│   ├── receipt.js          # Receipt form behavior
│   ├── paymentprocess.js   # Online payment processing button
│   └── [10+ more JS files]
│
├── report/
│   ├── invoices_pdf_class.php      # PDF invoice base class
│   ├── invoices_pdfpackinglist.php # PDF packing list
│   ├── invoices_pdfquote.php       # PDF quote
│   ├── invoices_pdfworkorder.php   # PDF work order
│   ├── invoices_shippinglabels.php # Shipping labels
│   ├── invoices_totals.php         # Invoice totals report
│   ├── receipts_pdf.php            # PDF receipt
│   ├── receipts_pttotals.php       # Receipt payment type totals
│   ├── aritems_summary.php         # AR summary report
│   ├── aritems_clientstatement.php # Client AR statement
│   ├── clients_purchasehistory.php # Client purchase history
│   ├── clients_notesummary.php     # Client note summary
│   ├── lineitems_totals.php        # Line item totals
│   └── products_saleshistory.php   # Product sales history
│
├── install/
│   ├── createtables.sql    # BMS schema: clients, invoices, products, etc.
│   └── version.php         # Module version: 0.96
│
├── AJAX endpoints:
│   ├── invoices_lineitem_ajax.php   # Line item add/edit/recalc
│   ├── invoices_client_ajax.php     # Client selection → populate defaults
│   ├── invoices_addresses_ajax.php  # Shipping address selection
│   ├── receipts_aritem_ajax.php     # AR item selection for receipts
│   └── quickview_ajax.php           # Quick record preview
│
└── Page files:
    ├── invoices_addedit.php         # Invoice create/edit form
    ├── receipts_addedit.php         # Receipt create/edit form
    ├── clients_*.php                # Client management pages
    ├── products_addedit.php         # Product create/edit
    ├── aritems_aging.php            # AR aging report page
    └── [20+ more page files]
```

### `modules/recurringinvoices/`

```
modules/recurringinvoices/
├── include/recurringinvoices.php  # recurringinvoices extends phpbmsTable
├── javascript/recurringinvoices.js
├── install/createtables.sql       # recurringinvoices table
├── invoices_recurrence.php        # Recurrence schedule form
├── scheduler_recurr.php           # Scheduler task: generate recurring invoices
└── test.php                       # Test script
```

### `modules/sample/`

```
modules/sample/
├── include/sampletable.php   # sampletable extends phpbmsTable — template
├── install/                  # Install scripts
├── sampletable_addedit.php   # Sample form
└── adminsettings.php         # Sample admin settings
```

## `report/` — Report Base Classes

```
report/
├── report_class.php         # phpbmsReport: SQL assembly, where/sort, output
├── pdfreport_class.php      # pdfColumn, pdfColor, pdfFont, pdfStyle helpers
├── general_tableprint.php   # Render query results as HTML table
├── general_export.php       # Export to CSV/Excel
├── general_labels.php       # Label printing
└── general_sql.php          # Generic SQL query report
```

## `fpdf/` — PDF Library

```
fpdf/
├── fpdf.php          # Main FPDF class (PDF page, cells, text, images)
├── mem_image.php     # In-memory image handling
├── fpdf.css          # PDF viewer styles
└── font/
    ├── helvetica.php, helveticab.php, helveticabi.php, helveticai.php
    ├── times.php, timesb.php, timesbi.php, timesi.php
    ├── courier.php, courierb.php, courierbi.php, courieri.php
    └── makefont/makefont.php   # Font compiler utility
```

## `docker/` — Docker Support

```
docker/
├── init/
│   └── dump.sql           # Full database seed — loaded automatically on first start
├── fakesendmail.sh         # Captures mail() output to mail_capture/*.eml
└── errors.ini              # PHP error config: log only, no display
```

## `docs/` — Documentation

```
docs/
├── index.md                 # Master documentation index (this suite)
├── project-overview.md      # Executive summary + tech stack
├── architecture.md          # System architecture + data flow
├── source-tree-analysis.md  # This file — annotated directory tree
├── development-guide.md     # How to develop, run, debug
├── deployment-guide.md      # Docker deployment guide
├── data-models.md           # Database schema reference
├── component-inventory.md   # PHP classes + JS components
└── .archive/                # Archived scan state files
```

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
