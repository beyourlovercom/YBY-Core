# Andy Core v1.5.3 Release Notes

Release date: 2026-08-31
Release type: Stable
Plugin version: 1.5.3
Database version: 1.4.0

## Scope

Andy Core v1.5.3 adds the bounded BYL ERP WordPress Connector while preserving the existing v1.x runtime and Inquiry contracts.

- Adds the WP-API settings tab with connection, security, and provider health presentation.
- Adds HMAC-SHA256 V1 authentication, replay protection, rate limiting, mutation idempotency, and safe audit persistence.
- Adds read-only snapshots for Affiliates, Coupons, Referrals, Payouts, and Subscribers.
- Adds Affiliate provision/status, Coupon check/provision, and Payout Complete reconciliation endpoints.
- Uses WordPress/WooCommerce/AffiliateWP provider truth rather than a second business ledger.
- Records ERP-completed manual PayPal payouts in AffiliateWP; Andy Core does not transfer money through PayPal.

## Compatibility and fixes

- Database schema authority advances from 1.3.0 to 1.4.0 for Connector persistence.
- The schema upgrade is additive and idempotent.
- WooCommerce coupon read-back now follows the approved trim + case-insensitive Coupon identity when WooCommerce normalizes code casing.
- Existing Inquiry, Sticky CTA, Google authentication, Brand, Bottle, and frontend runtime contracts remain covered by regression tests.
