# Andy Core v1.9.0 Rollback Guide

## Scope

Rollback is code-only. Andy Core v1.9.0 does not require a database migration, so the runtime schema remains 1.5.0.

## Rollback

1. Deactivate only if required by the recovery procedure.
2. Restore the previously backed-up yby-core directory or the prior signed release package.
3. Confirm the restored plugin version.
4. Confirm Andy Core admin pages and public runtime load normally.
5. Confirm existing option/database data is unchanged.
6. If Andy Commerce is installed, verify its minimum-Core compatibility before reactivating it.

## Addon boundary

Rolling back Andy Core does not authorize deleting or changing any separate Addon.
If an Addon requires Andy Core 1.9.0, leave that Addon inactive until compatible Core is restored.

## Production boundary

No rollback action on www.beyourlover.com is authorized by this guide alone.
Production/cutover actions require the explicit Production gate.
