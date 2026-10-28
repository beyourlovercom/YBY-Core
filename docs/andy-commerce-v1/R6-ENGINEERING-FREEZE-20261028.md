# R6 ENGINEERING SCOPE FREEZE — 2026-10-28

**Decision:** R6 is formally **FROZEN / DEV TECHNICAL INTEGRATION SIGNED OFF**. This is **not** a Black Friday public launch, successful purchase, refund, payment certification, or permission to merge PRs. The user explicitly instructed freezing R6 now and advancing separate R7 (payment) and R8 (gift shopper flow) workstreams in parallel.

## Freeze baseline (readback at freeze)
- Commerce sole publishing branch **Draft PR #213**, exact source baseline `e768909f80a06360e152087daf7de2c9ebbfccbc`, 6 applicable CI SUCCESS, Currency Preview SKIPPED by design.
- Independent Campaign/Black Friday pack **Draft PR #242**, exact source baseline `70df9eaccded26f4cfcab921080abac83575abed`, 6/6 CI SUCCESS.
- Core menu adapter **Draft PR #248**, exact source baseline `78429706c8017541c36ca6b1e99659ad3ff61831`, five applicable CI SUCCESS; Currency Preview SKIPPED by design.
- Dev `dev.beyourlover.com` integrated via source-verified minimal modifications instead of replacing the Commerce ZIP, because concurrently managed Currency R13 Canary is different from PR #213 ZIP. Self-hosted DC SSH readbacks and rollback backups have already been recorded. WWW/Production untouched.

## R6 closed and immutable by default
1. Andy Core owns landing-page CPT and WordPress admin menu; Andy Commerce owns payments/currency/coupons/checkout; independent Andy Campaign owns reviewed Code-First marketing packs. No duplicate coupon engine or PHP editor.
2. Commerce V1 legacy Marketing and new Campaign have fail-closed mutual exclusion, conditional navigation, reversible independent plugin activation, scoped/verified backups. New master and old switches stay OFF on Dev.
3. BF2026 entitlement: one optional customer-chosen gift; **two only when source USD tax-excluded post-coupon merchandise >= USD199**, excluding shipping and gift upgrade fee; two distinct free in-stock simple SKUs required before activation, optional premium paid upgrade. Default gift catalog EMPTY.
4. Verified FX session/Store API cart five currencies (USD/EUR/GBP/AUD/CAD). Trusted Commerce-owned USD merchandise quote supports source-USD/tax-exclusive foreign carts with **at most one unrestricted native fixed-cart coupon**, many merchandise lines/quantities. Unsafe coupon/tax/rate inputs fail closed; no reverse conversion of shopper-visible FX totals.
5. Isolated Woo 11.2.1 validation: single item USD20 fixed coupon × 5 currencies × three $199 boundaries **15/15 PASS**; two-SKU/qty-two fixed coupon **20/20 PASS**; Commerce pure contract **58 PASS**; BF pure contract **27 PASS**; independent CI and core/checkout regression applicable checks PASS.
6. Draft `/gift/` page ID `40051` remains unpublished; Dev exact-path 404 guard prevents redirect to `/gift-selection-fee/`, while not affecting WWW or a future published gift page. OFF shortcode renders non-interactive notice without mutating cart.
7. Live Currency R13 Canary **must not be overwritten**; no new costs or platform workarounds; no unauthorized orders/refunds.

## R6 change-control (hard)
- **No additional R6 feature development** for percentage coupon, multi-coupon, multi-campaign, tax-inclusive, other gateways, large cart optimizations or additional landing-page functionality. Those become independent backlog items, not R6 reopening conditions.
- Only a reproducible **P0 safety/regression fix** can amend R6; separate exact-head review, tests, and owner approval for any production change. Keep all PRs Draft until R9/R10 signoff.
- R7 and R8 proceed in parallel; R9 depends on both. R10 release is a **separate approval** and may not be inferred from technical R6 signoff.

## Workstream handoffs and release gates
**R7 — Payment/FX/TEST/HPOS (unresolved, hard NO-GO).**
- Source/live R13 Canary `EXPECTED_ACCOUNT=acct_1RdAGvPKD6QEqtsG`; previous explicit authorized target `acct_1R57M7GR9MwFzg43`; presently connected Stripe TEST app `acct_1SrJdHCxlWhQx8Rb`. These are three different identities; **do not silently switch, rebind, change expected ID, or treat prior proof as live authorization**. Freeze until account/merchant authority is established via approved TEST path.
- Dev `woocommerce_stripe_settings` has `enabled=no`, `testmode=yes`, and a TEST secret present; WooPayments `enabled=yes`, `test_mode=yes`. R13 foreign canary is Blocks-only and Classic foreign Checkout is blocked. No payment intent/webhook/HPOS real order/refund created for this campaign.
- R7 must separately prove the permitted TEST merchant, matching key/verified account, checkout mode, five currency payments, provider callback, HPOS consistency, full/partial refund, duplicate webhook, rollback and isolation from WWW.

**R8 — Gifts/merchandising/customer UX (unresolved, hard NO-GO).**
- Owner-approved 2+ free SKU and premium SKU+upgrade fee; ERP/provider warehouse available stock, cost, product weight, destination restrictions and freight budget. Woo stock-status alone is inadequate when quantity is untracked.
- Dev real `/gift` page publication/landing-page QA only after content/catalog approval and OFF preview UAT. Checkout coupon, tax, stock, shipping, paid-upgrade consistency, mobile shopper selection and out-of-stock revocation must be independently signed.
- Open question: current picker is **optional** (checkout without a selected gift is allowed); Owner must sign whether “Every order gets 1 gift” means an entitlement or forced selection/default choice. Refund gift clawback policy also pending.

## Release invariant
**WWW untouched; Dev Campaign master OFF, legacy switches OFF, gift page draft.** R6 technical freeze must not be portrayed as R7–R10 completion.
