# Bricks Page Inquiry V1 — Wholesale Master Pilot

**Owner decision 2026-10-20:** all future Bricks landing pages own their popup content/layout/images/desktop-mobile fields. One universal Andy Core REST + Case ID backend; no fork or duplicate lead authority.

## Target
- **Pilot WWW page only:** `https://ybyirrigation.com/lp/irrigation-wholesale/` (Wholesale master #215). **This PR does not edit that WordPress page.**
- US #826 / Germany #824 / global popup / Google Ads / existing leads are out of scope.
- Owner screenshots show mobile modal title + submit with zero visible fields; desktop wastes ~half the dialog. Andy Core 2.2.10 classic global popup passes no mobile fields to renderer but CSS hides desktop fields at <=859px. Do not use it as the page-owned modal.

## Architecture
1. Bricks native element **YBY · Page Inquiry Popup** (General) is registered only if Bricks and Inquiry OS are active.
2. Bricks stores each element's title, subtitle, real image, optional mobile image, text, color, width, CTA routing, preset ID, desktop + mobile field IDs inside that page's Bricks element settings.
3. The element invokes existing `[yby_inquiry_modal]`; no new endpoint, lead table, notification, tracking event or conversion. It always supplies explicit nonempty mobile fields, even when editor's mobile list is empty. Existing plugin renders the popup later in the footer.
4. Client routing script applies only to pages with that element. It targets `[data-yby-inquiry-trigger]` and `a[href="#yby-inquiry"]` if *no explicit* `data-yby-modal-open` is present. Existing explicit targets are preserved. Exactly one page module should be used per page.
5. A small per-modal CSS class fixes mobile bottom-sheet scrolling and keeps the submit button separate from fields. Global/other page popups unchanged.
6. **Server-side required fields always come from the active preset**, not from a client-side red asterisk. Until a proper site-owned Email-required preset is installed and tested, **do not release the pilot to WWW**.

## Proposed #215 Bricks settings for first UAT
| Control | Candidate value |
| --- | --- |
| Popup title | Request a Wholesale Quote |
| Subtitle | Tell us which irrigation products and quantities you need. |
| Image | Choose actual Wholesale photo in the existing WordPress media library (do not add unverified assets) |
| Mobile image | Hide |
| Preset | `site_wholesale_quote` **only after this site-local Email-required preset is saved and server-tested** |
| Desktop fields | `name,company,email,whatsapp,country,product_interest,quantity,message` |
| Mobile fields | `name,email,whatsapp,country,product_interest,quantity,message` |
| Submit | Request Wholesale Pricing |
| Dialog width | 960px |
| CTA routing | All page inquiry CTA buttons |
| Accent | #2e7d32 |
| Thank You | Retain / verify page profile → #310 `/lp/thank-you-irrigation-wholesale/` |

**No cross-page operations**: changing title, image or fields on #215 must not change the popup for #826, #824 or any other page. Do not override named CTA targets that already point to a dedicated modal. When migrating a page, remove its obsolete page-specific PHP Code modal only after full UAT and rollback snapshot.

## Current PR versus production
- **Code status:** feature branch / Draft PR only.
- **Site status:** 2.2.10 and existing WWW forms unchanged.
- **Open dependency:** site-owned `site_wholesale_quote` preset/Email-required server validation (can be configured after Popup Field Studio V2's separate release/UAT); never silently deploy Draft PR #175.
- **Owner visual UAT gate:** M14 390×844 and desktop 1440×900 on actual Bricks preview. Confirm form fields are visible, scrollable, correctly arranged; CTA scroll and close/reopen; Email-empty REST negative tests in isolated environment, then Case ID/ERP tests only on explicit permission.
- **Rollout:** inspect real Bricks #215 saved structure, add a single new element without deleting the old component, verify page preview, then separately approve page-only WWW save/publish. Never enable Ads automatically.

## Known initial release limitations
- This is a page-owned Bricks element, **not** a complete redesign of the existing Andy Core Popup Field Studio admin. It reads a server preset ID; it does not let frontend editors weaken server validation.
- Color and dialog width are whitelisted. Mobile image is optional and off by default to prioritize field reachability.
- The universal Classic global popup's separate mobile-field bug remains a distinct global fix and must be tracked, not hidden by this new page-only element.
