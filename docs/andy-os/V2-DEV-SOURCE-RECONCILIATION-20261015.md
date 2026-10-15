# Andy Core L1/L2/L3 V2 — Dev Source Reconciliation & Release Gates

**2026-10-15.** This is a read-only authority note for Draft PR #106. Do not upload the PR as a whole plugin ZIP or overlay Dev files directly.

## Source authority map

| Dev installed file | Dev Git blob (raw) | Closest tracked GitHub source | Comparison |
|---|---|---|---|
| `yby-core/admin/class-yby-three-os-admin.php` | `cb4c1fbef010693076f7194b2dc63b4de7422251` | Three OS PR #94 `f1a09f4fa5c2556fb35d9da41f82cbb8ad0226c6`; P6 PR #101 `ee7426fc25ebb7ce869099b09f71d89d127eb018` | **Real functional Dev overlay**: Dev has queue/failure preview integration beyond M6; preserve Dev current |
| `yby-core/admin/class-yby-three-os-readonly.php` | `ef59cddafde25142c39937e63b1817297e53aa29` | PR #94 `5b513d624f3bcc0243d4b20fb67df6247ba182f4` | Normalizing CRLF→LF yields **identical contents** |
| `yby-core/admin/class-yby-mail-v2-p6-dev-preview.php` | `5b76fc54856a76fd13cd699d0eb5423fac454f1b` | PR #101 `631f4d9196c0f06880643a1060bfa102bbbbc968` | Normalizing CRLF→LF yields **identical contents** |
| `andy-commerce/admin/class-andy-commerce-admin.php` | `52346e54f73546533009ecfd32134b12875f38bc` | Commerce M3 and M45 Draft sources; `aab9aded0c297003423dfc95a1fb3d534dec4e90`, `1983060f390cfc25d851431c27b24116235b5111` | **Real functional divergence**, do not replace live M3/M4 functionality |
| `yby-core/admin/class-yby-andy-os-navigation.php` | `ddcc74b448ae60b598277596c054f344c552218e` | V1 Draft PR #103 / Hostinger PR #104 | Owner V1 UAT REJECTED; V2 supersedes **only navigation**, not old module controllers |

Read-only snapshots gathered via authorized Dev SSH; no credential transfer and no write to Dev.

## New V2 scope

In GitHub isolated Draft PR #106, only the **navigation adapter, Project Studio root label, Core hook registration and scoped L3 CSS/JS**, plus related harnesses, are changed. No changes to Three OS, Commerce M3/M4, newsletter tables, SMTP, WP database, real inquiries or checkout.

- L1 visible label: **Andy Core**, original slug `yby-os`.
- L2: seven WordPress native submenus (Inquiry, Newsletter, Email, Content, Marketing, Commerce, System), gated individually with **existing capabilities**.
- L3: content-area **inner-left sidebar** of links to actual preexisting tab/page URLs, and original controllers render the right area. No mandatory Overview/blank card page; static UI registry cannot generate arbitrary URLs from user input.
- Module OFF means no fake link (e.g. Docs OFF; queued SMTP mail OFF).
- Direct POST/nonce handling remains original.
- Mail-only and newsletter-only root capabilities are explicitly included.
- Core remains independent from Andy Commerce (Commerce retains own fallback if Core disabled).

## Critical validation / fail-closed gates

1. **No Dev deployment until** PR #106 exact-head CI (including genuine disposable WordPress admin router), page-by-page mapping review and Owner structure/feature confirmation.
2. Read-only capture current Dev `yby-core` runtime hashes immediately before preparation. Dev Core bootstrap and Project Studio differ from historical GitHub branch by existing overlays; produce exact **line-only patches** from current Dev files, never upload old GitHub entire files.
3. Allowlist must include CSS/JS as well as PHP if release is approved; no whole plugin ZIP.
4. Dev release only after backup outside webroot, verified rollback pointer, PHP lint, JS syntax, Git SHA, source diff, exact site/brand lock; use Hostinger File Manager or future authorized DC allowlist.
5. Actual authorized-account UAT covers all L2 and L3, old direct bookmark permission, Newsletter-only, Email-only, Woo manager, Inquiry-only, disabled Docs, and **no duplicate top horizontal tab navigation**. Confirm desktop and iPhone14/M390 viewport before Owner PASS.
6. WordPress Gutenberg editor, native Docs taxonomy and Landing Page/Project CPT remain in native editor context. No reparenting of unrelated WordPress or third-party plugin menus.
7. No GitHub main merge or WWW deployment without a separate explicit authorization.

## CI and limits

A GitHub disposable WordPress+MySQL test can validate menu registration and route access. It cannot substitute for Owner's real Dev browser screenshots or prove WooCommerce M3 / Newsletter P6 runtime behavior where the current Dev file is newer. Treat browser tests as independent gates.
