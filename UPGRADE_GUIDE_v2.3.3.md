# Andy Core 2.3.3 — controlled future upgrade gate

1. Stable distribution is authorized by R6; do not install on any site until separately authorized for that site, with signed immutable v2.3.3 verified.
2. Retain current site's full plugin files as backup. Do not overwrite existing customer/lead data or reset connector tables.
3. For YBY, reproduce the precise 2.2.10 → v2.3.3 path using Bricks 2.3.7 and the approved site-owned child theme in an isolated environment.
4. Check true uncached home, wp-json, wp-admin, wp-login, country advertising landing URLs, and no PHP Fatal / SQL errors.
5. Submit only synthetic inquiries on an isolated database; confirm Case ID, source preset, attribution (UTM/GCLID), and mail delivery interception.
6. On approved deployment, verify active version, monitor the actual site, retain rollback until signed off. Do not automatically switch update channels.

7. Preserve existing yby_lead_notifications records, Case IDs and all Connector records; no data reset is authorized.
