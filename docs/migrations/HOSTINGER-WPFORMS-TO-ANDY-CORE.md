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
