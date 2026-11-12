# Hostinger WPForms → Andy Core | isolated migration lane

**State:** R0 inventory implementation; no WordPress installation, uninstall, production deployment or main merge.

## Boundaries

- Dedicated branch: feature/wpforms-to-andy-core-site-migration, from main ee540507bdd2ab35963a8c5da214bd1f24c9d912 (2026-11-12).
- This R0 PR modifies only tools/wpforms-migration/, a dedicated offline test and this roadmap. No shared runtime, WordPress admin UI, plugin bootstrap, package version, Andy Commerce or AGENTS.md changes.
- One version of Andy Core for all target sites; each site owns its field/preset/notification settings. Do not create site-specific forks.
- Nothing may disable WPForms, send test emails, delete customer submissions or silently change public content in this stage. Draft PR != merge or deploy approval.

## Observed Hostinger baseline

Read-only SSH, WordPress SQL and plugin inspection on 2026-11-12: 12 WordPress sites have active WPForms Lite; 23 form definitions; 44 published posts containing the text 'wpforms'. The latter are only *possible* references, not proof of 44 displayed embeds. Elementor, widget, option and theme dependencies also need inspection. Another 7 substantial WordPress installs have no WPForms Lite; other host directories are essentially empty.

| WordPress site directory | Forms | Published content mentions | Status |
| --- | ---: | ---: | --- |
| blanchedalmond-flamingo-601606.hostingersite.com | 2 | 6 | Contact / business |
| darkgoldenrod-camel-347943.hostingersite.com | 2 | 2 | Email / contact |
| darkturquoise-bee-276354.hostingersite.com | 2 | 5 | Contact / email |
| dodgerblue-mantis-171767.hostingersite.com | 2 | 3 | Email / contact |
| fondomanpowergroup.com | 2 | 6 | Email / RFQ |
| funnyslip.com | 2 | 6 | Business / contact |
| gsfdcy.com | 2 | 2 | One provider-configured empty form: block pending review |
| lightcoral-weasel-975054.hostingersite.com | 2 | 4 | Email / contact |
| lightsteelblue-caterpillar-245164.hostingersite.com | 2 | 6 | Business / contact |
| mediumpurple-meerkat-978434.hostingersite.com | 1 | 1 | Contact |
| moccasin-badger-830954.hostingersite.com | 1 | 1 | Contact |
| skyblue-gaur-779563.hostingersite.com | 3 | 2 | Contact / selects / email |
| **Total** | **23** | **44** | **12 sites** |

WPForms Lite occupies 60,278 filesystem file+directory entries across those 12 installed copies. Andy Core has already been installed at ybybottle.com (2.2.10) and ybyirrigation.com (2.3.3); neither was among the 12 with WPForms installed. Full rollout net saving of ~57,500 inodes was a preliminary *estimate*, not a completed improvement.

## R0 read-only audit

Run from a reviewed branch checkout on the target site's approved environment:

    wp --path=/home/u595431186/domains/SITE/public_html --skip-plugins --skip-themes eval-file /path/to/YBY-Core/tools/wpforms-migration/audit-site.php

The command emits JSON with source form IDs, field types, required flags, count of choices, integration/notification flags, and possible published page/Elementor/option references. It NEVER exports form submissions, recipients, options, provider secrets, field values or message text. Never use it as an uninstall trigger.

Check the output's errors and truncated_at_500 flags. Published-content SQL LIKE matches are conservative leads for review and must be checked individually; dynamic or PHP-rendered embeds are outside the audit's coverage.

## Roadmap, gates and rollback

| Stage | Deliverable | Gate |
| --- | --- | --- |
| R0 | Reproducible audit + safe inventory + 12-site backlog | PHP lint, offline harness, review |
| R1 | Draft Field Studio V2 conversion and embed mapping | All 23 forms' fields/choices/providers classified |
| R2 | Simple + multi-field site Local/Dev UAT | Mailbox delivery, lead persistence, spam/consent, desktop/mobile Owner PASS |
| R3 | Controlled site-by-site signed Andy Core rollout | Replace embeds first; verify both paths before disabling WPForms |
| R4 | Review 7 other substantial WordPress sites | Install Core only where the target site requires it |
| R5 | Recount inodes, post-cutover smoke and evidence | Confirm old form data preserved and approved rollback |

### Mandatory no-go conditions

- Missing or malformed source form fields; unsupported file upload, payment, provider integration or conditional behavior.
- Unmapped confirmation email, auto-reply, destination inbox, subscriber consent or provider.
- Form ID references still present in published content, Elementor, widgets, theme or custom code.
- Unverified site-level Popup Field Studio configuration, anti-spam/rate limit, form submission and mobile behavior.
- No Owner visual PASS or approved deployment procedure.

### Offline validation

    php -l tools/wpforms-migration/class-inventory.php
    php -l tools/wpforms-migration/audit-site.php
    php -l tests/wpforms-migration-inventory-harness.php
    php tests/wpforms-migration-inventory-harness.php

No test here proves site runtime, real mail receipt, or safe WPForms uninstall. Keep rollback backups, source WPForms definitions and existing form data until separately authorized removal.


## R0/R1 implementation evidence (2026-11-12)

- R0 audit executed **read-only with WordPress CLI** against all 12 installed WPForms sites after independent PHP lint/offline checks: **12/12 PASS**, no audit errors or 500-item truncations.
- Exact R0 totals: **23 forms / 79 field instances / 0 unknown field types / 44 published post-content references** (the 44 are candidates that require human placement review). Elementor and WordPress options can also contain references; counts can overlap, so do not add them to 44 as a distinct-page total.
- R1 candidate draft tool also executed read-only at all 12 sites: **12/12 JSON PASS / 23 inactive candidate presets / 45 draft site-specific fields**, plus built-in name and email fields. No presets enabled and **ready_to_apply=false** everywhere.
- Found production compatibility gates that cannot be silently copied: notification destination/confirmation behavior at all 12 sites; several distinct select choice values; multipart name field behavior; some forms without an email field; and gsfdcy.com provider integration.
- CI and offline test success **does not** approve the final email, anti-spam, embedding, frontend, or uninstall behavior.
- Test command: php tests/wpforms-migration-draft-harness.php (compares generated candidate to real YBY_Popup_Field_Studio::sanitize). Candidate state stays disabled.
- Draft CLI: wp --path=/home/u595431186/domains/SITE/public_html --skip-plugins --skip-themes eval-file /path/to/YBY-Core/tools/wpforms-migration/draft-site.php
- The draft CLI produces only a **review candidate**; never use it to overwrite an existing site option. Existing Andy Core settings, integration credentials, and old form content are not modified by these tools.


## R2 pilot evidence — moccasin-badger (2026-11-12)

Only site moccasin-badger-830954.hostingersite.com was changed. Main, the other 11 WPForms sites, the original Contact page ID 213 and its WPForms form ID 429 remain unchanged. WPForms is still ACTIVE.

### Backup, canonical test package and isolation

- Pre-R2 DB backup is stored outside webroot: /home/u595431186/.andy-r2-backups/moccasin-20261112/pre-r2.sql.gz; gzip verification PASS, 63 CREATE TABLE statements; SHA256 b09411483babae6f36b5fcc4968649f51864bd6c6ad92101cdc16998ff249b6f. It is mode 600 in mode 700 directory.
- A reproducible **unsigned UAT-only** Andy Core v2.3.3 package was assembled from canonical GitHub main commit ee540507bdd2ab35963a8c5da214bd1f24c9d912: 209/209 exact blob hash comparisons PASS, 154/154 PHP lint PASS. One divergent installed connector file was replaced **only in the staging copy** with the matching canonical GitHub content. Package SHA256 b561e1c51b5a16773903a728e7e485774ccff1b3942486e5376f64cf83839381. This package is NOT a signed Stable release and must NOT be propagated to other sites.
- Andy Core v2.3.3 was installed and activated solely at the pilot site. The enabled-modules option was initially seeded empty; only Inquiry OS was enabled for the test.
- Source-controlled pilot-only MU guard (tools/wpforms-migration/r2-moccasin-preview-guard.php) scopes Inquiry OS frontend to the preview URI; ordinary homepage and Contact page have no Andy Core popup JS and original WPForms is still present. The guard rejects ANY public Andy Core lead POST with HTTP 403 / andy_r2_leads_disabled. No real lead or email was submitted.
- Pilot preview post ID 555, slug /andy-core-r2-moccasin-429-20261112/, is password protected, noindex and nofollow, with a site-owned Field Studio V2 preset site_wpforms_429. Actual preview password is not stored in this repository. Input Name/Email required; Subject/Message optional. Preview submission button is disabled and additionally guarded server-side.

### R2 upstream bug: site Studio shortcode

The v2.3.3 shortcode render_modal_shortcode() passes an imploded field-ID **string** to resolve_field_ids(), which only accepts an **array**, when site studio preset desktop_fields is omitted. The modal thus silently fails to render. Reproduced on the pilot. A one-time site-locked workaround (tools/wpforms-migration/r2-moccasin-fix-shortcode.php) updates only preview page 555 to supply explicit desktop_fields and mobile_fields attributes. Shared Andy Core code/main were not changed. Must add an actual regression/fix before R3 production migration.

### Actual validation

- Public homepage and /contact/ return HTTP 200. /contact/ includes the existing WPForms UI, not the Andy Core modal. Password-protected preview page HTTP 200 and noindex.
- Real browser automation using a **separate disposable Chrome 156 headless session** (no restart or user Chrome takeover): desktop 1440px, mobile 390px; custom R2 modal opened and closed via actual click; modal dimensions 960px and 370px; four labels in both responsive layouts, Name and Email required; no horizontal overflow, no browser JavaScript exception; form submission disabled. Retested after site-isolation guard update.
- Six independent WordPress-required-field validation cases passed, including empty/invalid email and minimum-valid desktop/mobile. No lead writes.
- REST POST to /wp-json/yby/v1/leads returned **HTTP 403**, code andy_r2_leads_disabled.
- This is **automated R2 visual/interactivity PASS only**: no Owner visual acceptance and **no real mail, notification inbox delivery, or lead-database end-to-end PASS**.

### Remaining gates

- [ ] Owner visual PASS on password-protected pilot preview.
- [ ] Separate authorization for controlled synthetic lead test, SMTP acceptance, actual mailbox delivery and anti-spam.
- [ ] Fix site Studio shortcode default resolution and cover by regression before full R3.
- [ ] Signed shared release and site-by-site cutover authorization; WPForms stays enabled until replacement proven.

### Pilot rollback (not executed)

Preserve any legitimate records. Disable the R2 Inquiry OS module; delete only the R2 test post ID 555 and test-only Studio configuration, deactivate the R2 Andy Core instance, then remove the pilot-only MU plugin. Original WPForms form/page remain unmodified. Do not blindly restore the old database dump over newer content. Retain off-webroot backup.


## R5 FINAL readback — 2026-11-12 (supersedes earlier active-WPForms pilot notes)

Owner specifically authorized WPForms removal on the nine migrated Contact First sites. All nine plugins have now been **deactivated and deleted** after site-scoped reference replacement, rollback snapshot, and real public HTTP verification. No source-form database records were deleted; the other three sites are reserved for Owner manual migration.

| Migrated site | WPForms file+directory entries before | After | Contact HTTP / inline form |
| --- | ---: | ---: | --- |
| blanchedalmond-flamingo-601606.hostingersite.com | 4967 | 0 | PASS |
| darkgoldenrod-camel-347943.hostingersite.com | 4967 | 0 | PASS |
| darkturquoise-bee-276354.hostingersite.com | 4967 | 0 | PASS |
| dodgerblue-mantis-171767.hostingersite.com | 4967 | 0 | PASS |
| lightcoral-weasel-975054.hostingersite.com | 4967 | 0 | PASS |
| lightsteelblue-caterpillar-245164.hostingersite.com | 4967 | 0 | PASS |
| mediumpurple-meerkat-978434.hostingersite.com | 4967 | 0 | PASS |
| moccasin-badger-830954.hostingersite.com | 4996 | 0 | PASS |
| skyblue-gaur-779563.hostingersite.com | 4996 | 0 | PASS |
| **Total** | **44761** | **0** | **9/9 PASS** |

Remaining WPForms directories untouched: funnyslip.com 5541 entries, fondomanpowergroup.com 4967 entries, gsfdcy.com 5009 entries. All three source plugins are ACTIVE. Thus exact WPForms directory count across 12 target sites decreased from 60278 to 15517 entries: 44761 freed.

Nine rollback ZIPs were saved outside webroot under /home/u595431186/.andy-contact-first-backups/20261112/, totaling 124531345 bytes. They add 9 file entries. After excluding only these 9 backup ZIP entries, the directly attributable plugin-files-versus-ZIP inode reduction is 44752; the overall hosting-account inode delta can vary due to other backups, installation changes, and temporary files. A post-run home directory scan saw 311739 accessible filesystem entries (271089 files, 40632 directories including root, 18 symlinks) but encountered 55 inaccessible paths, so this **is not a certified Hostinger hPanel quota reading**.

Additional resolved dependencies before deletion: four old WPForms inquiry widgets across the About/Services pages of blanchedalmond and lightsteelblue, eleven newsletter or demo-appointment Elementor widgets across five sites, and one active lightcoral Blocksy Footer Gutenberg WPForms block. They were replaced with canonical Contact-page links, with complete pre-edit snapshots saved outside webroot. This deliberately retires their old standalone newsletter signup and simple appointment-request workflows; it does not promise equivalent mailing-list or booking functionality. All nine public Contact pages returned HTTP 200, rendered the Andy Core site_contact_v1 form and nonce, and did not display the old WPForms form. All 17 old WPForms form-definition database rows were preserved.

WordPress SMTP acceptance for earlier synthetic submissions does NOT prove actual inbox delivery. Signed Andy Core v2.2.11 was used for eleven deployment sites; the approved moccasin R2 trial had v2.3.3. GitHub main was not merged; branch-specific migration and regression work stays in Draft PR #315.

Recovery: restore the site's exact ZIP to its own plugins path, reactivate WPForms, restore page/Elementor/footer-widget snapshots as relevant, and disable the site-local Contact First gate before cutting back. Do not blindly restore full DB over newer real leads.
