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

A [Mailpit](https://github.com/axllent/mailpit) container also runs alongside the app as a dev-only SMTP catcher — staff notification emails (e.g. leave approval/rejection) are sent here instead of a real mail server. View caught mail at `http://localhost:8025`. For a non-Docker/production setup, point `SMTP_HOST`/`SMTP_PORT`/`SMTP_USER`/`SMTP_PASS`/`SMTP_FROM` env vars at a real SMTP server (same override pattern as `DB_HOST` above).

## Testing

There's no PHPUnit/Composer setup yet (matches the no-framework philosophy of the sibling apps). High-risk logic gets small, dependency-free automated test scripts under `tests/`, run directly with the CLI:

```bash
php tests/wage_calculation_test.php
php tests/payroll_close_test.php
```

(or `docker compose exec app php tests/<name>.php` if running via Docker). Everything else is verified via curl walkthroughs against a real database, following the same manual/scripted pattern used by `it-requisition`.

## Android REST API

Token-authenticated JSON API under `api/`, for the queue/HR Android app — restricted to the `queue_hr` role only (`accounting`/`admin` accounts are rejected at login, since they never use the app). It reads and writes the exact same tables as the web app: `api/queue.php` calls the same `fetchQueueBoard()` the web queue board uses, `api/caddy_status.php` calls the same `setCaddyQueueStatus()` the web queue board's status dropdown uses, and `api/leave.php` goes through the same `validateLeaveInput()` + `pending` status as every other leave entry point (staff web form, caddy portal). A change made through one surface shows up immediately in the others because there's no separate API database or duplicated business logic — just a second front door onto the same tables.

Auth is an opaque bearer token (`api_tokens` table, no expiry — revoke via `api/logout.php`), not a session cookie. `Authorization: Bearer <token>` must reach PHP's `$_SERVER['HTTP_AUTHORIZATION']`; the root `.htaccess` rewrite rule exists specifically because Apache/mod_php doesn't populate that by default. Every endpoint requires `includes/api_auth.php` first, which also installs a global exception handler so a DB error surfaces as a clean JSON 500 instead of a leaked PHP stack trace.

Curl walkthrough (assumes Docker, `http://localhost:8081`, and a `queue_hr`-role user):

```bash
# 1. Log in — get a token. accounting/admin accounts get 403 here instead of a token.
curl -X POST http://localhost:8081/api/login.php \
  --data-urlencode "email=somchai@caddymanagement.local" \
  --data-urlencode "password=<password>"
# => {"token":"<token>","user":{"id":2,"full_name":"...","role":"queue_hr",...}}

TOKEN="<token from above>"

# 2. View the FIFO queue (matches queue/board.php exactly)
curl http://localhost:8081/api/queue.php -H "Authorization: Bearer $TOKEN"

# 3. Update a caddy's status — same effect as the dropdown on queue/board.php
curl -X POST http://localhost:8081/api/caddy_status.php -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "caddy_id=6" --data-urlencode "status=waiting"

# 4. Caddy registry: list, get one, create, edit (mirrors caddies/list.php + caddies/form.php fields)
curl http://localhost:8081/api/caddies.php -H "Authorization: Bearer $TOKEN"
curl "http://localhost:8081/api/caddies.php?id=6" -H "Authorization: Bearer $TOKEN"
curl -X POST http://localhost:8081/api/caddies.php -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "full_name=ทดสอบ" --data-urlencode "phone=0812345678"
curl -X POST http://localhost:8081/api/caddies.php -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "id=<id from create>" --data-urlencode "full_name=ทดสอบ (แก้ไข)"

# 5. Record a leave request — lands as pending, same as every other entry point
curl -X POST http://localhost:8081/api/leave.php -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "caddy_id=6" --data-urlencode "leave_type_id=1" \
  --data-urlencode "start_date=2026-09-01" --data-urlencode "end_date=2026-09-01"

# 6. Log out — revokes the token; it stops working immediately afterward
curl -X POST http://localhost:8081/api/logout.php -H "Authorization: Bearer $TOKEN"
```

(Thai text as an inline `--data-urlencode` value can get corrupted going through Git Bash → native `curl.exe` on Windows — use `--data-urlencode "field@/path/to/file"` with the value saved to a file instead, if that's your setup.)

## Repo layout

- **`.scratch/caddy-management/spec.md`** — full spec: problem statement, user stories, implementation decisions, testing decisions, and out-of-scope items.
- **`config/`, `includes/`, `assets/`** — app bootstrap (DB connection, auth/session, shared layout, CSS)
- **`database/schema.sql`** — baseline schema; incremental changes land under `database/migrations/`
- **`tests/`** — dependency-free automated tests for high-risk logic (see Testing above)
- **`Dockerfile`, `docker-compose.yml`** — containerized run (see Running with Docker above)
- **`users/`** — admin-only user account management
- **`caddy/`** — caddy self-service portal (separate login/session from staff — see `includes/caddy_auth.php`)
- **`api/`** — token-authenticated REST API for the queue/HR Android app (see Android REST API above)
- **`reports/`** — cross-cutting operational reports (round volume, caddy request/ranking), separate from the per-purpose pages under `leave/` and `payroll/`
- **`ref/`** — reference prototypes and design notes per feature area (advance booking, caddy directory, queue dashboard, payroll/accounting summary, fairway precision).
- **`docs/agents/`** — configuration consumed by Claude Code agent skills (issue tracker, domain docs conventions).
- **`CLAUDE.md`** — entry point for agent skill configuration.

## Status

Issues #1–#10, #11, and #13–#21, #23, #25 implemented (auth, caddy registry, FIFO queue, round assignment, wage calculation, leave requests & reporting, advance booking, payroll close-out, per-caddy summary, extended caddy profile fields, caddy check-out, booking flight/player count/VIP, round cart number, operational reports, leave approval workflow, customer evaluation, staff notifications, caddy self-service login, performance KPI leaderboard, caddy self-service leave requests, payroll CSV export, Android REST API), plus a sidebar-based UI restyle, profile photos/last-login tracking, caddy check-in, and edit/cancel for bookings and leave requests. Leave requests now go through a pending → approved/rejected state (approvable by queue_hr or admin) before they exclude a caddy from the FIFO queue or block advance-booking caddy selection; editing a decided request resets it to pending. Customer evaluation (7-dimension 1-5 star rating + comment) is staff-entered against completed rounds — no customer-facing surface exists in this app — viewable per-caddy on the caddy directory (quick average) and summary page (full history). Leave approval/rejection now emails the other queue_hr/admin staff (excluding whoever made the decision) via a swappable notification helper (`includes/notifications.php`) — Email is the only channel built so far (LINE OA/push need a registered channel/app first); a failed send never blocks the approval itself. Caddies can now log into their own portal (`/caddy/`, separate login/session/table from staff `users`) to see their queue position, YTD rounds/pay, ratings, and leave history, and to submit their own leave requests (`caddy/leave.php`, landing as `pending` in the same approval workflow staff use) — staff issue the account via `caddies/credentials.php`. Accepting job offers and shift swaps are still deferred as follow-ups (#24, #26) since each changes a different existing workflow. A per-caddy KPI leaderboard (`reports/kpi.php`) ranks caddies by rounds served, days worked (from a new `attendance_log` — starts empty, no history predates it), earnings, repeat-selection rate, VIP job count, and average rating; "lateness" is intentionally not built (no shift-schedule concept exists to define "late" against), and "VIP job count" reads near-zero today because `is_vip` is set on the advance-booking row but never carried over to the round actually dispatched — both are documented gaps, not silently dropped. Incentive/reward mechanics are explicitly out of scope for #23; file separately if wanted. Accounting can now export a closed payroll period as CSV (`payroll/export.php`, caddy name/SCB account number/amount, UTF-8 BOM for Excel), browse/re-export the full history of closed periods (`payroll/history.php`, `payroll/view.php`), and the data-fetch/format-render split in `includes/payroll_export.php` is meant to take an SCB-specific bulk-transfer format later without reworking this path; `queue_hr` is blocked from all three pages. A token-authenticated REST API (`api/`, see Android REST API above) now gives `queue_hr` staff the same queue view/status-update/caddy-registry/leave-recording access as the web UI for a future Android client — `accounting`/`admin` can't log in through it, and every write reuses the exact same shared functions the web UI calls, so both surfaces stay in sync automatically. The dashboard's "แคดดี้ที่กำลังออกรอบ" (on-round) panel now has "เสร็จแล้ว"/"พร้อมออกรอบต่อ" actions that route through the rating page first — rating is required before the caddy is checked out or returned to the ready queue, not a separate optional step. Remaining tickets tracked on this repo's [Issues](https://github.com/poangpan/caddy-management/issues) page.
