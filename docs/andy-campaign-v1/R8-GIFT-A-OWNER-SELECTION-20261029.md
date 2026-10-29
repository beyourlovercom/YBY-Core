# R8 — Owner selection: Free gift A / BY489A-Lubes (2026-10-29)

**Status: OWNER SELECTED; NOT READY FOR FULFILLMENT / ACTIVATION.** Do not change the Black Friday master, redeemable catalog, Dev draft page, orders, inventory or WWW based on this document.

## Approved by Owner for campaign design
| Input | Recorded decision |
|---|---|
| Product | **BEYOURLOVER LUBRICANT 35ML** |
| Woo product ID | **30454** |
| Woo SKU | **BY489A-Lubes** |
| ERP product ID | **1346** |
| ERP warehouse aliases | BY489A, 6971796990292 |
| Gift position | **Free gift A** |
| Extra/upgrade fee | **USD $0.00** (free, no upsell amount) |
| Owner-supplied unit *purchase* cost | **USD $1.00** (100 cents; **not** final landed/fulfillment cost) |
| Owner-supplied packed weight | **10 g (UNVERIFIED)** |
| Shipping warehouse | **US West MorePlus**; runtime manifest `us-moreplus`, reconciled ERP Inventory code `us-la` |
| Allowed countries | **United States only** (`US`); never broaden silently |
| Owner selection | Explicitly approved **as the gift design choice**, not as verified physical/financial fulfillment data |

This input is stored as **non-executable data** at `packages/andy-campaign-packs/black-friday-2026/config/r8-owner-gift-selection-20261029.json`. It is **not** a runtime `byl_bf2026_gift_catalog` filter or a Woo option. The pure contract `tests/r8-owner-gift-selection-contract.py` requires it to remain non-activating until the remaining data gates pass.

## Evidence verified separately
- Actual `dev.beyourlover.com` read-only WC product #30454: `BY489A-Lubes`, **BEYOURLOVER LUBRICANT 35ML**, simple, purchasable, Woo listed price USD19.90, `instock` but Woo stock quantity **not tracked**, Woo product shipping weight **empty**. Dev BF gift catalog **0** and master **OFF**.
- Previous authenticated ERP Inventory read-only MorePlus/US-LA row #400: `quantity_on_hand=726`, `reserved=0`, `quantity_available=726`. This snapshot is **not the provider-verified fresh 24h inventory approval**; no `warehouse_checked_at` claim is made. NY 615 stock is irrelevant to Owner's MorePlus-only fulfillment decision.
- Previous July 2026 historic CNY1.20 costbook is **not** used for this gift's now Owner-selected USD1 procurement cost, and is not applied by this change.

## Open safety gates (explicit)
1. **Packed-weight discrepancy:** A 35 mL filled liquid bottle cannot plausibly have a 10 g *total packaged* weight. Confirm whether 10 g means just packaging materials or a mistaken unit; obtain a **measured packed weight in grams**, including filled bottle and shipping packaging, before shipping-rate calculation. Do NOT silently change the owner's `10` or translate it as `10 oz`. It remains captured verbatim and `verified_packed_weight_g=null`.
2. **MorePlus provider stock:** obtain a current provider-confirmed MorePlus available quantity, reservation position, explicit location mapping and timestamp no older than 24 hours; record reviewer.
3. **Costs:** current factory purchasing USD1 is recorded; additional packaging, MorePlus pick/pack, incremental last-mile US shipping, storage and tax/regulatory restrictions remain unverified. `landed_cost_usd_minor=null`.
4. **US-only liquid policy:** confirm whether the selected carrier/service, ZIP codes or PO boxes impose further limitations; do not assume all US shipping routes accept liquids.
5. **At least one more distinct free gift** is required by the already-frozen BF2026 rules (two truly available free options). Owner selected A only so Campaign `ready()` must remain false. Premium gift remains optional and not yet selected.
6. Final Dev shopper QA on the reviewed catalog plus R7's separately authorized payment path; no WWW release without standalone approval.

## Contract to keep
`upgrade_minor=0`, only `US`, provider `us-moreplus`, real Woo #30454. No test fixtures, no universal shipping, no inferred weight/approval. GitHub R8 Issue #253 / Draft PR #255 owns remaining gift work. Dev Campaign master OFF.
