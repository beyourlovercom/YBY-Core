# BF2026 R16 — P0 paid Gift removed on Cart reload

Date 2026-11-11. Source PR: draft, WWW global Master OFF / admin Canary OFF. This is a **repair**, not a new promotion launch.

## Production reproduction and safety
- User-authorized WWW Canary on existing v0.2.2-dev-rc3. Native retail cart USD138, Heart +USD10 and Camera replacement +USD15 both initially successful; add Ariel USD79 (USD217 retail, USD232 total including Camera).
- Normal full Cart-page reload **removed Camera**, reducing total to USD217, although admin Canary was still active. No order/payment, all test products removed, Canary stopped. Evidence Issue #311.
- Exact mechanism to validate: WordPress REST cookie handling demotes an authenticated principal when no `X-WP-Nonce` is present; the Store API uses its own nonce/Cart-Token. Original R14 Canary identity check could return false for an otherwise signed, authorized browser during REST cart lifecycle. Host Master-OFF gift-line cleanup and R15 reconcile could remove it.

## Minimal R16 changes
1. In `BYL_BF2026_R14_Canary::granted()`, permit **only WordPress REST requests** with a valid, independently verified WordPress `logged_in` cookie, matching the active operator user ID, `manage_options`, and the separate current signed 48-hex Canary cookie and nonexpired server-side SHA256 token. Trust neither Cart-Token nor an unverified cookie nor an anonymous claimed user.
2. Attempt narrowly scoped Canary bootstrap at Woo `woocommerce_cart_loaded_from_session` priority 1 before Andy Campaign host cleanup at 95. Existing `init` bootstrap remains and singleton guard prevents duplicate hooks. Host Master must still be OFF and registration state exactly `master_off`.
3. In R15 `reconcile`, if shipping country or SOURCE USD quote is transiently unavailable during cart restoration, **don't destructively remove** the chosen gift; unresolved country is blocked at checkout. Definite country CA or underthreshold still revokes.
4. Retain existing USD promo SKU whitelist, stock check, coupon protection, no warehouses assigned by plugin, and 30-minute signed operator-only release.

## Verification gates
- Unit mocks: R16 17 REST cookie/role/account/token cases PASS, R14 19 unchanged, R15 43 with transient missing-address and decisive CA revocation.
- Three independent Dev WP-CLI processes **on previous RC3** verified real Woo persisted session and user product categories can be rehydrated; this is a **baseline** and does not prove the R16 HTTP identity fix.
- Next: Install candidate RC4 **Dev only**, test REST no-wp-nonce and normal Woo HTML/full reload across distinct real HTTP requests, signed administrator and anonymous isolation, then GitHub CI on exact-head and canonical ZIP.
- Then request Owner's manual WWW v0.2.3-dev-rc4 upgrade, 30-minute signed Canary recheck; after successful tests stop Canary, clear test cart.
- **Never:** enable global Master, pay, create genuine WWW orders, rewrite product sale prices, alter any physical warehouse gift SKU.

## Other release gates not in R16
- ERP inbound order gift-meta P0: separate ERP Issue #18.
- USA-only clearance price and FX/coupon attribution checks.
- Black Friday landing FAQ text still mentions free gift for all international orders; update only after approved copy QA.
