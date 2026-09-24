# V190-12 — Owner UAT Checklist

Status: READY / WAITING FOR OWNER

Environment:
https://dev.beyourlover.com/wp-admin/admin.php?page=yby-commerce

Owner checks:
1. Log in to Dev WordPress admin.
2. Open Andy Commerce > Order Export.
3. Confirm the page layout is clear and the filters are understandable.
4. Confirm these presets are visible:
   - Default
   - Full Export
   - BYL Processing Orders
   - BYL Full Report
5. Confirm Settings contains default preset, batch size, BOM, and audit controls.
6. Because Dev currently has zero Woo orders, an export is expected to return a header-only CSV; no test order needs to be created.
7. Confirm there is no Order Import function.

Expected state:
- WooCommerce active.
- Andy Core active only on Dev for this UAT.
- woo_order_export enabled.
- no Production deployment.
- legacy plugin not removed anywhere.

Owner response gate:
- PASS: proceed to release/next explicitly authorized stage.
- Any issue: record exact UI/CSV issue and return to the feature branch.
