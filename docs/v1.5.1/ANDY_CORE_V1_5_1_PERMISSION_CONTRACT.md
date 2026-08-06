# Andy Core v1.5.1 Permission Contract

- `andy_core_leads_view`: list, detail, and read-only diagnostics.
- `andy_core_leads_manage`: status, priority, follow-up, and notes.
- `andy_core_leads_assign`: owner assignment.
- `andy_core_leads_archive`: archive and restore.
- `andy_core_settings_manage`: existing Settings capability; v1.5.1 keeps `manage_options` compatibility.

The implementation maps the capabilities to the existing administrator capability without changing existing roles. Every write checks capability, action-specific nonce, Lead existence, allowlists, owner validity, sanitation, and escaped output. Permanent deletion is not exposed.
