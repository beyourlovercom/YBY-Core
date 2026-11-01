# Andy Commerce R10-H2 — Manual coupon priority, SOURCE-only design and sandbox verification

Date: 2026-11-01. **NOT DEPLOYED / NOT RUNTIME-REGISTERED.** Owner requested independent H2 source design and sandbox tests. This does NOT authorize choosing a live coupon policy, Dev/www plugin installation, OBSERVE exit, Coupon ON, Woo session/coupon writes, order creation, payment, or main merge.

## Baseline and original failure
Draft integrated PR #213 HEAD before H2 = `fb3e40ff20a80c6e84e95cda8e6b29c0724c35c7`. Dev Andy Commerce 1.1.0-rc2 ACTIVE, global OBSERVE, R10-C Shipping=no/Coupon=no. The existing R10-C `woocommerce_coupon_is_valid` filter implements FIRST positive ordinary coupon applied wins; `woocommerce_coupon_is_valid` does NOT identify provenance. Existing native Woo 11.2.1, YayCurrency Pro 3.3.5, WebToffee Smart Coupons Pro 3.2.0 and StoreApps WooCommerce Smart Coupons 9.51.0 are an exact Dev stack but neither provider's auto coupon/credit/BOGO callback contract is certified end-to-end here.

**Critical native order from installed WooCommerce 11.2.1 `WC_Cart::apply_coupon()`:** normalize coupon, construct `WC_Coupon`, call `is_valid()` FIRST; check already applied; if individual use remove other coupons or reject based on `woocommerce_apply_individual_use_coupon` / `woocommerce_apply_with_individual_use_coupon`; append applied coupon; trigger `woocommerce_applied_coupon` LAST. `WC_Cart::remove_coupon()` changes cart/session and fires `woocommerce_removed_coupon`. Therefore simply changing a post-apply hook or optimistically removing a previous coupon in `coupon_is_valid` is not an atomic override and might bypass vendor validity or silently remove a valid entitlement.

## Candidate design (not coupled into plugin boot)
Standalone file `packages/andy-commerce/includes/class-andy-commerce-r10h2-manual-priority-shadow.php` exports only a **pure decision planner**:
- `plan(incoming, applied_snapshot, prior_vendor_valid)` returns `action` = `keep` / `deny` / `replace_auto`, `remove_codes`, `expected_snapshot` and `writes_performed=0`. Importing the class itself registers **no Woo Hook**. Nothing invokes the class from `andy-commerce.php` or the R10-C compatibility callback.
- Input coupon type must be known `percent`, `fixed_cart` or `fixed_product` with strictly parsed positive amount. A *verified* native free-shipping entitlement, a verified nonstandard Store Credit, BOGO or gift, and zero-value ordinary discount are protected from removal. Unknown/malformed coupon, unverified provider classification, duplicated code, or an existing two-ordinary-coupon violation fail closed.
- **Provenance is explicit and server-issued**, never inferred from coupon naming, current applied order, browser query parameter or free-form customer metadata. Incoming `manual` requires `origin_verified=true` and trusted `trusted_server_customer_submission`; existing `auto` requires `origin_verified=true`, `trusted_vendor_auto_apply` and an allowed source identifier (Woo/WebToffee/StoreApps). These are a *data contract*; source-only fixtures can simulate them. There is currently **no live integration to issue that evidence**. An outside client must not be allowed to manufacture these flags.
- Existing vendor rejection (`prior_vendor_valid=false`) is always preserved. Unknown provenance cannot trigger removal. Manual-first blocks a later auto; same code is a no-op; a confirmed manual submission arriving after an already-applied confirmed auto can produce a **prospective** `replace_auto` plan for that one auto code. Other auto against auto or manual against manual are denied.
- `snapshot_matches` supplies an ordered SHA256 optimistic concurrency check before any future adapter attempts removal. The checksum is not a secret or a user authorization token. The simulator demonstrates restoring the **exact prior snapshot** if a new manual coupon fails to apply, preservation of other entitlements, rejecting a concurrently changed snapshot and avoiding endless auto reapply.
- This is NOT an implementation of atomic Woo writes. Future approved adapter must also prevent late vendor auto reapplication, validate real automatic/manual provenance on Classic cart and Store API, ensure notices are correct, preserve auto-offer semantics and reconcile on checkout. No post-veto acceptance or premature `remove_coupon` is allowed. No `wp_options`, Woo session or cart write exists in this H2 candidate.

## Behavior matrix
| Cart before | Incoming | Candidate plan | Why |
| --- | --- | --- | --- |
| Empty | Verified manual or auto ordinary | Keep | First ordinary reward |
| Verified auto A | Verified manual B | Replace auto A (shadow plan) | Manual preference |
| Verified manual A | Auto B or manual B | Deny | Single ordinary discount |
| Ordinary A | Same code A | Keep | Idempotent |
| Auto A + native free shipping + verified credit + BOGO + gift | Manual B | Replace **only** auto A | Protected entitlements remain |
| Existing normal coupon unverified/unknown provenance | Manual B | Deny | Cannot authenticate automatic origin |
| Existing multiple ordinary coupons | Any normal coupon | Deny | Invalid cart snapshot |
| Any | Vendor/Woo validation false | Deny | No vendor veto bypass |
| Auto A | Manual B, snapshot changed before commit | Reject | Stale cart, no writes |
| Auto A | Manual B, apply later fails in simulation | Roll back | Exact original coupon metadata/order |

## Sandboxes and required follow-up gates
1. PHP deterministic H2 planner/transaction simulation: **100/100 PASS**. Simulated rollback is *not a real Woo rollback proof*. No WordPress startup, no actual cart mutation.
2. GitHub disposable WordPress/WooCommerce 11.2.1 + MySQL: `r10h2-native-woo-coupon-disposable.php` uses unsaved native `WC_Coupon` for percent/fixed-cart/fixed-product/free-ship metadata; checks H2 plan and confirms H2 Woo hooks absent. Exact local CI database `commerce_p5_isolated`, `http://commerce-p5.invalid`, external HTTP disabled; no Woo coupon applied or order created by H2. Full job also exercises existing Woo regressions. Exact-head CI result must be recorded after GitHub run.
3. Existing paid WebToffee/StoreApps binaries are **not bundled in GitHub CI**. No certification of actual dual-vendor automatic coupon provenance, BOGO, Store Credit, individual-use flag collisions, multi-currency voucher values or checkout provider retries yet.
4. Future R10-H3 Owner gate: confirm A/B policy (Owner can approve B based on this plan), acquire documented provider callback provenance mapping, design audited server-side origin ledger with race controls, implement a separate default-OFF and independently authorized adapter, run **real exact licensed vendor** Woo Classic/Blocks with five currencies and refund/cart cases in a disposable environment, prove session rollback, no tax/discount miscalculation and recoverability. Then submit *separate* Dev plugin version change + Dev installation approval; actual Woo transaction Hook ON and www installation are independently authorized.
5. R10-G2 legacy runtime quarantine remains intact; never exit global OBSERVE merely to test H2.

## Release boundaries / safety
CI/ZIP contract must verify this source file is included and byte-identical, **but NOT imported by `andy-commerce.php` or any registered runtime**. No version change from existing 1.1.0-rc2 in source, no installable release approval. Continue to keep PR #213 Draft; Issue #276 OPEN until real vendor compatibility and approved policy.
