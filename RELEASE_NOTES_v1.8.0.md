# Andy Core v1.8.0 — Release Notes

Release date: 2026-09-23

## Highlights

Andy Core v1.8.0 adds **Content Publishing V1**, a bounded ERP-to-WordPress publishing surface for article-like content while reusing the existing Connector security and idempotency foundation.

## Content Publishing V1

Namespace: `/wp-json/andy-core/v1/erp`

- `POST /content/preview` validates the exact content contract and returns the provider projection with `write_performed=false`; it performs zero WordPress writes.
- `POST /content/publish` requires Connector HMAC authentication plus `X-YBY-Idempotency-Key`, then creates or updates one bound WordPress `post`.
- Supported content types are `blog`, `guide`, and `comparison` only. Product/category publishing remains out of scope and fails closed.
- ERP article ID, layout snapshot ID, preview hash, content type, keyword/search-intent metadata, and indexing intent are persisted as bounded binding metadata and read back before success.
- A later accepted layout updates the same bound WordPress post instead of creating a duplicate. Ambiguous/multiple bindings fail closed.

## Indexing contract

- Draft requires `seo.indexing=noindex`.
- Publish requires `seo.indexing=index`.
- A status/indexing mismatch fails closed with `INDEXING_STATUS_MISMATCH`.
- Draft writes Rank Math `noindex, follow` and verifies it on read-back.
- Publish removes the explicit Rank Math robots override so normal site index/follow defaults apply, then verifies that no `noindex` override remains.

## Security and data safety

- Reuses Connector HMAC v1, replay prevention, timestamp bounds, rate limits, audit controls, and durable mutation idempotency.
- Raw article HTML, credentials, authentication headers, and secrets are not stored in idempotency/audit evidence.
- No automatic Production article publishing is part of the release closure.

## Compatibility

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Existing v1.7.0 Foundation, Docs, Landing Page, Article TOC, Connector, Inquiry and Email OS contracts remain in place.
