# Andy Core — Administrator Email Notifications V1

## BYL V3 canonical decision (2026-10-26)
- The new site `dev.beyourlover.com` and its planned full-site WWW cutover use **Andy Core Native Newsletter**, not WPForms.
- Native Newsletter persists subscriptions in `YBY_Newsletter_Store`, with customer opt-in, confirmation and unsubscribe paths.
- Native Newsletter **does not send an admin email for every subscription**. This is a read-only built-in behavior, not a deceptive editable toggle.
- Customer confirmation mail remains under independent Newsletter outbound approval/readiness and `YBY_Mail_Transport`. **Do not activate real sending** for this UAT.
- Never install or restore WPForms form `26680` on BYL Dev merely to satisfy old-site compatibility tests. That form is legacy only, not the BYL V3 release gate.

## Administrator Email UI
Go to **Andy Core → 邮件 → 通道**:
- WordPress new-user registration → admin: genuine editable toggle (off on approved BYL Dev).
- Andy Core native Newsletter → admin: fixed **不发送（内置规则，无需开关）** with live native service, customer outbound and readiness statuses.
- WPForms controls (form ID, admin addresses, per-form notice) appear **only when WPForms is installed** on a site; its legacy behavior is unchanged. Hidden legacy options remain preserved when saving on a native-only site.
- WooCommerce email summaries and native edit deep-links are read-only in Core; WC/Commerce retain setting authority.
- Inquiry notices, payment/order notices, customer registration notices and SMTP identity are never intercepted by this V1.

## BYL Dev-only acceptance
1. Confirm GitHub PR head SHA, target URL `https://dev.beyourlover.com`, affected PHP file SHA256 and known rollback; no WWW access.
2. Check native Newsletter store exists, and admin policy/hook is registered. Confirm registration admin notification disabled, customer account hook unaffected.
3. Render the real backend menu; assert the native Newsletter status row exists, and no WPForms ID/address fields are displayed on BYL Dev. Check administrator save, nonce, capability and setting readback.
4. Test native Newsletter schema **with isolated transaction, insert/read/confirm/rollback**, ensuring zero retained test records and zero outbound mail. Separately check native REST/API/outbound readiness; if outbound gates are OFF, **do not mark actual REST subscribe or customer SMTP delivery as PASS**.
5. Ensure WooCommerce source/read-only summary and inquiry delivery contracts remain intact.
6. Owner browser visual and functional UAT remain distinct from CLI render/source tests.

## Legacy WPForms adapter (optional, not BYL V3 scope)
The `wpforms_process_before_form_data` filter remains available for sites already using WPForms. It requires explicitly configured form IDs and administrator recipient matching; it never alters stored entries or customer recipients. Real WPForms end-to-end acceptance belongs to a compatible legacy site, not BYL Dev.

## Safeguards and release
- No global `wp_mail` blacklist, no SMTP provider replacement, no real send tests without approval.
- Source authority remains GitHub; Dev is a reproducible code-only integration target, never the source of truth.
- PR #227 stays Draft until CI, scoped Dev UAT and Owner acceptance. No `main` merge, Stable or WWW/cutover authorization is inferred.
- Rollback only scoped changed Dev PHP files and the saved Dev notification option if required.
