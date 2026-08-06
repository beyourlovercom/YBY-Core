# Andy Core v1.5.1 Test Matrix

## Automated Runtime Coverage

- Schema names, columns, defaults, unique and filter indexes.
- Migration idempotency and preservation of the existing Lead table.
- Status, priority, tab, owner, and page-size allowlists.
- Capability, Lead existence, output escaping, and no original Lead mutation.
- One list query, one detail query, bounded activities, and no N+1 paths.

## Regression Coverage

Existing Lead REST, Case ID, notification, Thank You, Bottle, Irrigation, Project Studio, Projects, Brand, Social Login, One Tap, and General Settings harnesses remain required.

## Isolated WordPress/MySQL Evidence

GitHub Actions run `31078688839` upgrades an isolated v1.5.0 plugin and synthetic Leads from database `1.2.0` to `1.3.0`. It verifies migration repair and idempotency, Lead immutability, no historical backfill, runtime settings binding, capability toggles, management activities, archive/restore, bounded activity reads, invalid page-size fallback, SQL-like search input, and 1k/10k/50k list measurements.

## Owner UAT Coverage

Owner UAT covers menu merge, Settings tabs, Inbox search/filter/pagination, detail management, timeline, archive/restore, permissions, diagnostics, and rollback.
