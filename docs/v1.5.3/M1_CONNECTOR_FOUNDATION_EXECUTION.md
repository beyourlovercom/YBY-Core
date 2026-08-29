# Andy Core v1.5.3 — M1 Connector Foundation

Status: M1 COMPLETE / VALIDATION PASS / OWNER VISUAL UAT PENDING
Worktree: `D:\ai\_worktrees\YBY-Core-v153-wordpress-connector`
Branch: `feature/andy-core-v1.5.3-wordpress-connector`
Base: `44b71dbab8627d019d16216d057e98d2add742da`

## Authority

Read and obey repository `AGENTS.md` and `ANDY_CORE_V1_5_3_WORDPRESS_CONNECTOR_WORK_PACKAGE.md` first.
The Owner-approved v1.5.3 contract is additive to released v1.5.2. Existing Inquiry, ownership/isolation, shortcodes, Sticky CTA / Global Dock, Settings, frontend output and DB data must not regress.
Do not modify the ERP repository. Do not merge, deploy, tag, publish or release.

## M1 scope

Implement only the bounded Connector foundation using the repository's existing admin/module conventions:
- add `Andy Core -> Settings -> WP-API`, tab `wp-api`, capability `manage_options`; do not register a standalone submenu;
- page header/content uses concise Chinese admin copy while preserving `WP-API` and `Andy Core v1.5.3` technical identity;
- add Connector Enabled setting, Site URL read-only, Connection Key, Contract Version `1` read-only, Key ID;
- add four live status cards: ERP Connector, WordPress, WooCommerce, AffiliateWP;
- provider detection must be graceful when WooCommerce or AffiliateWP is absent;
- display only real WordPress/provider version/state, never fabricated counts;
- add the exact ten-row contract endpoint table shell with truthful canonical status derived from connector configuration/provider presence;
- use existing Andy Core visual language rather than raw WordPress form-table UI.
## M1 boundaries

Do not implement HMAC signing, replay/nonce, idempotency storage, Affiliate provisioning, Coupon mutation, payout mutation, or production network calls in M1. Those are later milestones.
Do not store or render a Shared Secret yet; the UI may clearly state that HMAC credentials are configured in the next security milestone.
Do not bump database schema unless M1 has a proven need; prefer normal WordPress options for foundation settings.
Do not change released v1.5.2 behavior or existing menu slugs.

## Settings behavior

Use a dedicated Connector option namespace. Save must require `manage_options` + nonce, sanitize values, and must not overwrite unrelated Andy Core options.
Connection Key and Key ID must have conservative safe-format validation; blank is allowed while Not Configured.
Status vocabulary must be truthful: Connector Disabled, Configuration Error, Provider Missing, and Ready. M1 may report local Foundation Ready only in self-check evidence; it must not claim an ERP network connection or Connected UI state.
`Test Connection` in M1 is a local self-check only and must not call ERP or any external endpoint.

## Tests / evidence

Add focused tests/harnesses for menu registration, capability, independent settings, sanitization, provider-present/provider-absent detection, truthful self-check/status, endpoint availability, and no secret field/storage in M1.
Run relevant existing v1.5.2 regression tests, PHP syntax checks used by the repo, `git diff --check`, and secret scan of changed files.
Update the Work Package status/evidence for M1.
Commit M1 on the feature branch only after tests pass. Suggested commit: `feat: add v1.5.3 connector foundation`.
Stop after M1; report files changed, tests, commit SHA, known limitations and exact next milestone M2.

## Final M1 validation evidence

- Focused `tests/connector-foundation-harness.php`: PASS.
- Existing PHP harness sweep: PASS with Windows-equivalent accommodations: Google auth passed with Local PHP 8.2 + bundled OpenSSL config; Brand admin source contract passed from LF-normalized temporary copy, while the native CRLF checkout false-negative was confirmed line-ending-only and both referenced source blobs exactly match base `44b71db`.
- JavaScript harnesses: 5/5 PASS.
- PHP lint: 85 files checked, 0 failures.
- `git diff --check`: PASS.
- Changed-file credential scan: PASS, no credential-pattern hits.
- M1 boundary scan: no REST route registration, HMAC, external network call, shared-secret storage, replay/idempotency storage, or DB schema change in Connector files.
- Local WordPress runtime smoke: Connector/Core/Admin classes load on WordPress 7.1, WP-API Settings tab renders under the existing Settings submenu with `manage_options`, Connector defaults to `Connector Disabled`, exactly 10 contract rows render as unavailable.
- Full-provider HTTPS runtime smoke at `https://localdev.beyourlover.com`: WordPress 7.1, WooCommerce 10.9.4, AffiliateWP 2.35.0 and Andy Core M1 loaded successfully; provider cards report `Ready`, exactly ten contract rows are present, and every row remains unavailable while Connector is disabled. Andy Core was then activated on this local UAT site so the WP-API page is available for Owner visual UAT.
- Release metadata remains 1.5.2 / DB 1.3.0; no merge, deploy, tag, publish, release, or ERP repository change occurred.
