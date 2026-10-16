# Andy Core V2.2.3 Canary Manual Upgrade UAT

- Prerequisites: Existing Andy Core V2.2.2, Dev host allowlisted, Canary channel selected, Automatic Updates OFF.
- Keep the Dev plugin at V2.2.2 until signed GitHub V2.2.3 prerelease is published; do not upload a ZIP for this UAT.
- WordPress: Andy Core > Updates > Check for Updates; then Plugins > Installed Plugins > Andy Core > Update Now.
- Verify version V2.2.3, signature/checksum validation, plugin active, unchanged settings.
- Protected code-only backup and rollback required. No orders, payments, email writes.
