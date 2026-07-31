# Bottle inquiry runtime contract

Andy Core owns Bottle OEM and wholesale submission through the existing `POST /wp-json/yby/v1/leads` endpoint. Bottle pages must use the governed inquiry modal and `window.YBYLead.submit`; they must not call the endpoint directly, create Case IDs, or choose a Thank You URL.

## Presets and profiles

- `bottle_oem_inquiry` is the formal OEM preset and is restricted to the `bottle_oem` page profile.
- `bottle_wholesale_inquiry` remains available for wholesale pages and has no new profile restriction.
- The REST controller rejects an unknown/disabled preset and rejects a profile that is not permitted by the selected preset. The server stores the sanitized profile in `wp_yby_leads.page_profile`.

## Builder prefill

The page may populate modal controls carrying `data-yby-field-id` before submission. The accepted OEM builder fields are `project_path`, `spirit_type`, `selected_components`, `estimated_quantity`, `target_launch_date`, and `selected_model`; they are stored in the existing `custom_fields` JSON column. `project_path` is an allowlisted select value; text values are scalar-only and are sanitized and length-limited by the field registry.

## Thank You destination ownership

- A page-level administrator value stored through the governed Page Profile may explicitly override the global Thank You target.
- Explicit page overrides are sanitized and serialized server-side in `pageProfileOverrides`; the merged compatibility profile is not treated as an explicit override.
- The Lead payload cannot choose or replace the redirect destination.
- The Case ID remains server-generated, and only `case_id` may be appended to the resolved Thank You URL.
- When the page has no explicit override, the existing global and compatibility fallback behavior remains unchanged.

`source_url` and UTM fields are sanitized as existing lead metadata. No client input can set the final Case ID or Thank You destination.

## Shared Bottle runtime

OEM and wholesale use the Bottle site profile's existing Case ID namespace, notification routing, WhatsApp template, Thank You runtime, tracking event names, and lead/tracking deduplication. Configure those values in the site/brand profile; do not create a Bottle-specific API or a second tracking runtime.
