# YBY Irrigation — Wholesale Country Clone V2
## M0–M8 Development Evidence, Release Boundaries, Owner UAT (2026-10-15)

**Canonical source:** GitHub `beyourlovercom/YBY-Core`, branch `feature/irrigation-wholesale-country-clone-v2-20261015`. **Target:** independent Draft PR to `main`. **Older Solution PR #113:** historical; not to be merged or deployed as the Wholesale solution.

### Owner scope
- **ONLY** B2B dealer/distributor landing-page master `irrigation-wholesale`; never clone `irrigation-solution`.
- Existing Ads account `502-024-8458` should acquire wholesale buyers, distributors, engineering/procurement teams. 1-hectare retail leads not in target.
- ChatGPT Ads Manager opening is postponed. Keep existing Google Ads; do not invent pause results or pause mixed Wholesale campaigns indiscriminately.
- Protect Dev Wholesale mother page #215, Thank You #310, historical protected #699; protect Tanzania Solution #292 and prior US Solution draft. No live lead submissions, CRM/ERP writes, Ads enablement or Production deployment in V2 Dev UAT.

### Actual milestone evidence
| Phase | Progress and evidence | Actual status |
|---|---|---|
| M0 | Isolated Dev WordPress source #215 (published), Thank You #310 published, #699 historical draft. Two identical `_bricks_page_content_2` rows (SHA256 `552ac3ff52aa1e66cc20403bfda6da85d824996958abc955724a1679d4aeb19b`). 4 Bricks nodes: 2 sections, 2 Code. Two old source signatures are stale under current verifier; source immutability hash checked and never edited. Canonical global popup routes through `data-yby-inquiry-trigger`, `YBYLead`, `YBYTracking`, with `irrigation_wholesale` profile. | Dev source audit **PASS**; real lead submission not tested |
| M1 | GitHub standalone `YBY_Wholesale_Country_Clone_V2` fail-closed implementation: exact Dev host, canonical source ID+slug+meta hash, native Bricks regeneration+signing, Admin capability, fingerprint, new independent Project+Landing Drafts, collision detection and idempotent reuse. Dev CLI explicit temporary write flag. | **PASS on isolated Dev** |
| M2 | Germany Project #818, Germany landing #819, both `draft`; signed Code 2/2; source and protected records unchanged. | **PASS** |
| M3 | Independent `Bricks\\Frontend::render_content($nodes)` output: Germany 47,965 bytes; US 47,971; France 47,964; each with 16 Wholesale inquiry triggers, 16 profile markers, 0 `irrigation_solution`, 2/2 verified Code signatures. Real browser mobile/desktop Owner UAT, click-to-open modal and any end-to-end inquiry must still be verified. | Automated structural/render **PASS**; **Owner UAT pending** |
| M4 | Germany page-only Production release runbook drafted below. No WWW modifications or release approval yet. Production private read-only source audit was Owner-waived for earlier Solution branch, not a claim of verified Wholesale Production equivalence. | **PREPARED, NOT DEPLOYED** |
| M5 | Andy Core > Project Studio > Wholesale Country Clone V2. Dev injected 4-file allowlist only and saved rollback. Dev constant `YBY_WHOLESALE_CLONE_V2_DEV_ENABLED=true`; Production has no enabled V2 runtime. Dev admin render smoke `DEV_STUDIO_GET_PASS`: 4175 HTML bytes, country selection, create/reuse Draft button, preview links. | Dev **PASS**, Owner UI UAT pending |
| M6 | Germany repeat call reused #818/#819; USA #820/#821 and France #822/#823 independent Dev-only Draft pairs. Mother #215, Thank You #310 and protected #699 unchanged. | Dev multi-country **PASS** |
| M7 | Google Ads wholesale-only implementation plan and exact campaign/adgroup final-URL inspection rules below. Connected Windsor.ai reads were blocked by its Free plan because 2 accounts are connected, so no reliable IDs; do **not** claim Solution campaigns paused or touch unknown targets. | **PREPARED; Ads write verification pending** |
| M8 | Country operator SOP, rollback, UAT checklist and handoff prepared here. Final closure only after Owner mobile/desktop UAT and subsequent release evidence. | **HANDOFF READY; closure pending** |

### Actual Dev state / UAT links
- WordPress Studio: https://dev.ybyirrigation.com/wp-admin/admin.php?page=yby-project-studio&view=wholesale
- Germany Landing edit: https://dev.ybyirrigation.com/wp-admin/post.php?post=819&action=edit
- Germany Project edit: https://dev.ybyirrigation.com/wp-admin/post.php?post=818&action=edit
- USA Draft #821, France Draft #823. All three remain unpublished and require logged-in WP Preview.
- The plugin source #215 is never overwritten.
- Dev 4-file UI installed: `admin/class-yby-project-studio.php` (surgical insert preserving older Solution route), `admin/views/project-list.php` (add link), `admin/views/wholesale-country-clone-v2.php` (new), `inc/class-yby-wholesale-country-clone-v2.php` (new). Native lint 4/4 PASS.
- Private Dev backup: `/home/u686797605/.byl-uat/germany-clone-v1/v2-backup-20261015082024`. Dev `wp-config.php` was copied to the same private UAT directory as `wp-config.before-wholesale-v2.php` before enabling the Dev-only runtime flag.
- WP versioned package still has earlier Dev-only V1 country clone code; do not perform an entire plugin directory overwrite from main. The V2 branch does NOT contain or reuse the old Germany Solution transform logic.

### M4 controlled Production release requirements
1. Owner reviews Germany Draft in Dev and approves UAT. **Do not publish Drafts from M0–M3 automatically.**
2. Select the Production Wholesale mother by its **actual** page ID and verified contract on the target host; Dev #215 is not a Production ID. Owner earlier waived *authenticated private read-only comparison*, but production source mapping must not be guessed and safety must still fail closed.
3. Prepare an exact-file deployment candidate with signature generation on the target Bricks version. Record target file hashes, backup, rollback pointer, and the specific Germany page-only create/publish operation. Do not overwrite Tanzania, original Wholesale, Solution US draft, Thank You, existing CRM/ERP, or conversions.
4. Verify inquiry opening, payload source `irrigation_wholesale`, Thank You redirect, required B2B qualification fields, and conversion event exactly once using an authorized isolated test (not a real incoming lead). Owner reviews mobile 390x844 and desktop 1440x900 screenshots of **actual accessible Dev**.
5. Publish only independently approved Germany Wholesale page, then UAT actual Production preview; only afterwards attach any Google Ads Destination URLs. Do not link ads to a Dev Draft.

### M7 Google Ads Wholesale-only safety / no guessed campaign IDs
- Account: `502-024-8458` (YBY Irrigation). Need list of **real** active Campaign / Ad Group / Ad final URLs before pause. Windsor.ai gave a non-data billing notice (Free plan 2 accounts vs 1 allowed); metrics/IDs cannot be trusted.
- Pause campaigns/adgroups whose final URLs point exclusively to `irrigation-solution` or 1-hectare landing pages. For campaigns mixing Solution + Wholesale, pause **only affected ad groups/ads**, never the entire mixed campaign unless no active wholesale destination remains.
- Preserve B2B Wholesale campaign budget and historical tracking IDs. Ads spend may not be attributed to an unverified UAT page.
- For new country ads use `/lp/irrigation-wholesale-{country}/` only once live and confirmed, with B2B terms `wholesale`, `distributor`, `supplier`, `OEM`, `bulk`, `contractor`; exclude retail `1 hectare` and small-area intent according to actual query report.
- Record per-campaign pause confirmations including IDs/status/final URLs; if authorized direct read unavailable, complete a **manual Google Ads Owner UAT** via screenshots. **Do not claim paused** without evidence.

### M8 Country SOP (once Germany UAT is approved)
1. Choose Wholesale source #215 in isolated Dev and country from 16-entry reviewed registry.
2. Read-only Preflight PASS with source SHA/fingerprint; create/reuse a unique Project+Landing Draft (never overwrite).
3. Verify Bricks Code native 2/2 signatures, country H1, 16 inquiry triggers, profile `irrigation_wholesale`, original source unchanged.
4. Compare actual desktop and iPhone 14 real preview with source design; inspect B2B-only copy, stock/distributor claims and country-localized facts.
5. Verify modal opens, buyer type/product interest/quantity and any lead routing in isolated test. Verify Thank You and a single conversion.
6. Owner PASS; then minimum allowlisted page publication to Production, with rollback pointer. No blanket plugin deployments.
7. Connect country campaign with final URL only after live page PASS, monitor qualified lead rate and cost per B2B lead; avoid small-farm traffic.
8. On defect: pause only the affected new country ad, revert the new page, retain source #215/Thank You #310 and old inquiry data.

### Owner UAT checklist — point at which work should pause
- [ ] On iPhone 14/390x844, open Germany preview from logged-in Dev WordPress: check horizontal scroll, hero CTA, header, products, and sticky inquiry.
- [ ] On desktop 1440x900, check responsive layout, B2B hero country title and 16 triggers' representative product CTA.
- [ ] Click a wholesale CTA, verify canonical global inquiry modal opens and returns a form (do NOT send real lead just for visual UAT).
- [ ] Check buyer/distributor qualification, country, quantity, and product-interest options. The global preset may need B2B-specific qualification before Ads launch.
- [ ] Verify Germany Project/Preview links and re-click Create/Open Draft -> **reused**, no duplicate.
- [ ] Confirm US/France remain only unpublished Dev regression Drafts.
- [ ] Explicitly approve Dev Owner UAT. Production release and Ads follow separate live-only protections above.

**Status truth:** M0, M1, M2, M3 automated checks, M5, and M6 are Dev PASS. M4 not deployed, M7 campaigns not proven paused, M8 not formally closed. Do not misreport overall Production or Ads UAT as PASS.
