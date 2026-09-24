# Andy Core v1.9.0 — Release Notes

Release date: 2026-09-24

## Highlights

Andy Core v1.9.0 introduces **Addon Foundation V1**. Andy Core remains the generic WordPress foundation; product-specific B2C / Commerce runtime is intentionally kept outside the Core package.

## Addon Registry V1

- Runtime registration action: `andy_core_register_addons`
- Registry API: `YBY_Addon_Registry::register()`
- Reports installed, active, minimum-Core compatibility, and plugin dependency state.
- Supports optional inactive-installed discovery through `Andy Core Addon` and `Requires Andy Core` plugin headers.
- Exposes a read-only Addons admin surface and System Status summary.

## Lifecycle safety

Andy Core does not automatically install, remove, activate, or deactivate Addons. Addon lifecycle changes remain explicit administrative actions.

## Commerce boundary

Andy Commerce and Woo Order Export are not included in Andy Core v1.9.0. The previously validated Woo Order Export work remains outside this Core release and is handled by the separately approved Commerce Addon stream.

Historical affiliate/coupon/payout Connector behavior that predates this architecture remains compatibility debt and is not expanded in v1.9.0. Its eventual extraction requires an equivalent receiving implementation and regression evidence.

## Compatibility and data

- Core-only operation remains supported.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Existing module storage keys and stable routes remain unchanged.
- Production deployment remains a separate explicit gate.
