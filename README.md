# Andy Core

Andy Core is the shared WordPress foundation plugin for managed websites.

## Current release

- Product version: `1.5.6`
- Development version: None
- Database version: `1.4.0`
- Release status: Stable
- Plugin path: `yby-core/yby-core.php`
- Technical namespace: existing `YBY_*` and `yby_*` identifiers retained for compatibility

## v1.5.6 release scope

- Zero-config native WordPress updates from the public signed GitHub Release channel `beyourlovercom/andy-core-release`.
- No GitHub token, SSH, or wp-config.php update credential is required on installed WordPress sites.
- SHA-256, pinned Ed25519 signatures, safe ZIP validation, bounded code backup, post-install health validation, and rollback remain mandatory.

## v1.5.5 release scope

- First production-native secure-updater canary release; no business-runtime or database-schema change.
- Exercises the signed private GitHub Release -> WordPress native update path introduced in v1.5.4.

## v1.5.4 release scope

- Secure private GitHub Releases updater with authenticated, checksum-verified packages, bounded code backups, local health validation, and code-only rollback.

## v1.5.3 release scope

Version 1.5.3 adds the bounded WordPress Connector surface for BYL ERP integration while preserving existing Andy Core contracts:

- WP-API settings tab with truthful connection, security, and provider health status
- HMAC-SHA256 V1 authentication with signature, replay, rate-limit, and mutation idempotency protections
- Safe audit records and provider-safe behavior when WooCommerce or AffiliateWP is unavailable
- Five provider snapshots: affiliates, coupons, referrals, payouts, and subscribers
- Affiliate provisioning and status mutation with canonical provider read-back
- Coupon check and provision with conflict protection and canonical WooCommerce code read-back
- Payout completion reconciliation in AffiliateWP for ERP-completed manual PayPal payouts; Andy Core does not transfer money through PayPal
- Fixed WooCommerce coupon canonical readback handling
- Database schema version `1.4.0` for Connector persistence

## v1.5.2 release scope

Version 1.5.2 adds portable inquiry and subscribe conversion surfaces plus site-level presentation controls while preserving the existing Lead, Inquiry, notification, and database contracts:

- Brand & Theme runtime presentation controls for shared multi-site deployment
- Email admin hub for floating inquiry, popup inquiry/subscribe, and shortcode management
- Global Inquiry Popup and Subscribe Popup runtime
- Global floating inquiry with Mode A single Inquiry button and Mode B WhatsApp + Inquiry bottom dock
- first-screen deferred display, responsive previews, per-button color controls, and Custom CSS override support
- reusable Inquiry and Subscribe shortcodes, including the minimal Subscribe mode
- centralized `Settings -> 询盘通知` management for WhatsApp and Email Notification using the existing option authorities
- existing WhatsApp URL/message runtime reused without a second configuration authority
- Windows local-first sync and runtime UAT helpers for repeatable development verification
- database schema remains `1.3.0`; no migration is introduced by this release

## v1.5.1 release scope

Version 1.5.1 adds the local Inquiry Inbox and follow-up management layer for existing Leads:

- Inquiry Inbox with list, search, filters, pagination, and detail views
- Follow-up Management for status, priority, owner, next follow-up, notes, archive, and restore
- Active Inquiry Owners configuration scoped to WordPress users with role `salesperson`
- Salesperson role isolation with read-only access only to inquiries assigned to the current user
- Historical inactive owner retention with `{Display Name} (Inactive)` administrator display
- Activity Timeline for management events
- Inquiry Settings and read-only System Status tabs
- Project Studio routing remediation and Inquiry admin asset cache busting
- WordPress/MySQL validation for the `1.2.0` to `1.3.0` database upgrade path

## v1.5.0 release scope

Version 1.5.0 adds secure Google authentication and portable Bottle inquiry runtime support:

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
- opt-in Google One Tap for eligible logged-out visitors on normal public pages
- same-origin, single-use One Tap challenges, asynchronous authentication, and dedicated logged-in cookie confirmation
- Bottle OEM inquiry fields and preset/profile validation
- persisted `page_profile` lead attribution
- page-profile-specific Thank You URL precedence with strict URL validation
- Case-scoped, confirmed-only WhatsApp project summaries
- cross-brand email and WhatsApp identity regression protection

The login-page integration, safe existing-account association, and One Tap settings default to disabled. One Tap is intended for eligible returning Google users, never enables automatic account selection or automatic login, and leaves the visitor on the current page after a successful sign-in. A dedicated no-store endpoint cryptographically validates the returned WordPress logged-in cookie without exposing a REST nonce or identity data. Google and the browser control whether and where the prompt appears, including dismissal and cooldown behavior. Ordinary public pages remain cacheable because each short-lived challenge is requested after page load rather than embedded in page HTML.

Privileged, custom, mixed-role, and explicitly disabled-role accounts are never associated automatically. The implementation stores the signed provider picture URL for future display but does not globally replace WordPress avatars. Client Secret, Google API authorization, access or refresh token storage, manual account linking or unlinking, and Facebook, X, or TikTok authentication are not implemented.

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

Version 1.5.1 upgrades the database schema to `1.3.0` by adding idempotent Inquiry management and Activity tables while preserving existing Lead rows. Version 1.5.0 upgraded the database schema to `1.2.0` by adding the nullable `page_profile` Lead column.

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

## Release state

Andy Core v1.5.6 is the stable/current release. Development version: None. Production deployment remains a separate, explicitly authorized operation.

## Release process

All releases must follow:

- `docs/RELEASE_WORKFLOW.md`
- `docs/DEVELOPMENT_WORKFLOW.md`

Release history is recorded in `CHANGELOG.md`. Version-specific upgrade and rollback rules are recorded in the root release documents.
