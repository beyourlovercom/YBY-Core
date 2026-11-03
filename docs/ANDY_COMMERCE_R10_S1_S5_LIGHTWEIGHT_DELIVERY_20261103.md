# Andy Commerce R10-S1–S5: lightweight ordinary coupon policy

Date 2026-11-03. Scope frozen by Owner: **Andy Commerce selects at most one
ordinary positive discount coupon, verified customer manual beats vendor
automatically applied ordinary**. WooCommerce / WebToffee / StoreApps /
YayCurrency exclusively own coupon math, store credit balance, BOGO
gift quantity, stock, orders, tax, FX, payment and refunds.

Source branch: `feat/commerce-r10-s1s5-lightweight-20261103` from parent
Draft PR #213 exact HEAD `eae451da605555aec59d73491cc1e73476348193`.
This child is source/CI-only; sole Dev publisher remains PR #213.

## Stage contracts and evidence
| Stage | Contract | Evidence/state |
| --- | --- | --- |
| S1 | Freeze non-goals and native ownership | This document; no credit/BOGO/FX/refund handlers |
| S2 | Trusted manual origin, one ordinary, manual replaces only proven vendor auto | Narrow `R10S_Light_Coupon` opt-in adapter; real isolated native tests |
| S3 | Preserve credit, shipping, gift and unknown metadata | Paid WebToffee Store Credit coexistence, native free shipping, `individual_use` rejection, provenance ambiguity fail closed |
| S4 | Local Woo Classic candidate + Store API + FX | Windows native Classic WC cart/request tests; REAL HTTP signed Cart-Token Store API 5/5; **Dev browser and real payment/order NOT YET PASS** |
| S5 | Reviewed source/CI, rollback/Dev UAT packaging handoff | New source ZIP gate and 6 pure default-off tests; **Dev-installable ZIP only after #213 exact-head prepare and Owner approval; not deployed** |

## Mandatory gates
- Global `Andy_Commerce_Observation_Mode` prevents any business-module load.
- Existing `andy_commerce_r10c_coupon_enabled_v1` must be yes **AND**
  new `andy_commerce_r10s_light_priority_enabled_v1` yes;
  default is OFF without any options migration.
- Localhost requires exact marker/constant and exact paid vendor versions.
  Dev `dev.beyourlover.com` additionally requires staging environment
  and a separate server-only `ANDY_COMMERCE_R10S_DEV_UAT_AUTH` literal
  `OWNER_APPROVED_R10S_DEV_UAT`; www still requires separate production
  server authorization. Do not install without Owner approval.
- No coupon calculation override, ledger/stock/order/payment hooks or
  automatic backfill. Unknown coupon category, unverifiable origin,
  multiple manual codes, dual-vendor provenance, stale cart or
  individual-use protected collision **deny** rather than evict rights.
- Disable only new option `andy_commerce_r10s_light_priority_enabled_v1`
  to revert to existing R10-C behavior; disable existing Coupon option or
  enter global OBSERVE to restore no Andy Commerce coupon hook. The known
  Dev 1.1.0-rc2 ZIP is the package rollback; never overwrite a site with
  a child-branch preview ZIP.

## Windows owned WordPress evidence
WooCommerce 11.2.1, WebToffee Smart Coupons Pro 3.2.0,
StoreApps Smart Coupons 9.51.0, YayCurrency Pro 3.3.5,
localhost `127.0.0.1:14141`; licensed code stays on Windows.

- Native actual WC cart and Woo Classic AJAX coupon nonce tests: first manual accepted/second denied; verified
  WebToffee and StoreApps auto discount replaced by manual; WebToffee
  store credit and free-shipping entitlement preserved; individual-use
  cannot evict protected credit. Invalid Classic nonce and dual-vendor
  claim cannot authorize manual replacement. Module OFF preserves native
  two-ordinary-coupon behavior. No real customer/order/payment writes.
- Real Store API `GET /cart`, `POST /cart/add-item`,
  `POST /cart/apply-coupon` with genuine signed Cart-Token;
  real WebToffee auto ordinary pre-applied -> manual successful HTTP 200,
  second manual denied HTTP 400. Native final cart contains only manual.
- Actual Store API synthetic sandbox exchange rates: USD 95.00,
  EUR 87.40, GBP 76.00, AUD 144.40, CAD 128.25;
  all five HTTP scenarios PASS, not live FX, no real gateway or refund.
- Pure zero-hook default OFF/parent OFF/idempotent registration suite 6/6.
  R10-H original isolated policy harness 13/13 and H3C1 ticket 14/14.
- Tests dispose only their own fixture posts. CI never checks in
  paid plugins, DBs, credentials, email or live user information.

## Remaining Owner UAT / release acceptance (NOT claimed PASS)
1. Review this child Draft PR and integrate reviewed exact HEAD into sole
   parent Draft #213 **without main merge**.
2. Run `scripts/prepare-andy-commerce-dev-release.sh` on exact remote
   PR #213 with latest Currency #207 byte parity and inspect ZIP checksum.
3. Preserve installed known-good Dev 1.1.0-rc2 rollback ZIP and record
   controller/ZIP sha256; authorize Dev-only install explicitly.
4. Dev front-end Classic and Blocks/Store API manual-before/after
   automatic, KOL ordinary, protected credits/BOGO/free shipping,
   no-cookie, invalid nonce and five currencies; actual payment test
   only with separately approved payment UAT.
5. Confirm Option OFF / global OBSERVE returns exactly native behavior,
   zero Commerce coupon hooks, no order/credit/stock changes, then
   ask Owner for visual and transactional acceptance. WWW and main
   remain unchanged until separate pass.

## Separate vendor risk register (not R10-S scope)
Issue #276 stays OPEN. H3-D1 paid StoreApps local-CLI persisted credit
100/75 mismatch needs native full HTTP checkout verification; H3-D2
WebToffee gift cart 3 with stock 2 is rejected by Woo stock gate.
Neither is evidence of Andy Commerce having taken ownership of
those engines. Their existence is not solved by source-level R10-S CI.
