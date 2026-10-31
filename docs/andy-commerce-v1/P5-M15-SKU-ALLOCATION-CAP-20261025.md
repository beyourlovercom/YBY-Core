# M15 · SKU-level discounts + campaign usage cap (Dev source only)

Tested **2026-10-25** on the same isolated US Mac mini site `http://127.0.0.1:18973` and MySQL `aicc_commerce_p5_20261023`. Stacked on canonical **Andy Commerce Draft PR #213** at `b37d428eb211e0831f0d5e5a11eea2e0c482e18e`. **No GitHub main merge, Stable publication, live Dev plugin installation or WWW changes.** Commerce Marketing runtime remains OFF.

## Read-only allocation plan

`includes/class-andy-commerce-m15-discount-allocation.php` consumes native post-Woo-discount eligible cart lines and the **existing M10/M12** preview result. It **does not invent a separate eligibility engine** and NEVER registers Woo hooks or alters orders at plugin load.

- USD cents only, no floating point math in allocation; duplicate SKU, excluded line, unqualified BOGO/bundle, excessive promotion amount, missing product, invalid quantities and totals fail closed.
- BOGO assigns the discount **only to the reward product**, respecting verified buy/get quantities. When some reward units are not qualified, their per-unit discount remains **zero** (important for refunds).
- Bundle allocates the exact projected discount **only to specified bundle product IDs**. Largest-remainder method apportions integer cents with deterministic product-ID tie breakers; unrelated SKUs get **zero**.
- Fixed Spend & Save and Tiered allocate a single total projection across eligible items by post-native-discount price. Each product line has an explicit per-unit USD-cent discount vector for later partial-refund attribution.
- The output includes product IDs, quantities, discounted quantities, original native net lines, line-specific savings, remaining lines and explicit `checkout_mutations=0`. No Woo plugin hook or SKU price mutator is shipped/activated by this class.

Unit harness `marketing-m15-sku-allocation-harness.php`: **27 assertions PASS**, including 1-cent stable rounding, BOGO partially qualified get quantities, duplicate/missing/excluded SKU and excessive discount refusal.

## Actual Woo/HPOS line-item evidence

The isolated sandbox driver `tests/sandbox/m15-native-product-allocation-uat.php` loads the allocator explicitly with opt-in `AICC_M15_LOCAL_ORDER_WRITE=1`, exact localhost URL, exact sandbox DB, `local` environment, Woo HPOS ON, M14 OFF, USD-only and WordPress external HTTP blocked.

From actual Woo cart 2× USD40 product #27, USD45 product #28 and USD60 product #29, the driver obtains M12/M10 projection, computes M15 allocation and deliberately uses a **temporary native Woo product-scoped virtual coupon + per-line filter** on the isolated sandbox. It then persists genuine HPOS WC orders, reading each native line back:

| Case | Local HPOS order | Projected discount | Woo SKU discount attribution | CA tax | Total |
|---|---:|---:|---|---:|---:|
| BOGO | #58 | USD22.50 | #28 = USD22.50; #27/#29 = USD0 | USD13.80 | USD186.30 |
| Bundle | #59 | USD23.00 | #27 = USD13.14, #29 = USD9.86; #28 = USD0 | USD13.76 | USD185.76 |

No external transaction ID, gateway payment, mail, supplier/ERP event or live stock write occurred. Temporary Woo filters removed after each scenario. This proves **native Woo line-level reconciliation for these exact single-country fixtures**, not all purchase/return combinations or live promotions.

## Concurrency-safe usage cap ledger

`includes/class-andy-commerce-m15-cap-ledger.php`: source-only, not invoked by Commerce runtime. It defines **explicit opt-in** InnoDB schemas for per-campaign quotas and unique claim tokens. There is **no auto-migration** or activation hook.

- `reserve(campaign, token, immutable_limit)` holds a MySQL campaign-level `GET_LOCK` and an InnoDB transaction to atomically check `used+held < max`. Duplicate token returns its existing reservation and cannot double count; changing cap unexpectedly fails closed.
- `commit(campaign, token, persisted_woo_order_id)` consumes one held place **only after an actual order is persisted**; repeated commit same order is idempotent, different order is rejected.
- `release(campaign,token)` frees an abandoned held place only, never decrements completed sales. Once a token is released it cannot be reused.
- Limits are immutable per campaign version; **campaign editing, token expiry/reclamation, per-customer usage enforcement and actual Woo order-hook lifecycle are NOT implemented**. Expired reservations remain held until an explicitly authorized release; safer than overselling but not a full production release gate.

**Real isolated MySQL test:** `tests/sandbox/m15-cap-ledger-local-uat.php` initialized tables only on the disposable sandbox and exercised reservation/commit/release/cap mismatch/retry invariants. 12 concurrent WP-CLI processes attempted a max-3 campaign: **3 accepted / 9 rejected**, with `used=0, held=3`. Separate single-cycle test: `max=2, used=1, held=0` after commit and release. Real SQL advisory locks + DB transactions, not only mocks.

## Next gates before genuine P5 full promotion runtime

1. Bind cap reservations atomically to **persisted campaign revisions** and Checkout Blocks/Classic durable Woo order ID, release on checkout failure, address abandoned reservations safely, and prevent reused customer/device token and campaign stacking.
2. Validate native SKU-specific allocation for BOGO with multiple reward units, discounts that include tax or rounding, item-level refund/partial cancellation and per-product coupon limits; verify all five Woo shipping zones.
3. Add configured campaign selection, user limits and stock race safeguards. Implement migration/rollback and audit under Dev-only opt-in, with exact-head UAT before activation.
4. P6 external Stripe/WooPayments/PayPal test-mode credentials, isolated callbacks, 3DS/decline, refund and FX snapshots remain **NOT PROVEN**. Do not use live gateway credentials or assert payment PASS.

All M15 real-DB and Woo-writing drivers belong to `packages/andy-commerce/tests/sandbox/`, never the installable Commerce ZIP. #208 Dev Subscriber security HTTP403 passed, but user account cleanup is blocked by its 88-post association; do not delete or reassign it.

## Additional durable-order identity gate

The claim schema also defines a UNIQUE index on (campaign_id,order_id); reserved and released claims have NULL order_id, allowing multiple reservations while preventing two independently minted tokens from redeeming the same Woo order for the same campaign. The isolated MySQL database was explicitly migrated to this index. No live migration is present. The sandbox-only m15-one-order-one-cap-uat.php test attempted to commit a second token against the same order ID and confirmed the unique constraint rejected the write, the whole transaction rolled back, used stayed at 1, and the second reservation could be released without leakage.
