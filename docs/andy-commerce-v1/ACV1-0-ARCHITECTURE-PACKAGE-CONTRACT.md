# ACV1-0 — Andy Commerce V1 Architecture / Package Contract

Status: FROZEN FOR IMPLEMENTATION  
Date: 2026-09-24

## Runtime boundary

- Andy Core is the generic WordPress foundation.
- Andy Commerce is a separate B2C/WooCommerce Addon.
- Site themes remain presentation-focused.
- BYL-specific values belong in site configuration/presets whenever possible.
- WooCommerce business logic must not be pushed back into Andy Core.

## Repository / package layout

Near-term source remains in `beyourlovercom/YBY-Core` to reduce migration risk.

- Core runtime root: repository root.
- Commerce source package: `packages/andy-commerce/`.
- Release artifacts remain independent:
  - `andy-core.zip`
  - `andy-commerce.zip`

A B2B site must be able to install Andy Core without Andy Commerce.

## Addon dependency contract

Andy Core exposes a generic `YBY_Addon_Registry` and the `andy_core_register_addons` discovery hook.

An Addon declares:

- stable addon id
- name/version
- minimum Core version
- external requirements such as WooCommerce
- admin entry URL
- descriptive metadata

Core reports readiness/compatibility only. Core does not own Addon business logic.

## Andy Commerce V1 identity

- Plugin Name: Andy Commerce
- Slug: `andy-commerce`
- Version: `1.0.0`
- Minimum Andy Core: `1.9.0`
- WooCommerce: required
- Source path: `packages/andy-commerce/`

## Safety / migration rules

- PR #59 remains open and is only a validated migration source for Woo Order Export.
- No Production deploy.
- Do not disable/delete WebToffee.
- Do not delete BYL Platform until Commerce migration equivalence is proven.
- Do not create synthetic Woo orders on Dev.
- If Commerce needs a missing Core API, add only the generic Core API in Core scope.
