# YBY Core v1.3.0 Rollback Guide

1. Preserve the failed deployed plugin directory for diagnosis.
2. Restore the previous verified production plugin package.
3. Restore option snapshots when configuration rollback is required.
4. Restore the database only when schema or data corruption occurred.
5. Purge cache once and verify the site returns to the prior healthy state.
