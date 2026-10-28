# Marketing OS V1 -> Andy Campaign R4 — actual isolated WordPress joint-install and rollback

Date: 2026-10-28. **SOURCE-ONLY / local standalone UAT. No Dev, Stable or Production approval.**

## Exact source provenance (before any later commits)
- Andy Campaign Draft PR #242: Core L3 menu cutover changes at `29e3a7655bc4462e33ae0b0f26d96f2216e4d263`, backed by earlier R0–R3 master veto and Code-First packages.
- Commerce migration Draft PR #246: `a0723a8bb5a7f8806ae25908198fec9a481578ee` stacked on Currency sole-publisher PR #213 (which advanced independently to `9f2a808fb6a54744993d85a861513840357e408a`).
- The updated PR #213 touches only `class-andy-commerce-currency-r13-dev-canary.php` and its contract test relative to prior head; it does **not** alter the four Commerce Marketing R4 files patched here. PR #246 was retargeted to the new head as base metadata only; no force push, no Dev ZIP published.
- Isolated US Mac mini site: `/Users/a/AI/uat/andy-campaign-c3-20261027/site`; independent DB `aicc_campaign_c3_20261027`; loopback `127.0.0.1:18974`; HTTP external blocked, real mail blocked, cron disabled. Original inactive Commerce plugin copied to an isolated rollback backup outside plugin discovery. Source copied solely into this isolated test site's installed plugin directories. Official Dev and WWW not touched.

## R4 menu ownership
- Andy Core owns the single Marketing L2 destination. When Campaign installed: `admin.php?page=andy-campaign&tab=campaigns`.
- When Campaign installed, Andy Core's **Commerce** L3 shows only 总览 / 订单导出 / 结账运费 / 设置. Commerce's optional native L3 external registry also omits archived M7–M10 Marketing panes.
- Original Commerce Marketing Dev routes and in-place draft/test evidence **remain accessible by direct URL** solely for rollback/legacy Dev diagnosis. No source deletion, table migration, drafts migration, or new landing-page owner.
- Core Landing Pages native URLs `edit.php?post_type=landing_page`, `post-new.php?post_type=landing_page` unchanged.
- Code-first BF Campaign Pack still owns its own PHP logic and gift catalog; Currency/Stripe, HPOS/transactions and Shipping are still Commerce-owned.

## Isolated WordPress actual plugin toggles
1. Both Commerce and Campaign plugins activated, Marketing master/legacy STATUS/P1 OFF. Marketing destination points to Campaign; Commerce L3 does not show 促销.
2. Actual **WordPress plugin deactivation** of Campaign. Fresh request: Marketing destination returns `andy-commerce&tab=marketing-promotions`; Commerce L3 促销 visible again.
3. Actual reactivation of Campaign. Fresh request: Marketing destination points back to Campaign; Commerce 促销 hidden again.
4. Landing Pages editor/list remained untouched. No WP PHP processes restarted other than ephemeral loopback test HTTP server, which was stopped.
5. Final Campaign master and both Commerce legacy switches `no/no/no`.

## Real HTTP controller smoke
Authenticated isolated test admin cookie, loopback-only `127.0.0.1:18974`, GET:
- `admin.php?page=andy-campaign&tab=campaigns` — HTTP **200**
- `admin.php?page=andy-commerce` — HTTP **200**
- `admin.php?page=andy-commerce&tab=marketing-promotions` — HTTP **200**
- `edit.php?post_type=landing_page` — HTTP **200**
- `post-new.php?post_type=landing_page` — HTTP **200**

## Offline tests / gates
- Campaign: `ANDY_CAMPAIGN_CORE_NAV_PASS=10` (retains Commerce checkout and Landing links; removes duplicate Marketing L3 only when Campaign loaded), `ANDY_CAMPAIGN_LITE_DECISIONS_PASS=15`.
- Commerce: `COMMERCE_MARKETING_V1_CAMPAIGN_EXCLUSION_PASS=6`, `COMMERCE_MARKETING_V1_CAMPAIGN_MENU_PASS=7`. Prior P5 local Woo CI regression and Marketing V1 simulated promotion suites pass.
- Core governance regressions: preserve original Commerce L3 ordering when Campaign is *absent*; the first attempt moved 促销 after 设置 and failed existing Core harness. Corrected to preserve original order and only unset 促销 when Campaign present. Do not drop canonical legacy navigation regression assertions.
- Shared GitHub Release Package Provenance can fail transiently when base PR #213 is updated during PR #246 CI; ensure PR #246 base SHA matches the current exact PR #213 head and rerun CI without overwriting current Currency files.

## Not yet delivered
- This is not official `dev.beyourlover.com` customer UAT; not an installation of PR #246 through PR #213's sole Dev publisher.
- M15/M16 retained as old Dev sandbox reference, not physically moved; R2 remains deferred until campaign needs caps/claims.
- Actual paid Classic/Blocks orders, five currencies, Stripe, refunds, tax/dynamic-carrier full matrix, gift picker mobile UAT still require independent C4/C5 signoff.
- **NO-GO** for Production and for retiring/deleting legacy V1 code. Both migration PRs stay Draft pending coordinated Commerce Publisher and Owner release approvals.
