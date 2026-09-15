# Andy Core Email OS P8A — ERP Read-only Published Template Contract

Status: FROZEN
Date: 2026-09-15
Branch: `feat/andy-core-v1.5.8-email-template`

## 1. Purpose

P8A freezes the V1 contract between Email OS and ERP before any REST route or ERP consumer is added.

Source of truth remains WordPress / Andy Core Email OS. ERP is a read-only consumer only.

The resource planned for P8B reuses the existing authenticated Connector namespace:

`GET /wp-json/andy-core/v1/erp/snapshot/email-templates`

No second authentication system is permitted.

## 2. Ownership boundary

The P8 published-template resource includes only Email OS native providers:

- `wordpress`
- `andy_core`

WooCommerce is excluded from this Published Snapshot resource because Woo / VillaTheme remains its editor and runtime owner. Woo governance may be consumed separately in a future read-only bridge, but ERP must not treat Woo templates as Andy Core Published Snapshots.

## 3. V1 record schema

Contract version: `email-os-published-v1`.

Each returned template record contains exactly:

- `template_key`
- `provider`
- `label`
- `status` — always `published`
- `runtime_enabled` — observed native runtime availability, never ERP-writable
- `published_version` — immutable Email OS version number
- `content_hash_sha256` — SHA-256 of the immutable payload snapshot
- `published_at`

A template is eligible only when `YBY_Email_Template_Store::get_published_snapshot()` returns a valid immutable snapshot with a matching SHA-256 hash.

Draft-only, Disabled, missing-version, corrupt, invalid-JSON or hash-mismatched states are not projected as Published records.

## 4. Explicitly forbidden data

ERP V1 receives no draft or template-content payload. The contract must not expose:

- `working_payload` / `payload_snapshot` / `default_payload`
- subject, preheader, heading, copy, dynamic sections or CTA content
- sample context or variable values
- Owner Notes
- recipient addresses
- editor URLs / preview-test URLs
- SMTP credentials, API keys, OAuth secrets or transport secrets

ERP therefore cannot reconstruct, edit, preview or render a production email from this V1 resource.

## 5. Mutation prohibition

P8B may add only a signed authenticated `GET` route for this resource.

The following are forbidden in P8:

- POST / PUT / PATCH / DELETE template routes
- ERP-triggered draft save
- ERP-triggered publish / rollback / disable
- ERP-triggered test send or production send
- ERP-triggered Woo / VillaTheme mutations
- a second ERP-side canonical template store

ERP may cache response metadata for display/reconciliation, but Email OS remains the only source of truth.

## 6. Error / absence semantics

No Published Snapshot is not an error and must not fall through to Draft. The template is simply absent from the published collection.

Authentication failures use the existing Connector HMAC error contract. Provider/template-store read failures must fail closed and must never substitute working draft data.

The Connector envelope remains version `1`; `email-os-published-v1` versions the Email OS resource schema inside that authenticated contract.

## 7. Example record

```json
{
  "template_key": "wordpress:reset_password",
  "provider": "wordpress",
  "label": "Reset Password",
  "status": "published",
  "runtime_enabled": true,
  "published_version": 3,
  "content_hash_sha256": "<64 lowercase hex chars>",
  "published_at": "2026-09-15 17:00:00"
}
```

## 8. P8A gate

P8A is PASS only when code and tests prove:

- the field list is frozen and exact;
- only `wordpress` / `andy_core` can project Published records;
- Woo is rejected by the projection;
- malformed or incomplete Published metadata is rejected;
- forbidden draft/content/secret fields are absent;
- no REST route or ERP-side write behavior is introduced in P8A.

P8B may expose this frozen projection through the existing signed Connector GET snapshot resource without changing the schema.
