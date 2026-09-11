# Andy Core v1.5.7 Upgrade Guide

Upgrade target: Andy Core 1.5.7
Database target: 1.4.0

Sites already on Andy Core 1.5.6 can receive 1.5.7 through the native public signed GitHub Release update channel. No GitHub token, SSH change, or wp-config.php update credential is required.

The upgrade adds Inquiry Modal Default v1 and the `used_forklift_inquiry` built-in preset. Existing saved inquiry field registries and built-in presets are upgraded additively; explicit custom preset configuration is preserved.

No database migration is required. Existing Case ID, lead, tracking, updater, backup, health-check, and rollback contracts remain unchanged.