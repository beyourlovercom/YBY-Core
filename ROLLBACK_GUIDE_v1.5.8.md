# Andy Core v1.5.8 Rollback Guide

Rollback type: code-only
Target release: Andy Core 1.5.8

1. Use the existing Andy Core updater backup/rollback flow or restore the immediately previous validated plugin code package.
2. Do not install `v1.5.4-hotfix-completed-delivered-20260915` as a rollback substitute.
3. Database rollback is not performed. Email OS tables created by runtime database version 1.5.0 remain in place and are ignored by older code that does not use them.
4. Existing WooCommerce/VillaTheme ownership is preserved; rollback must not delete, unpublish or reprioritize Legacy templates.
5. After rollback, verify WordPress admin, WooCommerce order status behavior, inquiry runtime and updater health before reopening normal operations.

If 1.5.8 is reinstalled after a rollback, its idempotent installer must reassert `yby_database_version=1.5.0` without destructive backfill or template mutation.
