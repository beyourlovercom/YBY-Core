# Andy Core v1.5.3 鈥?BYL ERP WordPress Connector Work Package

Status: M1 COMPLETE / M2 COMPLETE / M2.1 COMPLETE / M3 COMPLETE / M3.1 SUBSCRIBER SNAPSHOT LOCAL UAT PASS / DELIVERY GATE PENDING
Contract ID: ANDY-CORE-V1.5.3-WORDPRESS-CONNECTOR
Baseline release: Andy Core v1.5.2
Branch: `feature/andy-core-v1.5.3-wordpress-connector-m3.1-subscribers`
Worktree: `D:\\ai\\_worktrees\\YBY-Core-v153-wordpress-connector-m31-subscribers`
Base HEAD: `fbb0ff5c934537fd5739f93adbb30b65ebab6e50`
Released v1.5.2 tag: `073ef9d34bbd5bfda1db7ca52caf358f7e6ade87`
Database baseline: `1.3.0`

## Authority

Owner-approved canonical functional contract: `Andy_Core_v1.5.3_WordPress_Connector_Full_Spec.md` supplied 2026-08-29.
Developer handoff contract: `Andy_Core_v1.5.3_Developer_Handoff_Prompt.md` supplied 2026-08-29.
Repository `AGENTS.md` remains binding for local-first implementation and UAT flow.

## Scope

Add a bounded WP-API / BYL ERP WordPress Connector module under the current Andy Core architecture.
Admin surface: `Andy Core -> Settings -> WP-API`, tab `wp-api`; no standalone submenu.
REST namespace: `/wp-json/andy-core/v1/erp`.
Add HMAC-SHA256 V1 authentication, replay protection, mutation idempotency, provider adapters, safe audit, provider snapshots and bounded mutations defined by the canonical contract.

## Hard compatibility gates

v1.5.3 is additive only. Existing Inquiry, Inquiry ownership/isolation, Inquiry shortcodes, Sticky CTA, Global Inquiry Dock, Settings, frontend hooks and current database data must not regress.
ERP remains a separate repository/product and is out of scope here.
No merge, deploy, tag, publish or release without explicit Owner authorization.

## Verified repository architecture

Admin pages are wired through `inc/class-yby-core.php` and existing controller classes under `admin/` / `inc/`.
Existing REST routes register on `rest_api_init` through dedicated controller classes.
Database migrations are centralized in `inc/class-yby-database.php` using idempotent `dbDelta` and `yby_database_version`.
Current module directory is lightweight documentation; do not force a new `src/` architecture that conflicts with the repository.

## Current local-UAT status

The dedicated BeYourLover local site provides the real provider stack for Connector UAT. WordPress 7.1, WooCommerce 10.9.4, AffiliateWP 2.35.0, and the local Andy Core M2 security-canary runtime are available at `https://localdev.beyourlover.com`. Provider-absent behavior remains covered by focused harnesses. Owner visual UAT passed.

## Stop gate

Delivery target is `OWNER UAT READY` with exact evidence. Stop before merge/deploy/release.

## Local full-provider UAT environment

A dedicated local BeYourLover WordPress environment has been inserted before M1 implementation:

- Path: `D:\ai\devbeyourlover`
- URL: `https://localdev.beyourlover.com`
- WordPress baseline: 7.1
- The restored BeYourLover local runtime exposes WooCommerce 10.9.4 and AffiliateWP 2.35.0 as active providers.
- Andy Core M1 is installed and activated locally for visual UAT while the plugin release metadata remains 1.5.2 / DB 1.3.0.
- No production site or ERP environment is involved in this UAT runtime.
- It is a runtime/UAT environment only; Andy Core source authority remains the isolated v1.5.3 Git worktree.

## M1 implementation evidence (2026-08-29)

- Owner UI revision: WP-API is a one-time settings surface under `Andy Core -> Settings -> WP-API` (`tab=wp-api`), with no standalone second-level menu. The tab remains protected by `manage_options` and uses concise Chinese admin copy/status badges.
- Added dedicated option `yby_core_connector_options` with Connector Enabled, read-only Site URL, Connection Key, read-only Contract Version `1`, and Key ID. Save uses `manage_options` plus a dedicated nonce, disabled autoload, and does not overwrite unrelated options. Identity sanitization accepts `beyourlover.com` and conservative alphanumerics plus dot, dash and underscore, while rejecting whitespace and HTML.
- Added truthful four-card status display. WordPress reports `get_bloginfo('version')`; installed providers report `Ready`, absent providers report `Provider Missing`, and no counts or fabricated network state are shown.
- Added local-only Test Connection self-check. Canonical statuses are `Connector Disabled`, `Configuration Error`, `Provider Missing`, and `Ready`; no ERP or external request is made and the UI never claims `Connected`.
- Added exactly ten contract endpoint table rows: the five approved GET paths and five approved POST paths. M1 registers no REST routes or HMAC, so every route row remains unavailable and reports `Configuration Error` when its provider is present/configuration is otherwise valid; absent-provider rows report `Provider Missing` first.
- No HMAC signing, replay protection, idempotency, provisioning, coupon/payout mutations, production network calls, ERP changes, or database schema/version changes were made. No shared secret field or value is stored/rendered.
- Focused evidence: `php tests/connector-foundation-harness.php` -> `Connector foundation harness passed.`; it covers the exact ten routes/no extras, disabled/configuration/provider-missing states, `beyourlover.com` acceptance and unsafe input rejection, capability/nonce gates, separate option namespace, and no shared-secret field/storage.
- Full-provider local runtime evidence: `https://localdev.beyourlover.com` returned WordPress 7.1, WooCommerce 10.9.4, AffiliateWP 2.35.0, Andy Core 1.5.2 with Connector loaded, all three provider cards `Ready`, exactly ten contract rows, and every row unavailable while Connector is disabled.
- Validation evidence: focused Connector harness passed; all five JavaScript harnesses passed; all PHP harnesses passed with the repository-equivalent Windows accommodations: `google-auth-harness.php` passed under Local PHP 8.2 with its bundled OpenSSL configuration, and the unchanged Brand admin harness passed from an LF-normalized temporary copy (the native Windows checkout false-negative is line-ending-only; both referenced source blobs exactly match base `44b71db`). PHP lint passed for 85 plugin/test PHP files, `git diff --check` passed, release metadata harness passed, and changed-file credential scan returned no secret patterns. Release metadata remains plugin/header `1.5.2`, `YBY_CORE_VERSION` `1.5.2`, `YBY_DATABASE_VERSION` `1.3.0`, with baseline `VERSION.md` development state.

## M2 security canary implementation evidence (2026-08-29)

- Added HMAC-SHA256 V1 authentication using Connection Key, Key ID, timestamp, nonce, and SHA256(body) canonical signing. Timestamp tolerance is +/-300 seconds; nonces are replay-protected; per-key rate limiting is bounded. HTTPS is required.
- Added Shared Secret generation/rotation gated by `manage_options` and a dedicated nonce. The secret is encrypted at rest using authenticated encrypt-then-MAC storage derived from WordPress salts, stored non-autoload, and shown only in the immediate generation response.
- Registered exactly one Connector REST canary: `GET /wp-json/andy-core/v1/erp/health`. The other nine contract endpoints remain unregistered and render as neutral gray `Not Available` / `未开放`.
- Local real-endpoint UAT passed: valid signed request -> HTTP 200 / `Ready`; replay -> HTTP 409 `yby_nonce_replayed`; bad signature -> HTTP 401 `yby_signature_invalid`; stale timestamp -> HTTP 401 `yby_timestamp_invalid`; obsolete `/wp-json/yby/v1/health` -> HTTP 404.
- Validation passed: Connector foundation harness, Connector security harness, all five JavaScript harnesses, 18 direct PHP regression harnesses plus the unchanged Windows LF-normalized Brand admin equivalent, PHP lint including the new security harness, and `git diff --check`.
- Owner visual UAT passed after the nine intentionally unopened business rows were corrected to gray `未开放`. No provider snapshot/mutation implementation, external ERP call, database schema/version change, production deployment, tag, or release is included in M2.

### Post-M2 gate

M2 security canary is local Owner-UAT PASS. Stop before merge/deploy/release; proceed to the next provider/data milestone only after delivery and explicit Owner authorization.
## M2.1 canonical contract alignment evidence (2026-08-29)

- Reconciled the merged M2 security canary with the Owner-approved HMAC V1 contract before opening provider/data endpoints. Canonical signing is now `METHOD + PATH_WITH_QUERY + TIMESTAMP + NONCE + CONNECTION_KEY + IDEMPOTENCY_KEY + SHA256(RAW_BODY)`; Key ID remains a required credential selector but is not a canonical payload slot.
- `X-YBY-Signature-Version: v1` is mandatory. `X-YBY-Idempotency-Key` canonicalizes to an empty slot for `GET /health`; future mutation endpoints must require it. The exact raw request URI including query order is signed and is not normalized or rebuilt.
- `GET /wp-json/andy-core/v1/erp/health` now returns the standard V1 success envelope with `ok`, `contract_version`, non-empty `request_id`, exact `connection_key`, and `data`. Authentication failures are converted to the standard error envelope with stable codes including `AUTH_INVALID`, `REPLAY_DETECTED`, `CONTRACT_VERSION_UNSUPPORTED`, and `RATE_LIMITED`.
- Real Local UAT passed: valid V1 request -> HTTP 200/envelope; missing signature version -> HTTP 400 `CONTRACT_VERSION_UNSUPPORTED`; bad signature -> HTTP 401 `AUTH_INVALID`; replay -> HTTP 409 `REPLAY_DETECTED`; exact signed query order -> HTTP 200.
- Focused Connector foundation/security harnesses, five JavaScript harnesses, 18 direct PHP regression harnesses, unchanged Brand-source equivalence, PHP lint 86/86, and `git diff --check` passed. No UI layout, provider snapshot/mutation, DB schema, production deployment, tag, or release change is included.

### Post-M2.1 gate

After this alignment is delivered, the next milestone is the read-only provider snapshot layer: Affiliates, Coupons, Referrals, and Payouts. Mutations and idempotency storage remain later milestones.

## M3 read-only Provider Snapshot evidence (2026-08-30)

- Registered exactly four additional HMAC-protected read-only GET routes: `/snapshot/affiliates`, `/snapshot/coupons`, `/snapshot/referrals`, and `/snapshot/payouts`. Together with `/health`, M3 exposes exactly five Connector GET routes. The five POST contract rows remain unregistered and `Not Available`.
- Snapshot queries accept bounded `limit`, opaque resource-bound `cursor`, and ISO-8601 `updated_after`. Invalid query input returns stable `VALIDATION_FAILED`; missing AffiliateWP/WooCommerce providers return `PROVIDER_UNAVAILABLE`.
- AffiliateWP 2.35.0 native read APIs are used for affiliates, referrals, and payouts. WooCommerce 10.9.4 coupon objects are used for coupon mapping. M3 performs no provider mutation and introduces no Connector database schema.
- Local real-provider HMAC UAT passed on `localdev.beyourlover.com`: all four snapshot routes returned HTTP 200 with real provider rows; cursor pagination returned distinct sequential affiliate records; future `updated_after` filtering returned zero rows for all four resources as expected.
- Focused Connector Foundation, M2 Security, M3 Snapshot, and M3 Provider Mapping harnesses passed. JavaScript harnesses passed 5/5. Google Auth passed with the Local PHP OpenSSL configuration; the unchanged Brand harness passed from an LF-normalized copy with source equivalence to `origin/main`.
- M3 does not include Subscriber Snapshot, mutations, idempotency storage, ERP code, version/database bumps, production deployment, tag, or release.

### Post-M3 gate

After M3 delivery and explicit Owner merge authorization, the next bounded Andy Core increment is M3.1 Subscriber Snapshot: a HMAC-protected read-only `/snapshot/subscribers` adapter over the confirmed WordPress Elementor signup authority, with normalized-email dedupe and no ERP-side source-merging logic inside Andy Core.

## M3.1 WordPress Subscriber Snapshot evidence (2026-08-30)

- Added one HMAC-protected read-only GET route: `/snapshot/subscribers`. Connector REST surface is now exactly six GET routes: `/health` plus Affiliates, Coupons, Referrals, Payouts, and Subscribers. The existing five POST contract rows remain unregistered and `Not Available`.
- Subscriber authority is read-only WordPress Elementor submission data in `{$wpdb->prefix}e_submissions` and `{$wpdb->prefix}e_submissions_values`. Only `Singup` / `signup` forms are included; `New Form` and other forms are excluded.
- Real Local database audit confirmed 518 signup submissions, 422 unique normalized non-empty emails, 57 duplicate-email groups, 96 extra duplicate submissions, zero empty emails, and a maximum of 10 submissions for one email.
- Subscriber normalization is `trim + lowercase`; one API item is emitted per normalized email. `subscribed_at` is the first signup `created_at_gmt`; `status_updated_at` is the latest signup `created_at_gmt`.
- Output mapping is fixed to `status=subscribed`, `consent_source=wordpress_elementor_signup`, `provider=wordpress`, and `source_site=<Connector Connection Key>`. Elementor is not exposed as the provider.
- Pagination uses an opaque resource-bound normalized-email keyset cursor. `updated_after` filters on the latest signup timestamp so a later repeat signup is re-emitted while the original first signup timestamp is preserved.
- Real HTTPS HMAC Local UAT passed end to end: the API returned exactly 422 unique subscribers across 5 pages, exactly matching the normalized WordPress authority set; the email with 10 submissions appeared exactly once. A midpoint `updated_after` check returned exactly 210 expected subscribers across 3 pages, and a future cutoff returned zero rows.
- Focused Connector Foundation, M2 Security, M3 Snapshot, and M3.1 Subscriber Snapshot harnesses passed. PHP lint passed for the complete tree, JavaScript regressions passed, and the unchanged historical Brand admin harness passed from an LF-normalized copy with its source files identical to `origin/main`.
- M3.1 adds no ERP code, mutation endpoint, provider write, database schema/version change, production deployment, tag, or release.

## M3.1A Subscriber Snapshot contract alignment evidence (2026-08-30)

- Added exactly one subscriber item field: `external_subscription_id`.
- The ID is frozen as `elementor_signup:` plus `hash('sha256', connection_key . "\n" . normalized_email)`, using the existing sanitized Connector `connection_key` and normalized lowercase-trimmed email. It is independent of submission IDs, signup repetition, dedupe aggregation, cursor pagination, and `updated_after` filtering.
- Focused subscriber snapshot coverage verifies non-empty prefixing, exact formula, same-input stability, different-site separation, dedupe/repeated-row stability, cursor/`updated_after` stability, and absence of plaintext email in the ID. No routes, mutations, DB/schema/version changes, ERP changes, or release/deploy actions are included.
- Real HTTPS HMAC Local UAT passed across 5 pages: 422/422 subscriber items had non-empty external_subscription_id, COUNT(DISTINCT external_subscription_id)=422, COUNT(DISTINCT normalized email)=422, the exact formula matched every item, and repeated plus updated_after requests preserved the same ID.

### Post-M3.1 gate

After delivery and explicit Owner merge authorization, return to ERP Marketing M1.0A for real `/sync/wpapi` Subscriber synchronization UAT. Shopify CSV and manual CSV source merging remain ERP responsibilities, not Andy Core responsibilities.
