# Project Module

## Purpose

Provide the canonical project-level business object for YBY marketing projects.

## Current MVP Status

- `YBY_Project` exists
- project values are read from ACF when available
- project values fall back to `get_post_meta()`
- project values merge with Page Profile compatibility fields
- project values merge with global config defaults
- `window.YBYProject` exists
- `window.YBYPageProfile` remains available as compatibility layer

## Required ACF / Meta Fields

### Project Identity

- `yby_project_id`
- `yby_project_name`
- `yby_project_type`
- `yby_project_status`

### Market

- `yby_country`
- `yby_language`
- `yby_region`
- `yby_target_market`

### Product

- `yby_product_interest`
- `yby_product_category`
- `yby_product_line`

### Assets

- `yby_catalog_url`
- `yby_youtube_video_id`
- `yby_case_study_url`

### Views

- `yby_landing_url`
- `yby_thank_you_url`
- `yby_return_page_url`

### Messaging

- `yby_whatsapp_message`
- `yby_email_subject`
- `yby_email_intro`

### Tracking

- `yby_tracking_group`
- `yby_ga4_content_group`
- `yby_ads_conversion_group`

### CRM

- `yby_crm_pipeline`
- `yby_crm_owner`
- `yby_lead_priority`

## Priority Rule

Project runtime values follow this order:

1. YBY Project
2. YBY Page Profile
3. YBY Core global config
4. System defaults

## Future Roadmap

- project-aware admin visibility
- project-specific reporting helpers
- controlled CRM mapping after framework approval
