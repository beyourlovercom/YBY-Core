# Creator Readonly History ? isolated two-account synthetic security UAT

Date: 2026-10-17
Scope: the standalone Creator read-only API provider class, not other WordPress features.

## Authorization boundary

All IDs, account totals, referral records and payout records are invented in-memory
fixtures. No WordPress database or network access, no WWW/Dev real user,
no actual AffiliateWP account and no production credential is used.

Synthetic Creator A: Dev user 321, WWW user 421, affiliate 521.
Synthetic Creator B: Dev user 322, WWW user 422, affiliate 522.

In the isolated fixture only, both triples appear in BYL_CREATOR_REMOTE_BINDINGS.
This does NOT expand the real WWW production Canary allowlist, which remains
limited to the separately authorized Dev user 4 / WWW user 4 / Affiliate 44.

## Signed API simulation

Invoke the exact, unmerged YBY_Creator_Readonly_History provider class using
WordPress/AffiliateWP test doubles. The in-process request mocks create the same
HMAC-signed canonical GET URL and headers as the caller. Test all of:

- A and B may independently read only their own totals, visits, commissions,
  own referral orders and own payouts, with distinct values
- A-to-B and B-to-A affiliate IDs and WordPress IDs cannot be swapped, even with
  a valid HMAC signature, and unbound third accounts are rejected
- Bad or missing signatures; correctly signed but expired timestamps;
  replayed nonce; correctly signed duplicate query fields; POST requests reject
- Unsigned body/JSON parameter overrides cannot change the signed URL's data scope
- Foreign-owned data injected by a simulated provider cause the entire response
  to be rejected, with no partial mixed-tenant output
- Only affiliate-scoped data-provider queries execute, no queries for unknown
  identities, no business records modified
- Diagnostics are OFF and denied requests do not expose signer internals

## Run

    php -l scripts/test-creator-two-identity-synthetic-uat.php
    php scripts/test-creator-two-identity-synthetic-uat.php

The named GitHub Actions step Creator two-account synthetic cross-tenant isolation UAT
runs these checks in Andy Core Regression, independently of the existing
legacy v2.2.6 release package pipeline.

## Status/limitations

This is a synthetic in-memory integration harness covering the concrete
API provider, HMAC and owner-scoped reads. It is not an actual multi-login,
database-backed WordPress/browser test, and it is NOT an authorized real
WWW cross-affiliate probe. It cannot on its own establish production
cross-account UAT PASS; that remains a distinct gated task.

No change to the deployed WWW Canary, Website Dev/WWW theme, real AffiliateWP
data, permissions, or two existing Draft PR merge states.
