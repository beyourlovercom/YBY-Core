# Andy Core v1.5.8 Upgrade Guide

Target: Andy Core 1.5.8
Runtime database version: 1.5.0
Updater compatibility database version: 1.4.0

1. Upgrade from the current stable Andy Core 1.5.7 through the normal signed native updater or the separately authorized Production release path.
2. Do not install `v1.5.4-hotfix-completed-delivered-20260915`; 1.5.8 already includes the canonical PR #40 implementation.
3. After activation, allow Andy Core to run its idempotent database upgrade. `yby_database_version` must become `1.5.0` and the Email OS template/version tables must exist.
4. Confirm Email OS loads and WooCommerce remains provider-owned; VillaTheme must not be disabled or bypassed automatically.
5. Confirm `/wp-json/wc/v3/orders/statuses` contains the continuous segment `completed → completed-delivered → cancelled`.
6. Confirm the WordPress Connector remains authenticated and ERP Email OS consumption remains read-only.
7. Confirm no new PHP fatal errors, then complete Owner UAT before WWW release.

Compatibility note: the legacy signed updater field `database_version=1.4.0` is retained only so 1.5.7 can validate the 1.5.8 package. The package also carries `runtime_database_version=1.5.0`, and runtime migration authority is `YBY_RUNTIME_DATABASE_VERSION`.
