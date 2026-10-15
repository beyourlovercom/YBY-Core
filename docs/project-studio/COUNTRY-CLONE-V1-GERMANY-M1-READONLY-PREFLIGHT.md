# YBY Irrigation — Project Studio Country Clone V1 (Germany)
Status: M1 preflight candidate, Draft PR, NOT deployment-ready
Date: 2026-10-15
Owner approval: M1–M3 Dev-only scoped work; no Production, Ads, merges, Bricks signing or real lead writes.

## Baseline and context
- Canonical repository: beyourlovercom/YBY-Core, default branch main.
- Existing Tanzania source: Production `landing_page` ID 292. US 798 is an existing Production draft per handoff; NEVER overwrite either.
- Current code CPT: `landing_page` (commit d60a9cff corrected the outdated `yby_landing_page` recovery document).
- Germany has no existing Production Project ID / Landing ID / Ads. No advertising operations are in scope.
- Source Tanzania Project Country was displayed as `-` in existing Studio. Do not invent a source Project Country from the title, global site fallback, or page rendering.

## Implemented on branch
- `Project Studio → [Source Project] → 复制到新国家（只读预检）`.
- German target fixed to `Germany / DE`; source Landing ID entered by operator.
- POST nonce, exact source Project ID, `andy_core_settings_manage` + both edit_post capability + edit_pages checks.
- Diagnoses supported `landing_page` CPT, Project Country, page raw country, consistency, Bricks content structure, Code/SVG elements, country-specific references, source fingerprint.
- Hard write gate: results always BLOCKED until full schema, mapping and Bricks legal signing reviewed. No `wp_insert_post`, `update_post_meta`, code execution change, URL rewrite, signer, or Ads code exists in this feature.
- Existing project list/overview/runtime routes are unchanged except the added preflight link.

## Windows LocalWP readonly evidence (2026-10-15)
Test context: `D:\ai\devybyirrigation` (Local site, not Production). This evidence is NOT a Production read.
- Local source post 292 exists as `landing_page/publish`.
- Its Bricks content is stored in `_bricks_page_content_2`, 27 top-level elements: 14 section, 13 code.
- Each of the 13 Code elements has settings keys `code`, `executeCode`, `signature`, `user_id`, `time`.
- A signature attribute being present DOES NOT mean it can be reused on a new Post or was cryptographically validated by this audit.
- Page 292 has raw `yby_country`; Local Project 518 is `draft` and its `yby_country` is empty.
- Local post 798 is absent; this does NOT say anything about Production post 798.
- Local WP PHP CLI requires using the installed Local PHP 8.2 runtime with mysqli/pdo_mysql enabled; system php 8.3 WinGet lacks mysqli in default CLI config.

## Unresolved blocking conditions
1. Read-only authenticated Production confirmation: source Tanzania `yby_project` ID, raw Country, mapping to 292, Bricks signature state, and US 798 fingerprint.
2. Design and test an approved per-node country transform for both country selectors, selected_country and country input, YBYProject/YBYPageProfile/YBYContent/YBYTemplate, PROJECT_SCENARIOS and JS fallback. Do not global-replace `Tanzania` or falsify Tanzania example case studies.
3. Decide source Project Country remediation without editing Tanzania (explicit verified source-page mapping vs owner-approved data repair).
4. Review Bricks Code elements, use official authorized sign-code UI flow on a NEW isolated draft after Dev-only approval. No copied signatures as valid, no unsigned code execution, no dangerous auto-sign.
5. Build draft-clone service with independent IDs, correlation/idempotency, compensation/rollback, no copied form/customer data/secrets, and strict WordPress current_user_can / nonce gates. Cross-check Project-to-Page runtime binding.
6. Verify Dev environment isolation/rollback before any site deployment; production and Google Ads still out of scope.

## Regression
`php tests/project-country-clone-preflight-harness.php`
M1 preflight code and six modified/new PHP files lint PASS from authorized Windows isolated worktree. CI workflow `.github/workflows/core-regression.yml` automatically includes `tests/*-harness.php` on PR.

## Owner UAT gate
Only mark M1/M2 complete once an isolated DEV draft can be safely generated with **legal native Bricks signing**, isolated Project+Landing IDs and correct country/forms/thank-you/tracking. Check Desktop and iPhone 14. No Production or ads work without separate approval.
