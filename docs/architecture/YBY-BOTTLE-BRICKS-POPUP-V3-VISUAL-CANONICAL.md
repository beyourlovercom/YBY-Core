# YBY Bottle Popup V3 — Bricks Visual Canonical (Dev-only)

Status: **visual design locked; #759 final public-page visual UAT remains gated**. Evidence dates below are historical snapshots, not assertions of today's live Dev state. Integrated Andy Core source is in main via PR #182 (2026-10-22).

## Authoritative design / ownership
- **Owner-selected visual**: "Premium Glass Bottles for Global Brands" / approved *Luxury Glass Bottle Project Form UI.png* mockup.
- **Bricks owns** visual popup template (editable image, photographic overlay text, typography, colors, positioning, responsiveness) and landing-page click interaction. Reuse **template #759** on `dev.ybybottle.com`; do not create another popup template.
- **Andy Core owns** site Studio preset `site_bottle_wholesale_v2_uat`, desktop/mobile field matrices, labels, placeholders, required flags, sanitization, lead submission, attribution/Case ID/GA4 deduplication. **No second form or second lead endpoint.**
- No other popup source may visually override the Bottle page: Core Global Popup `#yby-global-inquiry-popup` is **not** the OEM visual.
- **Do not hard-code brand styles in shared Andy Core CSS**. Avoid stacking new global CSS and site MU loaders. Bricks template-local style is the canonical visual after cleanup.

## 2026-10-22 Dev facts (dated source read-back; recheck before any site change)
- Existing OEM landing page **#219** `/lp/glass-bottle-oem/` has element `oemhtml` with **six** anchors using `data-yby-modal-open="yby-bottle-oem-modal"`; it contains an existing `oemmodal` shortcode with `bottle_oem_inquiry`. **Do not silently replace or delete those existing triggers before Owner UAT.**
- New Bricks Popup **#759** remains **Draft** with **31 native, separately editable Bricks elements** and a scoped custom CSS visual contract (desktop split image/form; mobile stacked hero/form, scrollable fields).
- Hero currently uses existing single-bottle `hero-custom-spirits-bottle.webp`, which differs from the approved reference's *multi-bottle* photograph. Flag for Owner review; do not claim exact pixel match.
- The new Bricks `[yby_inquiry]` integration uses the site Studio preset and is included in the Owner-approved Studio/Bricks integration source to output separate device field lists with server-side field presentation and required rules. One form, one endpoint.
- The old Core Global Popup still retains the legacy irrigation preset; no change to the global or WWW state.
- Bricks documented behavior: Popup template must be published and its template condition must include the target page before page click interactions can open it. **Draft alone cannot pass real page-trigger UAT**. An isolated Dev-only publish/condition change and reversible CTA rewiring are separate, gated steps.

## Acceptance gates
1. Bricks real-page visual read-back: editable photo/overlay headings/three benefits, no missing CSS, responsive mobile scroll and CTA. **Isolated structure/CSS Playwright layout PASS** at desktop 1440×960, M14 375×812 and narrow 320×700; **live authenticated Bricks and Owner M14 visual PASS remain pending**.
2. Functional: Studio site fields are per-device; check email/quantity/category required, prevent inactive controls from payload; no dual form/duplicate leads.
3. Preview safety: no real lead/CRM writes/payments/emails during UAT.
4. **Owner final visual PASS** before replacing legacy OEM CTA and before making it a public frontend popup.
5. Release: Andy Core integration PR #182 **was merged to main** (commit `e2bc8bb851ab99bef6d8c0362e497cdaab293bf4`), but **no corresponding Stable release or site rollout is authorized by that merge**. This documentation reconciliation remains Draft. Any new code merge, Stable release or Dev/WWW installation requires its own approval. Preserve backups/rollback.
6. Historical CI context: older standalone Studio head `510112d984aa1b2cfff0533e0f1ead5fc7d6e121` had Release Package/Mail P9 failures; those results must **not** be attributed to current main. Integrated PR #182 head `621c2c59723ac87dbbd81e82f06e6347c1d52ea9` passed **4/4 CI** before its authorized main merge. Preserve all release safety checks.

## Immutable visual copy
- Left brand line: **YBY Bottle**
- Eyebrow: **GLASS BOTTLE OEM & CUSTOM DEVELOPMENT**
- Left heading: **Premium Glass Bottles for Global Brands**
- Advantages: **Custom design & mold development**; **Distinctive decoration & finishing**; **Solutions for global brands**
- Right heading: **Start Your Bottle Project**
- Right description: *Tell us about your bottle requirements and our team will follow up with you within 24 hours.*
- CTA: **Submit Bottle Project**
- Mobile must keep a readable overlay over photo, then vertically stack the form.

Any change to visual hierarchy, image strategy, or copy is a new Owner visual approval, not a casual UI regression adjustment.

## 2026-10-22 Dev-only evidence (dated snapshot, not final public visual signoff)
- Current #759 post type `bricks_template`, template type `popup`, status `draft`, 31/31 native Bricks elements verified by authorized Dev WordPress readback; old 14-node visual baseline preserved in staging for rollback.
- Current template-local CSS SHA256: `fbf51a3bff0756ce4cada5882d3c07c410e587de304e459b1a04c7bbf2b97575`. Single authoritative visual template; no new shared plugin CSS or new Popup ID.
- Native elements: gold square “Y”, separate white headline “Premium Glass Bottles for”, gold “Global Brands”, three icon + short description rows, editable right form and privacy note.
- Playwright offline visual metrics: at M14 375×812 hero 357×229 and form 357×451, form scrollHeight 770 > clientHeight 451, CTA bottom 734 < 812, horizontal overflow false, hero clipping false. At 320×700 also no overflow/clipping. These metrics come from a safe local structure-driven rendering; **do not present them as live post-activation UAT**.
- Actual Dev PHP inquiry shortcode render from the deployed candidate: `FORM_COUNT=1`, desktop responsive markers 8, mobile 7, submit initially disabled. Current field matrix comes exclusively from `site_bottle_wholesale_v2_uat`. Offline screenshot test fields are illustrative and do not override the live mobile Company=OFF rule.
- Unresolved visual gap: approved mockup contains a multi-bottle photo; existing onsite hero image is single-bottle. Do not replace it with an unverified product shot or mark pixel-perfect visual PASS.
- Before any release or site deployment, re-fetch **current** PR/main CI and target-site state. The 2026-10-22 PR #182 main-only merge changed GitHub source, **not** Stable/WWW, OEM #219 public triggers or live lead operations.
- Distinguish **#759** (existing Bricks OEM popup visual template) from **#762** (separate Bottle Dev Draft native `YBY · Page Inquiry Popup` element used for isolated Save/Reopen/390px UAT under PR #182). Do not replace #759 or redirect #219 CTA based on #762 test success.
