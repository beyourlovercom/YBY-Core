# Andy Commerce R10-C-F — RC2 Dev Owner Approval Dossier (2026-11-01)

Status: SOURCE-PREPARED / OWNER GATE PENDING. No Dev RC2 or WWW installation is authorized.

## Current states
- GitHub integrator is Draft PR #213, main unchanged. Current Dev WordPress: Andy Commerce 1.1.0-rc1 ACTIVE, global OBSERVE, R10-C Shipping=no, Coupon=no.
- Dev exact versions ACTIVE: WooCommerce 11.2.1; YayCurrency Pro 3.3.5; WebToffee Smart Coupons Pro 3.2.0; StoreApps Smart Coupons 9.51.0. License keys were not copied or activated; extra-site licensing entitlement remains unverified.
- Dev six prior fixed-cart coupons, zero orders in prior final readback. Existing /denglu login should remain unchanged.
- WWW is outside this release scope: do not upgrade, activate Commerce hooks, exit OBSERVE, or merge main.

## RC2 delta
- R10-C-E Dev readback showed native Woo free_shipping instances: zone0 method11 USD69; zone5 method9 USD69; zone4 method8 USD129; zone3 method6 USD169; zone1 method2 unconditional. Old sandbox-only hardcoded zone0/instance1 would NOT work at real Dev settings.
- Scoped R10-C-E source dynamically verifies the exact native method in Woo package's matching zone; no guessing an instance, no overriding unconditional free shipping, special shipping-class exclusions, tax-inclusive or gift cases.
- R10-C-F is version-only: plugin header, PHP constant, readme and native-order assets cache prefix become 1.1.0-rc2. Official ZIP contract requires rc2; it must reject rc1.
- Original dual module switches both OFF unless explicitly opted in with Owner server authorization and exact vendor versions. Crucial global OBSERVE early return disables all R10-C hooks even if module options are misconfigured.
- One-ordinary-coupon policy remains FIRST APPLIED wins (including an auto-applied ordinary coupon); manual-override policy requires separate Owner decision and UAT.

## Evidence already completed
- R10-C-E real isolated Windows Woo using changed native shipping instance3 with original instance1 disabled: five currencies x eight scenarios 40/40 native eligible-quotes parity, 11/11 localhost Store API tests, exactly one free-shipping rate at qualifying thresholds, second ordinary code rejected, native free-shipping coupon allowed.
- Sandbox after test: original shipping instance1 restored, synthetic instance3 removed, both module flags OFF, zero orders, localhost 14141/14041 shutdown.
- Dynamic-zone source harness 12/12, global fail-close harness 10/10, bootstrap source contract 12/12, rc2 version-sync PASS, eight explicit observe-mode cases 8/8; PHP lint PASS.
- Real Dev exact coupons installed/active and vendor Woo callbacks verified; full live Dev checkout/BOGO/store credit/auto coupons not proved.

## Approval / Rollback / No-go
Before Dev install require exact-head CI SUCCESS for main Draft PR #213, official canonical ZIP and SHA256 attestation built in pristine exact-head worktree, independent checksum verification, header 1.1.0-rc2, no test assets or secrets.
Before replacement require exact Dev siteurl, plugin version 1.1.0-rc1, global observe, both R10-C flags no, Woo/vendor versions checked; create private 1.1.0-rc1 rollback archive outside public_html, preserve old 1.0.14 rollback.
After install verify plugin active 1.1.0-rc2, mode observe, both flags no, no R10-C class or transaction Hook loaded, /, /denglu, /cart and Woo Store API GET HTTP200, package-file parity. Stop on any failed gate; approved-only rollback uses verified private RC1 archive.
No payment/checkout order/stock/customer mutation, no production release; a later separate approval is required before any mode or module enable. Dev active WordPress plugins alone do not prove commercial license entitlement.

## Narrow recommended Owner pass
pass R10-C-F：批准 Andy Commerce 1.1.0-rc2 仅在 Dev 受控安装与只读验收；Shipping OFF、Coupon OFF、OBSERVE 不变；www、main、支付及交易 Hook 均不授权。

Approval status: NOT YET GRANTED.
