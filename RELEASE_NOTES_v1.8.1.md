# Andy Core v1.8.1 — Release Notes

Release date: 2026-09-23

## Highlights

Andy Core v1.8.1 adds **Analytics / GTM Control Layer V1**. GTM4WP 2.x remains the WordPress / WooCommerce tracking engine; Andy Core provides the governed event, site-profile, provider-adapter, consent-adapter, and diagnostics control layer around it.

## Analytics module

- Registry module id: `analytics`
- Default state: OFF
- Settings option: `yby_analytics_settings_v1`
- Provider: GTM4WP
- Andy Core does not store or inject a GTM Container ID, GA4 Measurement ID, or Google Ads conversion ID.

## Business Event Contract

The existing browser API `window.YBYTracking` remains compatible. The canonical V1 event registry preserves:

- `generate_lead`
- `thank_you_page_view`
- `click_whatsapp`
- `click_whatsapp_after_lead`
- `download_catalog`
- `submit_project_details`
- `return_to_lp`
- `view_case_study`

Inquiry runtime now routes events through the governed tracking facade rather than directly owning arbitrary `dataLayer.push` calls.

## GTM4WP adapter

- Uses GTM4WP's public `GTM4WP_VERSION` identity only; no private provider API dependency.
- Explicit V1 compatibility range: `>=2.0.0` and `<3.0.0`.
- Does not inject a second GTM container.
- Does not mutate GTM4WP settings.
- Does not take ownership of WooCommerce ecommerce provider events.
- Dev UAT observed GTM4WP `2.0.2`.

## Analytics Site Profile

Provides a read-only canonical projection of trusted site identity and existing tracking context, including tracking group, GA4 content group, and Ads conversion group. Existing Project/Page metadata remains authoritative and is not migrated or rewritten.

## Duplicate-GTM diagnostics

Diagnostics can detect duplicate GTM loaders, duplicate noscript iframes, and multiple container IDs. Cleanup policy is fixed to `diagnose_only`; Andy Core does not automatically remove GTM4WP, theme, snippet-plugin, or manually installed tags. Dev UAT found one container, `GTM-MQS84CR4`, with no duplicate injection.

## Consent Adapter

Consent mode is fixed to `respect_existing`. Andy Core does not create a default granted/denied state, call Google Consent mutation APIs, or modify a CMP. External CMP/GTM integrations may project normalized consent state through the documented adapter filters.

## Compatibility and data

- Existing `window.YBYTracking` behavior remains compatible.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Analytics remains default OFF after upgrade until explicitly enabled per site.
- Production deployment is intentionally deferred while v1.9.0 development continues.
