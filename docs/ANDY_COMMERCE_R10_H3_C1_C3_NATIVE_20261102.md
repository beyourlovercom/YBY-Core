# Andy Commerce R10-H3-C1/C2/C3 — Native request auth, five-currency Store API, late rollback

Date: 2026-11-02. **DEV/WWW unchanged, SOURCE-ONLY candidate, no live hook registration, no main merge.**

## Baseline and strict release authority
Parent Draft PR #213 exact baseline `ea957cc9e57e6200bd75e7f04b5ec376d45acf3d`. Dev Andy Commerce is `1.1.0-rc2`, global `observe`, R10-C Shipping=`no`, Coupon=`no`. www remains `1.0.14` OBSERVE. No owner approval for plugin install, OBSERVE exit, active Woo Coupon/Checkout Hook, real customer changes, main merge or payment orders.

Local Windows sandbox (ONLY): `D:\ai\r10b-vendor-uat\disposable\www`, marker `.andy-r10b-isolated-local`, URL `http://127.0.0.1:14141`, isolated MySQL 14041, WooCommerce 11.2.1, YayCurrency Pro 3.3.5, WebToffee Pro 3.2.0, StoreApps Smart Coupons 9.51.0. Licensed vendor plugins have not been copied into GitHub.

## H3-C1 SERVER authorization — verified but NOT deployed
New file `packages/andy-commerce/includes/class-andy-commerce-r10h3c1-manual-ticket-shadow.php` implements request-local, cryptographically random **one-use** ticket for a server-trusted handler. A Classic POST requires its separate **verified Woo cart nonce**, because WC_Form_Handler::update_cart_action coupon branch does not enforce a nonce locally. A Store API POST must match the exact cart/apply-coupon route and hold a verified Woo Store API nonce OR a valid signed Cart-Token whose issuer and user_id match the currently loaded session. The ticket binds coupon code, exact Woo cart/session identity and ordered currently applied coupon snapshot. Each consume destroys the ticket before checking input; stale, replayed, wrong-code and changed-cart requests deny. This code never reads a browser-supplied origin_verified flag and never mutates a cart.
- Native actual WC/wp_create_nonce/WP_REST_Request/signed Woo CartToken test **18/18 PASS** in owned sandbox. No real HTTP coupon handler integration, orders or payment.
- Portable pure contract **14/14 PASS** on stub runtime, suitable for GitHub CI.
- **Limitation**: future actual Classic and Store API trusted handler integration and matching outer Woo request lifecycle still requires a separately approved, default-OFF adapter. This source class is NOT imported or registered in plugin bootstrap.

## H3-C2 five-currency normal coupon — actual HTTP Store API
The YayCurrency configuration is stored as published post type `yay-currency-manage`, NOT ordinary `wp_options`: local fixture IDs USD=10, EUR=20, GBP=21, AUD=22, CAD=23. Local **test fixture rates**, not current real-market exchange rates: USD 1.00; EUR 0.92; GBP 0.80; AUD 1.52; CAD 1.35. Existing local fixture was preserved; no rates were invented or altered.
A WP-CLI request reports correct selected currency but WC CLI totals stay in USD. Therefore FX was verified using REAL FRONT-END HTTP Store API `GET /wc/store/v1/cart`, `POST /wc/store/v1/cart/add-item`, `POST /wc/store/v1/cart/apply-coupon`, actual signed Cart-Token and independently selected YayCurrency cookie, with real local product USD100 and ordinary fixed-cart coupon USD10.
- USD: before 100.00, discount 10.00, total **90.00**.
- EUR: before 92.00, discount 9.20, total **82.80**.
- GBP: before 80.00, discount 8.00, total **72.00**.
- AUD: before 152.00, discount 15.20, total **136.80**.
- CAD: before 135.00, discount 13.50, total **121.50**.
All HTTP requests returned correct selected currency and discount coupon; **5/5 native Store API ordinary coupon currency parity PASS**, with both licensed Smart Coupon plugins activated. Local test product ID=139 and coupon ID=140 were intentionally temporary, not business records.
- Earlier localhost-only PHP single-thread server GETs initially timed out because the request queue accepted internal WordPress Cron/loopback requests. A separate owned PHP test process with logs showed EUR HTTP200 and repeat GET cart returned all five currency codes; this is NOT evidence of Dev/www outages.
- **Limitations**: no real WebToffee/StoreApps Store Credit redemption, BOGO gift inventory, individual-use collision, paid checkout/gateway, invoice/AfterShip, or real shipping quote/tax-country matrix has been certified by the normal-coupon parity result. Existing local FX rates are test rates only.

## H3-C3 actual late post-manual failure with each real licensed vendor
Actual local WebToffee and StoreApps auto engines were first proven to apply automatic ordinary coupons and write their true provider session histories (H3-A). In separate synthetic Woo carts, each vendor then applied real auto coupon, native free-shipping coupon and successful manual coupon (H3-B provider-specific replay guards and targeted session cleanup were applied). A controlled **late error after successful replacement** was then simulated.
A test-only restore mechanism reinstates the exact original coupon ORDER using native WC_Cart::set_applied_coupons(), both vendor session-history keys plus StoreApps candidate cache and chosen shipping method, restores Woo notices, calls calculate_totals, and compares the full native WC totals and notices using existing H3-C complete snapshot.
- **StoreApps 21/21 PASS** (including coupon order / WC totals / notices / provider session+shipping exact).
- **WebToffee 20/20 PASS** (same exact fields). These tests represent a materially stronger proof than the old naive remove/re-add approach that reordered coupons.
- Native ordinary-coupon isolated late error probe also returned EXACT recovery for coupon order, native total/tax/discount/shipping, Woo notice and session. No orders/payments.
- **Limitations**: the restore logic is intentionally in isolated test scripts ONLY; it has not been hardened into a production Woo handler/transaction adapter and is not guaranteed safe for credit ledgers, free-gift stock, payment actions, refunds or a crash after external webhook side effects. Do not call these real live transactions accepted.

## GitHub regression / release contract
New C1 source stays unregistered. Official ZIP contract verifies exact source bytes but rejects C1 bootstrap import/call; all new C1/C2/C3 scripts are linted, and C1 pure one-use auth harness runs in GitHub CI. Exact paid-vendor tests remain **Windows-owned sandbox execution evidence** because licensed vendor binaries are not bundled in public GitHub Actions.
Any CI green state must NOT be treated as enabling Coupon ON or approval to exit OBSERVE.

## Open blockers
1. H3-C1: true server-interceptor integration inside Classic/Store API, including session/nonce/cart-token and one-use tickets, guarded default OFF; no browser-origin flags.
2. H3-C2: real Store Credit consumption, BOGO/gifts/individual-use, country shipping/tax and five-currency checkout/gateway tests on exact licensed vendor stack. Plain fixed-cart coupon test is already 5/5 PASS.
3. H3-C3: production-grade guaranteed rollback for the entire paid-vendor credit/gift ledger, idempotent external side effects, checkout retries, notices and concurrency.
4. Separate Owner release approval for any new Dev version or active transaction Hook; www and main are still outside scope.

**Verdict: H3-C1 authenticated source contract PASS; H3-C2 genuine five-currency normal-coupon Store API parity PASS; H3-C3 real ordinary automatic/manual replacement late rollback in both vendors PASS. FULL H3 paid-vendor credit/BOGO and live transactional rollout remains OPEN. Keep Issue #276 OPEN.**
