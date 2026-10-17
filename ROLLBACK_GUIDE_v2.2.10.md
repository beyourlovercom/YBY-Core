# Andy Core v2.2.10 — code-only rollback plan (per-site Owner-approved deployment only)

- Publication of v2.2.10 does not itself install WordPress plugin files. This rollback applies only to a separately approved site installation.
- Before any separately authorized deployment, capture the precise installed site's Andy Core code version, signed candidate SHA256, plugin inventory, WordPress Core version, active themes, database runtime version and a code-only archive stored outside public_html with restrictive permissions.
- Require the plugin path `yby-core/yby-core.php`; use only signed verified releases and preserve the site-specific Canary/Stable allowlist and auto-update OFF/ON choice.
- If a future install fails signatures, backup, version/hash, runtime health or unrelated-plugin invariants: stop other UAT triggers, restore Andy Core automatic updates OFF, restore the isolated Cron to read-only `--check` if changed, preserve evidence and offer **Owner-approved** restoration of plugin files only.
- Never roll back or overwrite WordPress database tables, real orders/payments, inquiry/mail records, WooCommerce/ERP business settings or SMTP credentials as an automated recovery.
- Recheck site HTTP health, installed plugin version, WordPress Core, themes, other plugins and disabled unintended modules before marking a future site rollout PASS.
