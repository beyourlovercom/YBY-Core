# Update Control V1 — Dev Cron scheduler health investigation (2026-10-19)

## Observed live facts, read-only via self-hosted OAuth DC / Dev Hostinger
- Confirmed environment is `dev.beyourlover.com`, WordPress 7.1.3, WP-CLI available.
- `DISABLE_WP_CRON` is defined as `1`: built-in page-visit cron spawning is disabled by the website configuration.
- User-level `crontab -l` returned no lines; Hostinger's separately managed/system-level scheduled tasks were **not** enumerated by this command, so we cannot categorically claim that *no* external runner exists.
- WordPress option-backed scheduled list contained events past due since 2026-10-16, including `wp_version_check`, WooCommerce admin milestones, nonces, Site Health, and the Action Scheduler runner. This strongly suggests no effective regular cron runner or insufficient execution.
- During read-only inventory a `wp cron event list --skip-plugins --skip-themes` invocation **bootstrapped WordPress with this Update Control plugin skipped** and may re-register `wp_update_plugins`/`wp_update_themes`; such a snapshot cannot be treated as a regression of a plugin that was not loaded.
- Owner screenshot with active plugin showed policy-clean Cron events absent and `wp_version_check` preserved, but it was overdue at 2026-10-16 21:18:58 UTC.

## WordPress core contract
Current WordPress `wp_schedule_update_checks()` schedules version, plugin, theme check events. `wp_version_check()` can invoke `do_action('wp_maybe_auto_update')` during real Cron. Preserving the core event is necessary but insufficient; a working external runner is needed if `DISABLE_WP_CRON=1`.

## Scope of this PR
- Separate **policy compliance** from **scheduler execution health** in read-only Andy Core Update Control admin panel.
- Clearly warn when core check is over one hour overdue, and count all pending due Cron jobs without executing them.
- Explain external-runner uncertainty and do not auto-enable WP-Cron, reschedule events, or run backlog.
- Keep 3 mandatory update-control settings, signed updater gates, native manual updates and other Cron policies unchanged.

## Further acceptance / explicit authorization required
1. Dev Owner visual screenshot for new health warning panel.
2. Confirm whether external Hostinger scheduler is intentionally off in Dev; decide safe bounded treatment of overdue tasks.
3. Any execution of overdue Cron may trigger WooCommerce, newsletter, payment-adjacent integration or mail work. **No automatic catch-up/repair without explicit owner authorization**.
4. No merge to main / Stable / WWW; retain scoped stacked PR review and GitHub CI.
