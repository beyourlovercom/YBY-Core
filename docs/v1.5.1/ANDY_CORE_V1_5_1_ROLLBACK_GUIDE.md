# Andy Core v1.5.1 Rollback Guide

Use only in the isolated Dev/UAT environment before production authorization.

1. Disable the v1.5.1 plugin and restore the prior v1.5.0 plugin package.
2. Restore the database from the Dev backup only if migration verification fails; never delete `yby_leads`.
3. Confirm the stored database option returns to `1.2.0`, the original Lead table is present, and Lead REST/Case ID/email/Thank You checks pass.
4. Remove only the v1.5.1 management and activity tables if the Dev migration created them and the approved rollback procedure permits it.
5. Record the package, SQL backup, hashes, operator, timestamp, and validation results.

Production rollback requires separate explicit authorization and a fresh production backup.
