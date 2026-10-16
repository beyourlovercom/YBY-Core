# Andy Core v2.2.4 — Canary code rollback

**Documentation only; no rollback or installation authorized in the current PR.**

- Verify target site/installed version and record protected previous-code snapshot from Andy Core's updater.
- If package/signature/PHP/liveness validation fails, follow fail-closed upgrader and **code-only** rollback, then verify the restored plugin version and menu behavior.
- Preserve WordPress site data; code-only backup must not be used as a database rollback.
- Do not remove the independently Owner-accepted navigation fixes when doing local source-only rollback.
- Do not touch WWW/Production, customer data, payment gateways, SMTP credentials or unattended auto-update settings without their independent gates.
