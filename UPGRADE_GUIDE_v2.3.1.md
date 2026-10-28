# Andy Core v2.3.1 — controlled upgrade checklist (candidate)

1. Do not install on shared Dev/WWW/Production until separately approved by Owner for that site.
2. Independently verify candidate ZIP SHA256, signed Ed25519 metadata, source SHA and version 2.3.1 against pinned public key.
3. Retain original signed 2.3.0 immutable rollback package and verify plugin update channel.
4. Preserve current schema markers: updater DB 1.4.0 and runtime DB 1.5.0; retain yby_lead_notifications, Woo orders, customers and Cron jobs.
5. Module OFF: native Woo errors and successes (with Core minimalist skin). ON: success/info use island; errors and warnings never create duplicate island.
6. Check real Woo product first-add/second-add, invalid coupon and My Account login, checkout field validation, accessibility and CSS/JS fail-open.
7. Noninteractive notification container must not show red focus box; actual action links retain keyboard-focus affordance.
8. Do not replay historical mail, trigger orders/payments or backlogged Cron in acceptance.
9. Candidate merge, signed release and each site installation require distinct approvals.
