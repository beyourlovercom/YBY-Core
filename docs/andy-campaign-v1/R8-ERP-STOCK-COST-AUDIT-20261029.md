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

## Blockers and next approved steps
1. Confirm at least 2 free real gift SKUs and an optional paid upgrade; neither a UAT fixture nor the USD retail catalog price can be accepted as factory/landed gift cost. Existing records prove no production/packaging costs for the four candidates.
2. For each approved SKU: signed factory/landed USD cost, current provider-verified warehouse available count after reservations, packaging weight in grams, total incremental international carrier cost, compliant allowed/excluded countries, responsible reviewer/approval date.
3. Investigate **00W07 weight_kg=15.9900** as a likely source mapping/unit inconsistency; do not silently reinterpret as 15.99g or use in carrier pricing.
4. ERP Product OS and Inventory OS records are distinct: `available_stock=NULL` on product list does not override positive records in the Inventory module. No matching exact-SKU row does not exclude another approved alias or external warehouse; investigate mapping rather than asserting zero inventory.
5. R8 readiness contract must remain fail-closed; currently Dev gift catalog and approvals both 0; Campaign master OFF, /gift/ page #40051 draft, no customer gifts/real orders. PR #255 remains Draft. 
6. R7 Dev Currency R13 runtime drift is tracked separately; until corrected with approved publisher, no claim of foreign-currency gift Checkout parity.

**Release NO-GO:** no SKU gift selection may be enabled merely because Woo marks it `instock` or ERP has a stock row; missing cost/weight/regional proof still blocks the commerce release.
