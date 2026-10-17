# Andy Core U3C — Hostinger isolated unattended WP-Cron design (Draft)

**Status: GitHub-only design; not installed or scheduled.** Workstream: [Issue #131](https://github.com/beyourlovercom/YBY-Core/issues/131). Do not merge or deploy without separate Owner authorization.

## Evidence
- U3B on `dev.beyourlover.com` successfully installed signed `2.2.7 -> 2.2.8` by running the **specific** WordPress-native `wp_maybe_auto_update` cron event. Other plugin, core and theme versions remained unchanged; automatic updates returned OFF.
- However, `DISABLE_WP_CRON=1`; no standing `wp_maybe_auto_update` event; numerous unrelated WooCommerce / Jetpack / Action Scheduler events are overdue. The user-level SSH shell does not expose `crontab`, so Hostinger hPanel Cron Jobs must be checked separately.
- Read-only Hostinger checks confirmed `/usr/local/bin/wp`, `/usr/bin/bash`, `flock`, `timeout` available. Installed BYL Dev is v2.2.8 Active / Canary / auto OFF. This candidate does NOT modify those settings.
- Official Hostinger hPanel supports **Websites → Dashboard → Cron Jobs → Custom**, with UTC schedule. The exact Hosting plan, existing Cron inventory and scheduler quotas are not yet checked by authenticated hPanel read-only UI.

## Proposed U3C staged gates
1. **Code-only / Draft PR (this step):** Keep `u3c-byl-dev-cron.sh` in GitHub. Syntax-check and review boundaries. Default `--check` is strictly read-only. Do not upload to Hostinger or create hPanel Cron entries.
2. **Separate Owner gate: dry-run scheduler:** Review hPanel Cron inventory; only if no overlap, authorize secure site-specific staging of this script outside `public_html` and a *single* Hostinger **Custom** Cron entry (e.g., every 6 hours UTC) calling `/usr/bin/bash <private-script> --check`. Record time and hPanel task output; auto stays OFF. This proves unattended dispatch **only**, not an auto-upgrade.
3. **Separate Owner gate: full unattended UAT:** After preparing, signing and separately authorizing immutable **v2.2.9 Canary Prerelease**, confirm v2.2.8 installed and 2.2.9 offered. Authorize temporarily setting Andy Core auto ON on BYL Dev and changing that **same one site-specific** hPanel task to `--execute`. Confirm no manual CLI triggering or admin Update Now occurs. Wait for real hPanel scheduler firing; preserve task output and WordPress updater evidence, post-install version, SHA256/Ed25519, rollback and isolated non-target versions. The script attempts only `wp_maybe_auto_update`, with `flock` and 180-second `timeout`, exact-source/target check and fail-closed automatic OFF restore.
4. Remove/disable the test Cron job or revert to read-only `--check` after UAT. Keep Bottle Dev Canary/OFF, Production and BYL WWW unchanged; no Hostinger-managed MU modifications, DB migrations or mail/payment/order writes.

## Why not the usual wp-cron.php / --due-now?
Because dozens of unrelated scheduled tasks are overdue, global Cron dispatch could invoke real WooCommerce, Jetpack, Action Scheduler, orders or mail. This proposal only runs the WordPress-native `wp_maybe_auto_update` event and never dispatches other queued events.

## Non-promises
- With auto OFF, this script **cannot** install a plugin. The check-only task does not prove plugin delivery.
- Even when `--execute` is invoked, the script permits only a future **signed v2.2.9** update from installed v2.2.8; no alternate or later target.
- The script assumes WordPress Core 7.1.3 and will fail closed on drift; never silently loosen guards.
- `--execute` unavoidably writes a WordPress update-check transient, a scoped Cron event, the plugin files **only through** the WordPress automatic updater, its bounded backup, and changes auto-option back OFF. These writes are not authorized by simply approving this Draft PR.
- hPanel task configuration is not available from SSH read-only inspection; validate it in the actual Hostinger account before enabling anything.

## Proposed hPanel command once approved
`/usr/bin/bash /home/u247343654/domains/dev.beyourlover.com/.aicc-andy-core-u3c/u3c-byl-dev-cron.sh --check`

For the later **separate** full UAT gate only, replace `--check` with `--execute`. Place the script outside the web root with restrictive permissions. Use hPanel task output for proof; do not create another global WordPress Cron worker.
