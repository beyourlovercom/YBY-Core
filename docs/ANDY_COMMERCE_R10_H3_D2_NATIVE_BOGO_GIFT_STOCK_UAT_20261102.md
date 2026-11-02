# Andy Commerce R10-H3-D2 — Native BOGO gift quantity and stock UAT

Date: 2026-11-02. **ISOLATED WINDOWS LOCAL ONLY; NOT TRANSACTIONALLY ACCEPTED.**
Parent Draft #213 exact base `eae451da605555aec59d73491cc1e73476348193`.
Licensed plugin sources remain on Windows and are NOT committed to GitHub.

## Real vendor fixture and execution

Under owned localhost WordPress `http://127.0.0.1:14141`,
WooCommerce 11.2.1, WebToffee Smart Coupons Pro 3.2.0,
StoreApps Smart Coupons 9.51.0 and YayCurrency Pro 3.3.5
are installed. The test is
`packages/andy-commerce/tests/r10h3d2-bogo-native-disposable.php`.
It creates disposable managed-stock products: purchased unit USD60
(stock 20) and gift unit USD15 (stock 2), plus actual WebToffee
`wbte_sc_bogo` Buy 2/Get 1, repeated entitlement and full gift
discount. The coupon requires the real paid vendor source ownership
metadata `wbte_sc_bogo_created_on_sc_bogo`; without it the native
provider correctly rejects an untrusted BOGO coupon. Test fixtures
are deleted in a finally block. This is NOT a mock coupon planner.

## Results

| Assertion | Actual native observation | Result |
| --- | --- | --- |
| Coupon applied by paid provider | True, real giveaway success notice | PASS |
| Buy 2, receive 1 | Gift cart row quantity 1 | PASS |
| Repeated calculate_totals | Gift remains 1, no duplicate | PASS |
| Add four more paid units (buy 6) with gift stock 2 | Gift row quantity 3 (overshoots available stock) | **FAIL** |
| No checkout order, stock data untouched | Gift product stock still 2 | PASS |
| WooCommerce check_cart_items before checkout | Returns false, 1 error notice | PASS |

Native provider adds three gift units even though managed gift stock is
two; WooCommerce correctly rejects this overstock cart at its
`check_cart_items()` validation stage. This is a **cart/UX stock
allocation defect**, NOT evidence that three items were shipped or
that checkout accepted oversold inventory. The 6-assertion suite
correctly exits with failure (5/6 PASS).

## Further mandatory gates

- Fix or fail-close gift allocation before the cart displays an
  impossible quantity. Verify product variations, out-of-stock and
  backorders, quantity changes and manual coupon interactions.
- Native Classic/Store API checkout/order, stock decrement,
  cancelled/refund restock and gift order attribution are NOT tested.
- Test WebToffee legacy BOGO and any StoreApps gift rewards separately.
- H3-D1 StoreApps persistent Store Credit mismatch remains a separate
  BLOCKER. The H3-D2 coupon is SOURCE-ONLY UAT and cannot bypass it.
- No plugin release ZIP, Woo bootstrap import, dev/www writes, payment
  or shipment, main merge, OBSERVE exit, Shipping/Coupon activation.
