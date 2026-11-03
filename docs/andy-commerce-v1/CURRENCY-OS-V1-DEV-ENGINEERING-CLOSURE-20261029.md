# Currency OS — V1 Dev Compatibility Engineering Closure (2026-10-29)

## Owner's final engineering boundary
Currency OS must **not** recreate an FX engine or payment gateway. YayCurrency Pro 3.3.5 is the only live conversion engine; WooCommerce is the source of actual prices, native cart/discount totals and transactions; Andy Commerce/Campaign determines promotion eligibility using **original USD merchandise amounts after verified discounts, excluding gifts/paid upgrades, shipping and tax**.

## Closure decision — V1 SOURCE-USD COMPATIBILITY DEVELOPMENT: CLOSED AT DEV EVIDENCE LEVEL
The **compatibility engineering and Dev UAT scope** can be archived. This is **not** an assertion that every coupon plugin, BOGO, Bundle, foreign-currency gateway charge, shipping/tax matrix, refund or WWW rollout has been accepted.

### Verified evidence
- DEV active: Andy Commerce 1.0.12 (new source-USD adapter), YayCurrency Pro 3.3.5, and Black Friday 2026 **0.1.1-dev**; legacy Andy currency runtime OFF, Andy Campaign master OFF.
- Five configured currencies USD/EUR/GBP/AUD/CAD. Existing actual Dev installed BF plugin `BYL_BF2026_Gifts::eligibility()` **40/40 PASS**: source USD $0/$198.99/$199.00/$199.01 and gift-line exclusion across currencies.
- Actual Woo `WC_Cart::calculate_totals()` **20/20 PASS** and native Woo temporary-coupon allocation **25/25 PASS** (fixed-cart, percent, fixed-product, multiple SKUs/quantities); test coupons confirmed cleaned.
- Five guest HTTP session Cookie-preserving carts and `/checkout/` page renders **5/5 PASS**, with session cart removal **5/5 PASS**; these are **checkout render** checks only, **not** order/payment/refund transaction proofs.
- Native Woo disposable CI 11/11 PASS for coupon allocation and USD min/max spend thresholds. Earlier mock contracts 73 PASS and BF patch 54 PASS remain supplementary, not replacements for above real Dev tests.
- Canonical Commerce Dev ZIP from PR #213 source was installed and read back with SHA, BF 0.1.1-dev from exact GitHub candidate also installed and read back with SHA and rollback backup. No WWW, GitHub main or Stable deployment.

### Explicitly OUT OF SCOPE, independent gate
- Black Friday **R8 gift activation**: Gift A BY489A-Lubes Dev product mapping/current stock remains unresolved; Owner selected Gift B VX609A (candidate Dev SKU 609A/ROSEY product #25045) but current warehouse stock, cost, packed weight and allowed countries remain unverified; `/gift/` WP page #40051 remains Draft and master OFF. This must **not** hold the Currency compatibility subproject open.
- Unverified BOGO/Bundle/external pricing engines, coupon stacks, fixed local currency price exceptions: **FAIL CLOSED** until separate Marketing OS acceptance. Do not advertise "all discounts compatible".
- Payment, charge currency, webhooks, taxes/shipping matrix, HPOS retry and refund proof under old Currency R13 P0 [Issue #234]: its **self-built Currency/payment R13 expansion is frozen and superseded by YayCurrency Pro** as sole conversion engine. Any legitimate Woo/gateway acceptance must be scoped as a distinct native payment/checkout release gate, NOT restart an Andy-built FX/Stripe bridge.
- No production release is implied by Dev engineering closure. Any WWW installation requires a separately approved exact-version controlled rollout.

### Source of truth
- Sole Commerce Dev release branch Draft PR #213; do not merge main/Stable from this closure.
- Related Yay source adapter PR #258, Dev UAT PR #262, BF Dev-only PR #263; BF Gift B decision recorded separately.
- Closure means **STOP adding Currency OS features or repeating already PASS test matrices**. Continue only separately authorized BF R8 inventory/selection and native checkout/payment release work.
