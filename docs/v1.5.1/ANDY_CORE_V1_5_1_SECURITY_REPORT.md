# Andy Core v1.5.1 Security Report

## Automated evidence

CI passes PHP lint, all PHP harnesses, and all JavaScript harnesses. Static coverage confirms existing REST, Case ID, email, Thank You, Bottle, Irrigation, Social Login, and One Tap contracts remain covered.

## Owner UAT checks

The isolated Dev environment must verify capability and nonce rejection, Lead existence validation, owner validation, status and priority allowlists, tab allowlist fallback, escaped output, SQL-like search input, and read-only original Lead fields. No Production credentials or data are required.
