# Andy Core v1.5.8 — Email Template OS V1 — Canonical Architecture

Status: EMAIL OS V1.1 ARCHITECTURE PIVOT FROZEN
Date: 2026-09-14
Branch: `feat/andy-core-v1.5.8-email-template`
Base: `main@b8cf37b5b2cec4b0e3d14be58aae51a0731e2407`

## 1. Product ownership

Andy Core Email OS is the unified owner-facing control center for email discovery, governance, Global Email VI, diagnostics, preview/test entry points, and Andy Core-owned native business templates.

WooCommerce remains the runtime/source owner for WooCommerce transactional email. Woo templates are edited in WooCommerce native settings and/or the active Mailonix / legacy customizer surface. Email OS MUST NOT rebuild a competing Woo editor and MUST NOT register WooCommerce outbound runtime override hooks.

Andy Core owns structured payloads, variables, preview rendering, publication state and published-version metadata only for templates whose editor/runtime ownership is explicitly `wordpress` or `andy_core`.

Andy Core does NOT own SMTP credentials, provider transport, queue delivery, retries or inbox deliverability. Those remain in the transport layer (for example WP Mail SMTP or the site's chosen mail provider).

ERP is read-only. ERP may consume published Andy Core-owned template metadata through the existing authenticated Andy Core Connector contract, but it must not edit templates or render runtime email HTML.

## 2. Final IA

Canonical parent: `Andy Core → Email OS`

Email OS top-level tabs:
- 悬浮询盘
- 弹窗询盘
- 弹窗订阅
- 弹窗抽奖
- 短代码询盘
- 短代码订阅
- 营销群发
- 邮件模板

`邮件模板` is no longer a Settings tab. The historical Settings URL remains redirect-only compatibility and must route to the canonical Email OS tab.

Email Template OS internal sections:
- 概览
- 品牌样式
- WooCommerce
- WordPress
- Andy Core
- 发布与测试

Settings remains for system configuration only:
- 常规
- 询盘
- 询盘通知
- WP-API
- 系统状态
- Updates

The existing Email submenu is promoted to `Email OS` and becomes the unified owner-facing hub for website Email surfaces and the Email Template OS. This IA change does not transfer SMTP/transport ownership into the template renderer.

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

V1.1 governance metadata SHOULD additionally expose `editor_owner`, `current_editor`, `editor_url`, `preview_test_url`, and health/status diagnostics where safely discoverable.

WooCommerce registry entries are discovered from runtime mailer classes so native Woo and third-party classes such as Smart Coupon remain visible. Discovery does not imply Andy Core runtime ownership. Woo entries are governance/bridge records; WordPress and Andy Core entries may become native editable templates in later gates.

## 4. Storage model

Database version target: `1.5.0`.

The existing Email OS tables remain valid foundation for Andy Core-owned native templates and immutable published versions. WooCommerce discovery rows do not require copying Woo template HTML into Andy Core and do not make Andy Core the Woo runtime source of truth.

### `wp_yby_email_templates`
Stores one mutable identity / working state for Andy Core-owned editable templates. Provider-discovered templates may be represented as read-only governance metadata without an Andy Core working payload.

Minimum fields:
- id
- template_key (unique)
- provider
- label
- status (`draft`, `published`, `disabled`)
- working_payload (LONGTEXT JSON, nullable for bridge-only providers)
- published_version_id (nullable)
- created_at
- updated_at

### `wp_yby_email_template_versions`
Stores immutable published versions for Andy Core-owned editable templates.

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

There is one canonical Andy Core renderer for Email OS-owned native templates:
- admin preview
- test email
- production runtime for explicitly Andy Core-owned WordPress / Andy Core adapters only

Preview and runtime for Andy Core-owned templates MUST NOT use separate markup implementations.

WooCommerce email rendering remains owned by WooCommerce / Mailonix / the active Woo editor path. Email OS may surface provider preview/test links or diagnostics, but the Andy Core renderer MUST NOT replace Woo runtime output.

The Andy Core renderer consumes:
1. published or draft payload
2. template runtime context
3. Global Email VI
4. component library

Output contract for native templates:
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

Allowed native-template states:
- Draft
- Published
- Disabled

Rules for `wordpress` / `andy_core` editable templates:
- Draft is editable and never automatically becomes runtime output.
- Publish creates a new immutable version snapshot and SHA-256 content hash.
- Published points the native Andy Core adapter to the new snapshot atomically once that adapter gate is explicitly enabled.
- Disabled means the native Andy Core adapter is not active for that template.
- Rollback republishes an earlier immutable snapshot as a new current published selection; history is preserved.

WooCommerce provider status is observed and governed, not shadow-published by Andy Core. Email OS must not create an independent Woo publish state that conflicts with WooCommerce / Mailonix.

## 10. Preview / test contract

For Andy Core-owned native templates, the editor layout is:
- left / center: structured editor
- right: live preview

Preview modes:
- Desktop
- Mobile

Test data supports representative datasets by native template type. Test email uses the same Andy Core renderer and payload, but is clearly marked as a test send and cannot silently publish a draft.

For WooCommerce templates, Email OS exposes routing to the authoritative Woo / Mailonix edit and preview/test surfaces plus diagnostics. It does not duplicate the Woo editor.

## 11. Legacy Customizer coexistence

Legacy `woocommerce-email-template-customizer` remains installed and active during V1.1 governance unless a later separately approved migration says otherwise.

Email OS responsibilities are detection and governance:
1. detect whether the legacy customizer is active;
2. identify the current editor / rendering owner where practical;
3. surface conflicts or ambiguous ownership;
4. route the owner to WooCommerce / Mailonix / legacy editor surfaces;
5. never deactivate, bypass, or suppress the legacy plugin during P3/P4.

There is no V1.1 requirement to import all Woo HTML into Andy Core, suppress legacy wrappers per Woo template, or perform a forced Woo runtime cutover.

## 12. WooCommerce bridge contract

P4 is a governance bridge, not a runtime canary.

WooCommerce Bridge V1 should provide:
- runtime-discovered Woo template inventory
- enabled / disabled state where discoverable
- recipient / audience metadata
- current editor label (`Woo 原生`, `Mailonix`, `Legacy Customizer`, or diagnostic/unknown)
- direct `Woo 设置` / provider editor jump links
- preview/test entry where supported
- transport / health diagnostics without SMTP credential ownership

Hard rule: Andy Core MUST NOT register `woocommerce_email_*` runtime replacement hooks or otherwise become the outbound Woo renderer in P4.

## 13. WordPress / Andy Core native mail paths

WordPress system mail is a separate adapter from Woo mail classes.

V1.1 native adapters may include, where applicable:
- new user/account notification
- password reset
- password / email change notification compatibility

Andy Core business templates include inquiry notifications first, followed by Affiliate / sales-assignment / operational messages registered through the same template registry rather than bespoke HTML builders.

These WordPress / Andy Core templates are the primary scope for the structured editor, immutable publish versions, variable diagnostics and Andy Core renderer.

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

WooCommerce migration is not required for Email OS V1.1. Woo provider-owned content remains in WooCommerce / Mailonix / the active customizer and is referenced through discovery and bridge metadata.

For WordPress / Andy Core native templates, migration/import is non-destructive: map known placeholders to governed variables, flag unsupported structures for review, and never guess unknown variables.

Legacy `woocommerce-email-template-customizer` remains installed and active during the governance phase. No production deactivation occurs as part of P3/P4.

Rollback principle for native Andy Core templates: disable the native adapter and return to the prior WordPress / plugin source path while preserving draft and published history.

P5B runtime rule: Draft content is never an outbound source. Only a valid immutable Published Snapshot with matching SHA-256 may drive WordPress / Andy Core native mail. Missing, disabled, invalid or hash-mismatched snapshots fail closed to the pre-existing WordPress / Andy Core mail path. WooCommerce remains excluded from native runtime adapters.

P6 transport/test rule: SMTP/API/OAuth credentials remain exclusively owned by the active transport provider. Email OS may read only non-secret provider health metadata. Native test sends require an explicit authorized click, use only an immutable Published Snapshot + Sample Context + the canonical Renderer, prefix the subject with [Email OS Test], and never persist the recipient address. WooCommerce test sending remains owned by WooCommerce / the active Woo editor.

P7 governance rule: VillaTheme / Legacy Customizer remains active and owns Woo runtime. Email OS performs read-only discovery of published mappings, default templates, rule-driven variants and unmatched types. Multiple published templates for one Woo email are not automatically conflicts because VillaTheme may select them by country, language, product, payment or price rules. Email OS MUST NOT delete, unpublish, reorder or rewrite Legacy templates during governance.

## 17. Delivery roadmap

- P0/P0.5: runtime reconciliation and Local 1.5.7 alignment — COMPLETE
- P1: IA / Email VI / visual UAT — OWNER PASS
- P2/P3 Foundation: registry, schema, Email VI and native renderer foundation — COMPLETE / ACTIVATED LOCALLY
- P3.1: Email OS IA + Architecture Pivot — COMPLETE
- P4: WooCommerce Registry / Native Editor Bridge — COMPLETE
- P5A: WordPress + Andy Core Native Template Editor / immutable publish — COMPLETE
- P5B: WordPress + Andy Core Published Snapshot Runtime Adapter — COMPLETE
- P6: Transport / Test / Health Center — COMPLETE
- P7: Legacy Customizer Governance — CURRENT
- P8: ERP read-only published-template contract
- P9: full regression / UAT / release gate

## 18. Architecture Gate

P3.1 is considered PASS only when implementation and tests preserve all of the following:
- Email OS is the canonical owner-facing hub; `邮件模板` is not a Settings tab
- runtime-discovered registry remains intact
- WooCommerce runtime/editor ownership remains with WooCommerce / Mailonix / active provider
- no WooCommerce outbound runtime override hooks are registered by Andy Core
- structured payload + one renderer are retained for WordPress / Andy Core native templates
- immutable published versions with SHA-256 hash remain available for Andy Core-owned templates
- Legacy Customizer is detected/governed but not disabled or bypassed
- SMTP transport remains outside Email OS ownership
- ERP remains read-only
- Email VI does not alter trusted Case ID identity
