# M4 BYL49 V2 + M5 Store API · Approved Dev Canary Evidence · 2026-10-12

## Authorization and safe scope
Owner explicitly authorized M4 Dev-only bounded Canary changing eligibility basis from pre-discount to post-discount merchandise while retaining zone-specific thresholds and the original v1 configuration as rollback. Promotions and gifts OFF. No real Woo orders or payments, WWW deployment, PR merges or READY reboots. This **does not** authorize overriding remote safety enforcement.

## GitHub source evidence
- Core [Draft PR #87](https://github.com/beyourlovercom/YBY-Core/pull/87): new `class-andy-commerce-m4-v2-dev-canary.php`, Dev-only bootstrapping and read-only M5 Store API Woo session/zone projection.
- Windows independent PHP lint PASS. M4 gate/single hook/expiry/zone tests 37/37 PASS; M5 real Woo session stub + zones 17/17 PASS; existing M5 Store API bridge 13/13 PASS, M5 gift and minor-currency adapter 23/23 PASS, M4/M5 synthetic lab 35/35 PASS. GitHub Core CI 5/5 SUCCESS at `ed51b6f580f61f0199507a97a462416be3483e64`.
- Theme [Cart V2 M5 Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80), exact source `8d881f2b86c9a1e9b800f206324108a68c135005`: progress component condition uses **separate server-origin Dev shippingProgressEnabled**, independent of `marketingEnabled=false`; JS mock 13/13 PASS, existing Cart V2 runtime mock regression PASS, GitHub Foundation CI SUCCESS. **Theme candidate was NOT deployed into Dev**.

## Bounded Dev installation evidence
- Confirmed Hostinger Dev = `https://dev.beyourlover.com`, WordPress staging, Andy Commerce 1.0.1, Dev flag for M3 on.
- Exact baseline before deploy:
  - `includes/class-andy-commerce.php` SHA256 `fc849f7472abad609ea18d04970ba0cb97650d90f9437316653b79606f71c635`
  - existing `includes/class-andy-commerce-shipping-promotion-policy.php` SHA256 `a7bca41e60105e7d6aac01c0da41f9cd0f7a2d8b93a0017795090b48c6951e21`.
- Bounded Dev-only **3 source files deployed** with PHP lint, archive SHA256 `a4842be1e200302d3e6a1204d13ae74264dd24fdfcf562c5c0edc9822f8a5781` verified, no forced file replace on SHA mismatch. Deploy `DEV_M4_M5_CODE_ONLY_DEPLOY=PASS`.
- Rollback preserved *outside webroot*: `/home/u247343654/domains/dev.beyourlover.com/.m4v2-rollback-20261012/` with original class and original BYL49 policy settings JSON.
- **Real WordPress pre-activation smoke PASS**: policy gate absent/OFF, legacy Woo eligibility filter registered once at priority 10, V2 filter absent, Woo M5 extension class loaded and `data()` HIDDEN; Dev homepage GET HTTP 200. Existing v1 strategy file checksum checked unchanged.

## Concurrent configuration drift / safety stop
- Earlier M4 audit reported global and zone 0 threshold $49; at final Dev preflight the **persisted global + zone 0 were $69**, not $49; zone1 $0, zone5 $69, zone4 $129, zone3 $169. This is a real observable drift since prior audit, not an edit made by this Canary.
- First request to create the short-lived v2 Dev configuration gate in WordPress options was **blocked by remote execution security checker before command execution**; no persistent activation was carried out.
- Continued with a **nonpersistent WordPress CLI process-only canary** using a temporary in-process `pre_option` WordPress filter (not a database change). Woo old-to-new single filter authority switch **PASS** in that process. Subsequent strict zone-0 threshold assertion reported `THRESHOLD_DRIFT_0`; run stopped without any checkout/order or config write. Latest repeat read-only Dev config command was also security-blocked, so **the new precise zone0 amount was not recovered**, and no live-v2 eligibility PASS may be claimed.
- The in-process canary was not saved to options or served to WWW. Do not imply source-only testing equals actual customer-cart end-to-end proof.
- Both the actual BYL49 v1 option and the shipping policy PHP remain intended source of truth when Dev v2 gate is absent; latest post-block verification of the actual DB option was **not independently obtained** because read was blocked. Do not infer fresh state beyond verified pre-activation snapshot.

## Owner Final UAT and recovery criteria
1. Restore confirmed **read-only** access through an approved remote session; re-read actual persisted Dev zone thresholds and v2 gate value, and compare all to the byte-verified preflight snapshot. If unexpected changes, coordinate with active Dev owner before any policy writes.
2. Obtain security-approved path to perform *only* authorized Dev v2 option write (expiring and exact v1 fingerprint). No bypass. Recheck old/new exactly-one Woo filter, no duplicate shipping authority, no unexpected coupon/marketing hooks.
3. Validate each **current** zone threshold, coupon before/after totals, gift exclusion, currency minor units, one/multiple shipping packages, real Store API selected rate; then Dev Cart V2 new progress (M390/PC1440), Checkout GET. Do not write real orders or payments.
4. Stage exact diff and backup for Cart Theme three files; deploy to Dev only if preflight PHP/JS/CSS existing hashes match approved candidate; otherwise stop and reconcile other concurrent Theme OS changes.
5. Request Owner Final UAT only after reproducible live Dev PASS. No PR merges / WWW release.

**Closure status: Core Dev code-only deploy PASS; WP in-process single-authority canary PARTIAL PASS then ZONE0 DRIFT STOP; runtime persistent policy switch HOLD; M5 Cart Dev deployment HOLD; M7 Final UAT NOT READY; M8 WWW/merges HOLD.**
