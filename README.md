# Andy Core

Andy Core is the shared WordPress foundation plugin for managed websites.

## Current release

- Product version: `1.4.0`
- Development version: `1.5.0-dev`
- Database version: `1.1.0`
- Plugin path: `yby-core/yby-core.php`
- Technical namespace: existing `YBY_*` and `yby_*` identifiers retained for compatibility

## v1.5.0 development scope

The active development line adds secure Google authentication:

- one Social Login submenu under Andy Core
- provider status cards for Google, Facebook, X, and TikTok
- validated Google settings stored outside the main Core option
- role and same-site redirect security boundaries
- `[yby_social_login provider="google"]` with an on-demand Google Identity Services button
- one anonymous POST endpoint at `/wp-json/yby/v1/auth/google`
- local RS256 ID Token verification with cached Google PEM certificates
- Google `sub` identity mapping, restricted WordPress registration, and standard login sessions
- double-submit CSRF, short-lived login nonce, authoritative-email, disabled-role, and internal redirect enforcement
- optional integration with the standard WordPress login page
- canonical `[yby_social_login provider="google"]` placement from WordPress, templates, or Bricks
- optional verified-email association for existing Subscriber and Customer accounts only
- Google `sub` identity lookup for every returning login after association

The login-page integration and safe existing-account association settings default to disabled. Privileged, custom, mixed-role, and explicitly disabled-role accounts are never associated automatically. The development implementation stores the signed provider picture URL for future display but does not globally replace WordPress avatars. Client Secret, Google API authorization, access or refresh token storage, manual account linking or unlinking, One Tap, and Facebook, X, or TikTok authentication are not implemented.

## v1.4.0 purpose

Version 1.4.0 is a compatibility-safe product-name transition from **YBY Core** to **Andy Core**.

It changes the user-facing product identity while preserving all established runtime and integration contracts. WordPress upgrades the existing plugin in place rather than treating Andy Core as a second plugin.

## Included capabilities

- Case ID generation and validation
- Lead Session helper
- Thank You Page integration helper
- Config Center runtime output
- Project Engine
- Page Profile Engine
- Content Runtime Engine
- Project Template Engine
- Tracking helper standardization
- Safe email template generation
- Project Studio admin layer
- Runtime Viewer
- Governed public Lead REST endpoint
- Notification Center architecture
- Governed inquiry email delivery
- Lead notification recipient settings
- Structured Inquiry source metadata
- Structured `custom_fields` JSON storage
- Preset-based server allowlisting for flexible fields
- Reusable Inquiry Modal and Sticky CTA shortcodes

## Compatibility contract

The following technical identifiers remain unchanged in v1.4.0:

- plugin directory: `yby-core/`
- main plugin file: `yby-core.php`
- PHP classes and constants: `YBY_*`
- functions and option keys: `yby_*`
- text domain: `yby-core`
- database tables: existing `wp_yby_*` tables
- shortcodes: `[yby_inquiry_modal]`, `[yby_sticky_cta]`
- HTML attributes: `data-yby-*`
- CSS selectors: `.yby-*`
- REST namespace: `/wp-json/yby/v1/`
- browser runtime APIs: `window.YBY*`
- Case ID format and existing brand codes

## Public runtime APIs

The following browser objects remain stable:

- `window.YBYCoreConfig`
- `window.YBYProject`
- `window.YBYContent`
- `window.YBYTemplate`
- `window.YBYLead`
- `window.YBYTracking`
- `window.YBYThankYou`
- `window.YBYPageProfile`

## Lead endpoint

The governed website inquiry endpoint is:

`POST /wp-json/yby/v1/leads`

The route:

- accepts public lead submissions
- validates and normalizes Case ID
- stores core lead data and structured Inquiry metadata
- persists preset-allowed flexible fields in `custom_fields`
- sends one notification after successful storage
- keeps the existing HTTP 200 success response shape
- treats email delivery failure as non-fatal

## Inquiry components

Modal example:

```text
[yby_inquiry_modal
    id="irrigation-inquiry-global"
    preset="irrigation_quick_inquiry"
]
```

Sticky CTA example:

```text
[yby_sticky_cta
    label="Get Quote"
    href="#yby-inquiry"
    modal_id="irrigation-inquiry-global"
    source="site_global_sticky_cta"
    profile="site_global"
]
```

Shared trigger example:

```html
<a
    href="#yby-inquiry"
    data-yby-inquiry-trigger
    data-yby-source="page_get_quote"
    data-yby-page-profile="page_profile"
>
    Get Quote
</a>
```

## Activation and storage

Default settings remain stored under existing option keys, including:

- `yby_core_options`
- `yby_lead_notification_primary_recipient_email`

The database schema remains `1.1.0`; v1.4.0 performs no schema migration.

## Security boundaries

- Case ID remains server-owned.
- Client-provided brand, website, and Case ID values are not trusted by the Lead endpoint.
- SMTP credentials, recipients, and CRM webhook configuration remain server-only.
- Tracking payloads continue to exclude blocked PII fields.
- GTM, GA4, Google Ads, Bricks, and theme installation remain outside plugin ownership.

## Not included in v1.4.0

- Social Login
- Google, Facebook, X, or TikTok authentication
- CRM implementation
- ERP implementation
- AI implementation
- WooCommerce transaction logic
- database schema changes
- theme or Bricks changes

## Development direction

The previous fixed long-term roadmap is superseded by a feature-driven roadmap.

The active v1.5.0 development feature is **Social Login**, beginning with the secure Google authentication flow documented above. This development work does not change the scope or stability of v1.4.0.

## Release process

All releases must follow:

- `docs/RELEASE_WORKFLOW.md`
- `docs/DEVELOPMENT_WORKFLOW.md`

Release history is recorded in `CHANGELOG.md`. Version-specific upgrade and rollback rules are recorded in the root release documents.
