# VERSION — Andy Core 2.3.3 P0 Stable Hotfix

- Stable Release Version: 2.3.3
- Release Stage: R6 approved for signed Stable publication; deployment to any WordPress site requires separate site-specific approval
- Database Version: 1.5.0 (runtime schema, unchanged)
- Updater Compatibility Database Version: 1.4.0 (unchanged)
- Previous Public Stable: 2.3.2 (immutable; known incompatible with the YBY Bricks child theme)
- Production YBY: pinned at 2.2.10 until separate authorization
- P0 Root Cause: colliding Bricks child/Core inquiry class and duplicate element ID
- Database P0: dbDelta invalid single-line payout/claims table definitions
- Required UAT: actual Bricks 2.3.7 and approved YBY child theme; synthetic Inquiry/Case ID/UTM/GCLID
- R6 Release: approved 2026-10-30; do not republish 2.3.2 or auto-upgrade YBY. R7 production deployment NOT approved.
- Incident: docs/incidents/2026-10-30-yby-andy-core-v232-p0.md
