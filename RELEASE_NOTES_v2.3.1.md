# Andy Core v2.3.1 — signed release candidate (unpublished)

Source baseline: Owner-approved main merge PR #247 commit 22dcfdfef842e522731d7744de943b6940060dd7.
Public Stable v2.3.0 is immutable and remains Latest until separately approved release.

## R7 user experience patch
- Success/information notices use Core-owned compact Dynamic Island, without initial native banner flash or layout jump.
- WooCommerce errors and warnings remain NATIVE ONLY: repeated add, invalid coupon, invalid login, stock, payment, checkout and validation.
- Native Notice Minimal Style A: no background/border/shadow/large focus rectangle, small icon and legible text and inline link.
- Preserve form validation, role=alert and keyboard focus for actionable links; native fallback on missing JS/CSS or 2500ms timeout.

## Integrity and compatibility
- Real isolated WP guest product and login UAT, plus Owner R7 visual PASS; CI/real transactions remain separately validated.
- No database schema or Woo order/payment change. Preserve yby_lead_notifications audit and all customer data.
- Database updater compatibility 1.4.0; runtime 1.5.0; signing public key unchanged.
- This candidate does not authorize public tagging, Stable publication, or any website install.
