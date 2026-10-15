# Wholesale Country Clone V2 — Canonical Roadmap & M0 Evidence (2026-10-15)

## 0. Governing decision
Owner pivoted YBY Irrigation multi-country Ads landing generation to **B2B Wholesale Irrigation**. Only clone the existing `irrigation-wholesale` distributor landing, **never** `irrigation-solution` (Tanzania #292, Germany Solution #816). Keep those historical pages and all existing inquiries unchanged. ChatGPT Ads Manager onboarding deferred; Google Ads account 502-024-8458 should focus on wholesale B2B. Actual pausing of non-wholesale Ads remains unverified; Windsor free-account read access was blocked.

GitHub remains single source. Earlier Germany Solution PR #113 stays Draft, unmerged, not deployable as the Wholesale country-clone template. Separate future Wholesale implementation from the deprecated Solution transform.

## 1. M0 verified evidence — isolated Dev ONLY
Read-only WordPress SSH over the verified `yby-shared-dev` alias:
- Host WordPress siteurl = `https://dev.ybyirrigation.com`.
- `landing_page` **#215**, slug `irrigation-wholesale`, title `IRRIGATION WHOLESALE`, **publish** — canonical Wholesale source candidate.
- `landing_page` **#310**, slug `thank-you-irrigation-wholesale`, title `THANK YOU IRRIGATION WHOLESALE`, **publish** — canonical success-page candidate, relationship not yet confirmed.
- `landing_page` **#699**, title `IRRIGATION WHOLESALE - 保护，GPT第一版`, **draft** — protected historical page; do not use as source.
- Existing `yby_project` rows in Dev: **#518 Tanzania** (draft) and **#815 Germany Irrigation Solution** (publish). No verified dedicated wholesale `yby_project` yet.
- #215 meta keys include `_bricks_editor_mode`, `_bricks_page_settings`, **two** `_bricks_page_content_2` metadata rows. This is a possible duplicate-row/active Bricks source selection issue; do not overwrite/repair either row during M0.
- `wp post meta get 215 _bricks_page_content_2 --format=json` parsed into **4 top-level elements**: two `section` elements and two Bricks `code` elements, with native `executeCode`, `signature`, `user_id`, `time`, `code` settings. Code lengths **39,807** and **2,119** chars; second includes `cssCode`. Do not transplant existing signatures: use native Bricks signature creation after transformations.
- HTML/CSS appears mostly inside custom Code elements (Wholesale is not structurally identical to 27-element Tanzania #292). Wholesale-specific native source inspection required before cloning.
- Token inspection found `wholesale`, `distributor`, and `inquiry` in source code; **no visible `admin-ajax`, `wp-json`, `gtag`, `gclid`, `dataLayer`, `utm_`, `thank` or `submit` literal in extracted main code**. This does NOT establish missing inquiry functionality: it may be global Andy Core injection, browser runtime, forms plugins or non-inline tracking.
- Existing Woo/CRM, form endpoints, global inquiry buttons, Thank You redirect and Ads conversion events are **NOT YET VERIFIED**. Do not mislabel as PASS.
- Source clone must retain full 2 custom Code blocks, IDs regenerate via native Bricks helper, signed by authorized workflow, source content fingerprint and collision safe-guards.

## 2. Complete Roadmap
Estimated hands-on engineering hours; Ads review/provisioning waits not included. Stages can overlap.

| Stage | Goal | Acceptance evidence | Estimated hours |
| --- | --- | --- | ---: |
| M0 | Wholesale source truth and contract | Exact Dev source IDs, Bricks node/signature map, identify global inquiry/CRM/thank-you/tracking injection; inspect active duplicate meta row, baseline URL and mobile rendering; source fingerprint | 2-4 |
| M1 | Wholesale Clone Engine V2 | Separate allowlisted wholesale source registry, country transforms on 2 Code blocks and page settings, source unchanged, dev-only default-OFF, capability+nonce+fingerprint, collisions/idempotence, proper Project↔Landing linkage | 4-6 |
| M2 | Germany Wholesale Draft | Create separate Germany Wholesale Project + Draft page, native Code signatures and responsive source-preserving transform; leave #215/#310/#699 unchanged | 2-4 |
| M3 | Dev integration UAT | Mobile iPhone 14/desktop actual preview, B2B audience copy, inquiry popup and global Andy Core integration, Thank You, test-only CRM/ERP/Ads conversion checks; no real inquiries | 2-4 |
| M4 | Germany minimum Production release | Owner-approved file allowlist, backups/rollback, isolated Germany page-only publish after UAT; no Tanzania/US/Wholesale source writes, preserve all live inquiry paths | 2-3 |
| M5 | Project Studio multi-country UX | Choose Wholesale source, country dropdown, preview/diff, Draft create/reuse and localizable fields, one-click publish only after owner gate; action log/rollback | 3-5 |
| M6 | USA and second country | Independent U.S. Wholesale clone with isolation; second country validates cross-country idempotence and routing; countries as market localization, not invented case studies | 2-4 |
| M7 | Google Ads B2B controls | Read Ads campaign + final URLs with an authorized unblocked data source, pause Solution campaigns/adgroups while preserving Wholesale; bind approved country ads and conversion; avoid mixed-campaign wrong pause | 2-3 |
| M8 | Final closure and operator SOP | UAT sign-off, reusable 30–90 minute country checklist, GitHub handoff, monitoring/rollback for each new country | 1-2 |

**Indicative total: 18–35h** with parallel delivery; Germany prioritized M0→M4, then standardize M5→M8. Once mature, new country 0.5–1.5h unless exceptional content or compliance.

## 3. Release rules
1. The generic `irrigation-solution` country conversion is out of scope. Never carry its legacy Germany references into Wholesale.
2. Existing `irrigation-wholesale` **#215** is source candidate **only in Dev**, not a confirmed Production post ID. Preserve original immutable content and all global inquiry/conversion functions.
3. Original ID #699 is a historical protected draft; no edits.
4. Do not claim a direct inline form exists until global Andy Core inquiry handlers are identified; inspect actual rendered browser and plugin hooks.
5. Production private read-only audit of old Solution posts was explicitly waived by Owner, but it does not authorize destructive writes or uncontrolled deploy.
6. No Production deploy, PR merge, Google Ads change, or live lead without a matching action-specific safety check.
7. Ads objective is wholesalers, distributors, B2B projects and procurement, not one-hectare retail leads.

## 4. Immediate open M0 checklist
- [x] Identify Dev source #215, success-page candidate #310, protected #699
- [x] Identify Dev Project Studio rows and absence of Wholesale Project
- [x] Determine Bricks structure: 2 sections + 2 Code, code length and signature presence
- [x] Detect duplicate `_bricks_page_content_2` meta key; no modification
- [ ] Identify active Bricks value and duplicated meta rows safely
- [ ] Determine global inquiry/form rendering, backend handler, Thank You relationship
- [ ] Establish source fingerprint, exact transform registry and B2B-only country variables
- [ ] Capture 390×844 mobile and 1440×900 desktop baseline and compare
- [ ] Confirm safe rollback and open independent V2 work branch
