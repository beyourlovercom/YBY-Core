# P0: YBY production outage following Andy Core 2.2.10 → 2.3.2

**Date:** 2026-10-30. **Scope:** ybyirrigation.com, incident analysis and isolated hotfix candidate. **Production status:** recovered and pinned to 2.2.10; no hotfix production deployment authorized.

## Observed symptoms and recovery

- 01:25 UTC: YBY production upgraded yby-core from 2.2.10 to 2.3.2; the updater retained a 2.2.10 directory backup.
- Dynamic homepage, WordPress REST API and wp-admin returned HTTP 500; cached homepage could still return HTTP 200.
- Independent recovery restored 2.2.10 only. The dynamic homepage, REST API and admin login then returned expected responses.
- The 2.3.2 incident copy was retained separately on the hosting account. No production database rollback was performed.

## Confirmed PHP Fatal root cause

YBY's Bricks 2.3.7 child theme already owns a custom Bricks element named yby-page-inquiry. Its inc/wholesale-inquiry-215.php registers elements/yby-page-inquiry.php on init priority 11, defining YBY_Bricks_Page_Inquiry_Element.

Andy Core 2.3.2 also registered the same element name and PHP class on init priority 11. With the actual production Bricks + child theme copied into a fully isolated WordPress/PHP/MariaDB instance, the upgrade reproduced HTTP 500 and the fatal:

Cannot declare class YBY_Bricks_Page_Inquiry_Element, because the name is already in use (inc/elements/class-yby-bricks-page-inquiry-element.php line 8).

This affects all uncached PHP requests, not just a page-specific render. An unmodified WordPress theme does not reproduce the 500, explaining why theme-agnostic PHP tests were insufficient.

**Fix:** Core registration now defers until priority 100, recognizes an already registered yby-page-inquiry element and preserves child-theme ownership. The Core fallback class has its own unique PHP name, eliminating the duplicate declaration when themes register in a different order. Existing Bricks element IDs are retained.

## Independently confirmed database migration defect

The 2.3.2 upgrade also introduces a required lead notification table while retaining runtime database version 1.5.0, causing the complete installer to run on sites coming from 2.2.10. In the older Connector schema, payout_bindings and payout_claims each had several CREATE TABLE columns on one line. WordPress dbDelta() misinterprets that format on repeat installation, emitting malformed ALTER TABLE SQL, corroborated by YBY's error log at 01:26:50 UTC.

**Fix:** one definition per line and WordPress-compatible primary key format. In an isolated MariaDB instance, installing the previous version then upgrading and invoking the installer twice passes with no WordPress database errors. These SQL errors were **not** alone responsible for the observed HTTP 500.

## Why tests missed it

1. dev.ybyirrigation.com had Andy Core 1.8.1 at the time of inspection, not the production 2.2.10 → 2.3.2 upgrade path.
2. The v2.3.2 Login OS acceptance concentrated on BYL Dev login, alias, route hiding and package signature. It did not exercise YBY's site-owned Bricks custom element.
3. PHP lint, signature verification and standalone test harnesses cannot detect a conflict between two different packages both defining the same class at runtime.
4. The release gate did not require uncached HTTP smoke, actual Bricks child-theme compatibility and idempotent WordPress database migration before allowing a site-specific upgrade.

## Isolated evidence / test matrix

| Scenario | Home | REST | Admin | Bricks element |
|---|---|---|---|---|
| Core 2.2.10, vanilla WP | 200 | 200 | 302 | N/A |
| Unpatched 2.3.2, vanilla WP | 200 | 200 | 302 | N/A; SQL errors |
| Unpatched 2.3.2 + Bricks 2.3.7 child | **500** | **500** | **500** | Fatal duplicate class |
| Candidate fix + Bricks 2.3.7 child | 200 | 200 | 302 | Child-theme class |
| Candidate fix + Bricks 2.3.7 parent only | 200 | 200 | 302 | Distinct Core fallback class |

Both Bricks scenarios pass without fresh PHP Fatal or database errors. The synthetic YBY_Database::install() double-pass also passed.

## New gates and release rules

- CI: synthetic WordPress **2.2.10 → candidate** upgrade on MySQL 8, repeatable Connector migration, uncached homepage, REST and login HTTP smoke.
- PHP regression harnesses: child-theme ownership and isolated Core-only fallback registration.
- For YBY final UAT: use Bricks 2.3.7 + the exact approved child theme, confirm admin/REST, all **actual** advertising landing URLs, inquiry capture, Case ID and attribution. No fake production inquiry writes.
- Keep production at 2.2.10 until a new, immutable signed hotfix version is built, explicitly approved and tested. Do **not** republish or overwrite the already released 2.3.2 artifact.
- GitHub PR and checks are **not** a production deployment authorization.
