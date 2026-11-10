# BeYourLover Black Friday 2026 — Gift V2 / v0.2.2-dev-rc3

**Status:** GitHub Draft, Dev-only candidate, **not installed or enabled on WWW**. WWW currently runs v0.2.1-dev-rc2 and Campaign/Canary remain OFF.

## Current Owner-frozen customer rules
- Dates: 2026 Nov 10–30 (America/Los_Angeles).
- **US shipping:** positive merchandise cart receives 1 random surprise gift, USD199+ receives 2. At USD99+ the customer may pick **one** extra-cost gift from `/holiday/gift/`, charged USD10/15/20, replacing one random slot. Only 1 paid choice maximum.
- **Non-US shipping:** USD merchandise **under $99 no random gift**, USD99–198.99 **one**, USD199+ **two**. No paid gift selection abroad.
- USD threshold is source USD merchandise after native coupons, excluding gift, upgrade, fees, taxes and shipping.
- Warehouse/ERP chooses the actual free surprise gift SKU. This plugin stores **random-gift entitlement quantity**; it never invents stock or warehouse SKU.
- Order meta: `_byl_bf2026_random_gift_qty`, `_byl_bf2026_total_gift_slots`, `_byl_bf2026_paid_gift_product_id`; paid Woo product lines carry actual gift SKU and upgrade USD price.

## WWW Gift taxonomy
The existing public `/holiday/gift/` Woo product categories are:
- parent `gift` #2726, `gift-upgrade` #2728;
- `gift-upgrade-10` #2729, `gift-upgrade-15` #2730, `gift-upgrade-20` #2731;
- all 15 Owner-supplied approved-list product identities assigned, **5 per tier**, no product price/category removal.
- Woo currently flags Cloud Spirit, Cloud Ipo, Wavey out of stock; they may be shown as unavailable but cannot be selected.
- **Gift-free** category #2727 remains empty because warehouse chooses actual free products: no need to guess a Woo SKU.

## Activation
- Install the package via existing WP admin only after Dev native UAT and Owner authorization. Dependency Andy Campaign Lite v0.1.0; do not uninstall host.
- Default **no gift runtime** for public visitors. Only current administrator's 30-minute, signed, secure-cookie Canary activates gift hooks while Campaign master remains OFF.
- WooCommerce Gift product loop offers native one-choice forms **only** during an authorized Canary session and qualifying US cart; the ordinary public page continues displaying the 15-item catalog without an active promotion CTA.
- Actual globally available promotion still requires separate Owner release approval/constant, not provided in this candidate.
- R10 global Woo sale price meta writer is still locked OFF. Do not conflate US-only Black Friday discount copy with globally visible pre-existing Woo sale prices.

## Tests & release gates
- Isolated PHP fixture suite: **268/268 PASS** (R15 38 new checks). On installed Dev plugin v0.2.2-dev-rc3, **16/16 real WooCommerce native cart and in-memory order-meta assertions PASS**, including US choice $10/$15, CA $69/$138/$217, cross-country revocation, and restored Dev test categories. Independent anonymous Dev Store API safety regression also PASS. GitHub exact-head CI, browser-signed Canary, persisted order metadata and ERP receipt still require verification.
- **No real payment or automatic warehouse shipment** from tests.
- Remaining: Dev Woo order metadata and ERP import readback, US paid choice from the actual Gift page, cross-country and FX/coupon UAT, then Owner manual WWW RC3 installation and restricted Canary. Only afterward seek full global launch approval.
- Main Black Friday landing headline/FAQ previously said all global orders receive a free gift; now international cutoff is USD99, so public FAQ/hero requires an explicitly scoped copy QA before all-site launch.

Full source contract: `docs/andy-campaign-v1/BF2026-R15-GIFT-CATALOG-AND-ORDER-ENTITLEMENTS-20261110.md`.
