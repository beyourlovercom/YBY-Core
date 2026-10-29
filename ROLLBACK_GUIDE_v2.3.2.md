# Andy Core v2.3.2 — rollback guide

- With an authorized admin session, first disable Hide Legacy. Do not publish the private login path.
- If locked out, authorized hosting file manager/SSH access can disable Core or restore signed v2.3.1.
- Restore the exact prior immutable signed v2.3.1 ZIP, without altering previous GitHub Release assets.
- Keep database, yby_lead_notifications, Woo orders/customers, email receipts, payments and Cron unchanged.
- Recheck normal admin access, original Social Login, password reset UI and Woo customer login after recovery.
- Do not install on WWW as part of GitHub publishing.
