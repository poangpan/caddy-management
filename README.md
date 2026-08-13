# Caddy Management System (ระบบบริหารจัดการแคดดี้)

An internal web application for a golf resort to manage caddy queueing, records, leave, and weekly payroll.

## Problem

The resort currently runs caddy operations without any digital system, causing three simultaneous issues:

1. **No auditable queue standard** — no record of arrival order or round counts, so queue fairness can't be verified after the fact.
2. **No centralized caddy registry/status** — no overview of how many caddies are available on a given day or who has leave booked in advance.
3. **Manual payroll calculation** — wage calculation by hole count is error-prone, with no systematic close-out/approval step before bank transfer.

## Solution

An on-premise web app (PHP + MySQL/MariaDB) that lets:

- **Queue/HR staff** check caddies into a FIFO queue, update caddy status (available / on round / waiting / on leave), manage the caddy registry, record leave, and take advance bookings on behalf of customers.
- **Accounting** close out payroll weekly and export CSV/Excel for bank transfer via SCB.
- **Admins** manage user accounts and permissions.

The system is independent of the existing tee-time booking system and is **not exposed to customers directly** — staff always key in round/customer/booking data on the customer's behalf.

An optional **Android app** (native, distributed as a sideloaded APK, no Play Store) gives queue/HR staff the same queue/registry/leave data via REST API for use walking the course. It's a supplementary channel, not a replacement — the web UI for this role always remains fully functional on its own.

## Requirements

- PHP 8.0+ with the `pdo_mysql` and `fileinfo` extensions
- MySQL 5.7+ or MariaDB 10.3+
- `uploads/caddies/` and `uploads/users/` must be writable by the web server user (caddy/user profile photos)

## Setup

### 1. Create the database

```bash
mysql -u root -p --default-character-set=utf8mb4 < database/schema.sql
```

This creates the `caddy_management` database, its tables, and a seed admin account.

Then apply migrations in order (each one is a single ticket's schema increment):

```bash
for f in database/migrations/*.sql; do
    mysql -u root -p --default-character-set=utf8mb4 caddy_management < "$f"
done
```

### 2. Configure the database connection

Defaults live in `config/db.php`; override via environment variables for production:

| Variable | Default |
|----------|---------|
| DB_HOST  | 127.0.0.1 |
| DB_NAME  | caddy_management |
| DB_USER  | root |
| DB_PASS  | (empty) |

### 3. Run it

```bash
php -S 0.0.0.0:8080
```

Then open `http://<host>:8080`. For production, point Apache/IIS at this folder as the document root.

### 4. First login

- Email: `admin@caddymanagement.local`
- Password: `Admin@1234`

Change this password immediately after first login, via "จัดการผู้ใช้งาน" (User management).

## Running with Docker

An alternative to the manual setup above — builds the app on `php:8.2-apache` and runs MariaDB alongside it, auto-loading `schema.sql` plus every file under `database/migrations/` on first start:

```bash
docker compose up -d --build
```

App: `http://localhost:8081` (same seed login as above). DB is exposed on host port `3308` if you need to connect directly. Adding a new migration later means also adding a mount line for it in `docker-compose.yml` (`db.volumes`), numbered so it sorts after `000_schema.sql` — there's no migration runner, so the ordering is just filename order.

## Testing

There's no PHPUnit/Composer setup yet (matches the no-framework philosophy of the sibling apps). High-risk logic gets small, dependency-free automated test scripts under `tests/`, run directly with the CLI:

```bash
php tests/wage_calculation_test.php
php tests/payroll_close_test.php
```

(or `docker compose exec app php tests/<name>.php` if running via Docker). Everything else is verified via curl walkthroughs against a real database, following the same manual/scripted pattern used by `it-requisition`.

## Repo layout

- **`.scratch/caddy-management/spec.md`** — full spec: problem statement, user stories, implementation decisions, testing decisions, and out-of-scope items.
- **`config/`, `includes/`, `assets/`** — app bootstrap (DB connection, auth/session, shared layout, CSS)
- **`database/schema.sql`** — baseline schema; incremental changes land under `database/migrations/`
- **`tests/`** — dependency-free automated tests for high-risk logic (see Testing above)
- **`Dockerfile`, `docker-compose.yml`** — containerized run (see Running with Docker above)
- **`users/`** — admin-only user account management
- **`ref/`** — reference prototypes and design notes per feature area (advance booking, caddy directory, queue dashboard, payroll/accounting summary, fairway precision).
- **`docs/agents/`** — configuration consumed by Claude Code agent skills (issue tracker, domain docs conventions).
- **`CLAUDE.md`** — entry point for agent skill configuration.

## Status

Issues #1–#8 and #10 implemented (auth, caddy registry, FIFO queue, round assignment, wage calculation, leave requests & reporting, advance booking, payroll close-out, per-caddy summary), plus a sidebar-based UI restyle and profile photos/last-login tracking for caddies and users (see `ref/`). Remaining tickets tracked on this repo's [Issues](https://github.com/poangpan/caddy-management/issues) page.
