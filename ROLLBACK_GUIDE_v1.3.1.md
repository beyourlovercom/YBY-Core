# YBY Core v1.3.1 Rollback Guide

1. Disable the v1.3.1 plugin package if the Dev smoke test fails.
2. Restore the previously validated YBY Core v1.3.0 package.
3. Remove or disable the new global modal and sticky CTA shortcode placements.
4. Existing legacy `data-yby-modal-open` triggers remain the compatibility path.
5. Re-run Lead, Case ID, Thank You, WhatsApp, and tracking smoke checks.
