# Andy Core v1.5.1 Permission Contract

- `manage_options` is the existing site capability and is the v1.5.1 compatibility gate for Inquiry, Settings, and management writes.
- The conceptual capabilities `andy_core_leads_view`, `andy_core_leads_manage`, `andy_core_leads_assign`, and `andy_core_leads_archive` remain reserved for a future role migration; v1.5.1 does not silently alter WordPress roles.

The implementation maps the capabilities to the existing administrator capability without changing existing roles. Every write checks capability, action-specific nonce, Lead existence, allowlists, owner validity, sanitation, and escaped output. Permanent deletion is not exposed.
