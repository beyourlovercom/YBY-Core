# Andy OS V1 — Hostinger File Manager / Dev-only release

- GitHub source: Draft PR #103, exact head `91286e28de751cd724ffcb345134e1d4a454c095`; branch `release/andy-os-v1-dev-hostinger-20261014` is **a Dev-only release staging branch**, not GitHub main.
- Files were built by patching **current Dev** runtime sources, not overwriting them from an old GitHub archive.
- Two originals verified before patching: `admin/class-yby-project-studio.php` SHA256 `1f51884996281f86e2fd89bba8ce37ac871c86dcf6e10da7cd91044f1ba2ab17`, `inc/class-yby-core.php` SHA256 `d0b16dec903e0ea96f6c30c33747662d434588aaa49715af538061a275ff0ef5`.
- New file `admin/class-yby-andy-os-navigation.php` matches source PR blob `ddcc74b448ae60b598277596c054f344c552218e`.
- No Commerce plugin files, database migrations, real orders, payments, messages, SMTP, WWW or ERP files included.

## Gate (mandatory)

**Only** use Hostinger File Manager for `dev.beyourlover.com` path `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/yby-core/`.

1. Download current Dev originals `admin/class-yby-project-studio.php` and `inc/class-yby-core.php`; verify SHA256 matches the above hashes exactly. If either differs: STOP and regenerate, do not overwrite.
2. Save independent backup copies in a dated folder **outside the active plugin** (or download locally). Confirm backups can be restored before proceeding.
3. Open staging branch `release/andy-os-v1-dev-hostinger-20261014`, navigate to `deploy/hostinger-dev-andy-os-v1/`. Download exactly three PHP files individually; **do not upload the whole repository or plugin ZIP**.
4. Upload `admin/class-yby-andy-os-navigation.php` (new) first to Dev plugin admin folder, then replace **only** `inc/class-yby-core.php`, then `admin/class-yby-project-studio.php` with the matching staged files. Avoid WWW. Files contain no data migration and no reboots are needed.
5. If WordPress reports a critical error or admin becomes inaccessible: restore both backed-up originals and remove new navigation file. Do not troubleshoot against live production.
6. Validate with actual authorized WP-admin account, `admin.php?page=yby-os`, six visible sections, no duplicate Core/Content/Commerce root, `andy-business`, `yby-core`, `andy-core-leads`, `andy-commerce` and existing Cart/Checkout; verify content editor and phone viewport. Record owner UAT only after observed PASS.

## Do not mistake success

GitHub CI 4/4 PASS establishes code-check baseline; **Hostinger upload and Dev Owner UAT are not completed by merely preparing this release.**
