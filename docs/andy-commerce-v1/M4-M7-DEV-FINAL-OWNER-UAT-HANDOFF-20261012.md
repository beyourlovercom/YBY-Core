# Andy Commerce Marketing M4–M7 · Dev Final Owner UAT Handoff · 2026-10-12

## Decision scope
**Owner M3 list Visual PASS and M4/M5 synthetic shadow lab Visual PASS are retained.** This handoff is **NOT** a declaration that M7 *real Woo Cart* UAT has passed. A final hands-on Dev test is still necessary. All Marketing campaigns/BOGO/gifts remain OFF; no Production/WWW, payment, order, PR merge, release/tag or READY restart authorized.

## Verified current state (Dev)
- Dev host `dev.beyourlover.com`, WP staging, Andy Commerce 1.0.1.
- Effective free-shipping settings source is **`byl-platform-legacy`**, not the dormant `andy-commerce` option. Effective thresholds are **Zone0=$49, Zone1=$0, Zone5=$69, Zone4=$129, Zone3=$169**. The dormant Commerce option global threshold $69 does **not** replace effective Zone0 $49.
- M4 V2 Dev-only opt-in code is installed. The 6-hour expiring flag was extended only for bounded UAT; the flag fingerprints **both** source options, and no old source/settings were overwritten. A separate WordPress request established **one** Woo eligibility availability hook: legacy false, M4 V2 priority 10. All five zone arithmetic checks PASS; post-discount `line_total` excludes tax/shipping, flagged automated gifts excluded.
- M5 Cart Store API read-only extension is actually registered: `extensions.andy_commerce_marketing_v1`. An empty guest Woo cart returns `show_progress=false, state=hidden`, and no false free-shipping award.
- M5 Cart V2 Dev-only deployed exactly `inc/cart-drawer.php`, `assets/js/cart-drawer.js`, `assets/css/cart-drawer.css`; retained live Dev Notification N6 Cart bridge, existing coupon and quantity failure recovery. Post-deploy homepage/Cart opt-in/Checkout **GET** all HTTP 200. No active marketing campaign.
- M3 DB read-only check: 3 drafts + 1 archived, **all active=0**.
- Windows independent isolated real browser CDP viewport captures (NOT 500px headless crop): M390 `width=390, scrollWidth=390, drawerOpen=true`; PC1440 `width=1440, scrollWidth=1440, drawerOpen=true`. Both had marketing slots hidden and M5 progress hidden in **empty cart**. Browser rendered with no horizontal overflow. Offline PHP/Node mocks and targeted Cart regression PASS.
- **Remote safety check blocked the proposed isolated temporary Woo add-item POST BEFORE it executed.** No such cart mutation was performed by the assistant. Thus *nonempty customer cart + chosen Woo rate + server progress* still requires direct Owner UAT; do not infer it from the synthetic harness.

## Required Owner Final UAT — do not place an order
1. Open [Dev Cart Drawer V2](https://dev.beyourlover.com/?byl_cart_drawer_preview=1) on phone or desktop. Empty state must show **no free-shipping progress bar** and no gift/promotion.
2. Click **Add to cart** for a normal in-stock simple product on Dev. The Cart must display correct Woo product/quantity/subtotal. Do **not** click the Checkout/Place Order or payment submit.
3. With a valid shipping address and actual selected shipping rate, M5 may show a **green informational threshold progress**. It must use the effective zone threshold **$0/$49/$69/$129/$169** and Woo **discounted product subtotal**; it must never claim shipping was awarded unless Woo separately confirms the selected free-shipping rate. If address or selected rate is incomplete, **M5 progress must stay hidden** (this is fail-closed and not a failure).
4. Remove that Dev test product. Cart must return to empty, with no progress/gift/marketing. Ensure on **M390** and **PC1440** the Drawer does not overflow horizontally and coupon, total and checkout link align. Existing native coupon limit remains one; use only an existing approved Dev test coupon if available; do not create a real offer.
5. Reply **M7 Final UAT PASS** only if these checks work, or give a screenshot + the symptom/zone/address used if FAIL. M3 and M4/M5 visual previews do not need to be retested.

## Engineering / integration caveats
- **No new PR merge authorization.** [YBY-Core Draft PR #87](https://github.com/beyourlovercom/YBY-Core/pull/87) includes M3 source plus M4/M5 code, must reconcile with independent Core PRs #80/#81/#82 before any main merge. [Theme Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80) includes Theme T4/Cart V2 source, must reconcile with independent Theme PR #63/#79 and Notification bridge before merge. Do not shortcut merges.
- Windows local 40/40 M4 and 17/17 M5 mock, adapter 23/23, M45 Lab 35/35 plus Theme Cart M5 13/13, original Cart runtime regression PASS. Core 5/5 GitHub CI SUCCESS at code HEAD `894c537b12c8a9079d032ffdb5704844b16fc3f0`. The latest Theme Draft head may not have a corresponding GitHub workflow check yet; require **exact-head** successful Foundation CI before future merge.
- Scoped recovery pointers: Dev original Commerce runtime at `/home/u247343654/domains/dev.beyourlover.com/.m4v2-rollback-20261012/`; original Theme 3 files at `/home/u247343654/domains/dev.beyourlover.com/.m5-cart3-backup-20261012/`. No live v1 shipping policy source modified.
- M4 Dev flag expires automatically in ~6 hours from renewal (do not promise it remains on next day). Retest and renew only under existing authorized Dev-only gate if needed. After expiry old Woo pre-discount policy remains authoritative.
- Do not ship to WWW, enable promotions, create real orders/payments, or merge PRs based on this UAT alone. M8 separately gated.

## Roadmap state
M3 Owner Visual PASS; M4 V2 Dev Canary runtime gate and five-zone nonorder checks PASS; M5 Dev runtime/Store API + real M390/PC1440 empty Cart PASS; M6 offline integration PASS; **M7 Owner real-cart Final UAT PENDING**; M8 main merge/WWW/marketing activation HOLD.
