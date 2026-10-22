# Andy Commerce Marketing OS V1 — M4–M8 Independent Owner UAT Gate
Date: 2026-10-12
Canonical repo: beyourlovercom/YBY-Core
Status: **IN PROGRESS; NOT final Owner PASS**

## Source / baseline
- PR #80 M3 Dev draft CRUD exact-head `af62c031ad3860de80ea6ed6c080e6c6f9fc3fad`: GitHub 5/5 CI success, Owner **visual list V2 PASS only**; remains Draft/unmerged.
- PR #81 M4 pure shadow exact-head `ebdb1df40d0a72448360b5e074eff5fb90d8811c`: local PHP M4 harness 10/10 PASS.
- PR #82 M5 pure progress contract updated after initial audit: local PHP M5 harness 11/11 PASS, requires a Woo-selected shipping rate ID. Reconfirm latest SHA/CI before closure.
- Cart V2 website PR #63 `30ee230d7e1001cc21afd3c374d8c50431c5ff89`: Foundation CI PASS; **Draft/unmerged; no final Owner UAT evidence**.
- Theme T4 composite website PR #79 `540c82f80f63c9ec1b8dc5efefcb439a37a7c5d1`: source-only, CI PASS; not deployed. Cart/Theme integration must follow this bootstrap to avoid overwriting Drawer.
- Dev read-only (Windows DC via Hostinger SSH): `dev.beyourlover.com`, WP 7.1.3, Woo 11.2.1, Andy Commerce 1.0.1 active, WP_ENVIRONMENT_TYPE staging and M3 Drafts flag ON. This is **not** evidence of M4/M5 runtime deployment.
- BYL49 existing `pre_discount_merchandise_subtotal` remains authoritative. Proposed `post_discount_merchandise_subtotal_excluding_tax_and_shipping` is NOT active.

## M4–M8 gate matrix
| Stage | Objective | Minimum pass evidence | Status |
|---|---|---|---|
| M4 | Legacy/new BYL49 shadow; no mutation | deterministic threshold divergence; unchanged Woo settings and policy SHA; one shipping authority | OFFLINE tested; runtime handoff HOLD |
| M5 | Woo-selected-rate progress without claiming awards | rate ID, Woo Store API cart truth, missing-rate/legacy/multipackage hidden, Cart V2 reuse | OFFLINE tested; Dev Cart binding HOLD |
| M6 | Non-order synthetic Canary | exact PHP harness, zero Woo writes, gift/discount OFF, G2/checkout unchanged | check `marketing-m6-nonorder-integration-harness.php`; no real cart POST |
| M7 | Owner visual/functional UAT | Desktop 1440 and Mobile 390; draft reload, list/archive, no false shipping bar; Cart GET only | NOT PASS; owner action pending |
| M8 | Closure / distinct rollout gates | exact SHA, GitHub CI, regression evidence, Owner UAT, independent merge approval, independent WWW approval | NOT PASS; no merge or WWW |

## Dev Owner review links
1. [Andy Commerce Marketing draft admin](https://dev.beyourlover.com/wp-admin/admin.php?page=andy-commerce&tab=marketing-promotions). Existing M3 Dev-only screen; test reload, draft/archive/search/revision and OFF state. No publish.
2. [Existing Cart Drawer V2 Dev-only preview](https://dev.beyourlover.com/?byl_cart_drawer_preview=1). Verify existing Woo-native shipping status only. Do **not** expect M5 promotional progress: BYL49 legacy basis is still active and M5 must fail closed.
3. [Existing Woo Cart GET](https://dev.beyourlover.com/cart/). Confirm neutral fallback, totals consistent after Woo response.
4. [Checkout GET](https://dev.beyourlover.com/checkout/). Navigate only, no order placement or payment.

## Required UAT observations (no commerce writes)
- M390/PC1440: list V2 typography and pagination, 3 draft + archived sample visibility, actual saved state and all promotion toggles OFF.
- Empty Cart: no marketing placeholder; gift OFF, no fictitious $49 bar.
- No address/no rate: neutral shipping text; selected free shipping must come from Woo and not from shadow threshold.
- Coupons and cart mutations remain under existing Cart V2 workstream and require approved synthetic Dev-only fixtures before any POST; this Marketing branch does not duplicate Cart V2.
- Browser console error count and screenshot evidence to be attached only after actual captures; never label absent screenshots PASS.

## Non-negotiable go/no-go
M4 migration from old to new BYL49 calculation has a dedicated policy/version/rollback authorization gate. Do not infer it from routine Dev-only development permission. WWW private server has no SSH and is out of scope. No gift, BOGO, bundle, Spend & Save activation. Do not merge PRs #80–82, this isolated candidate, Cart #63 or Theme #79 without separate exact-head Owner approval. Never reboot READY devices.
