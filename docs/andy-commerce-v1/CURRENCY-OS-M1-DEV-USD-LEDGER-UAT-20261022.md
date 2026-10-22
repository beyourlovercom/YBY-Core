# Andy Commerce Currency OS V1 — M1 Dev-only implementation and UAT gate (2026-10-22)

## 0. Owner directive / boundaries
- **Replace YayCurrency**, not renew or install a newer YayCurrency binary. Treat the uploaded third-party YayCurrency Pro ZIP as untrusted; never install/execute/derive code from it.
- Scope **dev.beyourlover.com only**. Do not modify WWW or use WWW SSH; do not merge GitHub main, release Stable, restart READY devices, run real orders, take payments, or send real email.
- Andy Core remains the generic foundation; **Andy Commerce owns WooCommerce Currency OS**. Continue the existing **two-plugin maximum** and preserve Marketing M4–M8 current Dev work.
- The live Dev WordPress plugin inventory checked via China Mac formal OAuth DC shows **Andy Core 2.2.11**, **Andy Commerce 1.0.3**, **WooCommerce 11.2.1**, Stripe Gateway 11.1.0, WooPayments 11.2.0, PayPal 2.0.27; **YayCurrency is not installed on Dev**. No deactivation needed.
- This branch stacks only on existing **Draft PR #191** exact head `cbe6532afb3872724a1429bc24a94cde727c821e` to avoid losing M8 source. Existing Dev marketing activities must stay OFF.

## 1. Approved irreversible business semantics
- USD is the **only canonical business accounting and campaign eligibility currency**. Use **discounted qualifying item subtotal USD minor units**, excluding tax, shipping, free gifts and specially excluded SKUs, to evaluate USD 69 / 99 / 129 / 169 or other *configured campaign* thresholds. The USD 99 example is illustrative; **not a change** to current region- or item-specific shipping policies.
- Customer may select USD/EUR/GBP/AUD/CAD. The same auditable FX snapshot converts product price, discounts, shipping, tax and final customer payable to the selected currency, not merely the symbol. Store order-local details + immutable rate snapshot when the **separately developed Woo/order bridge** is ready.
- Per-item rounding and final local payable **must reconcile exactly**. Discounts computed in USD first; convert line net to local and derive local displayed line discount as local gross minus local net.
- Shipping eligibility is computed independently of USD sales/accounting order total. USD $95 eligible merchandise + USD $10 shipping + USD $7 tax = USD $112 accounting sale, but **still fails** a USD $99 merchandise free-shipping threshold.
- Currency cannot override native Woo shipping-zone determination by delivery address. Historical orders/refunds retain original currency and payment auditability.

## 2. Delivered M1 source-only Dev candidate
- New `includes/class-andy-commerce-currency-os-v1.php`: **pure PHP quote calculation** from prevalidated Woo-style discounted USD lines, strict rate snapshot metadata, controlled five-code allowlist, integer USD minor units and fixed-precision synthetic USD→local exchange rates, promotion eligibility and ledger reconciliation. Throws on missing currency/rate, underflow, overflow or malformed data. No WordPress hooks, DB, HTTP, checkout or payment writes.
- New `admin/views/currency-os-v1-dev-lab.php`: Dev-only (`home_url` host strict check) **manage_woocommerce** GET-only shadow preview of synthetic USD 120 gross / 25 discount / 95 qualifying / 99 threshold / 10 shipping / 7 tax; five currency selector. **Sample rates are illustrative**, not a pricing provider. Checkout/paid order NOT connected.
- Narrow add-only bootstrap require and Andy Commerce `?page=andy-commerce&tab=currency-os` route. Other Marketing M4–M8 preview code unchanged.
- Version metadata bumped to **1.0.4** for any Dev-only plugin package; does not constitute Stable release.
- US Mac mini authenticated self-hosted DC: PHP lint PASS; **82 source-only currency tests PASS**; plugin version sync, Marketing M8, M7 draft security, legacy shipping coexistence, Native Order Detail regression all PASS.
- This candidate **cannot yet replace YayCurrency in real checkout**. Preview subtotal tax line is an explicitly supplied Woo-style USD amount; actual tax must be recalculated/verified via Woo before payment integration.

## 3. Next Dev milestones
- M2: authenticate FX provider, TTL/freshness and exchange rate fee/rounding policy, currency switching state plus data contract; required no USD silent fallback.
- M3: real Woo product/cart price and coupon evaluation in USD with captured post-discount eligible subtotals; no duplicate discount or currency hooks.
- M4: native zone 0/1/3/4/5 and special $30/parcel, PILLOW $30/unit rules, tax classes/inclusive/exclusive and shipping caching verified in all five selected currencies.
- M5: real native Woo Checkout Blocks/classic, Stripe/WooPayments/PayPal gateway currency capabilities, matching charged currency and order metadata, refunds and historical orders; **test-mode only**.
- M6: owner Dev desktop and 375×812 M14 UAT, shadow parity, shipping country matrix and controlled rollback; only after all checks can production launch be discussed separately.

## 4. Dev release and safety gate
- Confirm currently installed Andy Commerce Dev source is equivalent to parent **#191** before overwriting; the plugin header version **alone** does NOT prove source equivalence. Back up current Dev state/package; demonstrate staged rollback.
- Dev-only installation of reviewed **1.0.4** package via WordPress admin is authorized by owner; **no new plugin** and no YayCurrency reactivation.
- If an exact source or safe rollback cannot be established, keep Dev package upload HOLD; source-only PR may still proceed.
- No rate filters or payment hooks will be attached in M1. Currency OS remain SHADOW, with no write option or activation toggle.
