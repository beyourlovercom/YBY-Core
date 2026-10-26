# Andy Core — Administrator Email Notifications V1

## Purpose
Manage low-value administrator notifications in Andy Core → 邮件 → 通道 without replacing WooCommerce, WPForms, wp_mail(), WP Mail SMTP, or the existing Tencent-scoped newsletter transport. The Email OS transport tab continues to show its prior status and now includes the notification policy panel.

## Default / release safety
- Backward compatible by default: no options stored; registration_admin and newsletter_admin default to ON. No existing site has mail suppressed merely by installing this change.
- Change permissions require manage_options, andy_core_settings_manage, and andy_core_mail_view; POST uses admin-post.php plus nonce.
- Does not send tests, modify SMTP routing, delete submitted forms, send marketing emails, change order/customer data, or modify WooCommerce settings.
- Generic Core code contains no site-specific email address or form ID. No new costs/dependencies.

## Provider contract
| Provider | V1 integration | Safety |
| --- | --- | --- |
| WordPress registration admin | Hook wp_send_new_user_notification_to_admin | Never touches wp_send_new_user_notification_to_user |
| WPForms newsletter admin | Hook wpforms_process_before_form_data | Only explicit allowlisted form IDs and exact administrator addresses / {admin_email} smart tag; preserve remaining recipients and entries |
| WooCommerce | Live read-only email list with deep links to native WC settings | Never writes WooCommerce email options or hooks blanket wp_mail suppression |
| Inquiries / security / transport | Unchanged | No suppression intercept |

Option key: yby_core_mail_admin_notifications. Fields: registration_admin, newsletter_admin, newsletter_form_ids, newsletter_admin_recipients.

## BYL environment UAT steps
1. Install/test this branch only on approved Dev after verifying backup and target; do not install incrementally on legacy www.beyourlover.com.
2. Navigate to admin.php?page=andy-core-email&tab=transport. Confirm transport status unchanged, card visible, WooCommerce listing read-only.
3. For BYL newsletter, enter form ID 26680 and the real administrator notification destination (support@beyourlover.com only if the current WPForms notification matches it); uncheck Newsletter → 管理员. Uncheck 新用户注册 → 管理员.
4. Save as a fully privileged administrator; reload and verify the settings persist.
5. With a controlled non-production form submission, confirm WPForms stores the entry, customer mail is not blocked, and the newsletter admin-only notification is not generated. Inspect whether WPForms uses deferred sending on this install; if so, test that path too. Do not send real mail without Owner approval.
6. Confirm inquiry form admin notifications still work, WooCommerce order / payment alert emails remain unchanged, and the customer new-account/password email remains enabled. Check all WooCommerce listed links.
7. If any condition fails, re-enable the two administrator toggles or roll back the branch. Existing data is untouched.

## Non-goals & follow-up
- V1 does not claim real inbox delivery or production visual acceptance.
- WooCommerce email business policy remains WooCommerce / Andy Commerce owned under AGENTS.md. A future Commerce adapter may expose editable settings using WC email APIs with exact readback and permission gates; do not maintain a second competing persistent option in Core.
- Do not replace wp_mail globally: that would risk password reset, checkout, customer confirmation, inquiries, and security events.
