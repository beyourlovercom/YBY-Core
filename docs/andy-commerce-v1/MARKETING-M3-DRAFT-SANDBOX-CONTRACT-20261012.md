# Andy Commerce Marketing OS — M3 Draft Campaign Contract (OFFLINE ONLY)
Date: 2026-10-12
Status: M3 code-only synthetic schema and in-memory workflow. **NO WordPress storage, admin form, Woo runtime hook, Dev deployment or promotion activation.**

## 0. Source of truth and dependencies
- Canonical source: `beyourlovercom/YBY-Core/packages/andy-commerce/` with GitHub `main` the **sole future target**.
- BYL Platform is retired. Do not revive or add a new promotion plugin.
- The real Dev plugin is Andy Commerce v1.0.1, while its consolidation source [PR #67](https://github.com/beyourlovercom/YBY-Core/pull/67) remains unmerged. Marketing admin [Draft PR #77](https://github.com/beyourlovercom/YBY-Core/pull/77) is M1 read-only UI and still awaits Owner design UAT. [Draft PR #78](https://github.com/beyourlovercom/YBY-Core/pull/78) is a separate pure M2 evaluator with CI PASS; no runtime integration.
- This M3 branch starts independently from main to avoid modifying unapproved M1/M2 HEADs. Before integrating any changes into the real Andy Commerce plugin, reconcile PR #67, #77, #78, main exact HEAD and dependencies. **No implicit merge.**

## 1. Owner-approved business model
- Marketing is the operator UI to configure promotions; Andy Commerce is the **only** discount and eligibility engine; WooCommerce owns canonical cart, shipping, coupon, tax and order values. ERP Marketing may plan and report, but not duplicate web discount rules.
- Default monetary eligibility: Woo post-discount merchandise line totals, **excluding shipping and all taxes/fees**. Exclude reward/gift self-qualification. Use currency minor units and Woo tax/rounding contract before any runtime activation.
- Existing BYL49 pre-discount policy is still active and remains **unchanged** until a separately authorized M4 transition; never show a free-shipping progress bar using a fictional new threshold.
- Each future campaign is off by default, cannot self-publish and requires independent owner approval and safe activation. Existing single manual coupon constraint; automatic offers do not stack by default unless a separately approved compatibility matrix permits it.

## 2. M3 schema & in-memory sandbox
Implemented by `includes/class-andy-commerce-marketing-draft-sandbox.php` and tested by `tests/marketing-m3-draft-sandbox-harness.php`.
- Explicit `sandbox=true`, synthetic `manage_woocommerce_checked=true`, `nonce_checked=true`, actor ID and deterministic clock are required for mock mutations. **These are offline test inputs, NOT actual authentication**; the future WordPress controller MUST call `current_user_can('manage_woocommerce')`, verify nonce, perform server-side authorization on each edit, and enforce a real locking boundary.
- Always `schema_version=1`, `status='draft'`, `active=false`; the normalized model also marks `runtime_enabled=false` and `requires_publish_owner_gate=true`. No paths to `scheduled`, `active`, `published` or plugin hooks exist.
- Strict allowlists, bounded strings/IDs/quantities/country list/UTC timestamps, unique coupon/BOGO selectors, one bundle price OR discount percentage, ascending tiers, gift reward item ID and linked existing Woo `free_shipping:N` method ID (syntactic reference only; no Woo method check performed).
- The rule types: `bogo`, `bundle`, `spend_save`, `tiered`, `free_gift`, `free_shipping`. Only BOGO/bundle/spend-save/tiered have synthetic calculations in M2. Gift and shipping are draft contract templates **only**.
- In-memory `create` / `revise` / `archive` operations with immutable campaign ID and type, optimistic revision 1→2→3, fail-closed stale revision, immutable archived item, and bounded audit event metadata (actor ID, operation, campaign ID, version, synthetic timestamp). No activation/publication.
- **Nothing persists** beyond the isolated PHP process. No WordPress database table, wp_options value, filesystem cache, session, network or external connector is touched. Class is deliberately **not loaded by** `andy-commerce.php` or `class-andy-commerce.php`.
- All operations are reversible simply by discarding the isolated test process; this is NOT a deployed admin module or finished CRUD.

## 3. Future persistence design (requires separate approval)
Proposed only; **not implemented**:
- Andy Commerce owns one versioned campaign table (ID, schema version, status/draft, revision, timestamps, actor ID, payload hash) plus append-only audit table. Strong uniqueness + revision compare-and-swap in a transaction.
- Read-only preview and independently authorized draft create/edit/archive endpoints. Every endpoint enforces WP capabilities + nonce + target site ownership, sanitized input, auditable writes, conflict responses, request size and quotas. Nothing publishes via Save.
- Validate the real Woo product IDs/variants, stock, shipping zone method and currency prior to approval; sandbox schema only checks structure.
- Do not create migrations, WordPress options, HTTP handlers, admin write forms or active Woo hook on this branch. Dev-only deployment and schema change require exact-head CI, verified backup, Dev/WWW DB isolation and **explicit Owner approval**.
- Separate publish pipeline must require Owner activation authorization, valid start/end, rollback and Woo-side idempotent award/discount computation.
- M3 contract remains independent of Cart Drawer PR #63. Marketing Slots and Free Gift on Cart stay OFF.

## 4. Testing and roadmap
- Offline harness: six OFF templates, strict UTC calendar validation, duplicate field rejection, country/price/quantity bounds, bad selectors/overlap, user/nonce/sandbox gates, revisions/conflicts/archive/audit, bootstrap runtime isolation.
- CI: `.github/workflows/core-regression.yml` automatically runs `packages/andy-commerce/tests/*-harness.php`. Preserve Foundation/Shipping/HPOS/Newsletter/Inquiry test suites.
- **M3 current milestone:** schema and in-memory sandbox + automated tests. **M3 persistent draft management is NOT complete** until DB schema, admin form and controlled Dev Owner UAT are separately authorized and implemented.
- **M4:** BYL49/old pre-discount vs approved post-discount excluding tax/shipping policy compatibility and separately authorized migration.
- **M5:** read-only Store API promotion progress bars for Cart and Checkout only from active Woo-confirmed policy, gifts OFF until separate policy approval.
- **M6–M8:** synthetic Canary, Owner iPhone/desktop UAT, PR merge and WWW activation all independent gates.
