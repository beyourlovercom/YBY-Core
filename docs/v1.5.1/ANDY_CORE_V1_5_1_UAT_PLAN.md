# Andy Core v1.5.1 Owner UAT Plan

1. Install the RC only in the isolated Dev environment and confirm database migration to `1.3.0`.
2. Confirm Andy Core opens Inquiry while Project Studio, Projects, Brand, Social Login, and Settings remain reachable.
3. Exercise General, Inquiry, and read-only System Status tabs.
4. Search and filter synthetic or approved Dev Leads; verify bounded pagination.
5. Open a Lead and confirm original fields are read-only.
6. Change status, owner, priority, follow-up, and note; inspect the activity timeline.
7. Archive and restore; confirm the Lead remains unchanged.
8. Test restricted roles and confirm capability boundaries, including Subscriber denial and Salesperson read-only detail mode.
9. Configure Active Inquiry Owners, confirm only users with WordPress role `salesperson` are listed, and verify active owners can receive new assignments.
10. Disable an Active Inquiry Owner and confirm that historical assigned inquiries remain visible to that Salesperson while new assignments to that inactive owner are denied.
11. Confirm administrator owner dropdowns show `Unassigned`, active owners, and the current inactive historical owner as `{Display Name} (Inactive)` only on records that already belong to that inactive owner.
12. Remove and restore the `salesperson` role from an inactive owner; confirm access is lost while the role is removed, historical `owner_user_id` data remains unchanged, and access returns when the role is restored.
13. Seed stale Active Inquiry Owner option values for Administrator, Subscriber, and a deleted ID; confirm they do not grant Salesperson identity, owner assignment eligibility, or Inquiry access.
14. Confirm the Andy Core submenu contains exactly Inquiry, Project Studio, Projects, Brand, Social Login, and Settings, with no duplicate Andy Core item.
15. Run existing Bottle and Irrigation regression checks and confirm Lead REST, email, Case ID, and Thank You behavior.
16. Use the rollback guide if migration or management checks fail. Do not test against Production.

## Permission Sources

- `SALESPERSON_IDENTITY_SOURCE`: WordPress role = `salesperson`.
- `ACTIVE_OWNER_SOURCE`: Inquiry Settings Active Inquiry Owners.
- `HISTORICAL_OWNER_RETENTION`: YES.

## Delivery

The RC is intended for the isolated Dev URL supplied by the owner. Create a WordPress administrator or approved Editor test user there; do not reuse production credentials. The owner must verify the checklist manually and keep the Draft PR open until acceptance.
