# Andy Commerce R10-G/H/I/J — Read-only preactivation audit (2026-11-01)

**Current authorization:** Dev runs Andy Commerce 1.1.0-rc2 ACTIVE with global OBSERVE and R10-C Shipping=no/Coupon=no. GitHub PR #213 remains Draft, main unmerged. No Dev/www transaction activation or extra Dev install approved.

## G: legacy bootstrap — hard blocker to OBSERVE exit
The plugin entrypoint returns early under OBSERVE. When OBSERVE is exited, the main Commerce boot invokes Shipping Promotion Policy/Runtime, Currency Runtime C1, Currency Dev V1, Marketing P2/P5 and other legacy modules, independently of the new R10-C module flags.

The old Shipping Promotion Runtime registers Woo free-shipping availability, chosen method, virtual coupon, individual-use, cart-session and Store API callbacks. Currency modules, if separately armed, may register price, coupon, shipping or payment-blocking hooks. Marketing P2/P5, if independently armed, may register coupon, gift and Classic/Store API checkout callbacks. Thus R10-C Shipping OFF / Coupon OFF is **not** an overall legacy-hook OFF switch.

**Critical actual Dev read-only finding:** Pure legacy Shipping Policy methods (no boot/hook registration) returned settings_source=byl-platform-legacy, persisted settings valid=true, runtime_active=true. BYL_Shipping_Promotion_Runtime class was not loaded in the current OBSERVE request. Therefore exiting OBSERVE would cause the old Commerce Shipping Runtime to boot with a valid legacy shipping policy, potentially affecting real free-shipping quotes even with R10-C Shipping OFF.

The live R10-C class was NOT loaded; R10-C own shipping/coupon callbacks=0. Dev Currency Runtime and R13 Canary option enabled=false; Marketing P4 cap=no. Existing vendor/Woo registered callbacks still include coupon_is_valid=24, Store API checkout update=7. Do not attribute all to Andy Commerce. G source-only invariant harness **12/12 PASS**, proving the collision risk is a real code property, not safe deployment approval.

**G verdict: BLOCKED from global OBSERVE exit.** Need a future source-only, fail-closed legacy runtime allowlist/quarantine and a disposable full-Woo acceptance before any Owner-approved Dev activation.

## H: ordinary coupon order — policy open
R10-H source-only isolated PHPUnit-free PHP contract **13/13 PASS**. Tests the first and second ordinary codes, reverse order, auto-first, manual-first, same code repeat, valid free shipping, nonstandard Store Credit, zero-value discount, existing vendor rejection, fail-closed invalid code, OBSERVE and module OFF.

Current R10-C code is **FIRST POSITIVE ORDINARY COUPON WINS**. An automatic ordinary coupon applied first blocks a customer's later manual coupon; a manual code applied first blocks a later automatic coupon. Marketing P2's own sandbox prefer_manual_coupon logic is a different gated module, not evidence that the two policies combine correctly. Actual Dev WebToffee/StoreApps BOGO, auto-apply, gift and Store Credit behavior remains unproved.

Owner decision: (A) accept first-applied-wins including an auto-first rejection of manual entry; or (B) favor customer manual codes over automatic ordinary codes, preserving native free-ship/credit/BOGO entitlements. B needs additional code, idempotence/coupon-origin regression and a separate Owner release pass; not implemented now.

## I: read-only storefront smoke
From Windows, HTTPS GET: /, /denglu, /shop/, /cart/, /free-shipping/, /wp-json/wc/store/v1/cart each HTTP 200. Empty anonymous /checkout/ redirected to /cart/ HTTP 200, which does **not** prove checkout shipping, tax or payment with an actual cart. No cart/add-item POST, apply-coupon POST, checkout/order, payment or stock action permitted/performed. Existing Windows isolated genuine Woo 5-currency 40/40 and local Store API 11/11 results are separate sandbox evidence, not actual active Dev Checkout certification.

A single read-only www homepage GET from the Windows network returned HTTP 445; do not infer a WWW outage from it. No www changes occurred.

**I verdict: read-only smoke PASS; complete browser-with-items/Checkout acceptance OPEN, gated by G/H.**

## J: recommendation and Owner gate
Do not approve WWW installation, global OBSERVE exit, or R10-C modules ON based on this evidence. Next approve only source-only R10-G2 legacy-runtime isolation development and select A/B coupon priority. Keep existing Dev 1.1.0-rc2 / OBSERVE / both OFF, keep rc1 rollback, do not merge main. Any later Dev active transaction UAT must have its own explicit Owner pass. WWW observe-only install and live transaction/payment each require additional separate authorizations.

No new plugin deployment, site option changes, plugin license changes or checkout/order writes were made in this R10-G/H/I/J audit.
