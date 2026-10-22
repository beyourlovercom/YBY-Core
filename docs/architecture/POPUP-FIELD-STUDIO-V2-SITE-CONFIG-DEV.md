# Andy Core Popup Field Studio V2 — Site-Owned Configuration

**Status: Dev-only development PR; not released or deployed.**

## Existing UI (no new menu)

Extend Andy Core → 询盘管理 → 弹窗 directly under the existing Business Preset field. Retain Visual Template, Business Preset, Title, Subtitle, Media Library image, Custom CSS, Save and Preview/Test. Show a single compact full-width field matrix, not one duplicate card per field. Select a row to edit desktop/mobile titles and placeholders in one editor; preview separately. The original WordPress POST handler and nonce remain the owner.

## Schema / per-site scope

Each WordPress installation alone owns option yby_popup_field_studio_v2. When absent/empty, legacy field registries and popups are not changed.

- Per-site custom field IDs must start with site_; support text, email, tel, number, textarea, select, radio, multiselect, checkbox. Example site_farm_size, site_bottle_category and site_film_size. **No hardcoded vertical preset is installed at other sites.**
- Site presets likewise have site_ IDs and an independent per-field desktop/mobile configuration: enabled, required, label, placeholder, plus per-preset page profile, title, subtitle, photo and layout. Disabling any field also disables its required state.
- Existing Inquiry OS field and preset filters merge opt-in saved site definitions without rewriting old defaults or replacing legacy business forms. Existing media library, Custom CSS, URL/UTM/Google click tracking, Case ID and ERP/CRM lead pipeline stay with their original owners.
- A new preset is first cloned from an existing preset (original preset remains read-only); the user explicitly chooses to apply it to the site's Global Popup and clicks the original Save button.
- Required fields are checked in the native browser form and REST controller against trusted site configuration. The client device hint is not an authentication mechanism: fields that must universally be required (e.g. business email) must be marked required for both desktop and mobile.

## Security

Original admin capability/nonce, strict site_ ID whitelists, type lists, limits of 30 site fields / 16 site presets / 30 options each, escaped output, no arbitrary JS stored in Studio, and serialized JSON size limit. Legacy presets remain unaffected until opted in.

## Release gates

Independent GitHub Draft PR and CI → disposable Dev-only WP UAT in the **existing** settings tab → desktop/M14 Owner PASS → separately authorized exact-head merge → single shared signed Andy Core release → site-by-site opt-in. Never create per-site Core forks. No production web updates, lead submissions, ERP mutations or Ads activation without independent approval.

## Known compatibility detail

The template selector is independent of the business preset; visually intensive styles remain site-owned. The inline form preview is a no-network mock; the existing Preview/Test opens the last saved native renderer.
