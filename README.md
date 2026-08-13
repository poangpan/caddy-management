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

## Repo layout

- **`.scratch/caddy-management/spec.md`** — full spec: problem statement, user stories, implementation decisions, testing decisions, and out-of-scope items.
- **`ref/`** — reference prototypes and design notes per feature area (advance booking, caddy directory, queue dashboard, payroll/accounting summary, fairway precision).
- **`docs/agents/`** — configuration consumed by Claude Code agent skills (issue tracker, domain docs conventions).
- **`CLAUDE.md`** — entry point for agent skill configuration.

## Status

Spec and UI prototypes only — implementation hasn't started yet. Issues are tracked on this repo's [Issues](https://github.com/poangpan/caddy-management/issues) page.
