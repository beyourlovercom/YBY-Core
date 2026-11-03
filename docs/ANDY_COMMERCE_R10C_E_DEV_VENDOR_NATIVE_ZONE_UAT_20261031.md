# Andy Commerce R10-C-E — Dev vendor activation and dynamic Woo shipping matching

Date: 2026-10-31. SOURCE-ONLY CORRECTION, NOT YET DEPLOYED.

## Scope
Owner R10-C-D authorized Andy Commerce 1.1.0-rc1 to be installed only on dev.beyourlover.com, with global OBSERVE and Shipping/Coupon OFF. It did not approve an additional version or transaction Hook activation. PR #213 remains Draft; no WWW changes.

## Actual Dev inventory (authenticated WP-CLI, no live orders)
- Dev Andy Commerce 1.1.0-rc1 ACTIVE, global runtime observe, R10-C shipping=no and coupon=no. R10-C class NOT LOADED and both associated hooks NOT REGISTERED in this mode.
- Woo 11.2.1, YayCurrency Pro 3.3.5, WP 7.1.4, Woo base USD, prices excluding tax.
- Read-only-copied installed Windows Localnew plugin code was privately packaged, SHA256 verified and WordPress-activated on Dev without any license key transfer or license activation: WebToffee Smart Coupons Pro 3.2.0 and StoreApps WooCommerce Smart Coupons 9.51.0, both ACTIVE.
- Both vendors load in the real WP runtime: Woo coupon_is_valid gets 10 WebToffee callbacks, 12 StoreApps callbacks; coupon_get_discount_amount gets 6 and 1; coupon_applied gets 6 and 3. This is a genuine dual-vendor runtime check rather than only zip discovery.
- Existing Dev coupons: 6 fixed_cart types; shop_order post count was 0 before these installs. No existing BOGO/store-credit fixtures available for real campaign UAT.
- HTTPS GET Dev homepage, /denglu, /cart and Woo Store API cart all HTTP 200 after activating both. No checkout, payment, order or WWW modifications.
- Active WordPress plugin status is not verified commercial license entitlement for Dev. WebToffee single-site licenses may require staging-specific entitlement. No website license was moved/deactivated or bypassed.

## Critical shipping mismatch found on real Dev
R10-C originally hard-coded free_shipping for shipping zone 0, method instance 1, matching the synthetic Windows sandbox but NOT live Dev. Actual native Dev Woo (read-only WP-CLI):
- zone 0: free_shipping instance 11, min USD69, requires min_amount, ignore_discounts=no.
- zone 1: free_shipping instance 2, unconditional (no minimum; must never get false epsilon correction).
- zone 5: free_shipping instance 9, min USD69.
- zone 4: free_shipping instance 8, min USD129.
- zone 3: free_shipping instance 6, min USD169.
These are enabled methods. Zone IDs do not prove country assignments; use Woo package matching dynamically.

## Candidate correction on this branch only
File packages/andy-commerce/includes/class-andy-commerce-r10c-transaction-compatibility.php:
- Match the exact native active free_shipping instance associated with WC_Shipping_Zones::get_zone_matching_package($package), not a hard-coded default zone or instance.
- Require matching requires, ignore_discounts, enabled state. Leave no-threshold methods and unmatched instances alone.
- Keep previously approved safety conditions: OBSERVE overrides all, independent default-OFF Shipping/Coupon module flags, Owner server authorization, exact Woo/Yay/WebToffee/StoreApps versions; USD merchant basis; no tax-inclusive, gift, special class or sub-cent overrides; only equal minor units and epsilon less than 1e-9; later vendor filters may still deny.
- No change to plugin version in installed Dev, Woo shipping settings, coupon code precedence or payment/order writes.
- Offline PHP mock-Woo regression has 12/12 PASS: Dev actual method IDs 11/9/8/6, $69/$129/$169 thresholds, unconditional-free zone rejection, missing/mismatched instances and method metadata, below-threshold, special class, global OBSERVE and module OFF.
- Separate CI check added to Andy Commerce Dev ZIP Contract workflow.

## Not approved / still open
- The new source is NOT installed on Dev 1.1.0-rc1. Dev has R10-C OFF and remains safely in OBSERVE.
- Real browser native multi-zone quote regression, auto vs manual ordinary coupon precedence, BOGO, Store Credit/gift cards, product exemptions and provider licensing eligibility remain to verify.
- This branch is source-only; no 1.1.0-rc2 release, WWW update, exit from OBSERVE, main merge or R10-C module activation without another Owner pass.
