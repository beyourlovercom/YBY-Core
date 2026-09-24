# Andy Core v1.9.0 — Addon Foundation

## Decision

Andy Core remains the generic WordPress foundation. Business-specific extensions are separate Addons. C-end / commerce business logic does not belong in the Core package.

This release introduces a generic Addon Registry only. It does not create, install, remove, activate, or deactivate any Addon.

## Runtime contract

External first-party Addons register on:

`andy_core_register_addons`

using:

`YBY_Addon_Registry::register( $id, $metadata )`

Supported metadata includes name, description, version, plugin file, minimum Core version, plugin dependencies, and settings URL.

## Inactive installed discovery

An installed Addon may expose plugin headers:

`Andy Core Addon: <addon-id>`

`Requires Andy Core: <minimum-version>`

This lets Core report installed/inactive/compatibility status without knowing any product-specific Addon in advance.

## States

- ready
- inactive
- not_installed
- incompatible_core
- dependency_missing
- unknown

## Safety boundary

The Addons page is read-only for lifecycle operations. Andy Core does not automatically install, delete, activate, or deactivate plugins.

## Architecture boundary

Andy Core owns:

- Addon registration contract
- dependency/compatibility status
- generic Addons UI
- System Status projection

An Addon owns:

- its business runtime
- product-specific dependencies
- business settings
- product menus and workflows

WooCommerce / B2C behavior must not be added to the generic registry.

## Legacy Commerce extraction debt

The pre-1.9 Core codebase already contains historical provider-specific Connector bindings such as affiliate, coupon, payout, and a Woo order-status bridge. They predate the v1.9 Addon decision.

They are not expanded in v1.9. Their migration/removal must be coordinated with the approved Commerce Addon so existing ERP contracts are not broken. They must not be deleted from Core before an equivalent receiving implementation and regression evidence exist.

## Release gate

Andy Core v1.9.0 is not ready for release until:

1. Addon Registry tests pass.
2. Core-only regression passes.
3. Core + approved Addon integration contract passes.
4. Product version/package metadata is closed at 1.9.0.
5. Windows localdev Owner UAT passes.
6. Production remains separately gated.
