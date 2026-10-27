# Andy Core v2.3.0 — candidate controlled upgrade

1. Verify current signed 2.2.12 installed version and intact rollback archive; confirm exact-source commit SHA.
2. Verify candidate ZIP and metadata version 2.3.0, SHA256, Ed25519 signature using pinned trusted public key.
3. Keep updater compatibility DB 1.4.0 and runtime DB 1.5.0; preserve yby_lead_notifications and all lead/outbox/order data.
4. Do not upgrade an actual site until separate Owner deployment approval. Isolate email and Cron.
5. Module OFF by default. Test native compact notice and no island JS on OFF; ON tests success/error and critical handling.
6. Check Woo checkout and validation, both themes, viewport, fallback when CSS/JS unavailable; switch OFF to roll back notice interception.
7. Main merge, signed candidate, Stable/Latest publishing and site installation remain distinct approval gates.
