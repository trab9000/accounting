# phpBMS Documentation Index

**Type:** Single-part legacy web application (modernized)
**Primary Language:** PHP 8.3
**Architecture:** Multi-Page Application (MPA), server-rendered
**Database:** MySQL 8.0
**Deployment:** Docker Compose (PHP 8.3-apache + MySQL 8.0)
**Last Updated:** 2026-03-17

---

## Quick Reference

- **App URL (Docker):** http://localhost:8080
- **phpMyAdmin:** http://localhost:8081
- **Mail Viewer:** http://localhost:8080/mail_viewer.php
- **Error Log:** `/home/bart/accounting/docker_error.log`
- **Start:** `cd /home/bart/accounting && docker compose up -d`
- **DB Reset:** `docker compose down -v && docker compose up -d`

## Getting Started

```bash
cd /home/bart/accounting
docker compose up -d
# Open http://localhost:8080
```

---

## Generated Documentation

- [Project Overview](./project-overview.md) — Executive summary, tech stack, modernization changes
- [Architecture](./architecture.md) — Request lifecycle, subsystems, data flow, Docker layout
- [Source Tree Analysis](./source-tree-analysis.md) — Annotated directory structure
- [Component Inventory](./component-inventory.md) — PHP classes, JS components, AJAX endpoints
- [Development Guide](./development-guide.md) — How to develop, debug, add pages/modules
- [Deployment Guide](./deployment-guide.md) — Docker setup, production deployment
- [Data Models](./data-models.md) — Full database schema with all tables and columns

---

## Key Files for AI-Assisted Development

When working on features, start with these files:

| What you're doing | Read these |
|---|---|
| Any page | `include/session.php`, `include/tables.php` |
| New module class | `include/tables.php` (phpbmsTable), a similar module in `modules/bms/include/` |
| Form fields | `include/fields.php` |
| AJAX endpoint | Any `*_ajax.php` in `modules/bms/` |
| Search/list view | `include/search_class.php`, `search.php` |
| PDF report | `report/pdfreport_class.php`, `modules/bms/report/invoices_pdf_class.php` |
| Database schema | `docker/init/dump.sql` or `docs/data-models.md` |
| Docker/deployment | `Dockerfile`, `docker-compose.yml`, `docs/deployment-guide.md` |

---
_Generated using BMAD Method `document-project` workflow — 2026-03-17_
