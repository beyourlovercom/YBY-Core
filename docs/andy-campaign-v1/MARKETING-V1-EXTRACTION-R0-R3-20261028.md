# Marketing OS V1 → Andy Campaign selective retirement, R0–R3

Owner decision: 2026-10-28. Source-only; not Dev/Production approval.

## Baseline authority and branch inventory
- Legacy mixed Currency + Marketing Dev publisher **Draft PR #213** at `feat/marketing-m10-policy-m9-bridge-dev-20261023`. This PR contains Currency R13, legacy Marketing V1, M15/M16, Dev simulator and source/ZIP tests. It is **not merged** and is the *sole authorized Commerce Dev ZIP publisher*, subject to its separate release gate.
- Separate **Draft PR #242** at `feature/andy-campaign-lite-c0-c2-20261027` owns Code-First Lite kernel and independent Black Friday Campaign Pack; Core-native landing_page content/editor and Core L2/L3 navigation are unchanged.
- This decision **does not install, rename or destroy** anything in Woo options/database, M15 tables, Dev/WWW, Commerce Currency or existing marketing UAT evidence.

## R0: V1 freeze — source disposition
| Commerce V1 source | Current constraints | Disposition |
|---|---|---|
| M7 Draft Studio | `andy_commerce_m7_inactive_drafts_v1`; Dev-only option; M10 policy and lifecycle coupling | **Retain as historical Dev preview**; do not migrate draft payload or clone admin editor |
| M9/M10 six-type & shadow policy simulators | USD-first, OFF-only, tied to Dev fixtures | **Preserve harnesses**; no live dual calculator; new events coded as independently versioned Packs |
| M11 real Woo cart reader | Woo snapshot and USD safeguards; read-only | **Reference for a future neutral read API**; no runtime relocation needed for BF2026 |
| M12 six shadow | Test-only, no live Woo writes | **Archive tests**; don't ship as Campaign runtime |
| M15 discount allocation | Cent-exact and per-SKU, rule-type/schema specific, USD minor | **Optional reusable pure algorithm**; candidate only if a real pack needs it |
| M15 cap ledger | MySQL advisory lock + InnoDB; tables `<prefix>aicc_m15_campaign_caps` and `<prefix>aicc_m15_campaign_claims` | **Do not migrate/rename/create tables now**; requires explicit schema/data authority and legacy claims reconciliation |
| M16 Woo order cap reconcile | Persists `_aicc_m16_test_campaign`/`_aicc_m16_test_claim`; requires USD, specified Woo statuses | **Never use as generic live claim handler unchanged**; redesign before future cap-limited pack |
| M-P2, P4, P5 Dev reward/coupon/order adapters | M-P1 disposable/dev identity, USD, sandbox/COD, M7 draft and M15 ledger | **Keep as Dev-only rollback evidence, default OFF**; never live-wire from Campaign |
| Currency/Stripe/Checkout/Shipping R13 | Commerce transaction authority | **Remain in Andy Commerce** and independently released by PR #213 |

No auto-migration, no database modification, and no marketing content rewrite is part of R0/R1/R3.

## R1: source-only boundary / publisher independence
- Root `AGENTS.md` forbids new Commerce Marketing V1 business features; PR #213 remains sole Commerce Dev ZIP publisher and retains its Currency code without modification from Campaign PR #242.
- Core-owned `landing_page` CPT, native editor/list/new routes and L2/L3 menu remain untouched. Campaign only stores existing Page IDs and uses reviewed PHP packs.
- Marketing entry is Andy Campaign when installed; legacy Commerce marketing view is a **legacy testing route**, not a new concurrent production menu/engine.
- Do not remove Commerce Marketing code until after a separate audited Currency/Marketing compatibility release, rollback plan, and all old V1 UAT records are preserved.

## R3: reciprocal execution exclusion (two independent source patches)
Contract flags:
- `andy_campaign_lite_master` = `yes` means the new Campaign runtime may be requested.
- `andy_commerce_marketing_dev_uat_enabled` = `yes` and/or `andy_commerce_marketing_p1_sandbox_enabled` = `yes` indicate old Commerce V1 runtime requested (including split/mismatched states).

**Campaign side (#242)**:
- Any legacy flag ON produces `legacy_engine_active` and no Campaign Pack boot callbacks.
- Operator cannot enable Campaign master or a Campaign Pack while any legacy flag is ON (HTTP 409). Disabling always remains available. Neither flag is silently overwritten.

**Commerce side (separate source-only child branch of #213)**:
- Legacy M-P1::current() rejects active Campaign master before returning a current draft.
- M-P5 checkout/order cap reservation availability rejects active Campaign master.
- Dev UAT `arm` handler refuses to arm V1 while Campaign master ON (HTTP 409). Legacy emergency OFF, read-only preview/archives and Dev tests remain accessible; no need to erase old M15 DB records.
- The Commerce child patch does NOT update #213's branch, Dev installed ZIP or Currency runtime. Its own review is required before a future publisher handoff.

Truth table for runtime requests:
| Campaign master | Commerce V1 STATUS or SWITCH | Campaign callbacks | V1 execution |
|---|---|---|---|
| OFF | OFF/OFF | OFF | OFF |
| ON | OFF/OFF | Only individually eligible packs | OFF (new Commerce guard) |
| OFF | either ON | OFF | Dev-only subject to existing P1 strict site/identity gate |
| ON | either ON | OFF (`legacy_engine_active`) | OFF (`legacy_runtime_allowed=false`) |

The last row is **fail closed, not permission to disable or delete historical orders**. Two-way operator UI guards reduce conflicting writes; real-world option writes outside these UIs still rely on runtime vetoes. No code can guarantee absolute isolation from independently installed third-party Woo promotions.

## Remaining gates
- Complete Commerce-side child PR CI and ensure #213 sole Dev-publisher constraints still hold. Then reconcile actual Commerce installed versions and stage the two compatibility patches together behind explicit Dev approvals; never install an old ZIP over Currency R13.
- Review M15/M16 only when new Code-First Pack needs quota. Preserve schema, cap claims and exact old source until migration/rollback is audited.
- C4/C5 actual Classic/Blocks order persistence, HPOS, taxes, shipping, refunds, five-currency Stripe and shopper UI remain NO-GO for customer launch.
- This migration source work does not authorize merging main, installing Dev, modifying WWW, real orders or automated email.
