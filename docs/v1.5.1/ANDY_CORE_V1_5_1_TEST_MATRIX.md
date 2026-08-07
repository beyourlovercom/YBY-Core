# Andy Core v1.5.1 Test Matrix

## Automated Runtime Coverage

- Schema names, columns, defaults, unique and filter indexes.
- Migration idempotency and preservation of the existing Lead table.
- Status, priority, tab, Active Inquiry Owner, and page-size allowlists.
- Capability, Lead existence, output escaping, and no original Lead mutation.
- One list query, one detail query, bounded activities, and no N+1 paths.
- Salesperson identity is sourced only from WordPress role `salesperson`.
- Active Inquiry Owners control only new assignment eligibility.
- Historical inactive owners remain stored and readable to their Salesperson while the role remains `salesperson`.
- Stale active-owner settings containing Administrator, Subscriber, or deleted IDs are filtered at runtime.

## Regression Coverage

Existing Lead REST, Case ID, notification, Thank You, Bottle, Irrigation, Project Studio, Projects, Brand, Social Login, One Tap, and General Settings harnesses remain required.

## Isolated WordPress/MySQL Evidence

GitHub Actions upgrades an isolated v1.5.0 plugin and synthetic Leads from database `1.2.0` to `1.3.0`. It verifies migration repair and idempotency, Lead immutability, no historical backfill, runtime settings binding, capability toggles, management activities, archive/restore, bounded activity reads, invalid page-size fallback, SQL-like search input, Salesperson role identity, Active Owner assignment eligibility, stale allowlist filtering, query override isolation, direct lead access isolation, and 1k/10k/50k list measurements.

## Required Permission Gates

`SALESPERSON_ROLE_IDENTITY_GATE`, `ACTIVE_OWNER_GATE`, `INACTIVE_OWNER_HISTORY_GATE`, `INACTIVE_OWNER_NEW_ASSIGNMENT_DENY_GATE`, `ROLE_REMOVAL_ACCESS_GATE`, `ROLE_RESTORE_HISTORY_ACCESS_GATE`, `STALE_ALLOWLIST_SECURITY_GATE`, `ADMIN_ALL_LEADS_GATE`, `DIRECT_LEAD_ACCESS_ISOLATION_GATE`, `QUERY_OVERRIDE_ISOLATION_GATE`, `DEFAULT_OWNER_GATE`, `OWNER_DROPDOWN_GATE`, `SALESPERSON_A_SCOPE_GATE`, and `SALESPERSON_B_SCOPE_GATE`.

## Owner UAT Coverage

Owner UAT covers menu merge, Settings tabs, Inbox search/filter/pagination, detail read-only mode for Salesperson, administrator owner assignment, inactive historical owner display, timeline, archive/restore, permissions, diagnostics, and rollback.
