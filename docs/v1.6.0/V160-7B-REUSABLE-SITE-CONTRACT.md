# V160-7B — Reusable Site Contract

Date: 2026-09-18

Docs OS is reusable through the `yby_docs_os_contract` filter. BYL remains the default compatibility profile.

Default contract:

```php
array(
    'post_type' => 'docs',
    'taxonomy' => 'doc_category',
    'base_path' => 'docs',
    'related_meta_key' => '_betterdocs_related_articles',
)
```

A host site may override these values without forking Docs OS.

Canonical ownership remains separately controlled by `yby_docs_os_canonical_v1` and production fail-close protection.
## Asset contract

Docs OS does not hard-code BYL's R2 domain. When Advanced Media Offloader metadata exists, the runtime reads the configured Cloudflare R2 public domain plus `advmo_path` and rewrites render-time image/srcset URLs only.

- No media re-upload.
- No post-content rewrite.
- Missing offload metadata falls back to original content URL.
- Site-specific public domains remain external configuration.

## Compatibility contract

BetterDocs is treated as a migration compatibility source, not a permanent runtime dependency. The default `related_meta_key` preserves current BYL related-doc mappings, while a future site may provide another metadata key through the contract filter.

Search, TOC, category/document surfaces, canonical routing, schema isolation and responsive assets use the resolved contract rather than BYL hostnames.
