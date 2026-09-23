# Andy Core Analytics / GTM Control Layer V1

Status: V181-0 architecture contract for Andy Core v1.8.1.

## Purpose

Andy Core owns the **Analytics Control Layer** while GTM4WP 2.x remains the WordPress / WooCommerce tracking engine.

Andy Core does not fork GTM4WP, inject a second Google Tag Manager container, or become a second WooCommerce ecommerce tracking engine.

## Ownership boundary

Andy Core owns:

- module enable/disable state
- versioned Analytics settings
- Site Profile analytics context
- canonical Business Event Contract
- GTM4WP adapter boundary
- Consent Adapter boundary
- duplicate-GTM diagnostics / cleanup support
- debug and contract validation surfaces

GTM4WP owns:

- GTM container/runtime integration already configured by the site
- WooCommerce ecommerce dataLayer behavior supplied by GTM4WP
- provider-specific WordPress integration details

Business modules must not depend directly on GTM4WP classes/functions and must not push arbitrary ungoverned payloads directly to `dataLayer`. They publish approved business events through the Andy Core Analytics contract.

## Compatibility

The existing `YBY_Tracking` helper remains a compatibility facade. v1.8.1 must not break existing `window.YBYTracking`, historical event names, page-profile tracking context, or Thank You tracking wrappers while ownership moves behind the Analytics layer.

## Module policy

- module id: `analytics`
- version: `1.0.0`
- schema version: `1`
- default: OFF
- dependency: Core Runtime + Module Registry
- settings option: `yby_analytics_settings_v1`

Default OFF is intentional until GTM4WP compatibility and duplicate-GTM cleanup are verified on each target site.

## V1 settings contract

- `provider`: `gtm4wp`
- `data_layer_name`: `dataLayer`
- `site_profile`: `auto`
- `consent_mode`: `respect_existing`
- `debug`: boolean, default false

The settings contract does **not** store GTM Container IDs, GA4 Measurement IDs, Ads Conversion IDs, Google credentials, or consent-vendor secrets.

## Event ownership rule

The future Business Event Contract must define approved names, required/optional fields, PII policy, dedupe identity, and adapter projection before an event is emitted.

Existing event names observed in the compatibility facade include:

- `generate_lead`
- `thank_you_page_view`
- `click_whatsapp`
- `click_whatsapp_after_lead`
- `download_catalog`
- `submit_project_details`
- `return_to_lp`
- `view_case_study`

This document records existing names; V181-3 will decide their canonical contract and payload schemas.

## Explicit non-scope for V181-0..2

- no GTM container injection
- no GA4 tag injection
- no Google Ads tag injection
- no GTM4WP setting mutation
- no WooCommerce ecommerce event replacement
- no consent-state mutation
- no Production deployment

## V181-3 Business Event Contract

The canonical event registry is implemented by `YBY_Analytics_Event_Contract`. The existing `YBY_Tracking` facade sources its public event list from that contract, preserving the historical event names and order.

Inquiry runtime no longer pushes directly to `window.dataLayer`; it calls `window.YBYTracking.push()` so payload sanitation and the future provider adapter have a single ownership point.

The V1 registry records event category, dedupe policy identifier, and a hard `pii=false` contract. Detailed per-field schemas and provider projection can evolve without allowing arbitrary business-module dataLayer writes.

## V181-4 GTM4WP Adapter

`YBY_Analytics_GTM4WP_Adapter` detects the active provider through GTM4WP's public `GTM4WP_VERSION` constant and exposes bounded runtime diagnostics. The adapter does not call GTM4WP private APIs, mutate GTM4WP settings, inject a container, or take ownership of WooCommerce ecommerce events.

The existing frontend tracking facade keeps one provider push seam. When the Analytics module is OFF, the historical `window.dataLayer` behavior is preserved. When enabled, the adapter may project the validated versioned `data_layer_name` setting.

Dev evidence on 2026-09-23 observed GTM4WP plugin path `duracelltomi-google-tag-manager/duracelltomi-google-tag-manager-for-wordpress.php` with public `GTM4WP_VERSION=2.0.2`.

## V181-5 Analytics Site Profile

`YBY_Analytics_Site_Profile` provides a read-only canonical analytics context without migrating or rewriting existing Project/Page metadata.

The projection contains trusted site identity (`site_brand_key`, `site_brand_name`, `website_host`) plus the existing runtime tracking dimensions (`tracking_group`, `ga4_content_group`, `ads_conversion_group`). Project runtime remains the compatibility source, so Page Profile fallback behavior for `trackingGroup` is preserved.

Frontend business events read the canonical Site Profile first and fall back to the historical `YBYProject` / `YBYPageProfile` keys. The Analytics module can also load the shared public runtime when Inquiry OS and Project Studio are disabled, while remaining default OFF.
