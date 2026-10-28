# R6 Commerce → Andy Campaign USD basis, safe Dev cutover (2026-10-28)

## Scope and source authority
- **Andy Commerce remains the sole owner** of WooCommerce currency, taxes, shipping, coupons and checkout data. Andy Campaign owns only the gift entitlement decision and reviewed PHP campaign pack.
- Sole Commerce publisher **Draft PR #213** exact source HEAD at implementation: `4ee97d3f2b5be96dcfe37ea217bf3ff8f936202b`; separate **Draft PR #242** Campaign HEAD: `130d477fc6e8df8623652a28e91163b200046c8e`. Both remain Draft, no merge to main or WWW.
- New `packages/andy-commerce/includes/class-andy-commerce-campaign-usd-basis-v1.php`, required once via Commerce bootstrap and **registers no hooks or payment callbacks**. It does not create, modify or delete orders, coupons, products, sessions, Stripe intents, refunds or schema.
- Contract `Andy_Commerce_Campaign_USD_Basis_V1::quote(WC_Cart $cart)` returns `ok`, `reason`, `basis_minor` in **USD cents**, current currency, provenance, FX rate micros/hash, coupon count and merchandise line count. This is **read-only, ephemeral** cart evidence, not a stored order ledger and not a signed external API.

## Truthful guarantees / intentionally unsupported cases
| Cart case | Source | Entitlement quote |
|---|---|---|
| USD with native Woo coupon allocation | Woo `line_total` tax-excluded post-coupon (skipping Campaign gifts) | Supported, current cart only |
| EUR, GBP, AUD, CAD **without coupons** and Woo tax-exclusive catalogue prices | Woo `WC_Product::get_price('edit')` source USD merchandise, integer quantities; every local `line_total` must reconcile against the Commerce FX rate, within one minor unit per line | Supported for **tested simple sale-price/cart cases** only |
| Foreign coupon of any type, tax-inclusive catalogue prices, unknown promo marker, missing/stale FX, inactive currency runtime, inconsistent local line total or malformed quantity | No safe source-backed exact USD allocation | **Fail closed**, no gifts |
| Stripe, Checkout/HPOS order, refunds, tax-inclusive sale, five-currency payment | Not implemented by this quote | **NO-GO** |

- **Critical GBP evidence:** at Dev FX `749160` micros, USD198.99 and USD199.00 both display as GBP149.08. Any local→USD inverse quote at the $199 boundary can grant unearned gifts. The new contract takes unfiltered source USD cents and reconciles the local Woo line instead.
- Campaign `BYL_BF2026_Gifts::eligibility()` uses this Commerce quote **only for foreign currencies**. Existing USD Woo `line_total` calculation remains unchanged.
- Campaign premium upgrade stays **source USD** in `WC_Product::set_price()`, as Commerce Currency Runtime already converts Woo getters; pre-converting would apply FX twice. Gift picker may display Commerce-converted local upgrade estimates. Conversion or quote failure blocks selection.

## Evidence
- Commerce pure no-DB fixture `COMMERCE_CAMPAIGN_USD_BASIS_PASS=25`: exact USD cents at 198.99, 199.00, 205.00 in EUR/GBP/AUD/CAD; coupon, tax-inclusive, runtime, stock marker and mismatched local-price guards. Existing currency coupon boundary `90 cross-currency assertions` PASS.
- Campaign pure no-DB fixture `BYL_BF2026_PURE_CONTRACT_PASS=27`, Lite decisions `15`: source USD threshold for EUR/GBP/AUD/CAD with stubbed verified quote, rejection on unsupported/failed quote, source USD9.99 premium with a single GBP7.48 getter conversion.
- **PR #213**: all six applicable GitHub workflows SUCCESS; one Currency preview workflow SKIPPED per event rules. Exact-head prepare script PASS: candidate ZIP SHA256 `0414810403353d77ff610fb88c95bd19070de52cb7c568d265c18b6492110fc9`; **the ZIP was NOT installed**.
- **PR #242**: six workflows SUCCESS.
- Dev `dev.beyourlover.com` minimal source deployment (authorized self-hosted DC/Hostinger SSH): original Commerce bootstrap `class-andy-commerce.php` backed up outside Webroot, **one require line inserted**, new no-hook Commerce basis file copied from exact PR #213 with SHA256 `e2bf323e478b9e0db402723b490adbf9fc2f4ba51bee66cb24b78df9e710a004`. Only the reviewed Campaign gift-rules class copied from PR #242 SHA256 `2ac6fd5e5b2306f1e7f77f0bc2794dcae2abbf7d1ff98936aa6056b9f28e5231`. Old gift-rules file also backed up outside Webroot.
- Dev real WP load: both classes YES; Marketing master `no`, old P1/Dev switches `no/no`; no gift options in catalog, no gift forms while OFF. Currency R13 Canary unchanged at `88c4e293...`. Original source backups in `/home/u247343654/.byl-dev-r5-campaign-20261028/backup/`.
- Dev read-only **native Woo WC_Cart instance** with existing hidden UAT product ID40029 and line_total USD15.00 returned `ok=true`, `basis_minor=1500`, currency USD, `woo_coupon_allocated_net`. No cart session persistence, products, coupons, orders or payment were created.

## Remaining strict gates
1. No full-coupon foreign USD allocation; native `fixed_cart`, percentage and mixed tax classes require a separately audited per-line source USD post-coupon ledger. Do **not** inverse-FX coupons.
2. No real BF gifts configured; Dev gift page ID40051 remains **draft**, and marketer master OFF. Owner must approve at least two truly free gift SKUs plus any premium upgrade price, with warehouse stock/cost, weights and regional shipping restrictions.
3. Dev uses Classic Checkout and the foreign Currency Runtime intentionally hides payment methods. R13 foreign canary supports Blocks-only with verified Stripe TEST account; currently attached Stripe TEST account is different from the previously approved account. HPOS order, webhook, refunds and shopper UI remain **NO-GO** until separate authorized provider session gate.
4. **Do not overwrite the live Currency R13 Canary** with the otherwise-passing PR #213 ZIP. Its installed SHA differs from the candidate due to concurrent work. Main/WWW untouched.
