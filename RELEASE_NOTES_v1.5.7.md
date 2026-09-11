# Andy Core v1.5.7 Release Notes

Release type: Stable UI/runtime update
Plugin version: 1.5.7
Database version: 1.4.0

Andy Core 1.5.7 introduces Page-Owned Inquiry Modal architecture. Andy Core now owns the reusable inquiry engine and layout variants, while each landing page can own its modal instance, media, copy, field order, labels, placeholders, and CTA configuration.

The reusable `split_visual` desktop layout and `compact` mobile layout support product-specific landing pages without forcing one global visual or field schema across unrelated products. Compact mobile modals can hide media and use the approved Name, Email / WhatsApp, and Inquiry Details flow without internal scrolling.

The generic `page_owned_inquiry` preset is available for new landing pages. Existing presets, including `used_forklift_inquiry`, remain available for backward compatibility. Combined contact routing, Case ID, source attribution, tracking, and hidden product-interest context continue through the existing lead pipeline.

No database migration is introduced; database version remains 1.4.0.