# phpBMS - Development Guide

**Date:** 2026-03-17

## Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| Docker | 20+ | Required for local development |
| Docker Compose | 2.x | Included with Docker Desktop |
| Browser | Modern | Firefox, Chrome, Edge |

No PHP, MySQL, or Apache installation needed locally — everything runs in Docker.

## Quick Start

```bash
cd /home/bart/accounting

# Start all services (detached)
docker compose up -d

# Tail logs
docker compose logs -f web

# Stop
docker compose down
```

Services after startup:

| Service | URL | Credentials |
|---|---|---|
| phpBMS app | http://localhost:8080 | See DB seed for admin credentials |
| phpMyAdmin | http://localhost:8081 | root / rootpass |
| MySQL direct | localhost:3307 | bmsuser / bmspass (DB: phpbms) |

## Project Structure for Developers

```
accounting/
├── include/            # Core libraries — session, db, tables, fields, search
├── modules/            # Feature modules (each self-contained)
│   ├── base/           # Users, roles, notes, menu, scheduler
│   ├── bms/            # Business logic: clients, invoices, products, receipts, AR
│   ├── recurringinvoices/ # Recurring invoice scheduling
│   └── sample/         # Template for new modules
├── common/             # Shared JS, CSS, images
├── report/             # Report base classes (HTML + PDF)
├── fpdf/               # FPDF library (PDF generation)
├── docker/
│   ├── init/dump.sql   # DB seed — auto-loaded on first container start
│   ├── fakesendmail.sh # Captures outgoing mail to mail_capture/
│   └── errors.ini      # PHP error settings (log only, no display)
├── .htaccess           # PHP error config (suppresses warnings in AJAX output)
├── .user.ini           # PHP ini overrides
├── settings.php        # Active DB config (Docker or live)
├── settings.docker.php # Docker DB credentials (server=db)
├── settings.live.php   # Production DB credentials
├── mail_capture/       # Captured outgoing emails
├── mail_viewer.php     # Browser-based mail viewer
└── migrate_passwords.php # One-time ENCODE() → bcrypt migration
```

## Settings Configuration

`settings.php` contains four constants parsed by `phpbmsSession`:

```
mysql_server   = "db"       # Docker service name; use "localhost" for bare-metal
mysql_database = "phpbms"
mysql_user     = "bmsuser"
mysql_userpass = "bmspass"
mysql_pconnect = "false"    # Persistent connections — false recommended for Docker
```

To switch to production: copy `settings.live.php` → `settings.php` and update credentials.

## Adding a New Page

Every PHP page follows this pattern:

```php
<?php
require_once("../../include/session.php");  // Auth, DB connect, settings load
include_once("include/tables.php");
include_once("modules/bms/include/myclass.php");

$thetable = new myClass($db, $tabledefid);
$therecord = $thetable->processAddEditPage();

$pageTitle = "My Page";
$phpbms->cssIncludes[] = "pages/mypage.css";
$phpbms->jsIncludes[] = "modules/bms/javascript/mypage.js";

include("header.php");
?>
<!-- HTML form -->
<?php include("footer.php"); ?>
```

`session.php` handles: MySQL connect → settings load → session start → login check → redirect to `index.php` if not authenticated.

## Database Access

All DB work goes through the global `$db` object (`db` class, mysqli-backed):

```php
// Safe query (always cast/escape user input)
$result = $db->query(
    "SELECT * FROM clients WHERE id = " . (int)$_GET["id"]
);
while ($row = $db->fetchArray($result)) { ... }

// Escape strings
$safe = $db->escape($_POST["name"]);

// Insert ID after INSERT
$newid = $db->insertId();

// Affected rows after UPDATE/DELETE
$count = $db->affectedRows();
```

**Never** use `mysql_*` functions — removed in PHP 7. The `mysql_real_escape_string()` global polyfill in `session.php` exists only for legacy code; use `$db->escape()` for new code.

## The phpbmsTable ORM

Module classes extend `phpbmsTable` and get CRUD for free:

```php
class myTable extends phpbmsTable {
    function __construct($db, $tabledefid) {
        $this->maintable = "mytable";
        parent::__construct($db, $tabledefid);
    }
    // Override prepareVariables() to sanitize before save
    // Override insertRecord() / updateRecord() for custom logic
    // Override getDefaults() to set form defaults
}
```

`phpbmsTable` reads column metadata via `SHOW COLUMNS FROM <table>` at runtime. **Important:** MySQL 8 returns types like `int unsigned` (no display width). The `_normalizeFieldType()` method in `db.php` handles this by stripping the ` unsigned` suffix.

## Form Field System

```php
$theform = new phpbmsForm();
$theform->addField(new inputField("name", $value, "Label", $required));
$theform->addField(new inputDatePicker("receiptdate", $value, "date", true));
$theform->addField(new inputSmartSearch($db, "clientid", "Pick Client", $value, "client", true, 51));
$theform->addField(new inputCurrency("amount", $value, "amount", true));
$theform->jsMerge(); // MUST call before include("header.php")

// In the HTML:
$theform->showField("name");
```

Available input types: `inputField`, `inputTextarea`, `inputBasicList`, `inputCheckBox`, `inputDatePicker`, `inputTimePicker`, `inputSmartSearch`, `inputCurrency`, `inputChoiceList`.

## AJAX Endpoints

Files named `*_ajax.php` return JSON or plain text (no HTML wrapper). Pattern:

```php
<?php
require_once("../../include/session.php");
header("Content-Type: application/json");
$result = []; // build response
echo json_encode($result);
?>
```

PHP warnings must NOT appear in AJAX output — `.htaccess` sets `display_errors Off` and logs to `docker_error.log`.

## Email in Development

All outgoing `mail()` calls are captured to `mail_capture/*.eml`. View at:

```
http://localhost:8080/mail_viewer.php
```

No real email is sent in the Docker environment.

## Error Logs

```bash
# On host
tail -f /home/bart/accounting/docker_error.log

# Inside container
docker compose exec web tail -f /var/www/html/docker_error.log
```

## Password Migration

Run once after first setup if the database has old MySQL `ENCODE()` passwords:

```
http://localhost:8080/migrate_passwords.php
```

Converts all user passwords to bcrypt hashes compatible with PHP `password_verify()`.

## Rebuilding Docker Image

After changes to `Dockerfile`, `docker/errors.ini`, or `docker/fakesendmail.sh`:

```bash
docker compose build --no-cache
docker compose up -d
```

After changes to `docker/init/dump.sql` (reseed database from scratch):

```bash
docker compose down -v    # destroys db_data volume
docker compose up -d      # re-seeds on startup
```

## Common Issues

| Issue | Cause | Fix |
|---|---|---|
| 500 on INSERT/UPDATE | MySQL 8 `int unsigned` not normalized | Fixed in `db.php:_normalizeFieldType()` |
| Login fails after migration | Passwords still ENCODE() format | Run `migrate_passwords.php` |
| Email not received | Expected in Docker | Check `mail_capture/` or `/mail_viewer.php` |
| DB connection fails | Container not ready | Wait for `db` healthcheck; check `docker compose ps` |
| AJAX returns HTML error | PHP warning output | Check `docker_error.log`; `.htaccess` should suppress display |

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
