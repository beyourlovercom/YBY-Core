# Page Profile Module

## Purpose

Provide page-level runtime configuration for landing pages and Thank You flows.

## Current MVP Status

- `YBY_Page_Profile` exists
- ACF `get_field()` is used when available
- `get_post_meta()` is used as fallback
- page profile values are merged with global config
- `window.YBYPageProfile` exists
- Page Profile remains available after Project Engine introduction
- Thank You URL, catalog URL, WhatsApp message, product interest, and tracking group can be page-specific

## Compatibility Rule

`window.YBYPageProfile` remains the compatibility layer.

If `window.YBYProject` also exists:

1. `window.YBYProject` is canonical
2. `window.YBYPageProfile` remains available for existing integrations

## Supported Fields

- `yby_profile_id`
- `yby_page_type`
- `yby_product_interest`
- `yby_country`
- `yby_catalog_url`
- `yby_youtube_video_id`
- `yby_thank_you_url`
- `yby_return_page_url`
- `yby_whatsapp_message`
- `yby_crm_pipeline`
- `yby_tracking_group`

## Future Roadmap

- more page-type presets
- safer admin visibility for profile values
- broader page integration after staging validation
