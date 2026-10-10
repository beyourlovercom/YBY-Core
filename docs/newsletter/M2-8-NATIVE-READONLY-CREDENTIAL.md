# Andy Core Newsletter M2.8 — independent read-only HMAC identity

Status: CODE-ONLY / Draft PR review gate. This document does not authorize Dev or Production deployment.

## Security problem

The existing general YBY_Connector identity authenticates both snapshot GET and state-changing POST routes. Temporarily enabling the general Connector for an M2 Newsletter read-only canary grants a broader capability than intended.

## Implementation contract

- New class: inc/class-yby-connector-newsletter-readonly.php, loaded by the canonical Connector.
- Separate default-absent option namespaces: yby_core_newsletter_readonly_options and yby_core_newsletter_readonly_secret; neither autoloads.
- No new REST endpoints, Admin UI, general Connector configuration changes, plugins, or schemas.
- Only trusted WP-CLI can provision/revoke the identity. Provisioning rejects invalid keys, general-key-ID collisions, replacements of existing identities, or TTL outside 1–900 seconds. Random 32-byte secrets are encrypted at rest with WordPress salts (AES-256-CBC with HMAC integrity protection); plaintext is not stored.
- The scoped key never activates the general Connector. A matching scoped key must pass its explicit scope or fail closed, never fall through to the general identity.
- ONLY allowed: GET /wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=N, where N = 1..100, in the ERP gateway's exact query order, against real REST route /andy-core/v1/erp/snapshot/subscribers.
- Denied: missing/alternative source; Elementor legacy reads; cursor or updated_after; extra/duplicate/reordered query; request body or idempotency key; all other snapshots; every POST, non-GET method, or unrelated route.
- HMAC v1 signs the exact GET request using the existing canonical string. Reject invalid signature/version, missing nonce, TLS failure, timestamp outside ±300 seconds, repeated sequential nonce, or expired identity.
- Per-key transient replay and rate namespaces, max 60 requests per minute; WordPress transients do not guarantee atomic nonce consumption during concurrent races, so do not overclaim concurrency safety.
- Expiration fails closed even if options are not yet deleted. Explicit CLI revocation deletes both options. TTL at most 15 minutes.
- Legacy general Connector authentication remains backwards compatible, including other routes.

## Code-only evidence

tests/connector-newsletter-readonly-security-harness.php exercises default-off state, issuance validation, encryption at rest, no general Connector activation, valid bounded native GET, negative route/query/body/method tests, invalid HMAC/version/time/identity/TLS, tampered MAC, expiration, rate limiting, replay, general Connector compatibility, and credential revocation.

CI uses existing Andy Core PHP lint plus PHP, Commerce and JavaScript harnesses. Earlier Windows PHP lacked OpenSSL: verify cryptographic harnesses in an OpenSSL-enabled environment without changing or restarting Windows.

## Separate Owner acceptance gates

1. Exact-head GitHub CI PASS plus Connector/security-owner and shared Dev/G5 safety review.
2. Use an officially supported approved execution mechanism for secret provisioning without revealing a raw credential in assistant/tool output. Do not bypass blocked credential-transfer tool controls.
3. Recheck Dev database isolation, zero native subscribers, all Newsletter/mail/public API flags OFF, and general Connector OFF.
4. Permit only a signed native GET from an isolated ERP client and zero-row response plus negative cases. No live import or subscriber sync POST, no legacy Elementor reads, no email, no changes to existing localERP, and no WWW/Production operations.
5. Revoke and verify missing scoped options, no side effects. If the supported secure executor remains unavailable, leave LIVE DEV SIGNED E2E BLOCKED instead of marking PASS.

Disposable CI WordPress/MySQL↔ERP signed HTTP E2E is separate from a real Dev test.

Related GitHub work items: ERP M2 #448, ERP M2.8 #452, Core #71 and Website mail safety #67.
