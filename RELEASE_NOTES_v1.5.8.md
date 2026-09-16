# Andy Core v1.5.8 Release Notes

Release type: Stable Email OS / WooCommerce integration update
Plugin version: 1.5.8
Runtime database version: 1.5.0
Updater compatibility database version: 1.4.0

Andy Core 1.5.8 introduces Email OS V1.1 as the owner-facing email governance center. WordPress and Andy Core native templates now support Draft / immutable Published lifecycle, SHA-256 integrity validation, native runtime adapters, health/test tooling, and read-only ERP Published metadata consumption.

WooCommerce transactional mail remains owned by WooCommerce and the active template provider. Email OS provides registry, diagnostics, deep links and Legacy Customizer governance without registering Woo outbound runtime replacement hooks or replacing VillaTheme.

This release also includes PR #40 `completed-delivered / 完结&送达`. WooCommerce REST order statuses preserve the ordered segment `completed → completed-delivered → cancelled`.

The runtime database schema advances to 1.5.0 for Email OS template/version tables. The signed updater compatibility marker remains 1.4.0 so existing Andy Core 1.5.7 installations can validate and install 1.5.8 through the native updater; signed metadata additionally records `runtime_database_version=1.5.0`.

The obsolete `v1.5.4-hotfix-completed-delivered-20260915` must not be installed. Version 1.5.8 contains the canonical completed-delivered implementation directly.
