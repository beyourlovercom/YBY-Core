# Andy Core v2.3.0 — unpublished Notification UI release candidate

- Includes approved GitHub main features from PRs #238, #240, #243 at source baseline 5bb00a71de3600a0f9bc2b895d76e0cfef33d296.
- Core-owned Dynamic Island for supported Woo Classic success, info, warning and error notices, plus known Block-style notice banners.
- Native Notice Compact Skin applies ON/OFF, without overwriting theme business logic.
- Original Woo notices retained on error, unavailable assets or unsafe handoff; critical errors stay visible and field validation remains actionable.
- Default OFF in unified Andy Core Modules settings; independent of Theme and Andy Commerce version upgrades.
- Preserves v2.2.12 data and yby_lead_notifications Outbox/audit; no schema version bump (runtime 1.5.0, updater compatibility 1.4.0).
- Test coverage BYL Theme V3 and Twenty Twenty-Five, R5 Owner Visual UAT PASS; no blanket third-party gateway claim.
- Candidate only: signing, Stable publication and individual site installations are independent Owner gates.
