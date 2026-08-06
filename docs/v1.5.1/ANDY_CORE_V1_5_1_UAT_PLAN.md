# Andy Core v1.5.1 Owner UAT Plan

1. Install the RC only in the isolated Dev environment and confirm database migration to `1.3.0`.
2. Confirm Andy Core opens Inquiry while Project Studio, Projects, Brand, Social Login, and Settings remain reachable.
3. Exercise General, Inquiry, and read-only System Status tabs.
4. Search and filter synthetic or approved Dev Leads; verify bounded pagination.
5. Open a Lead and confirm original fields are read-only.
6. Change status, owner, priority, follow-up, and note; inspect the activity timeline.
7. Archive and restore; confirm the Lead remains unchanged.
8. Test a restricted role and confirm capability boundaries.
9. Run existing Bottle and Irrigation regression checks and confirm Lead REST, email, Case ID, and Thank You behavior.
10. Use the rollback guide if migration or management checks fail. Do not test against Production.
