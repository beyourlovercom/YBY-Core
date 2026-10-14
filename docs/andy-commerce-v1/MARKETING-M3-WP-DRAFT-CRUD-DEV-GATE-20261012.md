# Andy Commerce M3 — Dev-only Persistent Draft CRUD Gate
2026-10-12 | Source: PR #79 exact-head 7155521f34e3ebea73ae6a3618077c60f9f1d00e

## Safety contract
All campaigns remain DRAFT, active=false, runtime_enabled=false. No publish, schedule or activation endpoint. Do not connect Woo cart, checkout, shipping or discounts. Keep BYL49 readonly shipping file unchanged. No WWW deploy, PR merge, payment or order writes.

## P0 before any Dev database write
1. Obtain independent Production database identity from an authorized read-only source and compare Dev DB_NAME/DB_HOST/prefix; do not access WWW SSH.
2. Verify G2 permit absent and current plugins. Verify off-webroot complete Dev database dump with SHA256, exit status, dump completeness and rollback pointer; ideally isolated restore.
3. Require exact-head GitHub CI, scoped code review and explicit Dev-only deployment gate. Do not modify existing database tables.

## Proposed Dev schema
Two new WordPress prefix-scoped tables: andy_commerce_campaign_drafts and andy_commerce_campaign_audit.
Drafts fields: primary ID; unique UUID; allowlisted type; schema version; revision; status draft/archived only; active=false; bounded canonical JSON payload and SHA256; creator/updater; UTC timestamps.
Audit: append-only ID, draft ID, revision, actor, event created/revised/archived, payload hash, UTC time.
Use atomic compare-and-swap revision protection with DB transaction; reject stale edits and audit write failure. No execution engine reads these tables.

## WP UI and security
Create/list/edit/archive only under Andy Commerce Marketing. Authenticated manage_woocommerce capability and action-specific nonce on every mutation; strict server-side PR#79 validation; bounded data, sanitized fields and pagination. Use nonce-protected admin-post endpoints. No publish controls. Label all as Draft only / OFF.

## Validation and rollback
Disposable WordPress/MySQL integration tests for create/revise/archive, permissions, nonce, malicious inputs, stale revision, audit atomicity, immutable archive, six disabled types and lack of Woo side effects.
Dev Owner UAT on desktop and 390px mobile: persistence survives reload, revision conflict, archive, audit, BYL49 read-only, all campaigns OFF. Restore only using verified rollback evidence. Never run a destructive rollback on a validated Dev installation merely to claim PASS.

## Verified preflight evidence
PR#79 4/4 GitHub CI SUCCESS. Dev DB_NAME u247343654_NUOBf; DB_HOST 127.0.0.1; prefix wp_. Production database identity is not independently verified; M3 database dump is not yet verified. Hence persistence writes remain fail-closed.