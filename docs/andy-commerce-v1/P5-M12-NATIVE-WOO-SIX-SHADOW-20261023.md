# Andy Commerce M12 | Native Woo Cart → Six-Type Shadow

Exact parent/source: M11 unified PR #213 at `ee82aac5bac8d780251f55c1053cf94063d4827f`. This M12 branch must be integrated into **only PR #213** after CI and actual isolated Woo UAT. Do not independently publish a ZIP to Dev/WWW or merge GitHub main.

## Behavior
- M11 obtains Woo's authoritative USD post-native-coupon lines, Woo shipping zone and selected shipping/tax/payable total, with explicit caller-provided special shipping/free gift/promotion eligibility per cart-key. Catalog product object + ID are verified before a line can enter the preview.
- M12 constructs the narrow existing **M10 Six_Type_Simulator** input from verified Woo lines. Preserves native manual coupon conflict/stack policy, date, country and one USD authoritative eligible subtotal. Special shipping and gift lines are not counted in marketing basis.
- Simulator accepts `WOO_NATIVE_READ_ONLY` provenance **only** with validated trusted read-only input and an explicit matching eligible USD amount. Existing `DEV_SYNTHETIC` inputs and legacy rules remain supported.
- Output always says `WOO_NATIVE_SHADOW_NO_MUTATION`, `runtime_enabled=false`, `real_checkout_enabled=false`, `native_woo_mutations=0`; *no* automatic Woo discount/gift/shipping or store-money write occurs.
- Fixed spend-save, percent spend-save, free gift, BOGO, bundle, tiered all use existing M10 evaluator (not a new rules engine). Product IDs must be unique in input; duplicate SKU lines currently fail closed.
- Zero payment/stock/ERP/email hooks, no runtime registration and no Site/Dev install.

## UAT
- Source: `marketing-m12-real-woo-six-shadow-harness.php` (48 assertions), M11 harness, M10 six-type matrix, ancestor full 43 tests, ZIP contract.
- Isolated US Mac mini WordPress 7.1.3, Woo 11.2.1, Andy Core 2.2.11, Commerce 1.0.8, distinct local MySQL and HTTP on 127.0.0.1:18973. Must verify actual native WC_Cart full six-type input/discounted totals after the code is pushed.
- P5 full promotion runtime only when separately enabled in isolated sandbox and proven with native orders; P6 real test gateway, native FX settlement and refunds not yet supported.

## Hard boundaries
Keep Dev/WWW unchanged; no main/Stable merge. All six marketing types remain OFF. #208 permission HTTP403 PASS, but its Dev test Subscriber has an unresolved 88-post authorship association; never delete content or alter that user's author IDs as part of M12.
