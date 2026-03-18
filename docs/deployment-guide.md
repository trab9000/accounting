# phpBMS - Deployment Guide

**Date:** 2026-03-17

## Docker (Development/Local)

### Services

| Service | Image | Port | Purpose |
|---|---|---|---|
| `web` | `php:8.3-apache` (custom build) | 8080→80 | PHP app server |
| `db` | `mysql:8.0` | 3307→3306 | MySQL database |
| `phpmyadmin` | `phpmyadmin:latest` | 8081→80 | DB admin UI |

### Starting Up

```bash
cd /home/bart/accounting

# First time or after Dockerfile changes:
docker compose build

# Start all services:
docker compose up -d

# Check status:
docker compose ps

# Logs:
docker compose logs -f web
docker compose logs -f db
```

### Database Seeding

On first start, MySQL automatically runs all files in `docker/init/`:
- `docker/init/dump.sql` — complete database dump with schema + seed data

To **reset the database** (re-run seed):
```bash
docker compose down -v    # -v removes the db_data named volume
docker compose up -d      # DB starts fresh and re-seeds
```

### Dockerfile Details

```dockerfile
FROM php:8.3-apache

# Extensions: gd (image handling), mysqli (database)
RUN docker-php-ext-install gd mysqli

# Fake sendmail captures all outgoing mail
COPY docker/fakesendmail.sh /usr/local/bin/fakesendmail
RUN echo 'sendmail_path = "/usr/local/bin/fakesendmail -t"' \
    > /usr/local/etc/php/conf.d/mail.ini

# PHP error config
COPY docker/errors.ini /usr/local/etc/php/conf.d/errors.ini

# Enable mod_rewrite, AllowOverride All (for .htaccess)
RUN a2enmod rewrite
```

### PHP Configuration

**`.htaccess`** (applied per-directory by Apache):
```
php_flag display_errors Off    # Never show errors in HTML output
php_flag log_errors On
php_value error_log /var/www/html/docker_error.log
```

**`docker/errors.ini`** (applied at PHP startup):
```ini
display_errors = Off
log_errors = On
error_log = /var/www/html/docker_error.log
```

Both work together — `.htaccess` for per-directory, `errors.ini` for early PHP startup.

### Settings

`settings.php` (the active config file) for Docker:
```
mysql_server   = "db"       # Docker service name
mysql_database = "phpbms"
mysql_user     = "bmsuser"
mysql_userpass = "bmspass"
mysql_pconnect = "false"
```

The `docker-compose.yml` MySQL environment matches these credentials exactly.

### Mail Capture

All PHP `mail()` calls go to `fakesendmail.sh`, which writes `.eml` files to `/var/www/html/mail_capture/` (mounted from `./mail_capture/` on host).

View captured emails at: **http://localhost:8080/mail_viewer.php**

---

## Production (Bare Metal / Traditional Hosting)

### Requirements

| Component | Version |
|---|---|
| PHP | 8.x with `mysqli`, `gd`, `session`, `mbstring` extensions |
| MySQL / MariaDB | 8.0+ |
| Apache | 2.4 with `mod_php`, `AllowOverride All` |

### Installation

1. Copy all project files to Apache document root (or subdirectory).
2. Create a MySQL database and user.
3. Copy `settings.php.sample` to `settings.php` and edit DB credentials:
   ```
   mysql_server   = "localhost"
   mysql_database = "your_db"
   mysql_user     = "your_user"
   mysql_userpass = "your_password"
   mysql_pconnect = "true"
   ```
4. Import `docker/init/dump.sql` into your MySQL database:
   ```bash
   mysql -u your_user -p your_db < docker/init/dump.sql
   ```
5. Navigate to the app in browser and log in.
6. If upgrading from phpBMS with old `ENCODE()` passwords, run `migrate_passwords.php` once.

### Apache VirtualHost Example

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/phpbms
    ServerName yoursite.com

    <Directory /var/www/phpbms>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Production Settings

Remove or restrict access to:
- `phpinfo.php`
- `migrate_passwords.php` (run once then delete)
- `mail_viewer.php` (development only)
- `docker/` directory (not needed in production)
- `install-dd/` and `installDD/` after initial setup

---

## Environment Comparison

| Item | Docker (Dev) | Production |
|---|---|---|
| PHP version | 8.3 | 8.x |
| MySQL | 8.0 in container | 8.0+ on server |
| `settings.php` server | `"db"` | `"localhost"` |
| `mysql_pconnect` | `false` | `true` |
| Email | Captured to files | Real sendmail/SMTP |
| Error display | Off (logged to file) | Off (logged) |
| DB seed | `docker/init/dump.sql` auto-loaded | Manual import |

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
