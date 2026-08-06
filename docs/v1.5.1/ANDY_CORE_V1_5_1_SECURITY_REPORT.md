# Andy Core v1.5.1 Security Report

## Executed validation

GitHub Actions run `31078688839` validated the feature in isolated WordPress `6.6.2`, PHP `8.3.33`, and MySQL `8.0.46` using synthetic Leads only.

The runtime validation passed capability checks for Administrator, Editor (with the runtime toggle), Author, and Subscriber; owner validation; status and priority allowlists; SQL-injection-like list search input; escaped note input; and original-Lead immutability. Editor assignment is disabled by `assignment_enabled`; Author and Subscriber have no Inbox access.

The admin implementation continues to enforce WordPress capability and nonce checks for management writes, uses the Settings tab allowlist with General fallback, and renders original Lead data read-only. No passwords, tokens, cookies, or customer data are emitted by the validation artifacts.

## Remaining UAT observation

Owner UAT must exercise browser request paths for nonce/CSRF rejection, unauthorized direct URLs, menu visibility, and System Status re-check behavior in the isolated Dev environment. These are manual browser confirmations, not deferred implementation work.
