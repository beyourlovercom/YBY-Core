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
- Never silently stack with another Campaign Pack that declares the gift resource. The Lite kernel gate is coarse: native coupons and unrelated third-party gift plugins still require compatibility tests.
- **Known release blockers:** Currency OS live non-USD orders unsupported (gift selector fails closed); native Blocks/Classic Checkout, coupons, taxes, FX, shipping, stock race, order refunds, role negatives and frontend mobile visual UAT require isolated Woo tests. Current implementation is an engineering baseline, **not** permission to enable customer gifting.
- No required automated gift redemption; a customer may checkout without selecting gifts. This is a deliberate nonblocking policy subject to Owner acceptance.
- No clawback of a gift on refunds after purchase; resolve refund policy before Production.
