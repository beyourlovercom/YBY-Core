# YBY Core v1.3.1 Upgrade Guide

1. Back up the active YBY Core plugin.
2. Replace it with the v1.3.1 package after validation.
3. Keep existing Inquiry, Lead, Case ID, Thank You, WhatsApp, and tracking configuration.
4. Add `[yby_inquiry_modal]` once in the global site shell.
5. Add `[yby_sticky_cta]` once if the site-wide sticky CTA is approved.
6. Migrate page CTAs to `data-yby-inquiry-trigger` with a meaningful fallback `href`.
7. Run Dev QA before production installation.
