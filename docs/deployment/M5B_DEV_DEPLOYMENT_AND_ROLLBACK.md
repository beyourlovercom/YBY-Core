# M5B Development Deployment And Rollback

## Deployment Identity

This document records the accepted M5B development deployment for the YBY Irrigation development environment.

- Environment: YBY Irrigation development
- Development domain: `https://dev.ybyirrigation.com`
- Deployment date: `2026-07-21`
- Source branch: `feature/v1.3.0-inquiry-component-system`
- Deployed commit: `9e0363aa2f1516c0ec2a7f785cfb0521a25ba83c`
- Package name: `yby-core-v1.3.0-dev-m5b3c2.zip`
- Package SHA-256: `C5DBC1A365192E0F6DCEA255FCA31F9A0DCCE2DD5900B9740514ED043175BE71`
- Plugin version: `1.3.0-dev`
- Database version: `1.1.0`
- WordPress version: `7.0.2`
- PHP version: `8.3.24`

This was a development deployment only. It was not a production release.

## Pre-Deployment Baseline

- Lead rows: `15`
- Latest Lead ID: `15`
- Latest Case ID: `YBY-IRR-20260721-98CLV9`
- Post SMTP latest log: `39`
- Plugin active: yes
- Plugin version: `1.3.0-dev`
- Database version: `1.1.0`

## Backup Evidence

Backups were stored outside the public plugin directory.

- Backup directory: `/home/u595431186/yby-backups/m5b3c2-20260721-101607`
- Plugin archive path: `/home/u595431186/yby-backups/m5b3c2-20260721-101607/yby-core-plugin.tar.gz`
- Database dump path: `/home/u595431186/yby-backups/m5b3c2-20260721-101607/wordpress.sql`
- Option snapshot path: `/home/u595431186/yby-backups/m5b3c2-20260721-101607/yby_core_options.json`
- Checksum file path: `/home/u595431186/yby-backups/m5b3c2-20260721-101607/checksums.txt`

No credentials are recorded in this document.

## Deployment Result

- Exact commit deployed: `9e0363aa2f1516c0ec2a7f785cfb0521a25ba83c`
- Critical deployed file hashes matched the package
- Plugin remained active
- No schema change occurred
- Cache purge succeeded
- Only `whatsapp_message_template` changed during runtime configuration
- No source code changed during deployment
- No deployment commit was created

## Runtime Acceptance

Accepted PASS evidence:

- public `__testOnly` result was `undefined`
- test mode was not enabled publicly
- Brand Runtime template was exposed
- Runtime secrets were excluded
- Catalog Runtime URL won
- stale Catalog did not override
- Return URL was correct
- YouTube Runtime ID won
- Case ID rendered
- Case ID hydrated into `sessionStorage`
- WhatsApp message used real line breaks
- Case ID appeared once
- empty fields were suppressed
- internal Source data was excluded
- legacy Project/Page messages were ignored
- tracking PII was excluded
- `generate_lead` was deduplicated per Case ID per session
- external tracking endpoints were blocked during QA
- Inquiry components remained stable

## No-Side-Effect Evidence

- Lead rows before: `15`
- Lead rows after: `15`
- Latest Lead unchanged: yes
- Post SMTP before: `39`
- Post SMTP after: `39`
- Notification created: no
- Email sent: no
- Real analytics transmitted: no
- Bottle deployed: no
- Production deployed: no
- Rollback required: no

## Rollback Procedure

This rollback procedure is documented for recovery use and was not executed during the accepted deployment.

1. Place the site in a controlled maintenance window when necessary.
2. Verify the backup files and checksums.
3. Preserve the current failed plugin directory for diagnosis.
4. Restore the backed-up plugin archive to the `yby-core` plugin directory.
5. Restore the database dump only when database or options rollback is necessary.
6. Restore the `yby_core_options` snapshot when only configuration rollback is required.
7. Purge application cache once.
8. Confirm plugin activation.
9. Confirm plugin version.
10. Confirm database version.
11. Confirm Lead row count.
12. Confirm latest Lead and Post SMTP log.
13. Verify public Runtime secret exclusion.
14. Verify Inquiry and Thank You pages.
15. Document the rollback result.

Rollback triggers:

- fatal PHP error
- uncaught YBY JavaScript failure
- plugin deactivation
- database version drift
- option corruption
- Lead creation during non-destructive QA
- email sent unexpectedly
- public secret exposure
- incorrect brand or Case ID identity
- duplicate Inquiry roots
- Runtime links failing acceptance

This document does not include live passwords or authentication commands containing secrets.

## Recovery Decision

- The accepted M5B deployment did not require rollback.
- The backup remains available for recovery.
- Production deployment was not authorized.
