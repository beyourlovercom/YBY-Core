# Andy Core ERP Content Publishing Contract V1

Status: C7B feature contract for Andy Core v1.8.0 integration.  
Stable plugin version metadata is intentionally not changed by this branch.

## Purpose

Provide one bounded, authenticated ERP-to-WordPress publishing surface for article-like Content Production records.

This contract does not replace SEO opportunity selection, Product publishing, WordPress admin workflows, or the existing Connector authentication/idempotency foundation.

## Routes

Namespace: `/wp-json/andy-core/v1/erp`

- `POST /content/preview`
  - HMAC-authenticated.
  - Performs validation and provider projection only.
  - Performs zero WordPress writes.
  - Does not require an idempotency key.
- `POST /content/publish`
  - HMAC-authenticated.
  - Requires `X-YBY-Idempotency-Key`.
  - Uses durable Connector idempotency action `content.publish`.
  - Creates or updates one bound WordPress post.

Both routes use Connector contract envelope version `1`.

Resource contract version: `content-publish-v1`.

## Exact request body

The request object must contain exactly:

- `erp_article_id` — positive integer.
- `layout_snapshot_id` — positive integer.
- `preview_hash` — lowercase SHA-256 hex.
- `target_site` — hostname only; must match this WordPress site's home host.
- `content_type` — `blog`, `guide`, or `comparison`.
- `title` — non-empty plain title, max 200 bytes.
- `html` — non-empty article HTML, max 524288 bytes; re-sanitized with `wp_kses_post`.
- `canonical_path` — null/empty or a local absolute path; schemes, query strings and fragments are forbidden.
- `seo` — exact object with `primary_keyword` and `search_intent`, each nullable.
- `requested_status` — `draft` or `publish`.

Product/category publishing is intentionally out of scope and must fail closed with `CONTENT_TYPE_UNSUPPORTED`.

## WordPress projection

V1 writes WordPress `post` only.

First successful publish for one `connection_key + erp_article_id` creates the post. Later accepted layouts update the same bound post. Multiple bound posts produce `CONTENT_BINDING_CONFLICT`; the Connector does not guess which post is canonical.

Bound metadata:

- `_yby_erp_connection_key`
- `_yby_erp_article_id`
- `_yby_erp_layout_snapshot_id`
- `_yby_erp_preview_hash`
- `_yby_content_contract_version`
- `_yby_content_type`
- `_yby_primary_keyword`
- `_yby_search_intent`

Metadata is read back immediately. A mismatch returns `PROVIDER_SYNC_FAILED`; a newly created half-bound post is compensated with an exact delete when WordPress exposes `wp_delete_post`.

The post itself is read back for ID, type, status, title, and content before success is returned.

## Security and idempotency

The existing Connector HMAC v1 contract remains canonical:

`METHOD + PATH_WITH_QUERY + TIMESTAMP + NONCE + CONNECTION_KEY + IDEMPOTENCY_KEY + SHA256(RAW_BODY)`

Existing replay prevention, timestamp bounds, rate limits, HTTPS requirements, secret handling, durable idempotency storage, and audit storage are reused unchanged.

The safe idempotency replay envelope is extended only for bounded Content result fields such as post ID, provider, URL, layout snapshot ID, preview hash, expected canonical path, and canonical-path match status.

No raw request HTML, credentials, authentication headers, or secrets are written to idempotency/audit records.

## Success data

Publish returns bounded data containing:

- `resource_contract_version`
- `provider=wordpress`
- `post_id`
- `external_id`
- `status`
- `url`
- `created`
- `layout_snapshot_id`
- `preview_hash`
- `canonical_path_expected`
- `canonical_path_match`

Preview returns the validated provider projection and `write_performed=false`.

## ERP integration gate

C7B is complete only when the ERP Content module signs this contract through its own Infrastructure adapter and demonstrates:

1. preview/local layout hash agreement,
2. real WordPress draft creation without duplicate posts,
3. same-key replay without a second provider write,
4. later draft update reusing the same WordPress post,
5. Owner UAT before final `requested_status=publish`.

No production article may be published automatically as part of this contract implementation.
