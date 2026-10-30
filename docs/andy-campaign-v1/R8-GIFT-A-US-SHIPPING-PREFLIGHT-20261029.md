# R8 Gift A — US domestic liquid mailing preflight (2026-10-29)

Status: **PUBLIC RULE REVIEWED; PRODUCT / MOREPLUS / CARRIER NOT VERIFIED; NO-GO**. Research-only. Does not modify any WordPress configuration, gift catalog, stock, Stripe, shipping rates or WWW.

## Grounded SKU and owner inputs
- Gift A: Woo #30454, `BY489A-Lubes`, 35 mL lubricant; ERP #1346, aliases `BY489A` and `6971796990292`.
- Cost input: USD 1.00 purchase cost, **not** validated landed cost.
- Supplied packed weight: 10 g; verbatim preserved but `weight_needs_remeasurement=true` because 35 mL of filled liquid plus bottle cannot reasonably total 10 g.
- Fulfillment scope: US West MorePlus, ERP mapping `us-la`, US-only, upgrade USD 0. Last ERP available quantity 726, not fresh provider verified.

## USPS public policy — conditional, not product approval
USPS Publication 52 §451.2 allows **nonhazardous liquids** in the domestic mail *when properly packaged*; hazardous liquids are subject to separate Chapter 3 restrictions. §451.3 requires a secure closure, outer packaging appropriate to prevent leakage, and liquid markings/orientation as applicable. Some containers must be triple packaged.
- Source: https://pe.usps.com/text/pub52/pub52c4_018.htm
- USPS hazardous / flammable liquid restrictions: https://pe.usps.com/text/pub52/pub52c3_017.htm
- Reference PDF (February 2026): https://pe.usps.com/cpim/ftp/pubs/pub52/pub52.pdf

**No claim that BY489A-Lubes qualifies yet.** The formulation, SDS / relevant hazard classification, actual closure and outer packaging, final domestic routing/service, PO-box rules, and MorePlus carrier acceptance have not been verified. An allowed USPS class does not automatically establish MorePlus's own acceptance.

## Exact signoff evidence still required
1. Current MorePlus / ShipOut provider-positive stock snapshot: warehouse/provider location mapping, real `available_qty`, `reserved_qty`, provider timestamp within 24 hours, reviewer identity; compare against ERP. Do **not** use the old ERP 726 row as today's provider stock.
2. Physical scale photo or signed packing-sheet weight for the **filled** bottle plus customer-order packing, in grams. Preserve Owner input 10 g as unverified input until measured.
3. Product composition / hazard information (SDS if available) and MorePlus acceptance of this exact 35 mL SKU for US domestic fulfillment; approved carriers / services and any ZIP/PO-box restrictions.
4. Incremental landed fulfillment cost including product USD 1.00, pack materials, pick/pack, storage and marginal last-mile shipping. Do not set `landed_cost_usd_minor=100` without proof.
5. Second independent free gift SKU signed by Owner and separately verified against current US provider inventory, weight, carrier and cost.
6. Only after both options pass: reviewed PHP manifest, Dev preview, shopper UAT, master remains OFF. Release requires separate approval.

## Provisional alternative research — NOT a selected Gift B
Historical audited cost sheet suggests `CY615A` CNY 11.90 (ERP #1475) as the cheapest *unverified* candidate beyond Gift A. No current US warehouse stock, packed weight, exact Woo identity, or provider shipping proof has been obtained. Keep `VX219A`, `609A`, `VX610A` as higher-cost comparison candidates without approval. Public product listings suggest `VX219A` may be "Sweet Bobo", but such third-party listings cannot establish our internal Woo SKU, stock or shipping eligibility.

Do not create fake verification timestamps, `provider_verified` evidence, `approved=true`, fake stock, or an active gift catalog merely to make `ready()` pass.
