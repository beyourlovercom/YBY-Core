# Andy Core v2.2.9 — U3D signed Canary candidate

## Purpose
- Version-only successor to **published** v2.2.8 Canary; **no application runtime, update policy, mail, schema, or security changes**.
- Prepare evidence for genuinely Hostinger-scheduled (unattended) WordPress `wp_maybe_auto_update` / `WP_Automatic_Updater` installation **2.2.8 -> 2.2.9** on `dev.beyourlover.com`.
- Keep all U3B/U3C achievements: pinned Ed25519, SHA256, Hostinger opt-in exception, domain/channel allowlist, unrelated-plugin/Core/theme/translation isolation, bounded rollback.
- Preserve updater compatibility DB 1.4.0, runtime DB 1.5.0, plugin path `yby-core/yby-core.php`, Hostinger managed MU, Mail OS, WooCommerce, orders, payments, checkout, SMTP.

## Current live state (before publication)
- U3B native updater 2.2.7 -> 2.2.8 PASS using controlled WP-Cron event, NOT unattended.
- U3C Hostinger unattended **check-only** invocation PASS at 2026-10-17T05:00:10Z from hPanel View Output.
- BYL Dev 2.2.8 Active / Canary / auto OFF; Hostinger U3C isolated custom Cron `0 */4 * * *` running **--check**, not **--execute**.
- Bottle Dev 2.2.2 Active / Canary / auto OFF; Stable/Latest 2.2.6 unchanged.

## Gates that must remain separate
1. Private exact-head Draft PR / four CI checks / 8 signed candidate attachments, signature + ZIP SHA256 + backward compatibility PASS.
2. Separate explicit Owner gate: publish 8 assets as **v2.2.9 Canary Prerelease** on public release repository, never Latest; no merge.
3. Separate explicit Owner gate: BYL Dev isolated Hostinger Cron **--execute**, temporary Andy Core auto ON, real unattended firing with no manual dispatch. Do not install via manual ZIP, wp plugin update, or wp-cron.php global queue.
4. Capture real scheduler output, signed updater logs, plugin/hash/backup/health/non-target diffs, and return auto OFF promptly. Never modify another site, Production, BYL WWW, Hostinger MU, DB, orders/payments/mail.
