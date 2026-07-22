# Brand Runtime Configuration

## Purpose

YBY Core is a shared WordPress plugin used across multiple YBY websites and brands.

Brand Runtime exists so one shared codebase can support:

- YBY Irrigation
- BC Glass Bottles
- future YBY websites

without hardcoding one brand into shared runtime code.

## Ownership Boundaries

### Site Profile owns

- `site_brand_key`
- `case_id_brand_code`
- authoritative stored website identity
- server-generated Case ID brand code

### Brand Profile owns

- `site_brand_name`
- `website_url`
- brand colors
- WhatsApp number
- Catalog URL
- YouTube video ID
- default country
- default product interest
- Thank You URL
- Return URL
- WhatsApp Message Template
- email presentation identity

### Server owns

- final Case ID generation
- stored brand
- stored website
- notification dispatch
- recipient configuration
- CRM webhook configuration

### Project/Page Profile may own

- project-specific values
- page-specific content
- product interest
- country
- crop
- farm size
- water source
- recommended system
- estimated range
- tracking groups
- attribution fields

The browser must not own authoritative brand, website, or Case ID generation.

## Runtime Precedence

Brand-governed presentation fields follow this accepted precedence:

1. Brand Runtime
2. compatibility fallback only where explicitly supported
3. safe neutral default

For the following fields, non-empty Brand Runtime wins:

- `thankYouUrl`
- `returnPageUrl`
- `catalogUrl`
- `youtubeVideoId`
- `whatsappNumber`
- `siteBrandName`

For the customer-facing WhatsApp message source, the accepted precedence is:

1. `whatsappMessageTemplate` from Brand Runtime
2. structured neutral system default

Legacy `Project` and `Page Profile` `whatsappMessage` values may remain stored for backward compatibility, but they no longer control customer output.

## Public Runtime Keys

The safe public Runtime may expose:

- `siteBrandKey`
- `siteBrandName`
- `caseIdBrandCode`
- `websiteUrl`
- `whatsappNumber`
- `whatsappMessageTemplate`
- `catalogUrl`
- `youtubeVideoId`
- `defaultCountry`
- `defaultProductInterest`
- `thankYouUrl`
- `returnPageUrl`
- `enableTracking`
- `enableCaseId`
- `enableCrmWebhook`

`enableCrmWebhook` is a boolean feature state only. It does not expose the webhook URL.

## Private Values Excluded From Runtime

The public Runtime must not expose:

- `crmWebhookUrl`
- primary notification recipient
- CC recipients
- BCC recipients
- SMTP host
- SMTP username
- SMTP password
- SMTP authorization
- secret tokens
- private WordPress option keys
- customer PII

## WhatsApp Template Behavior

Supported placeholders:

- `{brand_name}`
- `{case_id}`
- `{country}`
- `{crop}`
- `{farm_size}`
- `{water_source}`
- `{recommended_system}`
- `{estimated_range}`

Accepted behavior:

- placeholders are case-insensitive
- templates support multiline text
- literal escaped newlines normalize to real line breaks
- empty optional fields remove the complete line
- Case ID is mandatory
- Case ID appears once
- unresolved supported placeholders must not remain
- final text is URL-encoded once
- internal tracking or source values are excluded
- customer PII is not added automatically

## Neutral Shared Fallback

Shared code falls back to the neutral default below when no Brand Runtime template is configured:

```text
Hello {brand_name}, I submitted a website inquiry.

My Case ID: {case_id}

Project Summary
Country: {country}
Crop: {crop}
Farm Size: {farm_size}
Water Source: {water_source}
Recommended System: {recommended_system}
Estimated Range: {estimated_range}

Please contact me about the next steps.
```

Shared code remains brand-neutral. Irrigation wording belongs in the YBY Irrigation Runtime template. Bottle wording belongs in the Bottle Runtime template.

## Client/Server Security Rules

- accepted REST route: `/wp-json/yby/v1/leads`
- singular `/wp-json/yby/v1/lead` is not an accepted route
- client brand is ignored
- client website is ignored
- client Case ID is ignored
- server identity remains authoritative
- no PII enters the Thank You URL
- only `case_id` may be appended to the Thank You URL

## Test-Only Runtime Behavior

- `window.YBYThankYou.__testOnly` is not available publicly
- it is exposed only when `window.YBY_CORE_TEST_MODE === true`
- production WordPress pages must not enable this flag

## Portability Checklist

- configure Site Profile
- configure Brand Profile
- configure Case ID code
- configure canonical website
- configure colors
- configure WhatsApp number
- configure WhatsApp template
- configure Catalog
- configure YouTube
- configure Thank You and Return URLs
- configure email presentation
- configure recipients server-side
- verify public secret exclusion
- verify Case ID
- verify message formatting
- verify tracking deduplication
- verify no brand leakage
