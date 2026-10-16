# Andy Core Creator Historical Read-only Provider V1

Status: SOURCE ONLY / NOT DEPLOYED TO WWW.

This is a separate, disabled-by-default GET-only API for the new BYL Website V3 creator dashboard. It must not modify or replace WWW /affiliate-area/ and may not mutate AffiliateWP referrals, payouts, users, campaigns, or coupons.

## Required secure WWW config
BYL_CREATOR_REMOTE_API_ENABLED=true
BYL_CREATOR_REMOTE_KEY_ID=an independently chosen 8–64 character key ID
BYL_CREATOR_REMOTE_SIGN_KEY=an independently generated 32+ byte secret
BYL_CREATOR_REMOTE_BINDINGS=verified Dev-user-id => [wp_user_id => WWW ID, affiliate_id => AffiliateWP ID] allowlist; no wildcard

All values are runtime config only. No real keys in GitHub, theme pages, browser JS, logs, or PR comments. NEVER reuse the ERP Connector or AffiliateWP administrator API key. Default OFF; immediate revocation by removing the enabled flag or rotating the secret.

## Endpoint
GET /wp-json/andy-core/v1/creator/history?dev_user_id=N&affiliate_id=N&wp_user_id=N&ref_page=N&pay_page=N

It verifies SSL, required exact-signature headers, timestamp age <=120 seconds, single-use nonce, bounded paging, and WWW-side AffiliateWP ownership before reading. Returned fields contain only current approved affiliate totals, limited referral and payout lists, native currency and referral URL. Any missing plugin, inactive affiliate, foreign owner, mixed-owner record, invalid signature, or unsupported currency fails closed.

The consumer is independently prepared in beyourlover-web-v3 PR #127. A Dev WordPress user never chooses WWW affiliate ID; a trusted operator stores a signed mapping only after verifying a real person controls both accounts. Production WWW backend credentials and the Dev account binding are NOT provisioned by this PR.

## Verification and release gates
- PHP syntax and isolated synthetic security tests are source-only; no live AffiliateWP records read.
- Local runtime & plugin compatibility; independent review exact head; hosted CI (or specific Owner exception).
- WWW existing Andy Core version/plugin rollout compatibility and independent explicit WWW release approval.
- Separate Dev and WWW secrets provisioning in trusted private config (not chat or GitHub).
- Real approved account mapping, redacted authenticated read, forbidden-other-affiliate, pagination and Fx checks, visual Owner UAT on desktop and mobile.
- Never automatically enable on WWW or merge to main merely because tests pass.

## Live plugin API compatibility ? 2026-10-16
AffiliateWP 2.35.0 PHP source and Dev 2.37.1 runtime both expose affwp_get_affiliate(affiliate_id). The affiliates DB does NOT have get_affiliate(). Payouts are provided through affiliate_wp()->affiliates->payouts, not affiliate_wp()->payouts; the provider now implements the supported lookups, with isolated mocks reproducing both absent methods. This is a source-only repair until an exact-head WWW standalone Canary update is verified; do not automatically treat the earlier deployed package as updated.
