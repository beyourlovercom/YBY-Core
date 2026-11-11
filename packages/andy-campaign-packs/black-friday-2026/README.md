# BeYourLover Black Friday 2026 — Gift V2 / v0.2.3-dev-rc4

**Status:** R16 P0 repair candidate. WWW currently runs Owner-manually-installed v0.2.2-dev-rc3 with Campaign Master OFF and Canary OFF. v0.2.3-dev-rc4 is source-only until independent Dev and CI gates pass; do not deploy it to WWW without Owner manual installation and approval.

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
- R16 repair checks: authenticated REST signed-Canary 17 assertions PASS, updated R15 threshold and transient-address 43 PASS, baseline R14 19 PASS. Real Dev WooCommerce persistent session tested in **three separate PHP processes** (A/B/C) with saved DB session, Heart +$10 / Camera +$15 retained, CA country revocation, and temporary Dev terms/session cleaned. These cross-process tests are NOT yet full browser-HTTP/cart-reload Canary UAT. Exact-head CI and live WWW refresh await separate checks.
- **No real payment or automatic warehouse shipment** from tests.
- Remaining: R16 exact-head GitHub CI, Dev real-browser authenticated HTML/Store API persistence, safe Owner manual WWW RC4 upgrade and 30-minute Canary retest; independent ERP gift meta import and US-only price/FX/coupon checks. Full global launch remains NO-GO. P0 evidence: https://github.com/beyourlovercom/YBY-Core/issues/311.
- Main Black Friday landing headline/FAQ previously said all global orders receive a free gift; now international cutoff is USD99, so public FAQ/hero requires an explicitly scoped copy QA before all-site launch.

Full source contract: `docs/andy-campaign-v1/BF2026-R15-GIFT-CATALOG-AND-ORDER-ENTITLEMENTS-20261110.md`.
