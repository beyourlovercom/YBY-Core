# Andy Core V2.2 — Premium OEM Global Popup / 同一 Roadmap 扩展

Date: 2026-10-15. Parent issue: #102. Depends on Draft PR #110 (PHP shell) and Draft PR #111 (M2–M5 admin adapters). This is a **separate stacked Draft branch**, not a main merge or WWW release.

## Source of truth and baseline

- Real current reference page (read-only HTTP 200): `https://ybybottle.com/lp/glass-bottle-oem/`; `https://bottle.com/lp/glass-bottle-oem/` responds 404.
- The exact production popup visual/DOM and browser screenshots have **not yet been fully verified**; do not claim pixel parity or visual Owner PASS.
- Inquiry OS already contains `bottle_oem_inquiry`, `bottle_wholesale_inquiry`, and `irrigation_quick_inquiry` presets. The previous Global Popup only requested `irrigation_quick_inquiry`.
- Existing global popup source, trigger and submission contracts are owned by `YBY_Inquiry_Renderer`, `yby-inquiry-components.js`, Lead SDK and REST route. They must not be duplicated.
- Latest verified PR #111 baseline for this branch: `b70f4b287ac194f503ad06d39d7ff1fd08025226`, 25 changed files, four successful CI checks. M6 real Dev install and Owner desktop/iPhone14 UAT not yet completed.

## Milestone integration (without restarting M0–M3)

| Milestone | Deliverable | Gate / status |
|---|---|---|
| M5-P0 | Audit real OEM popup, preset contracts and conversion guardrails | Preset/code audited; pixel-level production reference capture pending |
| M5-P1 | Versioned Premium Global Popup visual template | In separate Draft branch; default `classic`, opt-in `premium_v22` |
| M5-P2 | Select business preset independently of visual | Existing `irrigation_quick_inquiry` / `bottle_oem_inquiry` / `bottle_wholesale_inquiry`, no new form schema |
| M5-P3 | CSS / regression / security gates | Scoped `public/css/andy-core-popup-premium-v22.css` and harness; CI + visual cross-device checks required |
| M6-P1 | Precise Dev-only allowlist release | Not authorized until all relevant baseline hashes, backup/rollback, CI, Dev-only activation plan are verified |
| M6-P2 | Desktop / 390px iPhone14 Owner UAT | Pending; owner must explicitly PASS |
| WWW continuation | YBY Bottle website popup visual independent development paused | Do not edit production; resume final integration UAT only after V2.2 Dev Owner PASS and separate WWW approval |

## Technical contract

1. **Two separate dimensions:** `inquiry_template` = `classic` (default) or `premium_v22`; `inquiry_preset` = one explicit approved existing preset. No automatic migration or business/site override.
2. **Original trigger/source preserved:** modal DOM ID `yby-global-inquiry-popup`, `data-yby-inquiry-modal`, `data-yby-global-popup="inquiry"`, field validation, `source_component`, `source_preset`, `form_version`, JS events, Lead SDK, REST, CRM/ERP, UTM/Google Ads attribution, success/failure handling stay owned by existing Inquiry OS.
3. **CSS architecture:** component selector `.yby-global-popup--inquiry.andy-popup-template--premium-v22`, versioned front-end stylesheet dependent on existing popup CSS; no `:root`, global `.wrap`, `button`, `form` or WordPress admin overrides. Do not load this CSS with the classic template. Follow V2.2 token conventions and explicit cache busting.
4. **Upgrade/rollback:** persist only two sanitized option keys under original `yby_core_popup_settings`; old installations default to original visual/preset. Core deactivation leaves independent Andy Commerce untouched. On rollback to prior Core, new option keys remain inert. No DB migration.
5. **No conversion changes:** never replace the existing OEM landing page form, overwrite a current Ads CTA, change submit endpoints, rewire CRM/ERP, invoke real lead submission, enable automated emails/Worker, or touch production analytics/Ads configs.
6. **Site/preset protection:** admins must verify target preset is installed/enabled on Dev and its page-profile eligibility. Do not automatically apply Bottle OEM globally to other business pages.
7. **Release gate:** GitHub code review + same-HEAD CI, initial/final Dev file hashes and exact allowlist, verified backup/rollback, WordPress role/nonce checks, JS-disabled and 390px/desktop screenshots, visual comparison against authorized production screenshot, protected campaign baseline, explicit Owner PASS.
8. **Independent PR:** compare against #111 head, not `main`. This extension is not permission to merge #110/#111, ship a ZIP to Dev, or change WWW.

## Dev-only proposed file allowlist (must be revalidated against actual Dev baseline)

- `yby-core/inc/class-yby-global-popup.php`
- `yby-core/public/class-yby-public.php`
- `yby-core/public/css/andy-core-popup-premium-v22.css` (new)
- `yby-core/admin/views/popup-settings.php`

Project documentation and tests remain GitHub-only. These four files are *not* an approved Dev upload manifest until the M6 preflight confirms the deployed V2.2 core and newer Three OS baseline; never use an old plugin ZIP to replace Dev.

## Specific UAT matrix

- Default: Classic + irrigation unchanged on desktop and iPhone14.
- Premium + Bottle OEM: visual shell, modal open/close/ESC/focus, full desktop fields, 390px contact field, original source metadata and synthetic validation.
- Premium + Bottle Wholesale: separate preset identity, same shared visual, no cross-business field leakage.
- Invalid or disabled presets fail closed, no fake success screen or test data written.
- Inquiry list/notice, Newsletter/Email/Marketing, Commerce M3/M4, Content, Brand/System screens still single-shell.
- Test no modal duplication, CSS conflicts, scroll lock regression, layout shifts, iframe/bricks collisions, consent and accessibility.
- Compare page-level original OEM Ads CTA/conversion tracking baseline read-only before/after; no real lead/transaction used.

**Current designation:** Draft code / integration in progress. GitHub passing CI is not a site visual UAT and does not authorize deployment.
