# Andy Core v1.5.7 Upgrade Guide

Upgrade target: Andy Core 1.5.7
Database target: 1.4.0

Sites already on Andy Core 1.5.6 can receive 1.5.7 through the native public signed GitHub Release update channel. No GitHub token, SSH change, or wp-config.php update credential is required.

The upgrade adds Page-Owned Inquiry Modal architecture, the generic `page_owned_inquiry` preset, and reusable `split_visual` desktop / `compact` mobile layout variants. Landing pages can override field order, labels, placeholders, media, copy, and CTA configuration without changing the shared inquiry engine.

Existing saved inquiry field registries and presets are preserved additively, including the legacy `used_forklift_inquiry` preset for backward compatibility. No database migration is required. Existing Case ID, lead, attribution, tracking, updater, backup, health-check, and rollback contracts remain unchanged.