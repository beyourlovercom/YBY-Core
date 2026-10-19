# Andy Core Update Control V1 — implementation and safety contract

Source: user-supplied Disable WordPress Updates plugin (reviewed, not copied verbatim).
Target: YBY-Core main baseline 2.2.10; branch-only feature, no version bump or deployment.

## Mandatory behavior
1. **Hide all native plugin-update badges and plugin update rows** across WordPress admin. Preserve WordPress core, theme and translation update counters/alerts.
2. **Disable other plugin automatic updates** without disabling the signed/allowlisted Andy Core updater.
3. **Delete unneeded WordPress update Cron tasks and prevent their re-creation**. Explicit whitelist only:
   - Always clean `wp_update_themes` (its background check).
   - If signed Andy auto-update is not authorized, clean `wp_update_plugins` and `wp_maybe_auto_update`.
   - Retain `wp_version_check` for WordPress core security checks; WP may trigger eligible core background upgrades during version checks.
   - When signed Andy Core auto-update is authorized, retain its necessary plugin discovery and background updater events.
   - Never touch WooCommerce, mail/newsletter, action scheduler, ERP/marketing or unknown Cron hooks.
4. Disabling Cron cleanup restores native WordPress update-check schedules using `wp_schedule_update_checks()` where available.

## Settings & rollout
Andy Core > Settings > Updates > Update Control, three independently gated checkboxes, all default OFF.
This is dev-only branch code until separately authorized integration/rollout.

## Security properties
- Do not globally filter `site_transient_update_plugins` or `plugins_api`; no tampering with update inventory, signatures or manual actions.
- Do not change user capabilities, `wp-config.php`, server cron or WP core files.
- No `remove_all_filters`, global HTTP API blocking or blanket Cron deletion.
- Settings POST requires nonce and `andy_core_settings_manage` plus security policy check.
- Domain allowlist/Ed25519/SHA256/rollback contract remains in YBY_Updater.
- Screens and plugin-specific/custom admin notices require Dev visual UAT; CI tests verify isolated policy only.
- If Disable WordPress Updates is still active on a site, its blanket updater/cron hooks may interfere and must be audited before Dev UAT (do not deactivate without owner approval).
- Hidden update notices reduce security visibility; perform periodic manual plugin update inventory audit and check Site Health.

## Verification
`php -l inc/class-yby-update-control.php`
`php tests/update-control-v1-harness.php`
`php tests/updater-hostinger-background-harness.php`
`php tests/secure-updater-harness.php`
The core regression workflow automatically executes all `tests/*-harness.php` on PR.

## Dev Owner UAT gate
- WordPress admin update bubble, plugin list rows, admin bar and native updates page: plugin notifications hidden; core security notice not hidden.
- User clicks each setting and verifies persistence and reversible behavior.
- Compare `wp cron event list` for only three named update hooks, with signed-update opt-out/opt-in cases.
- Exercise host-managed `wp_maybe_auto_update` interaction and signed Canary on approved Dev hostname, with real updates disabled until authorized.
- Confirm background checkout, mail/newsletter, Woo Action Scheduler and custom Cron are unaffected.
- Confirm native WP plugin update checks recover after cleanup option disabled.
No production or live payment/order mutation.
