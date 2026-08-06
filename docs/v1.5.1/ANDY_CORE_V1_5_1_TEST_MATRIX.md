# Andy Core v1.5.1 Test Matrix

## Static and Unit Coverage

- Schema names, columns, defaults, unique and filter indexes.
- Migration idempotency and preservation of the existing Lead table.
- Status, priority, tab, owner, and page-size allowlists.
- Capability, nonce, Lead existence, output escaping, and no original Lead mutation.
- One list query, one detail query, bounded activities, and no N+1 paths.

## Regression Coverage

Existing Lead REST, Case ID, notification, Thank You, Bottle, Irrigation, Project Studio, Projects, Brand, Social Login, One Tap, and General Settings harnesses remain required.

## UAT Coverage

Owner UAT covers menu merge, Settings tabs, Inbox search/filter/pagination, detail management, timeline, archive/restore, permissions, diagnostics, and rollback.
