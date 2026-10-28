# BYL Black Friday 2026 — independent PHP Campaign Pack

**Status:** Dev-only implementation candidate; not production approved. The pack is independently versioned. It registers with Andy Campaign Lite through `andy_campaign_register`, has no top-level Woo mutations and is OFF unless Lite grants execution.

Rules:
- Product discounts are configured using **WooCommerce native sale prices**; this pack does **not** implement an additional “up to 50%” discount.
- Gift entitlement is 1 per nonempty eligible USD merchandise cart; 2 if merchandise net **after native coupons**, excluding tax/shipping/gift-upgrade lines, is at least **USD 199.00**.
- Gift catalog is deliberately **empty by default**. Site-specific reviewed PHP provides the exact Woo SKU IDs and `upgrade_minor` integer (USD cents) using `byl_bf2026_gift_catalog`. This cannot be populated by a shopper.
- **Release-critical preflight:** at least **two distinct, free (`upgrade_minor=0`), in-stock, purchasable SIMPLE products** must exist before any campaign hook can launch. Expired/empty/invalid gift pool => `pack_not_ready`. An operator POST that tries to enable an unready pack is rejected. The paid premium choices are optional extras and cannot substitute for the promised free options.
- Stockout or duplicate gift selections are revoked on Woo cart reconciliation; the Lite kernel removes stale gift lines if the pack is OFF, expired or missing. Abnormal array-shaped POST inputs fail safely rather than reaching scalar sanitizers.
- Customer selects gifts through the `[byl_bf2026_gifts]` shortcode on an existing Andy Core-owned landing page or `/gift/` page. **No page is created or edited by this pack**. Selection is server-side POST + CSRF nonce; no extra JavaScript required.
- Woo keeps product, tax class, stock and checkout authority. Gift line price is 0 or configured upgrade fee; original cart product base price remains untouched. Source is saved in Woo order item metadata for auditing.
- Requires **WooCommerce 8.8+** for `woocommerce_coupon_get_items_to_apply`. Activation readiness fails closed on older versions. All coupon allocation (fixed-cart, percent, product discounts) must exclude promotional gift lines, including paid-upgrade fees. The earlier product eligibility filter alone is insufficient for fixed-cart coupon distribution.
- Actual isolated Woo regression: merchandise USD205 + premium upgrade USD9.99 + fixed-cart USD20 coupon => net eligible merchandise USD185, full upgrade USD9.99, final USD194.99 (no tax/shipping). Another native test: shipping USD12.34 remains separate from qualifying merchandise and is not discounted by the gift rule.
- The Classic checkout cart validation is complemented by `woocommerce_checkout_validate_order_before_payment` for Store API/Blocks. Source/tests support this hook path; **no actual persisted order or payment has been tested or approved**.
- Never silently stack with another Campaign Pack that declares the gift resource. The Lite kernel gate is coarse: native coupons and unrelated third-party gift plugins still require compatibility tests.
- **Known release blockers:** Currency OS live non-USD orders unsupported (gift selector fails closed); native Blocks/Classic Checkout, coupons, taxes, FX, shipping, stock race, order refunds, role negatives and frontend mobile visual UAT require isolated Woo tests. Current implementation is an engineering baseline, **not** permission to enable customer gifting.
- No required automated gift redemption; a customer may checkout without selecting gifts. This is a deliberate nonblocking policy subject to Owner acceptance.
- No clawback of a gift on refunds after purchase; resolve refund policy before Production.

### Safe OFF-state draft preview (R6)
- The `[byl_bf2026_gifts]` shortcode is now registered from the independently installed pack's `init` display hook regardless of the campaign's master switch, **without** booting any cart mutation hooks.
- When the host decision is not `eligible`, the shortcode only renders a neutral *not currently available* message; no gift buttons, no total recalculation and no coupon/checkout/POST hooks.
- Gift selection is only interactive after the pack is explicitly enabled and its >=2 free in-stock SKU readiness gate succeeds. A draft `/gift` page can be reviewed with marketing OFF. This does **not** authorize publishing the page or opening the promotion.
- Five-currency purchase is still **NOT** supported by this USD-only BF pack. The real Dev currency selector correctly updates five Store API cart amounts, but non-USD payment providers remain blocked in Commerce until their separate gateway/refund UAT. Do not misrepresent display/cart UAT as paid checkout.
