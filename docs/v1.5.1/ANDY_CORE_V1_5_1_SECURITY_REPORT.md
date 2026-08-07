# Andy Core v1.5.1 Security Report

## Executed validation

GitHub Actions validates the feature in isolated WordPress `6.6.2`, PHP `8.3.x`, and MySQL `8.0.x` using synthetic Leads only. The current remediation candidate adds runtime assertions for Salesperson role identity and Active Inquiry Owner assignment separation.

`SALESPERSON_IDENTITY_SOURCE`: WordPress role = `salesperson`.

`ACTIVE_OWNER_SOURCE`: Inquiry Settings Active Inquiry Owners.

`HISTORICAL_OWNER_RETENTION`: YES.

The runtime validation covers capability checks for Administrator, Editor (with the runtime toggle), Author, Subscriber, and Salesperson; status and priority allowlists; SQL-injection-like list search input; escaped note input; original-Lead immutability; no history backfill; and stale settings hardening. Editor assignment is disabled by `assignment_enabled`; Author and Subscriber have no Inbox access.

The admin implementation continues to enforce WordPress capability and nonce checks for management writes, uses the Settings tab allowlist with General fallback, and renders original Lead data read-only. Salesperson detail mode is read-only and does not expose owner, archive, restore, or follow-up write controls. No passwords, tokens, cookies, or customer data are emitted by the validation artifacts.

## Security Gates

The WordPress/MySQL validation emits `SALESPERSON_ROLE_IDENTITY_GATE`, `ACTIVE_OWNER_GATE`, `INACTIVE_OWNER_HISTORY_GATE`, `INACTIVE_OWNER_NEW_ASSIGNMENT_DENY_GATE`, `ROLE_REMOVAL_ACCESS_GATE`, `ROLE_RESTORE_HISTORY_ACCESS_GATE`, `STALE_ALLOWLIST_SECURITY_GATE`, `ADMIN_ALL_LEADS_GATE`, `DIRECT_LEAD_ACCESS_ISOLATION_GATE`, `DIRECT_LEAD_ISOLATION_GATE`, `QUERY_OVERRIDE_ISOLATION_GATE`, `DEFAULT_OWNER_GATE`, `OWNER_DROPDOWN_GATE`, `SALESPERSON_A_SCOPE_GATE`, and `SALESPERSON_B_SCOPE_GATE`.

## Remaining UAT observation

Owner UAT must exercise browser request paths for nonce/CSRF rejection, unauthorized direct URLs, menu visibility, and System Status re-check behavior in the isolated Dev environment. These are manual browser confirmations, not deferred implementation work.
