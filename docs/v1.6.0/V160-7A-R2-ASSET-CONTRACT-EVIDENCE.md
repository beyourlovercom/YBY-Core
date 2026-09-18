# V160-7A — R2 Asset Contract Evidence

Date: 2026-09-18
Scope: BYL Docs OS Local runtime
Mode: render-time asset resolution; zero post-content writes

## Source of truth

- Provider: Cloudflare R2
- Bucket: `imgbeyourlovercom`
- Public domain: `https://img.beyourlover.com`
- Existing media offloader metadata is used; the domain is read from configuration and is not hard-coded in Docs OS.

## Inventory

- Docs with inline image assets: **6**
- Original inline image references: **18**
- Matching attachment records: **18/18**
- `advmo_offloaded=1`: **18/18**
- Original R2 target URLs HTTP 200: **18/18**
## Runtime UAT

Docs OS now resolves image URLs at render time from Gutenberg attachment ID + `advmo_path` + configured Cloudflare R2 public domain.

Validated canonical pages:

- Affiliate Login
- Affiliate Register Guide
- Affiliate Link Creation
- Shipping Info Noticement
- Discount Code
- How To Track My Order

For all six pages:

- HTTP 200
- Docs entry local `/wp-content/uploads/` references: **0**
- R2 absolute URLs present: **PASS**
- Rendered responsive/srcset unique R2 URLs: **164**
- HTTP 200: **164/164**
- Broken R2 assets: **0**

## Data-retention gate

- Docs: **29**
- Postmeta: **481**
- Term relationships: **51**
- Identity fingerprint unchanged: `30f608aae0601210cbcf393e2bc0c389f610f25c2a61afffbf1b77dbc2578a48`

No image was re-uploaded. No `post_content` was rewritten.
