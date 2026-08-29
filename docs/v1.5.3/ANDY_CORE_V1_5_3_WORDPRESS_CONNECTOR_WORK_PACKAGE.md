# Andy Core v1.5.3 — BYL ERP WordPress Connector Work Package

Status: M1 COMPLETE / VALIDATION PASS / OWNER VISUAL UAT PENDING (M2 NOT STARTED)
Contract ID: ANDY-CORE-V1.5.3-WORDPRESS-CONNECTOR
Baseline release: Andy Core v1.5.2
Branch: `feature/andy-core-v1.5.3-wordpress-connector`
Worktree: `D:\ai\_worktrees\YBY-Core-v153-wordpress-connector`
Base HEAD: `44b71dbab8627d019d16216d057e98d2add742da`
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

The dedicated BeYourLover local site now provides the real provider stack for Connector UAT. WordPress 7.1, WooCommerce 10.9.4, AffiliateWP 2.35.0, and the local Andy Core M1 runtime are available at `https://localdev.beyourlover.com`. Provider-absent behavior remains covered by the focused harness. Owner visual UAT is still pending.

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

### M2 next step

Implement the security milestone: HMAC-SHA256 V1 request authentication, nonce/replay protection, and the secure credential lifecycle, with focused tests and local UAT before any provider mutations or external calls.
