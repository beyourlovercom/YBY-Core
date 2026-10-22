# YBY Bottle Popup V3 — Bricks Visual Canonical (Dev-only)

Status: **locked visual design; Owner final visual UAT pending** (2026-10-21).

## Authoritative design / ownership
- **Owner-selected visual**: "Premium Glass Bottles for Global Brands" / approved *Luxury Glass Bottle Project Form UI.png* mockup.
- **Bricks owns** visual popup template (editable image, photographic overlay text, typography, colors, positioning, responsiveness) and landing-page click interaction. Reuse **template #759** on `dev.ybybottle.com`; do not create another popup template.
- **Andy Core owns** site Studio preset `site_bottle_wholesale_v2_uat`, desktop/mobile field matrices, labels, placeholders, required flags, sanitization, lead submission, attribution/Case ID/GA4 deduplication. **No second form or second lead endpoint.**
- No other popup source may visually override the Bottle page: Core Global Popup `#yby-global-inquiry-popup` is **not** the OEM visual.
- **Do not hard-code brand styles in shared Andy Core CSS**. Avoid stacking new global CSS and site MU loaders. Bricks template-local style is the canonical visual after cleanup.

## Current Dev facts (source read-back)
- Existing OEM landing page **#219** `/lp/glass-bottle-oem/` has element `oemhtml` with **six** anchors using `data-yby-modal-open="yby-bottle-oem-modal"`; it contains an existing `oemmodal` shortcode with `bottle_oem_inquiry`. **Do not silently replace or delete those existing triggers before Owner UAT.**
- New Bricks Popup **#759** remains **Draft** with 14 editable elements and a scoped custom CSS visual contract (desktop split image/form; mobile stacked hero/form, scrollable fields).
- Hero currently uses existing single-bottle `hero-custom-spirits-bottle.webp`, which differs from the approved reference's *multi-bottle* photograph. Flag for Owner review; do not claim exact pixel match.
- The new Bricks `[yby_inquiry]` integration uses the site Studio preset and has been updated in this feature PR to output separate device field lists with server-side field presentation and required rules. One form, one endpoint.
- The old Core Global Popup still retains the legacy irrigation preset; no change to the global or WWW state.
- Bricks documented behavior: Popup template must be published and its template condition must include the target page before page click interactions can open it. **Draft alone cannot pass real page-trigger UAT**. An isolated Dev-only publish/condition change and reversible CTA rewiring are separate, gated steps.

## Acceptance gates
1. Bricks visual read-back: editable photo/overlay headings/three benefits, no missing CSS, responsive mobile scroll and CTA; record screenshots at desktop and **375×812 M14**.
2. Functional: Studio site fields are per-device; check email/quantity/category required, prevent inactive controls from payload; no dual form/duplicate leads.
3. Preview safety: no real lead/CRM writes/payments/emails during UAT.
4. **Owner final visual PASS** before replacing legacy OEM CTA and before making it a public frontend popup.
5. Release: PR remains **Draft**. Any GitHub main merge, Stable release or production/www change requires separate exact-head authorization. Preserve backups/rollback.
6. CI red must be diagnosed, not bypassed: latest head `510112d984aa1b2cfff0533e0f1ead5fc7d6e121` had failing Release Package Validation (PR merge provenance) and Andy Core Regression (Mail P9 baseline drift of `inc/class-yby-core.php`); local PHP Studio tests passed, but **whole PR CI is not PASS**.

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
