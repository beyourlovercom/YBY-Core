# Marketing V1 deprecation gate R4 — isolated joint Core/Commerce/Campaign verification

Date: 2026-10-28. **Draft PR #246 / not a publisher / no Dev or WWW writes.**

## Source boundaries
- This PR is **stacked on the current PR #213** sole Commerce Dev publisher. All changes in this PR concern only the old Marketing V1 execution veto, preservation of historical V1 menu routes, and regression evidence.
- The PR #213 base advanced to `9f2a808fb6a54744993d85a861513840357e408a` during R4. Compared with the former `78210e94effee8da30de35d89d82efcbced6c0f3` baseline, only Currency R13 Canary implementation/test changed; these migration patches did not touch either path. GitHub PR base metadata was refreshed; no force push or core Dev install.
- Preserve Commerce's Currency R13, Stripe, checkout and shipping code unchanged. Do not move M15/M16 MySQL cap or order records, delete archived M7–M10 drafts, or expose an old Commerce promotion runtime on WWW.

## What R4 changes
- **Core/PR #242** owns the unified Marketing L2 selection and hides Commerce's built-in 促销 L3 only while Andy Campaign is installed. Original ordering of all five Commerce tabs is unchanged when Campaign is absent (explicit Core regression).
- **Commerce/this PR** skips registering four extra legacy M7–M10 L3 menu links when Campaign is installed; the original direct Dev-only routes remain available for historical review/rollback. No editor or data migration.
- Legacy V1 execution remains blocked when `andy_campaign_lite_master=yes`; conversely Campaign will not attach new pack hooks if old V1 STATUS or P1 switch is enabled.

## Real isolated WordPress proof
- US Mac mini private isolated WordPress `aicc_campaign_c3_20261027`, Core 2.2.11, WooCommerce 11.2.1, both installed Campaign + Commerce plugins, no external HTTP/mail/cron, no official Dev or WWW changes.
- Real WordPress Campaign plugin ON -> Core Marketing destination Campaign, Commerce L3 = 总览、订单导出、结账运费、设置.
- Real WordPress Campaign plugin OFF (new PHP request) -> Core Marketing destination Commerce's original `marketing-promotions`, legacy 促销 L3 visible.
- Actual reactivation of Campaign (new PHP request) -> Campaign destination/unique L3 restored; local master OFF.
- Authenticated loopback HTTP routes returned 200: Campaign admin, Commerce admin, old Marketing direct URL, original Landing Page list and original Landing Page creation controller; loopback server stopped after test.
- Pure CI source tests: `COMMERCE_MARKETING_V1_CAMPAIGN_MENU_PASS=7`, `COMMERCE_MARKETING_V1_CAMPAIGN_EXCLUSION_PASS=6`; original Commerce Marketing Dev UAT and P5 regression retained. Campaign R4 Core nav `ANDY_CAMPAIGN_CORE_NAV_PASS=10`.

## Important gates
- No production launch, no actual Stripe/refund/payment/order test and no new Commerce ZIP authorization from this source branch.
- Keep PR #246 Draft and **do not merge directly to main**; it must be reviewed and integrated via PR #213's exact-head Dev publisher chain only after coordinated UAT.
- Continue campaign C4/C5 native Classic/Blocks order, HPOS, FX/tax/shipping, refunds and shopper UAT before production. Do not claim R2 M15/M16 physical migration completed.
