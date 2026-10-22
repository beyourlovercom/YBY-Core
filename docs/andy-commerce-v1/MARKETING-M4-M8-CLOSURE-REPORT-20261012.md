# Andy Commerce Marketing OS V1 — M4–M8 Closure / Final UAT Report
**Date:** 2026-10-12 (Asia/Singapore)  
**Status:** CONDITIONAL CLOSURE OF OFFLINE + NON-ORDER CANARY ONLY. **NOT** M7 Owner Final PASS, **NOT** full M8 Closure.  
**Source of truth:** `beyourlovercom/YBY-Core` GitHub; PRs remain unmerged.  
**Controls:** Dev only; WWW no SSH/deployment; no production order/payment; no real promotion activation; BYL49 old policy protected; READY devices never restarted.

## 1. Exact source and test references
| Stage | GitHub code | GitHub CI | Executed proof | Status |
|---|---|---|---|---|
| M3 Closure checkpoint | [PR #80](https://github.com/beyourlovercom/YBY-Core/pull/80) `af62c031ad3860de80ea6ed6c080e6c6f9fc3fad` | 5/5 SUCCESS | Prior M3 Smart Coupon list V2 Owner Visual PASS; Dev 3 drafts/1 archived all inactive | M3 visual milestone PASS, merge pending |
| M4 shipping shadow | [PR #81](https://github.com/beyourlovercom/YBY-Core/pull/81) `ebdb1df40d0a72448360b5e074eff5fb90d8811c` | 4/4 SUCCESS | Windows PHP lint PASS, 10/10 shadow harness PASS | Shadow PASS; migration HOLD |
| M5 progress projection | [PR #82](https://github.com/beyourlovercom/YBY-Core/pull/82) `ab3b998844f18db490013b0d88b435d2ef60f107` | 4/4 SUCCESS | Windows PHP lint PASS, 11/11 projection harness PASS; added mandatory Woo selected rate ID validation | Contract PASS; Cart binding HOLD |
| M6 non-order integration | [Draft PR #86](https://github.com/beyourlovercom/YBY-Core/pull/86) synthetic adapter and M4/M5 joint code | 4/4 SUCCESS before this documentation-only update; recheck newest head | Windows PHP 12/12; Dev off-webroot PHP 12/12; file/hash/DB-count invariants | Non-order Canary PASS |
| M7 | [Owner UAT gate](MARKETING-M4-M8-OWNER-UAT-GATE-20261012.md) | N/A | No final Mobile390/PC1440 marketing browser capture; Owner Final PASS not supplied | HOLD |
| M8 | [Issue #76](https://github.com/beyourlovercom/YBY-Core/issues/76) | N/A | No exact-head authorized merges, no production approval or campaign enablement | HOLD |

## 2. Verified Dev baseline and no-write invariants
Windows official BYL DC Remote accessed Hostinger Dev SSH with noninteractive credentials. Target path: `/home/u247343654/domains/dev.beyourlover.com/public_html`. Observed WP `7.1.3`, Woo `11.2.1`, Andy Commerce `1.0.1`, `WP_ENVIRONMENT_TYPE=staging`, M3 draft flag `1`. Active theme `beyourlover-v3`. Dev draft table counts before/after M6: `draft / active=0 / 3`, `archived / active=0 / 1`. Existing persisted BYL49 basis: `pre_discount_merchandise_subtotal`. Existing shipping policy SHA256 before/after Canary: `a7bca41e60105e7d6aac01c0da41f9cd0f7a2d8b93a0017795090b48c6951e21`.

M6 payload was transferred **outside webroot**, checksum verified (`6625d1ad044fa82e65cd834ff594bbba8776241204b794a46c7e4176cf53dc2d` for cross-platform ZIP), extracted only into a named temporary fixture, PHP `-l` passed and `M6_MARKETING_NONORDER_INTEGRATION_PASS=12` returned. The isolated ZIP and extraction directories were cleaned and `TEMP_CLEANUP_PASS` independently verified. No Woo hooks, theme files, API routes, coupons, options, shipping settings, cart state, checkout, payment or order data were written.

**What this does not prove:** The Dev front-end Store API/Cart connector has not been installed, the M4 policy has not been changed, M5 progress is not live, and no browser/visual tests of new M5 UI have been performed. Existing Cart V2 [PR #63](https://github.com/beyourlovercom/beyourlover-web-v3/pull/63) exact `30ee230d7e1001cc21afd3c374d8c50431c5ff89` and Theme T4 [PR #79](https://github.com/beyourlovercom/beyourlover-web-v3/pull/79) exact `540c82f80f63c9ec1b8dc5efefcb439a37a7c5d1` have SUCCESS CI but are Draft/unmerged. Final Cart Owner UAT remains unverified.

## 3. Test scenario coverage
M4 covers exact threshold, discounted divergence (e.g. pre-discount 6000 / post-discount 4500 vs 4900 threshold), gift exclusions, ineligible merchandise, malformed/negative amounts, overflow limits and no runtime bootstrap. M5 covers legacy policy hidden, verified Woo selection, lost/malformed rate hidden, integer remaining and percent, no gift/free-shipping award. M6 composes both in one synthetic non-order source, tests untrusted snapshots, no selected rate, duplicate selected packages, negative amount, empty cart, malicious rate ID, threshold reached without shipping award, and confirms all classes remain absent from WordPress bootstrap.

## 4. Defect and gating register
| Severity | Item | Impact | Safe disposition |
|---|---|---|---|
| P0 | Legacy BYL49 pre-discount basis differs from approved post-discount ex-tax/shipping basis | Could display or grant false threshold outcomes if silently changed | **Migration HOLD**; versioned, reversible, separate Owner policy authorization required |
| P0 | Cart Drawer #63 not merged, Theme T4 #79 source-only, no final Cart Owner UAT PASS | M5 may break Cart if independently injected | Reuse existing Woo Cart V2/Theme bootstrap; no second cart; run independent end-to-end Dev UAT |
| P1 | M5 Woo Store API adapter and actual Cart/Checkout consumer not wired | No visible M5 progress | Keep hidden/OFF; implement only after migration authority and verified Woo selected-rate provenance |
| P1 | Multi-package shipping rates intentionally fail closed in adapter | Progress may be hidden for split shipments | Later add package-aware policy contract; no fabricated status |
| P1 | M7 Mobile390 and PC1440 screenshot/browser evidence absent | Cannot claim Owner Final UAT PASS | Owner must review Dev pages; capture real screenshots and browser logs |
| P1 | Independent GitHub main/pr/source/version reconciliation pending | PR #86 integration candidate contains independently open PR #81/#82 source | Do not merge integration PR as an upstream shortcut; reconcile exact main/CI and avoid duplicate runtime hooks |
| P2 | Temporary Windows ZIP V1 had incompatible path separators | First off-webroot Dev fixture extraction failed; no Woo files affected | Rebuilt V2 cross-platform ZIP, 12/12 PASS, cleanup independently PASS |

## 5. Dev Owner Final UAT entry points
- [Marketing OS Dev admin](https://dev.beyourlover.com/wp-admin/admin.php?page=andy-commerce&tab=marketing-promotions) — existing authenticated M3 campaign-list UI, visual PASS previously restricted to V2 list. Check saved draft state, list filters, archive and inactive flags; never publish.
- [Cart Drawer V2 opt-in preview](https://dev.beyourlover.com/?byl_cart_drawer_preview=1) — existing Woo-native Cart workstream only; no claim of new M5 progress activation.
- [Woo Cart fallback](https://dev.beyourlover.com/cart/) — GET-only.
- [Checkout](https://dev.beyourlover.com/checkout/) — GET navigation only; no Place Order/payment.

**Owner Final UAT status: NOT PASS.** M4/M5 final visual tests cannot begin until distinct migration and runtime permissions are resolved, and Cart/Theme compatibility is verified.

## 6. Closure decision
**PASS (limited):** M3 original visual milestone, M4 read-only comparator, M5 offline projection/selected-rate fail-closed, M6 synthetic Windows + off-webroot Dev Canary, GitHub current pre-report CI, cleanup, readonly DB/policy invariants, GitHub/Notion audit trail.

**HOLD:** BYL49 new policy migration, M5 true Store API + Cart/Checkout UI integration, M7 actual final Owner browser UAT, M8 exact-head PR merges and production rollout. All real BOGO, gifts, bundles, spend-and-save and shipping promo activations remain OFF. WWW remains untouched.

**Next authorized action:** retain independent Draft PRs; obtain a distinct Owner decision on Dev-only BYL49 policy migration before any semantics change. After Cart V2 runtime remains authoritative and M5 is genuinely integrated, perform real M390/PC1440 Owner UAT. A new latest-head CI/evidence review and separate explicit merge/WWW approval are required for M8.
