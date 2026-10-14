# Mail OS V2 P6 — Outbox Schema / Read-only Bridge Review

**Date:** 2026-10-14  
**Authority:** User said continue from P3–P5 source CI closure. Authorizes P6 isolated **code/design/tests**, not Dev database creation, queue writing, sending, Worker enabling, production/WWW deployment or PR merging.

## Dependency chain and ownership

- P1 (#91) defines opaque envelope and state transitions; P2 (#92) synthetic dispatch plans; P3 (#95) sanitized snapshot read and auth expectations; P4 (#96) synthetic sender-policy and delivery result semantics; P5 (#97) 10,000 synthetic task simulation with cross-window quota conservation.
- Separate Canonical WordPress Email OS tabs live in the Dev-only M6/M7 branch (#94 based on #93), not in the Mail OS V2 stack. **No silent merging** across those two stacks.
- This P6 code is a **pure PHP proposal**. It does not register on `init`, call `dbDelta`, open a DB connection or run any SQL, send email, mutate options or schedule jobs. A query plan is not a query result.

## Proposed Outbox table

Logical table `$wpdb->prefix . 'yby_mail_v2_outbox'`; prefix strictly ASCII letters/numbers/underscores, bounded length and SQL identifiers quoted.

The schema fields exactly match `YBY_Mail_V2_Governance::schema_contract()['columns']` (20 fields): opaque ID, unique idempotency key, keyed event hash, external protected payload reference, channel, logical sender key, template identity/version, consent snapshot hash, priority, state, state_version, attempt/max_attempts, due time, lease token hash/expiry, allowlisted diagnostic code, created/updated UTC times.

Planned constraints/indexes: PK opaque_id, UNIQUE idempotency_key, index (state, priority, next_attempt_at), index (sender_key, state, next_attempt_at); MySQL InnoDB, ASCII binary collation for opaque keys.

**Fields explicitly absent:** recipient/To, subject, body, headers, SMTP credentials, raw provider response and authentication tokens. The separately protected payload store is NOT specified or authorized by this PR, nor are any message bodies created.

A `CREATE TABLE` statement is emitted as a **review-only string** (no `IF NOT EXISTS` that could mask drift). `schema_matches` validates an externally supplied exact manifest, but **cannot establish that the actual database matches** unless a separately authorized read-only adapter inspects the target database's columns, types, indexes, collation and name.

## P6 read-only query boundaries

The result of `readonly_query()` is only a SQL template plus numeric `LIMIT/OFFSET` bound values. It cannot execute. Required caller attestations:

1. WordPress server-side **Email OS read capability** (planned `andy_core_mail_view`), plus request nonce independently verified. No staff role auto-grants.
2. Isolated Dev site and dedicated **read-only connection** independently confirmed.
3. Exact schema and index verification from the real INFORMATION_SCHEMA + table isolation.
4. Page 1..1000, page size 10/20/50. Never interpolate untrusted identifiers or filter SQL.
5. Strict selected columns: opaque ID, state, channel, sender, priority, attempt/max attempts, next due, diagnostic code and state version.
6. Independent total/count-by-state SQL (not fake totals computed from a 1,000-row sample).

Queues show all current states, Failures show deferred/permanent_failed/uncertain. **Logs intentionally fail closed:** one stateful Outbox row does not represent an append-only delivery event history. A separate append-only audit contract and retention review are still necessary before an actual Logs page.

P1 mutation denial remains absolute. Even a matching schema, capability, nonce or `owner_approved` boolean does not permit UPDATE/INSERT/DELETE, retry, enqueue, lease or sending.

## Rollback, backups and migration gate

No automatic destructive rollback. On an isolated disposable Dev database *after separate Owner authorization*, first verify isolation, current schema, intended table absence and backup/recovery pointer. Stop rather than overwrite on existing table/drift. The review DDL may be applied only after an independent migration plan is accepted and its exact files, database and TTL/recovery are documented.

For Production: no table creation or deletion authorized; a future forward-only migration should be additive, with feature flags OFF, independent reconciliation and an Owner-approved data retention approach. DROP with real queue data is **never** the default rollback.

No provider-specific SMTP quota or delivery success may be inferred from `accepted_by_provider`. Woo transactional sends must remain on their existing routes until a separate controlled cutover.

## P6 completion gates

- Exact HEAD GitHub CI 4/4 and P1–P6 offline harnesses PASS.
- Draft PR remains OPEN/UNMERGED, not a deployment.
- Owner Code UAT for P6 read-only / migration architecture.
- **Separate required approval:** disposable Dev database/table create and synthetic inserts, verified read-only WordPress UI integration with PR #94 and real WordPress user roles.
- **Separate required approval:** Action Scheduler job registration, SMTP transport credentials/use, real sends, WWW or Production database migration.

Never present the SQL proposal, planned UI adapter or 10k synthetic simulation as an operational SMTP queue.
