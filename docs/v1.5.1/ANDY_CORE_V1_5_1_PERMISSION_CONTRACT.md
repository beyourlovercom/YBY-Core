# Andy Core v1.5.1 Permission Contract

- Salesperson is a view-only role with `andy_core_leads_view`; it can read only inquiries whose current Management `owner_user_id` equals the logged-in user. Unassigned inquiries and other owners are denied, including direct `lead_id` URLs.
- Administrator retains full visibility. Salesperson users do not receive manage, assign, archive, or settings capabilities.
- Owner assignment accepts only `Unassigned` (`0`) or IDs in the configured Salespeople / Inquiry Owners allowlist. Existing historical owner values remain stored and readable; they cannot be selected for new assignments unless enabled.

- `manage_options` is the existing site capability and is the v1.5.1 compatibility gate for Inquiry, Settings, and management writes.
- The conceptual capabilities `andy_core_leads_view`, `andy_core_leads_manage`, `andy_core_leads_assign`, and `andy_core_leads_archive` remain reserved for a future role migration; v1.5.1 does not silently alter WordPress roles.

The implementation maps the capabilities to the existing administrator capability without changing existing roles. Every write checks capability, action-specific nonce, Lead existence, allowlists, owner validity, sanitation, and escaped output. Permanent deletion is not exposed.
