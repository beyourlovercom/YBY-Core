# R8 real ERP product/cost/warehouse audit — 2026-10-29

Status: **R8 genuine gift catalog still NO-GO**, although its Dev PHP safety guard was deployed and previously passed true isolated WooCommerce 13/13 shopper tests and 6/6 CI. No products/orders/inventory were changed in this audit.

Method: user's already-open authenticated China Mac Chrome ERP tab; same-origin `GET` only to `/products/list?search=...`, `/inventory/products?search=...`, `/products/sku-aliases?search=...`. No navigation, UI state or ERP records changed.

## Four exact Woo candidate SKUs
| Woo ID | Canonical SKU | ERP product ID | ERP standard production cost | ERP standard packaging cost | ERP product available_stock | ERP weight_kg | Exact SKU warehouse inventory |
|---|---|---:|---|---|---|---|---|
| 21954 | `chargerc` | 1331 | NULL | NULL | NULL | NULL | 0 matching ERP rows (NOT proof of physical zero) |
| 17095 | `00W07` | 1289 | NULL | NULL | NULL | **15.9900** (anomalous for 18ml liquid; DO NOT treat as shipping kg) | 0 matching ERP rows |
| 30454 | `BY489A-Lubes` | 1346 | NULL | NULL | NULL | NULL | **2 warehouse rows** |
| 37763 | `414sleeve` | 1469 | NULL | NULL | NULL | NULL | 0 matching ERP rows |

**BY489A-Lubes inventory records (read-only ERP):**
- **us-ny** 美东纽约仓, inventory row id 358, on-hand **615**, reserved **0**, available **615**, stock `in_stock`, original `inbound_at=2026-02-11`.
- **us-la** 美西 MorePlus 仓, row id 400, on-hand **726**, reserved **0**, available **726**, `in_stock`, original `inbound_at=2026-01-06`.
- Aggregate ERP-recorded available stock **1,341 units**, *not a guarantee that a current physical warehouse count is 1,341.* The row's inbound date is **NOT** the latest sync/verification timestamp. Require fresh warehouse provider reconciliation/reservation and destination carrier rules.
- SKU Alias records exist: `6971796990292` and `BY489A` associated with this product search. No matching alias records were returned for the other three searched SKUs.
- Liquids require destination/shipping compliance screening. No landed unit cost, packed grams, fulfillment fee or country allowlist has been approved. Therefore this is **not yet an approved free gift** despite substantial ERP-recorded stock.

## Full Product OS cost coverage check
Authenticated read-only `GET /products/list?per_page=100&page={1,2,3}` returned 100 + 100 + 52 = **252 products**. Among these **0/252** have `standard_production_cost` populated, **0/252** have `standard_packaging_cost` populated, **0/252** have `available_stock` in the product listing. Only 10 + 5 + 12 = **27/252** have a non-null `weight_kg`, which still requires unit validation and packing evidence.

This means the Product OS list is **not currently a trustworthy source of gift landed-cost or warehouse stock** across the catalog; it does NOT prove the independent Accounting cost policies or import tables are empty. Do not conflate Product OS NULL with accounting-ledger absence. A read-only Accounting cost-to-SKU join or signed factory cost sheet is the next data gate.

## Prior uploaded costbook reconciliation found in ChatGPT Library (2026-10-29)
**Source:** `BYL-ERP-PRODUCT-COST-RECONCILIATION-2026-09-08.xlsx` (previously uploaded, **read-only**; original named `成本货本007组 7月进销存.xlsx`). Its READY tab has **55 uniquely matched product groups / 57 source rows**, with status `READY_NOT_APPLIED`; it explicitly warns that Product OS `standard_production_cost` is merely reference/estimate and that source rows must use a separately approved confirmed-cost contract. This is historical 2026-07 purchasing cost, **not** current product landed cost or authorization to write the Production ERP.

**Exact confirmed historical SKU match for real candidate:**
- ERP product **1346**, Woo SKU `BY489A-Lubes`, alias/match key **`BY489A`**, 35ml lubricant; `unit_cost_cny=1.20`, source long code `1BY489A5C0100`, provenance `confirmed_source_file`, status **`READY_NOT_APPLIED`**.
- **Do NOT** treat ¥1.20 as final gift unit cost. It does not independently prove current batch purchase cost, packing material, merchant payment fee, 2026 FX rate, warehouse storage, liquid compliance or last-mile carrier fee. Requires Owner-reviewed current costing/fulfillment addendum before R8 approval.

Other inexpensive source-matched historical costbook rows, **candidate research ONLY**:
| ERP product ID | SKU | Historical source unit cost CNY | Caution |
|---|---|---:|---|
| 1475 | `CY615A` | 11.90 | Warehouse-only catalog name pending; current SKU stock/packing unverified |
| 1466 | `VX219A` | 25.3764 | Current warehouse stock/packing unverified |
| 1333 | `609A` (source key `VX609A`) | 26.1012 | Alias must be matched; current stock/packing unverified |
| 1476 | `VX610A` | 26.4015 | Warehouse-only catalog name pending; current stock/packing unverified |

The originally short-listed SKUs `chargerc`, `00W07`, and `414sleeve` have **no exact match in this reconciled costbook**. This does not prove a cost never existed; it means this evidence source cannot approve them.

**Accounting reality check:** authenticated read-only ERP query for historic orders matching `BY489A-Lubes` returned 238 order-level records. Only **18/238** have a product-cost field populated; the others **220/238** do not. An *order-level cost*, often for a basket with multiple SKUs, is not usable as SKU unit cost. No order data was modified.

**Data-access stop:** the already-open China Mac Chrome/ERP read-only inspection later encountered a platform safety block. No alternative access path used to bypass. No additional inventory for the four newly researched cheaper candidates can be claimed.

## September US-NY physical-count evidence, historical only
Previous uploaded source `美东仓库-库存9月.xlsx` shows warehouse count dated **2026-09-04** for the two genuine alias SKUs mapped to `BY489A-Lubes`:
- `6971796990292` — **499 units**.
- `BY489A` — **116 units**.
- Both rows describe 35ml lubricant, warehouse `中塔-美东-NJ-01(US01)`, shared **13.970 × 12.700 × 1.524 cm** recorded dimensions (do not infer packed weight).
- Historical NY combined **615 units**, consistent with more recent ERP NY `quantity_available=615` readback. This supports **SKU alias identity and arithmetic consistency only**; it does **not** prove a real-time 24-hour provider reconciliation, stock reservation, latest country policy or SKU packed grams.
- The previously audited MorePlus/US-LA 726 units are a separate ERP record, **not** proven by this September NY snapshot.

## Second candidate discovery from historical inventory × costbook
Joined the **55 READY matched product costs** in the uploaded September 8 reconciliation against all **15 SKU rows** in the **September 4, 2026 US-NY inventory sheet**. This yields 9 exact-SKU/source-alias matches, of which the next least costly after `BY489A` are:

| ERP/Warehouse SKU | Product | Historical CNY unit cost | Sep 4 NY qty | R8 action |
|---|---|---:|---:|---|
| `DW01A` | BLACKFIRE | **35.93** | **19** | Candidate for owner-reviewed **premium upgrade**, not automatically a free gift |
| `VA699A` | ORTHUS | **44.503653334** | **33** | Candidate for owner-reviewed **premium upgrade**, not automatically a free gift |

The previous 15-row September inventory snapshot does **not** show `CY615A`, `VX219A`, `609A`, or `VX610A`. It is invalid to infer that their current stock is zero; neither can we approve these four from missing historical rows.

This historical intersection gives two additional *research* candidates, **not two approved free-gift SKUs**. Both stock observations are older than R8's 24-hour gate, and product packaging, destination carrier policy, current stocked location/availability and incremental gift margin are missing. No new SKU has been added to the runnable catalog.

## New R8 code-only freshness gate
R8 Draft PR #255 now also refuses unsigned, stale, or untraceable inventory reports, **even when a positive quantity is entered**. Every review manifest row must have:
- `approved=true`, positive `warehouse_available_qty`, positive `landed_cost_usd_minor` and `packed_weight_g`.
- A stable `warehouse_code`, one of `inventory_evidence_kind='provider_verified'` or `'erp_provider_reconciled'`, integer UTC Unix `warehouse_checked_at`, **within the last 86,400 seconds and never in the future**, and a nonblank `reviewed_by`.
- Reviewed nonempty `allowed_countries` of ISO alpha-2 strings. The checkout country must be on this per-SKU allowlist.
- Untrusted Woo `instock` alone, an old inbound/putaway date, and a historical stock spreadsheet **must not be used to populate** `warehouse_checked_at`.

Source checks: `BYL_BF2026_R8_READINESS_PASS=18`; BF gift pure contract `PASS=33`; **15/15** isolated local WooCommerce shopper UAT with 4 temporary products deleted and local options restored. This change remains **Draft PR source-only** until controlled Dev deployment is separately approved; Dev master OFF, real gift catalog remains empty.

## Checkout-wide gift revocation after stale alternate option (2026-10-29)
A separate R8 code review found that **per-item approval alone was insufficient**: if a shopper had selected free gift A, but *another* required free option B expired or lost review approval, gift A could remain in the cart even though the two-free-options promise and `ready()` had failed.

Fix in Draft PR #255 now checks overall `BYL_BF2026_Gifts::ready()` before **POST selection**, **picker rendering**, **cart reconciliation** and **Classic/Store API checkout gift validation**. Once the second approved free choice disappears:
- All previously selected BF gift lines are revoked at cart reconciliation — even if the individual selected SKU is still in stock.
- Checkout denies an existing BF gift until repaired, before payment; ordinary non-gift merchandise remains purchasable.
- The picker returns a safe unavailable state, not a misleading list of partial reward choices.
- Proof restored / at least two approved free SKU available again allows gift selection.

Evidence: pure PHP `BYL_BF2026_PURE_CONTRACT_PASS=37`, provider expiry and approval checks `BYL_BF2026_R8_READINESS_PASS=18`, and a **real isolated WooCommerce 19/19 customer-cart acceptance** on US Mac mini, including stale unselected SKU revocation. Four disposable local products deleted, local master/config options restored; no live orders, gifts, payments or refunds. Both checkout paths have source-level negative tests, but full browser paid checkout still requires R7/R9.

This revision is **source-only** at the R8 child Draft PR. The running Dev BF plugin remains OFF with no approved catalog, so the new global-revocation code is not claimed deployed. Do not enable public gifts on the old Dev revision.

## Final Store API address and regional picker gate (2026-10-29)
R8 child Draft PR #255 now explicitly distinguishes the **submitted `WC_Order::get_shipping_country()`** during Store API checkout from the potentially stale `WC()->customer->get_shipping_country()` session:
- Submitted France blocks an incompatible gift even if older cart session is United States.
- Submitted United States permits an approved gift even if older session is France.
- Missing submitted shipping country fails closed, even when an older session is United States.
- On the gift picker, if the shopper already has a known shipping country, unsupported gifts show **Unavailable for your shipping country** and their selection button is disabled. The POST selector independently denies a known excluded destination; when unknown, strict checkout verification still applies.
- This changes the campaign's gifting eligibility, **not** the underlying WooCommerce shipping policy or Stripe settings.

Evidence at exact R8 Draft PR #255 source commit `9f973e5736b230c041ab3ec125ee0a4fbeabfb08`: `BYL_BF2026_PURE_CONTRACT_PASS=40`, `BYL_BF2026_R8_READINESS_PASS=18`, and US Mac mini independent native WooCommerce shopper test **23/23 PASS**, including region picker rendering, order-vs-session authority and provider-stock expiry. Four temporary products deleted; local options restored. All 6 applicable GitHub CI SUCCESS. Source-only; current Dev BF runtime OFF and **not claimed upgraded**.

## Blockers and next approved steps
1. Confirm at least 2 free real gift SKUs and an optional paid upgrade; neither a UAT fixture nor the USD retail catalog price can be accepted as factory/landed gift cost. Existing records prove no production/packaging costs for the four candidates.
2. For each approved SKU: signed factory/landed USD cost, current provider-verified warehouse available count after reservations, packaging weight in grams, total incremental international carrier cost, compliant allowed/excluded countries, responsible reviewer/approval date.
3. Investigate **00W07 weight_kg=15.9900** as a likely source mapping/unit inconsistency; do not silently reinterpret as 15.99g or use in carrier pricing.
4. ERP Product OS and Inventory OS records are distinct: `available_stock=NULL` on product list does not override positive records in the Inventory module. No matching exact-SKU row does not exclude another approved alias or external warehouse; investigate mapping rather than asserting zero inventory.
5. R8 readiness contract must remain fail-closed; currently Dev gift catalog and approvals both 0; Campaign master OFF, /gift/ page #40051 draft, no customer gifts/real orders. PR #255 remains Draft. 
6. R7 Dev Currency R13 runtime drift is tracked separately; until corrected with approved publisher, no claim of foreign-currency gift Checkout parity.

**Release NO-GO:** no SKU gift selection may be enabled merely because Woo marks it `instock` or ERP has a stock row; missing cost/weight/regional proof still blocks the commerce release.
