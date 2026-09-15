# Andy Core Email OS P8B — ERP Connector Published Snapshot

Status: IMPLEMENTED
Date: 2026-09-15
Branch: `feat/andy-core-v1.5.8-email-template`

## 1. Route

P8B exposes the P8A-frozen projection through the existing Connector:

`GET /wp-json/andy-core/v1/erp/snapshot/email-templates`

Authentication is unchanged: the existing Connector HMAC v1 contract is mandatory.
No second credential, token, session, or ERP-specific auth path is introduced.

## 2. Response envelope

The outer Connector envelope remains `contract_version = 1`.
The snapshot data additionally exposes:

`resource_contract_version = email-os-published-v1`

Records remain exactly the eight fields frozen by P8A.
## 3. Eligibility and ordering

Only `wordpress` and `andy_core` registry identities are considered.
A row is emitted only when `get_published_snapshot()` returns a valid immutable snapshot and the P8A projector accepts it.

Draft-only, disabled, corrupt, missing-version, malformed-hash and WooCommerce identities are omitted.
Items are stable-sorted by `template_key` before pagination.

## 4. Snapshot query semantics

P8B reuses the existing bounded snapshot query contract:

- `limit`: default 50, maximum 100
- `cursor`: resource-bound pagination cursor
- `updated_after`: optional ISO-8601 timestamp

`updated_after` compares against the Published Snapshot timestamp, never draft update time.
No Published rows is a successful empty collection, not an error and never a Draft fallback.

## 5. Mutation prohibition

There is no template POST, PUT, PATCH or DELETE route.
The resource cannot save Drafts, publish, rollback, disable, test-send, production-send, edit Woo/VillaTheme, or mutate ERP state.
## 6. P8B gate

P8B is PASS only when tests prove:

- the route is registered as GET-only;
- existing Connector HMAC authentication remains the gate;
- only valid Published Native metadata is returned;
- the resource schema version is explicit;
- pagination and `updated_after` remain bounded/read-only;
- forbidden content fields are absent;
- no template mutation/send route is introduced;
- existing Connector snapshots and mutations still pass regression.

P8C may now implement the ERP-side read-only consumer without changing this WordPress contract.
