# Andy Core v1.5.8 — Email Template OS V1 — P0 Runtime Inventory

Status: FROZEN BASELINE
Date: 2026-09-14
Branch: `feat/andy-core-v1.5.8-email-template`
Canonical source baseline: `main@b8cf37b5b2cec4b0e3d14be58aae51a0731e2407`

## 1. Release / Local alignment

- GitHub `main` is Andy Core `1.5.7`, DB `1.4.0`.
- Public `andy-core-release` v1.5.7 is published.
- Published package SHA-256: `34c75d7533ec3d7f5e32592a96bd9ec3d7ab9e016cbe707ae0a3316324beed23`.
- `localdev.beyourlover.com` was on Andy Core `1.5.3` and has been aligned to published `1.5.7`.
- Local plugin remains active after alignment; frontend health check returns HTTP 200.
- Local DB version remains `1.4.0`.

## 2. Current mail runtime inventory

WooCommerce runtime currently exposes 25 email classes.

Core Woo / account / POS classes include:
- new_order
- cancelled_order
- customer_cancelled_order
- failed_order
- customer_failed_order
- customer_on_hold_order
- customer_processing_order
- customer_completed_order
- customer_refunded_order
- customer_invoice
- customer_note
- customer_reset_password
- customer_new_account
- admin_payment_gateway_enabled
- customer_pos_completed_order
- customer_pos_refunded_order

Additional Smart Coupon mailers include:
- wt_smart_coupon_gift
- wt_smart_coupon_abandonment_coupon_email
- wt_smart_coupon_signup_coupon_email
- wc_sc_email_coupon
- wc_sc_combined_email_coupon
- wc_sc_acknowledgement_email
- wc_sc_expiry_reminder_email
- wt_smart_coupon_store_credit
- wt_smart_coupon

## 3. Legacy WooCommerce Email Template Customizer

Plugin: `woocommerce-email-template-customizer`
Version: `1.2.14 Premium`
State: ACTIVE

Observed storage / content types:
- `viwec_template`
- `viwec_template_block`
- `wacv_email_template`

Observed legacy content:
- 18 `viwec_template` records
- 2 shared Header / Footer blocks
- 6 abandoned-cart / WACV email template records

Observed scenarios include New Order, Cancelled, Failed, Customer Failed, On Hold, Processing, Completed, Refunded, Partial Refund, Invoice, Customer Note, Reset Password, New Account and Abandoned Cart.

Legacy renderer behavior is not cosmetic only. It participates in:
- `wc_get_template`
- Woo email subject filters
- `woocommerce_email_header` at priority 0
- Woo mail callback params
- email styles / custom CSS
- attachments
- WordPress new-user / password-reset mail paths

Therefore v1.5.8 MUST NOT add a second wrapper on top of the legacy renderer.

## 4. Legacy visual baseline

Observed shared email design values:
- primary legacy brand color: `#9B3749`
- common backgrounds: `#F5F5F7`, `#F2F2F2`
- some individual templates drift to `#E43F5A`
- canonical legacy content width: 600px

The legacy design is migration evidence, not the new source of truth.

## 5. Delivery layer

`WP Mail SMTP Pro 4.5.0` is installed locally but currently inactive.

Email Template OS V1 owns template identity, content, rendering, publication and preview. It does NOT own SMTP credentials, provider delivery, retries or delivery observability.

## 6. Brand profile note

A prior CLI probe appeared to show a YBY Irrigation profile. That result came from using the wrong Local PHP/MySQL runtime and is INVALID for BYL.

Correct BYL runtime currently resolves to Andy Core default identity values (`YBY / CORE`) rather than a frozen BYL-specific Case ID identity. No repository evidence was found for a canonical BYL `case_id_brand_code`; v1.5.8 must not invent one as part of Email Template OS.

Email VI settings are therefore independent presentation configuration and must not mutate Case ID identity.

## 7. P0 conclusions

- 1.5.8 may proceed from `main@b8cf37b5`.
- Runtime discovery is mandatory; no hard-coded assumption of 21 email classes is allowed.
- Legacy Customizer remains active until per-template cutover gates pass.
- Smart Coupon compatibility is first-class scope.
- WordPress mail paths must be treated separately from Woo email classes.
- ERP is a read-only consumer of published template metadata; ERP must not become an editor or runtime renderer.
