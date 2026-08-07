# Andy Core v1.5.1 Permission Contract

- `SALESPERSON_IDENTITY_SOURCE`: WordPress role = `salesperson`. The role is the only Salesperson identity source; Active Inquiry Owners never grant Salesperson identity.
- `ACTIVE_OWNER_SOURCE`: Inquiry Settings Active Inquiry Owners. This setting only controls who may receive new Inquiry assignments.
- `HISTORICAL_OWNER_RETENTION`: YES. Disabling an Active Inquiry Owner never clears, rewrites, backfills, or reassigns historical `owner_user_id` values.
- Salesperson is a view-only role with `andy_core_leads_view`; it can read only inquiries whose current Management `owner_user_id` equals the logged-in user. Unassigned inquiries and other owners are denied, including direct `lead_id` URLs and `owner_user_id` query overrides.
- Administrator retains full visibility. Salesperson users do not receive manage, assign, archive, or settings access, and the runtime blocks those actions for restricted Salesperson users even if stale capabilities exist.
- New owner assignment accepts only `Unassigned` (`0`) or IDs in Active Inquiry Owners. Existing inactive owner values remain stored, readable to their Salesperson while the role remains `salesperson`, and visible to administrators as `{Display Name} (Inactive)` on the current record only.

- `manage_options` is the existing site capability and is the v1.5.1 compatibility gate for Inquiry, Settings, and management writes.
- The conceptual capabilities `andy_core_leads_view`, `andy_core_leads_manage`, `andy_core_leads_assign`, and `andy_core_leads_archive` remain reserved for a future role migration; v1.5.1 does not silently alter WordPress roles.

Every write checks capability, action-specific nonce, Lead existence, status/priority allowlists, active-owner assignment validity, sanitation, and escaped output. Permanent deletion is not exposed.
