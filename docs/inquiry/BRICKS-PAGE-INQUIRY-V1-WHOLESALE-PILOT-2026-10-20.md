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
6. **Server-side required fields come from the trusted Core preset**, not a visual asterisk. PR #179 now includes the opt-in `page_owned_email_required` preset; Email is pinned server-side and Name/Email are rendered on desktop/M14 even if a page editor omits them. **Do not release to WWW before isolated REST negative tests and Owner visual PASS**.

## Proposed #215 Bricks settings for first UAT
| Control | Candidate value |
| --- | --- |
| Popup title | Request a Wholesale Quote |
| Subtitle | Tell us which irrigation products and quantities you need. |
| Image | Choose actual Wholesale photo in the existing WordPress media library (do not add unverified assets) |
| Mobile image | Hide |
| Preset | `page_owned_email_required` (built-in, locked Email requirement; no manual site preset setup) |
| Desktop fields | `name,email,whatsapp,country,product_interest,quantity,message` |
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
- **Open dependency:** actual Andy Core 2.2.10 REST Email-empty/invalid negative tests, WordPress Bricks editor Save→Reopen UAT, and Hostinger incident clearance. A Windows LocalWP 1.6.0 test does not prove the 2.2.10 server contract; never silently deploy Draft PR #175.
- **Owner visual UAT gate:** Windows LocalWP real Chrome 1440×900 and M14 390×844 field visibility + scrolling passed previously; desktop layout Owner PASS. PR #179 only adds mobile touch polish, with no desktop CSS edits. Verify exact current HEAD on an approved clean Dev, authenticated Bricks Save→Reopen, Email-empty REST negative tests and final Owner M14 PASS; Case ID/ERP only on explicit permission.
- **Rollout:** inspect real Bricks #215 saved structure, add a single new element without deleting the old component, verify page preview, then separately approve page-only WWW save/publish. Never enable Ads automatically.

## Known initial release limitations
- This is a page-owned Bricks element, **not** a complete redesign of the existing Andy Core Popup Field Studio admin. It reads a server preset ID; it does not let frontend editors weaken server validation.
- Color and dialog width are whitelisted. Mobile image is optional and off by default to prioritize field reachability.
- The universal Classic global popup's separate mobile-field bug remains a distinct global fix and must be tracked, not hidden by this new page-only element.

## 2026-10-21 correction and scope lock

- Owner confirmed **ybyirrigation.com WWW Andy Core 2.2.10**, updated the day before. Windows LocalWP is **1.6.0**, which was only an isolated visual fixture; do not treat that as the WWW version or request an unnecessary Core reinstall.
- Freeze desktop split-layout visuals; mobile-only touch target and overscroll polish in PR #179.
- Cancel separate standalone plugin ZIP plan. Canonical solution remains a single Andy Core Bricks element in Draft PR #179; never run both implementations.
- A separate Hostinger P0 compromise investigation remains open. **No new plugin/code installation, admin login, or forms data submission on that host until security clearance and explicit production authorization.**
