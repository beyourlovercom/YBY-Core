# P9-5 Append-only Audit Source Decision — separate authorized V2 schema, never an Outbox redefinition

**Scope:** architecture and test-only follow-up to Mail P9 Draft #118. No schema is applied.

## Current verified facts

P6 Outbox is exactly 20 metadata columns, 4 indexes and one physical
synthetic table. Current P9 writer `open()` **denies extra tables** and
a writer principal with anything beyond database-scoped SELECT, INSERT and
UPDATE. Existing P9 Reader has SELECT only. P8 Hostinger live reader is
different, independently accepted in Dev Owner UAT; **no writer grants or
tables can be added to P8**. A mutable Outbox current-state row is NOT an
append-only event history.

P9-5 102-row independent private lab proved bounded mock-preacceptance
retries, race on deferred re-claim, limit exhaustion and ambiguous
expiry quarantine. **This is not proof of durable append-only event
logging or real SMTP.**

## Proposed future event source, pending specific Owner schema+role approval

Place an audit ledger in a **new isolated P9 V2 test database**, not in
the existing `p9_mail_synthetic_uat` schema. Do NOT add it to the
currently strict one-table manifest or make `open()` tolerate additional
tables silently. Proposed ledger columns, metadata only:

| Column | Rule |
| --- | --- |
| `opaque_id` | immutable 64-char digest, never customer ID |
| `state_version` | monotonically increasing unsigned BIGINT, unique with `opaque_id` |
| `from_state`, `to_state` | strict known P1 state names |
| `event_code` | fixed allowlist `enqueued`, `claimed`, `preacceptance_deferred`, `uncertain_hold`, `retry_exhausted`, `reviewed` |
| `diagnostic_code` | fixed non-sensitive code |
| `occurred_at` | UTC timestamp from DB, not an SMTP response |
| `event_hash` | digest over canonical metadata and prev hash, keyed/validated in a separately designed integrity scheme |
| `prev_event_hash` | previous linked audit event hash or genesis value |

Composite `PRIMARY KEY (opaque_id,state_version)`, append-only INSERT
role, no normal UPDATE/DELETE/TRUNCATE. Retention/deletion policy is
**not decided** by this ADR. A digest chain alone does not prove against
an attacker with DB administrative DELETE/ALTER privileges; tamper
evidence may require independent read-replica/WORM anchoring.

## Atomic recording invariant

1. Begin **one** InnoDB transaction on the exclusively approved V2
   dedicated writer DB and role.
2. Read exact current `state_version` with a lock, validate legal
   transition, strict synthetic proof and explicit capacity.
3. CAS UPDATE **one** Outbox row and INSERT exactly **one** audit event
   for its next version in **the same transaction**. No external I/O,
   SMTP or WordPress hooks before commit.
4. Require matched updated=1 and inserted=1; any violation rolls back
   both mutations, including on connection loss. On an ambiguous
   transaction commit, independently read by unique event key and
   state_version before deciding; **never blindly replay any mail**.
5. Future review of `uncertain` requires verified independent provider
   acknowledgement or explicit manual disposition; no return to queue.
   `retry_exhausted` remains non-dispatchable without new authorization.
6. Event viewer may show only redacted aggregate or carefully
   capability/nonce gated metadata; no subject, body, recipient, token,
   SMTP transcripts or leaked row payloads.

## Failure injection before any proposed live Dev connection

- Crash between CAS and audit INSERT inside transaction -> both rollback.
- Crash after INSERT but before COMMIT -> neither persists.
- Connection drops at COMMIT -> look up opaque/version/event key to
  resolve, not blind retry.
- Two distinct processes race with same expected version -> exactly
  one paired audit+state transition.
- Audit key collision -> whole transaction rollback.
- Audit role denied UPDATE/DELETE; reader denied INSERT/UPDATE/DELETE.
- Ambiguous provider response -> `uncertain` HOLD; replay denied.
- Drift in table/index/grants/retention policy or audit availability ->
  disable new experimental Worker; leave incumbent mail and P8 intact.

## Integration gate

Audit schema+grant+backup design, lifecycle and externally verified
retention must first be explicitly approved, and tested in an **entirely
new** P9-only synthetic V2 private DB. Only then can a composite
V2.2/P8/P9 adapter PR be reviewed. Do not call existing P9 Outbox
statistics or a JSON snapshot an append-only audit event stream.
The current independent private P9 100/102-row history is preserved.
