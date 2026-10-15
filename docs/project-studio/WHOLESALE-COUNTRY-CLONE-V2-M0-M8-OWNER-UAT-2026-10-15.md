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
| M3 | Germany Dev structural/render evidence PASS and Owner visual UAT explicitly approved. Dev Wholesale inquiry controls preserved; no real inquiry was submitted. Production follow-up CDP also validated real mobile and popup behavior independently. | **Dev Owner PASS; automated PASS** |
| M4 | Germany independently published on WWW as Wholesale Landing #824, from Production Wholesale master #215 / Thank You #310; Production uses 10 Bricks nodes/4 signed Code and Andy Core 1.5.2 (no plugin upgrade). Live HTTP 200, 17 Wholesale triggers, Germany-specific copy, iPhone 14 390px and desktop 1440px CDP PASS. Page-only backups exist. | **PRODUCTION PUBLISHED; browser tech PASS; final Owner Production UAT pending** |
| M5 | Andy Core > Project Studio > Wholesale Country Clone V2. Dev injected 4-file allowlist only and saved rollback. Dev constant `YBY_WHOLESALE_CLONE_V2_DEV_ENABLED=true`; Production has no enabled V2 runtime. Dev admin render smoke `DEV_STUDIO_GET_PASS`: 4175 HTML bytes, country selection, create/reuse Draft button, preview links. | Dev **PASS**, Owner UI UAT pending |
| M6 | Germany repeat call reused #818/#819; USA #820/#821 and France #822/#823 independent Dev-only Draft pairs. Mother #215, Thank You #310 and protected #699 unchanged. | Dev multi-country **PASS** |
| M7 | Google Ads wholesale-only implementation plan and exact campaign/adgroup final-URL inspection rules below. Connected Windsor.ai reads were blocked by its Free plan because 2 accounts are connected, so no reliable IDs; do **not** claim Solution campaigns paused or touch unknown targets. | **PREPARED; Ads write verification pending** |
| M8 | GitHub SOP and rollback documented, isolated Chrome CDP browser UAT reusable, 390px no overflow and popup opens without any submission. Closure requires final Production Owner UAT and separate Google Ads pause verification. | **TECHNICAL HANDOFF PASS; final closure pending** |

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

**Status truth (updated 2026-10-15):** M0–M3/M5/M6 Dev PASS, Dev Owner PASS. Germany Wholesale Production #824 is PUBLISHED with HTTP/Bricks/iPhone14/desktop/CDP popup technical PASS. Final Production Owner visual UAT remains pending. M7 Google Ads campaign pause status NOT VERIFIED (Windsor Free-plan data restriction); no Ads writes. M8 final closure pending. PR #121 remains Draft and unmerged.


## Germany Wholesale M4 — Actual Production release evidence (2026-10-15)

This section supersedes the earlier *release plan / not deployed* wording. The Owner approved independent Germany Wholesale publication earlier and passed Dev Owner UAT before this release.

- **Production URL:** https://ybyirrigation.com/lp/irrigation-wholesale-germany/
- **Production Germany Landing:** #824, **publish**. New page only; no Project CPT creation.
- **Actual Production master:** Wholesale Landing #215, **publish**, 10 Bricks nodes/4 Code elements; Thank You #310, **publish**. The Dev source was a different revision (4 nodes/2 Code). Production Andy Core 1.5.2 was **not upgraded** to Dev Andy Core 1.8.1. Original master/source/Thank You unchanged.
- **Release path:** \`scripts/wholesale-v2-production-page-release.php\` executed via authenticated private Production WP-CLI: exact host, parent, source fingerprint, target slug/collision, admin capability, Code native signatures, target-only page creation, verification, then publication; original source remained immutable.
- **Production signed Code:** 4/4 native Bricks verified, exactly 17 \`data-yby-inquiry-trigger\` Wholesale triggers; Germany H1 verified once; no old \`irrigation_solution\` transform or plugin replacement.
- **Germany-specific mobile/copy fix:** only post #824 updated using \`scripts/wholesale-v2-production-germany-mobile-hotfix.php\` and \`scripts/wholesale-v2-production-germany-inline-css-fix.php\`. Replaced 2 Africa-only hero claims with Germany B2B positioning. The metadata CSS was cached and not visible publicly; additional Germany-only **signed inline CSS** in target Code fixed real mobile layout. All 4 signed Code elements and 17 triggers remain intact.
- **Rollback backups (private Production filesystem):** \`~/.byl-uat/wholesale-v2-release/germany-824-pre-mobile-hotfix-20261015090339.ser\` and \`~/.byl-uat/wholesale-v2-release/germany-824-pre-inline-css-20261015090805.ser\`. Rollback new country by changing **only #824** to Draft; source #215, TY #310 and plugin are untouched.
- **Public HTTP check:** Germany, source Wholesale and Wholesale Thank You all returned **HTTP 200**. Germany live HTML has 17 Wholesale trigger attributes, global inquiry script/popup contract, no Africa copy.
- **Independent real browser test:** \`scripts/wholesale-v2-browser-uat.mjs\` launched isolated headless Chrome via CDP. Mobile emulation 390×844 @ DPR2: \`window.innerWidth=390\`, document scroll width \`390\`, title \`white-space:normal\`, \`overflow-wrap:anywhere\`, title/content/description boxes x24…366, 28px headline, Germany-specific copy. Desktop 1440×900: 17 triggers; correct title and page layout. The Wholesale CTA \`click()\` opened global popup (\`aria-hidden=false\`, \`hidden=false\`) with inquiry form present; **no form submitted**.
- **UAT captures (authorized Windows device only):** \`D:\\ai\\_uat\\wholesale-production-de\\cdp-iphone14.png\`, \`cdp-desktop.png\`, \`cdp-report.json\`. Actual screenshot confirmed mobile H1, description and CTA fully within viewport. No user Chrome profile was restarted.
- **Owner final gate:** Dev Owner PASS already recorded; ask the Owner to review this **actual Production** Germany page and confirm visual/CTA UAT. This approval is separate from Google Ads writes and PR merge. No real test lead was sent.

### Google Ads M7 — explicitly unresolved
- Account \`502-024-8458\` (YBY Irrigation). Owner instructed stopping \`irrigation-solution\`/1-hectare campaigns and preserving only \`irrigation-wholesale\` B2B campaigns, including mixed-campaign ad group granularity.
- Windsor.ai returned a **Free-plan read restriction**, not real campaign rows, because two Ads accounts are connected. Do not derive or invent Campaign IDs/Final URLs, pause by guess, or state that ads have been stopped.
- No Google Ads campaign/ad-group/ad writes were executed here. Resolve through a valid direct Google Ads view/connector or Owner manual Ads UI, selecting exact campaigns/URLs and reporting statuses. Germany Ads must not be activated until country-specific conversion tracking and the final owner release gate pass.
