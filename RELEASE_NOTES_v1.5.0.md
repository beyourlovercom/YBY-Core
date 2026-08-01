# Andy Core v1.5.0 Release Notes

## Release purpose

Andy Core v1.5.0 delivers secure Google authentication and a portable, governed Bottle inquiry runtime while preserving the existing `yby-core` plugin identity and frozen v1.x public APIs.

## Added

- Google Social Login with local RS256 ID Token verification.
- Optional WordPress login-page integration and safe existing-account association.
- Opt-in Google One Tap with single-use challenges and dedicated cookie session confirmation.
- Bottle OEM inquiry preset, governed fields, and page-profile validation.
- Lead `page_profile` persistence and SDK transport.
- Page-specific Thank You URL overrides.
- Confirmed-only, Case-scoped WhatsApp project summaries.

## Security and portability

- Privileged, custom, mixed, and disabled roles remain blocked from automatic Google association.
- No Client Secret, access token, refresh token, OAuth credential, or identity data is exposed publicly.
- Page Profile URLs accept only safe relative paths or structurally valid HTTP(S) URLs.
- Thank You redirects append only `case_id` and ignore payload-controlled redirect values.
- WhatsApp summaries exclude unconfirmed preset data and remain isolated by Case ID.
- Site Profile and Brand Profile prevent Bottle and Irrigation identity leakage.

## Compatibility

- Plugin path remains `yby-core/yby-core.php`.
- Technical `YBY_*`, `yby_*`, shortcode, REST, option, and browser runtime identifiers remain unchanged.
- Database version advances to `1.2.0` to add the nullable Lead `page_profile` column.
- Production deployment is not part of the release freeze.
