# Andy Core v1.5.8 — Email Template OS V1 — Canonical Architecture

Status: ARCHITECTURE FROZEN
Date: 2026-09-14
Branch: `feat/andy-core-v1.5.8-email-template`
Base: `main@b8cf37b5b2cec4b0e3d14be58aae51a0731e2407`

## 1. Product ownership

Andy Core is the source of truth for email template identity, structured content, variables, global Email VI, preview rendering, publication state and published-version metadata.

Andy Core does NOT own SMTP credentials, provider transport, queue delivery, retries or inbox deliverability. Those remain in the transport layer (for example WP Mail SMTP or the site's chosen mail provider).

ERP is read-only. ERP may consume published template metadata through the existing authenticated Andy Core Connector contract, but it must not edit templates or render runtime email HTML.

## 2. Final IA

Location: `Andy Core → Settings → 邮件模板`

Existing Settings tabs remain:
- 常规
- 询盘
- 询盘通知
- 邮件模板
- WP-API
- 系统状态
- Updates

Email Template OS internal sections:
- 概览
- 品牌样式
- WooCommerce
- WordPress
- Andy Core
- 发布与测试

The historical Andy Core submenu named `Email` is not the Email Template OS. Its current responsibilities are popup / floating-inquiry settings; it must be renamed or folded into the proper settings area to avoid IA collision.

## 3. Registry contract

The registry is runtime-discovered, not a hard-coded list.

Each template identity MUST include at least:
- `template_key`
- `provider` (`woocommerce`, `wordpress`, `andy_core`, future extension)
- `source_class` / source identifier when available
- `audience` (`customer`, `admin`, `system`)
- `label`
- `description`
- `runtime_available`
- `legacy_template_ref` when a legacy mapping exists
- `capabilities` / supported dynamic sections

WooCommerce registry entries are discovered from the runtime mailer classes so third-party email classes such as Smart Coupon remain visible.

## 4. Storage model

Database version target: `1.5.0`.

Planned tables follow existing `wp_yby_*` ownership conventions:

### `wp_yby_email_templates`
Stores one mutable template identity / working state.

Minimum fields:
- id
- template_key (unique)
- provider
- label
- status (`draft`, `published`, `disabled`)
- working_payload (LONGTEXT JSON)
- published_version_id (nullable)
- created_at
- updated_at

### `wp_yby_email_template_versions`
Stores immutable published versions.

Minimum fields:
- id
- template_id
- version_number
- content_hash_sha256
- payload_snapshot (LONGTEXT JSON)
- published_by
- published_at

Published version rows are immutable. Editing always changes the working draft, never the currently published snapshot.

### `wp_yby_email_design_settings`
A table is NOT required for V1 unless future multi-profile needs justify it. Global Email VI should initially use a governed Andy Core option payload because there is one active design profile per site.

## 5. Template payload contract

V1 uses structured blocks rather than free-form whole-email HTML.

Canonical working payload includes:
- subject
- preheader
- heading
- intro_copy
- dynamic_sections[]
- primary_cta
- secondary_copy
- additional_content
- enabled_blocks[]
- template_specific_settings{}

Renderer-owned shared structure includes:
- document / email-safe table shell
- global header
- global footer
- typography
- spacing
- CTA component
- responsive rules
- safe variable escaping

Template payload must not own duplicate global Header/Footer markup.

## 6. Variable contract

Variables are namespaced by provider/context.

Examples:
- order: customer_name, order_number, order_total, order_date, payment_method, order_items, billing/shipping values
- user: display_name, username, reset_url, account_url
- inquiry: case_id, name, email, whatsapp, company, country, product_interest, quantity, project_details
- site/brand: brand_name, site_url, support_email

Unknown variables MUST fail visibly in preview diagnostics and MUST NOT silently render arbitrary raw tokens into production output.

Variable availability is template-context aware; the editor only advertises variables that its source can provide.

## 7. Renderer contract

There is one canonical renderer for:
- admin preview
- test email
- production runtime

Preview and runtime MUST NOT use separate markup implementations.

The renderer consumes:
1. published or draft payload
2. template runtime context
3. Global Email VI
4. component library

Output contract:
- email-client-safe HTML
- inline critical presentation
- plain-text fallback where applicable
- no dependency on frontend theme CSS
- no JavaScript

## 8. Global Email VI V1

Owner-approved visual direction:
- canvas: `#F5F5F7`
- surface: `#FFFFFF`
- primary / CTA: `#9B3749`
- primary text: `#FFFFFF`
- text: `#17211B`
- muted: `#6B746E`
- border: `#E5E7E6`
- canonical desktop width: 600px
- mobile: fluid width with safe side padding
- card radius: 12px
- CTA radius: 8px
- font stack: `Poppins, Arial, Helvetica, sans-serif`

The old per-template `#E43F5A` drift is not carried forward.

Email VI presentation configuration is independent from Case ID / trusted site identity and must never mutate `case_id_brand_code`.

## 9. Publish state machine

Allowed states:
- Draft
- Published
- Disabled

Rules:
- Draft is editable and never automatically becomes runtime output.
- Publish creates a new immutable version snapshot and SHA-256 content hash.
- Published points runtime to the new snapshot atomically.
- Disabled means Andy Core does not override that template runtime.
- Rollback republishes an earlier immutable snapshot as a new current published selection; history is preserved.

## 10. Preview / test contract

Editor layout:
- left / center: structured editor
- right: live preview

Preview modes:
- Desktop
- Mobile

Test data supports representative datasets by template type. Test email uses the same renderer and payload, but is clearly marked as a test send and cannot silently publish a draft.

## 11. Legacy Customizer coexistence

Default state for new v1.5.8 runtime override: OFF.

Cutover is per-template / per-source, never an all-at-once switch.

For a template to be Andy Core-owned in runtime:
1. a valid Published version must exist;
2. renderer output must pass contract tests;
3. the legacy Customizer must be prevented from applying its wrapper/subject/template replacement for that exact template path;
4. source Woo/WordPress trigger semantics remain unchanged.

If Andy Core cannot resolve a valid published template, it fails open to the original source path / legacy path rather than sending a broken blank email.

The implementation must explicitly prevent dual Header/Footer wrappers and competing subject filters.

## 12. Canary sequence

First canary group:
- Woo Customer Processing Order
- Woo Customer Completed Order
- Woo Customer New Account
- Woo Customer Reset Password

After canary UAT, expand to remaining native Woo emails, then Smart Coupon / third-party mailers.

Abandoned-cart/WACV migration is compatibility scope but is not allowed to silently replace a third-party campaign engine; only template rendering assets are migrated where runtime ownership is technically safe.

## 13. WordPress / Andy Core mail paths

WordPress system mail is a separate adapter from Woo mail classes.

V1 adapters include, where applicable:
- new user/account notification
- password reset
- password / email change notification compatibility

Andy Core business templates include inquiry notifications first, with future KOL / operational messages registered through the same template registry rather than bespoke HTML builders.

## 14. ERP read-only contract

Reuse the existing authenticated connector namespace: `andy-core/v1/erp`.

Add a read-only published-template snapshot resource; do not create a second authentication system.

Snapshot fields should be minimal and stable:
- template_key
- provider
- status
- published_version
- content_hash_sha256
- published_at
- label

ERP receives no draft payload, no editable HTML and no transport credentials.

## 15. Security / permissions

All Email Template OS mutation screens require Andy Core settings-management capability (`andy_core_settings_manage`) and nonce protection.

Preview may read drafts only for authorized admins.

ERP snapshot uses the current Connector HMAC authentication and remains read-only.

Template content is sanitized per field / block type before storage and again escaped for its output context.

## 16. Migration / rollback

Legacy migration is import-first and non-destructive.

Migration records source references and maps known legacy placeholders to Andy Core variables. Unsupported structures are flagged for review rather than guessed.

Legacy `woocommerce-email-template-customizer` remains installed and active until the final cutover/regression gate. No production deactivation occurs during foundation development.

Rollback principle: disable Andy Core override for a template and restore the previous source/legacy runtime without deleting imported or published history.

## 17. Delivery roadmap

- P0/P0.5: runtime reconciliation and Local 1.5.7 alignment — COMPLETE
- P1: IA / Email VI / visual UAT — OWNER PASS
- P2: registry, schema, renderer, publish, coexistence and ERP contracts — FROZEN by this document
- P3: Global Email Design System + foundation runtime
- P4: Woo canary adapters / UAT
- P4B: remaining Woo + Smart Coupon compatibility
- P5: WordPress + Andy Core business templates
- P6: legacy importer / per-template cutover
- P7: ERP read-only published-template snapshot
- P8: full regression / release gate

## 18. Architecture Gate

P2 is considered PASS only when implementation preserves all of the following:
- runtime-discovered registry
- structured payloads
- one renderer for preview/test/runtime
- immutable published versions with SHA-256 hash
- no global forced cutover
- no dual legacy/Andy wrappers
- transport remains outside Email Template OS
- ERP remains read-only
- Email VI does not alter trusted Case ID identity
