# Cart Subtotal Reminder · Andy Commerce Settings · Dev Handoff 2026-10-13

## Owner intent
A small, occasionally edited storefront notice located directly before **Subtotal** on the Cart V2 Drawer, not a shipping threshold, promotion or transaction setting. Owner requested the edit live under **Andy Commerce → Settings**.

## New design / operating instructions (not deployed as of this document)
URL after approved Dev installation: `https://dev.beyourlover.com/wp-admin/admin.php?page=andy-commerce&tab=settings`.

Find **Cart — Subtotal Reminder**, with:
1. **Enabled** checkbox (default on);
2. **Reminder text** plain-text textarea (max 240 characters);
3. **Save Cart Reminder**;
4. **Restore default** (enables default text).
The default text is: 
> Please check shipping costs, import duties and local taxes for your country or region. Final charges depend on your delivery address.

Andy Commerce owns exactly one option `andy_commerce_cart_subtotal_notice_v1`, always autoload=false on save, no options migration or Woo policy change. WordPress `manage_woocommerce` permission and CSRF nonce needed. Text is sanitized on save and HTML-escaped on Cart. Theme reads options through the Andy Commerce class and provides the existing hardcoded wording as a fallback when plugin class unavailable. Neither discounts nor shipping fees, checkout, promotions, orders or payments are affected.

## Evidence
- [Core Draft PR #87](https://github.com/beyourlovercom/YBY-Core/pull/87) source: settings class, admin controller, view, existing Settings tab include; tests `cart-subtotal-notice-settings-harness.php` **11/11 PASS** for enable, edit, HTML cleaning, 240-character max, fallback and restore.
- [Theme Cart V2 Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80) source: `inc/cart-drawer.php` localizes Andy Commerce setting; `assets/js/cart-drawer.js` renders escaped copy or hides notice, and preserves Woo shipping and order totals. Windows JS regression **9/9 PASS** on default, custom, disabled; M5 progress **13/13 PASS**; existing Woo Cart mock regression PASS.
- Windows PHP lint for all four Core files and Theme PHP PASS. Core exact-head `ee4e08e9bb859b146e211af3f83636b41f27b780` GitHub 5/5 completed SUCCESS at time of audit; Theme exact-head workflow must be checked again before merge.

## Important state and safety
**NOT deployed to Dev yet**. Current Dev remains unchanged:
- `admin/class-andy-commerce-admin.php` SHA256 `ddb81593ca7bdd0987a97be1f8a5b8d711687a492a123c52cbd9882106f4b815`
- `admin/views/order-export.php` SHA256 `a7e0de38478bd1eafdf842f1abf3bcfd592a61cc3044a9060b749511d6f8a2f8`
- `theme/inc/cart-drawer.php` SHA256 `a3af11253263d16e9da7de3be78e93fdf03bc3e197f2a38923844f584aded443`
- `theme/assets/js/cart-drawer.js` SHA256 `267c3fe43c745e5171bfdf644ad9cd36d962e30938b8e9290d7ca6248a50a47c`
- New settings class and view are absent; WordPress option does not exist.
- Proposed Dev-only six-file package was assembled, PHP lint and tests PASS, but the official Windows BYL MCP transfer call was **blocked by OpenAI security inspection before transfer**. Do not claim the setting is editable on Dev until installed through an approved method.
- No PR merge, WWW deploy, Woo, order, payment or marketing activation occurred.
- For future authorized Dev deployment: verify Dev=staging and latest live file SHA, backup 4 existing files off-webroot, install exactly 6 scoped files (4 Core including 2 new, 2 Theme), verify WordPress admin view and localize setting, UAT both "enabled and custom notice" and "disabled", restore default, check existing Woo totals. Stop on hash mismatch, blocked tools or errors; no bypass.

## Owner UAT after deploy
Open Andy Commerce Settings, edit the wording, save and refresh Dev Cart V2. The new copy should appear before Subtotal without changing shipping or totals; toggle OFF should remove only that copy. Restore default should bring it back. The separate Terms and payment-logo footer remain unchanged.
