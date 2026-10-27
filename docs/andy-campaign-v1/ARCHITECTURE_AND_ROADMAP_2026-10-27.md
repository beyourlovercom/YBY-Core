# Andy Campaign V1.0 — code-first architecture and phased acceptance

Date: 2026-10-27. Status: **C0 architecture frozen; C1/C2 scaffold in Draft PR; not installed/deployed**.

## Owner-frozen ownership boundary
- **Andy Core** alone owns WordPress `landing_page` CPT, native list/edit/new controllers, Content L2/L3 navigation and common admin shell. No CPT migrations, page copies, new page editor, duplicate menus or SEO reset.
- **Andy Commerce** owns Currency, payment, checkout and transaction-adjacent shipping integration. Existing Commerce Marketing V1 Dev UAT (Draft PR #213) stays OFF and is not implicitly migrated.
- **Andy Campaign** is a distinct, minimal Woo-oriented plugin. It registers reviewed code-first campaign packs, manages campaign switches and UTC windows, logs administrative transitions, links only existing Landing Page IDs, and gates conflicting resource groups.
- **Campaign Packs** independently ship PHP business rules, optional shopper UI/shortcodes and tests. Their callbacks attach WooCommerce hooks only through the host's approved boot contract. Installing a pack does not by itself enable a promotion.

## User-facing menu
- Core existing **内容 → 全部落地页 / 新建落地页** is unchanged.
- Core existing **营销** L2 goes to Andy Campaign when it is available, otherwise falls back to original Andy Commerce Marketing. Campaign's L3 contains 活动列表 / 执行记录 / 系统设置.
- No new top-level WordPress navigation; the original Commerce routes remain accessible and its legacy tests stay OFF.
- The campaign's Landing Page ID fields accept only existing `landing_page` IDs. Editing uses get_edit_post_link(), with the native Andy Core editor.

## MVP implementation
- Files: packages/andy-campaign, isolated source from main. WordPress plugin header and dependency checks.
- Registration: `do_action('andy_campaign_register', $host)`, `$host->register(['id','name','version','resources','boot'])`; packages require source-reviewed installation. The host runs selected boot callbacks on WP init.
- Default state: GLOBAL OFF / each pack OFF; schedule UTC inclusive start, exclusive end; collision groups cart-discount/gift/shipping/coupon/checkout-display; deterministic fail closed.
- Legacy defensive gate: refuse execution if Andy Commerce Marketing Dev runtime or P1 activation setting says ON.
- Admin: capability `manage_woocommerce` to view and `manage_options` to modify; nonce + POST, bounded no-PII event history, no PHP editor or remote code installation.
- Only explicit page associations, no writes to post, postmeta, WP pages or Core CPT.
- No shopping frontend scripts shipped in Lite core.

## Honest technical limitations
- WordPress plugins share the PHP process. A malicious pack or one which adds hooks before the host callback can bypass the host kill switch; reviewed source is mandatory.
- Resource declarations offer a coarse collision gate, **not complete semantic proof** against other non-Campaign discounts or independent WooCommerce plugins.
- No full event-level order or payment audit in this MVP. Admin log only records operator changes; transactional correctness must be shown separately.
- WP admin switches are not a substitute for safety rollout, rollback package or checkout tests. Page caches/long-lived sessions need specific operational checks on scheduled changes.
- M15 cap ledger, M16 order reconciliation and SKU allocation from V1 are candidates for an audited later shared-services extraction, **not** live-wired here. Avoid a second live promotion calculation path.
- No live production activation or real payment, coupons, inventory, customer or order writes under this branch.

## Roadmap and gates
- C0 — freeze boundary in root AGENTS.md and this spec; preserve legacy Commerce UAT OFF.
- C1 — plugin bootstrap, explicit registration contract, global/per-pack switches and UTC schedule; PHP lint + harness.
- C2 — Core-owned L2 routing, readonly Landing Page links, bounded admin events, collision gate and sample pack; PHP/WP CI.
- C3 — isolated WordPress installation and OFF→ON→OFF operator UAT using the demo *display-only* pack, plus role negatives and existing Landing Page readback.
- C4 — first genuine Black Friday independent pack, isolated Woo cart/Classic/Blocks/refund/FX/tax/shipping tests and rollback.
- C5 — independently approved Dev integration and later production release gates. No main/Stable/WWW release is authorized by this architecture PR.

Acceptance: new Campaign Pack never edits Core's `landing_page` records, can independently register/enable/disable, and doesn't require modifying Andy Commerce. Production price/checkout claims remain unproven until C4/C5.
