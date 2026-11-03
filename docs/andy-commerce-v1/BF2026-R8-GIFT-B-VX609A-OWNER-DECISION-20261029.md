# Black Friday 2026 R8 — Gift B Owner Decision (2026-10-29)

## Frozen selection
- Gift A: BY489A-Lubes (previously approved free gift; website Woo ID 1346 not present on Dev; USD 1 purchase cost, packed 10g, MorePlus US West, US only in earlier handoff). Current actual stock remains unverified.
- Gift B: VX609A — Owner-selected second **free** gift. USD upgrade fee is 0. Supersedes pending/unspecified Gift B choices. Do not substitute another SKU.
- Internal SKU alias: VX609A corresponds to 609A / ROSEY. Read-only Dev product candidate: Woo product #25045, SKU 609A, name ROSEY, listed price 59, stock_status instock and manage_stock=no; no tracked stock quantity. Warehouse-specific mapping must confirm this really fulfills the requested VX609A, preserving original SKU as required.
- Historic Canada stock of 106 on 2026-09-05 and legacy cost number 26.1012 are context only, NOT live availability or verified USD landed cost. Do not infer their applicability to a 2026-10-29 Gift B release.

## Scope and gates
- Original USD merchandise after valid discounts, excluding tax, shipping, gift and paid-upgrade lines. One free gift by default; source USD net >= $199 gives two. YayCurrency Pro remains the sole FX storefront engine.
- BYL_BF2026_R8_Readiness::inspect() still requires two actual Woo simple purchasable in-stock products, Owner approval, positive current warehouse available quantity, landed cost USD minor, packed weight grams and allowed ISO-country list.
- Product listing status instock does NOT establish actual fulfillable warehouse quantity; Gift B shipping countries, pack weight, and USD landed cost remain unverified.
- Gift A requires a Dev-specific product ID (the WWW historical ID 1346 does not exist in Dev).
- Gift B requires positive confirmation that 609A / ROSEY Dev #25045 maps to VX609A and that target warehouse can ship to authorized destinations.
- Keep Campaign master OFF, /gift/ page #40051 Draft, Black Friday plugin 0.1.1-dev, Andy Commerce installed, and no further Currency OS feature expansion.

This is a versioned Owner selection record only. It does not approve Dev gift activation, website release, real order creation, shipping country assumptions, main merge, Stable or WWW changes.