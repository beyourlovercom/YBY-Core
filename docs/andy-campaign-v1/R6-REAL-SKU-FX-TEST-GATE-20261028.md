# R6 Black Friday 2026 — real Dev catalogue, FX boundary and TEST gateway gate
Date: 2026-10-28. Only `dev.beyourlover.com` read-only audit / safe-source tests. **No WWW, paid order or production launch permission.**

## Exact runtime/source and constraints
- Dev: Core v2.2.11, Commerce v1.0.12, Woo 11.2.1, Andy Campaign + Black Friday independent plugins v0.1.0 activated but **marketing master OFF**, Commerce legacy switches `no/no`. Dev `/gift` WP page ID `40051` is a **draft**, not a public landing page.
- Dev Black Friday gift catalog remains **empty** (`BYL_BF2026_Gifts::ready()=false`). No gift SKU selected or configured; the gift picker remains fail-closed.
- Classic Checkout on Dev, HPOS enabled. Currency R13 canary only authorizes its **Blocks TEST flow**, not Classic foreign-card charges. Real guest Store API previously returned USD $25.00, EUR €22.37, GBP £18.73, AUD A$35.60, CAD C$35.02 for one cart item; these are **price/cart session tests**, not payments.
- An attempted automated Dev product/coupon **creation** was blocked by platform safety controls. It was not retried by a workaround. No new products/coupons/orders/refunds were created in this continuation.

## Read-only existing SKU / gift pool inventory
These are current Dev product records, **not approved promotions**:

| ID | SKU | Public-product name | Woo price (USD) | Stock and status | Decision |
|---|---|---|---:|---|---|
| `21954` | `chargerc` | BeYourLover USB-A to USB-C cable | 9.90 | Instock, quantity not tracked | Candidate only; check compatibility and available logistics |
| `17095` | `00W07` | COKELIFE lubricant 18 ml | 15.99 | Instock, quantity not tracked | Candidate only; country/shipping restrictions |
| `30454` | `BY489A-Lubes` | BYL lubricant 35 ml | 19.90 | Instock, quantity not tracked | Candidate only; country/shipping restrictions |
| `37763` | `414sleeve` | Skull Man silicone sleeve | 20.00 | Instock, quantity not tracked | Candidate only; product compatibility |
| `40029` | `AICC-UAT-MKT-GIFT` | UAT gift, DO NOT FULFILL | 15.00 | Managed stock 20; hidden | **TEST FIXTURE ONLY** |
| `40027` | `AICC-UAT-MKT-REWARD` | UAT reward, DO NOT FULFILL | 45.00 | Managed stock 40; hidden | **TEST FIXTURE ONLY** |

Other misleading low-price records (e.g. fee item `GIFT-SELECTION`, hidden `Sticker Design`) were deliberately excluded. **Woo “instock” with untracked quantity is not proof of available ERP/warehouse inventory.** Require owner confirmation of ≥2 free product choices, costs, country reachability, inventory reservation, and premium upcharge before any reviewed PHP gift catalog is deployed and before master ON.

## Current Dev FX snapshot verified read-only
`Andy_Commerce_Currency_Runtime_Dev_V1::rates()` returned a valid fresh USD snapshot during the audit (age 6,438 s, max 72 h):
| Currency | USD→local rate in micros |
|---|---:|
| USD | 1,000,000 |
| EUR | 894,610 |
| GBP | 749,160 |
| AUD | 1,423,900 |
| CAD | 1,400,900 |

**Critical rounding counterexample:** WooCommerce conversion of **USD198.99** and **USD199.00** with current GBP `0.749160` both rounds to **GBP149.08**. Thus reversing post-coupon local minor units can falsely grant 2 gifts to a USD198.99 basket or deny a USD199 basket. Local presentation money is *not* an authoritative USD entitlement ledger.

A candidate inverse-FX algorithm was explored **in source only** and failed this safety criterion. It was **reverted before Dev deployment**. Black Friday pack remains **USD-only fail-closed** for new currency sessions. The regression harness now explicitly demonstrates the live GBP threshold collision (`BYL_BF2026_PURE_CONTRACT_PASS=27`). Prior Dev deployed BF two-file safe OFF draft-preview remains unchanged.

### Required integration design before foreign Black Friday launch
1. Commerce must expose a **trusted, exact, post-coupon, tax-excluded USD merchandise basis** for a given cart, with product-level sale/quantity, multi-coupon allocation, any tax-inclusive pricing, reference rate id, and atomic correlation with the Woo checkout session. No browser-entered USD value.
2. Campaign reads that authoritative USD basis and applies the `19900` minor-unit threshold. It may show a localized upgrade estimate, but only audited Commerce FX converts and an approved gateway quote may set the actual customer-charge currency. Fail closed if USD provenance absent, stale, mismatched or changed at checkout.
3. Test exact **USD198.99 / USD199.00 / USD199.01** boundaries in EUR/GBP/AUD/CAD with coupon and tax combinations, plus Classic and Blocks order line metadata and refunds; do not broaden the pack's `valid_currency` without these tests.

## Stripe and real order gate
- Dev standalone `woocommerce-gateway-stripe` was **disabled**, WooPayments was enabled in TEST mode. Dev Runtime deliberately returns **no available gateways for non-USD**. R13 canary source states Blocks-only for foreign-currency TEST.
- The connected Stripe app currently exposes only TEST `acct_1SrJdHCxlWhQx8Rb`; it is **not** the previously approved `acct_1R57M7GR9MwFzg43`. No account switch, test gateway enablement, payment intent, refund, or test charge was performed.
- HPOS enabled and Classic Checkout accessible is **not** evidence of a completed order. True USD and FX payment/refund UAT remains blocked until an authorized, matching TEST gateway with a verifiable account id and a supported checkout path is ready.
- Production/WWW remain unchanged. Dev master OFF, gift page draft. Draft PRs are not authorized to merge or publish a public gift offer by passing source tests.

## Next implementation gates
- [x] Read-only SKU candidate inventory and test-fixture discovery.
- [x] Review existing Dev FX snapshot, prove GBP ambiguity and fail-close code.
- [x] Add USD-only regression so a later contributor cannot accidentally infer eligibility from FX presentation.
- [ ] Owner-approved *real* gift SKU/stock/cost list and a code-reviewed Campaign catalog (no generic WP PHP editor).
- [ ] Commerce authoritative USD cart-basis interface and coupon/tax/rounding sign-off for 5 currencies.
- [ ] Approved matching Stripe TEST account, checkout mode, HPOS order+webhook+refund/rollback tests.
- [ ] Visual desktop/mobile shopper UAT for `/gift` and official Dev release gate, then separate WWW approval.
