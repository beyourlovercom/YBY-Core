# Andy Core V2 — Hostinger File Manager / DEV ONLY — 2026-10-15

**WARNING: Release candidate for manual Dev UAT, NOT Production or a whole-plugin installer. Never merge this release PR to main.**
- Source: PR #106 HEAD `92ce92eb21cce0a65a1a26ec0ff1373b52224bd5` (CI 4/4 SUCCESS), Dev-only release branch `release/andy-core-native-v2-hostinger-dev-20261015`.
- Live Dev target: `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/yby-core/`.
- The four existing PHP files are based on *actual current Dev*, not an older PR's WordPress plugin snapshot. Original live blobs must exactly match these preconditions:

| Existing Dev file | Original Git Blob SHA1 | Expected action |
|---|---|---|
| `admin/class-yby-andy-os-navigation.php` | `ddcc74b448ae60b598277596c054f344c552218e` | Replace with V2 controller |
| `admin/class-yby-project-studio.php` | `aa4d8b24065652cfab1ad44b12f0f3ddeb780541` | Replace precisely patched Dev V1 file (two root label changes) |
| `inc/class-yby-core.php` | `ddaba7cfd0c88fdc95dfe34aa183a427f5441149` | Replace precisely patched Dev V1 bootstrap file (four hook registrations only) |
| `inc/class-yby-landing-page-cpt.php` | `36a76c4b4a052a4a0b6b7734ced0862ef0cb8d25` | Replace precisely patched Dev CPT (show_in_menu only) |
| `assets/css/andy-core-native-l3-v2.css` | absent | Add new CSS |
| `assets/js/andy-core-native-l3-v2.js` | absent | Add new JS |

## Before ANY Hostinger action

1. In Hostinger confirm the target site is **dev.beyourlover.com**, never www.beyourlover.com. Independently inspect exact original SHA. A mismatch means STOP; regenerate from changed runtime.
2. Backup the four current PHP originals into separate date-labeled downloadable copies, with validated integrity and a restoration pointer **outside active plugin directory**.
3. Download **exactly these six files**, one by one, from `release/andy-core-native-v2-hostinger-dev-20261015` under `deploy/hostinger-dev-andy-core-nav-v2/`. No full Core/Commerce ZIP, no database import or migrations.
4. Stage/add the CSS and JS assets first, then replace `admin/class-yby-andy-os-navigation.php`, `inc/class-yby-landing-page-cpt.php`, `inc/class-yby-core.php`, finally `admin/class-yby-project-studio.php`; preserve paths and names exactly. Avoid renaming files with `.php.txt`.
5. After upload, externally verify final Git Blob SHA values against this release branch, PHP lint and WordPress plugin bootstrap. Stop if any fail.
6. Authenticated Dev Owner UAT: WordPress left L1 **Andy Core**; L2 Inquiry/Newsletter/Email/Content/Marketing/Commerce/System visible by role; no forced Overview/card; right L3 displays only actual existing handlers. Verify Business, Inquiry, Newsletter, Email, Commerce, native Landing CPT editor; direct old URL, no duplicate top tabs. Desktop + iPhone14/M390.
7. Frontend read-only regression: /business/, /cart/, /blog/, /checkout/ (empty-cart redirect is expected). **No actual submissions, orders, payments, real email, ERP writes or enabled mail Worker**.
8. Any fatal, 403, broken link or layout regression: immediately **restore all four original PHP files** and **delete two new assets** (only if absent before) to return to exact V1; verify restored hashes, then investigate. Do not overwrite WWW. Do not restart READY devices.
9. User alone may record Owner UAT PASS after observing the real Dev admin interface. Passing CI is not Owner PASS.

**Two plugins remain independently updateable.** No Andy Commerce, Three OS, Mail, newsletter tables or Woo runtime files included here.
