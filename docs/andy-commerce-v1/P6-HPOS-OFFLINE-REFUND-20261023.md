# P6 isolated HPOS refund account-evidence: offline only

**Scope**: U.S. Mac mini local site `http://127.0.0.1:18973`, dedicated `aicc_commerce_p5_20261023` schema, WooCommerce 11.2.1, HPOS enabled; independent Dev/WWW databases and gateway settings. This evidence does **not** prove external financial refund capability.

M13 disposable native Woo sandbox tiered test order **#41** was read back as **USD170.64**, COD only, unpaid and with empty external transaction ID. A separately guarded WP-CLI script created a **USD5.00 Woo HPOS refund object #42**, explicitly using `refund_payment=false` and `restock_items=false`. WC_Order after readback remained total **USD170.64**, total refunded **USD5.00**, unrefunded **USD165.64**. No external gateway call, funds, customer, stock, refund emails or remote fulfillment touched.

**Reproduction safety:** test driver `packages/andy-commerce/tests/sandbox/p6-hpos-native-refund.php` checks exact localhost site and schema, `WP_ENVIRONMENT_TYPE=local`, explicit `AICC_ALLOW_SANDBOX_WOO_WRITES=1`, HPOS enabled, *known order ID 41 with correct original total and no preexisting refund*, and COD-only unpaid order; this is one-shot and intentionally fails on rerun. Copy it to disposable `$HOME/AI/uat/andy-commerce-p5p6-20261023/tools` before running via the sandbox's WP-CLI. Do **not** point it at Dev or WWW. It is excluded from installable ZIP. Retain original DB rollback snapshot separately.

P6 external gateway test credentials and dedicated webhook callback proof are **still missing**: Stripe, WooPayments, PayPal; multi-currency captures and partial gateway refunds, 3DS decline/cancel, native-to-ERP currency snapshots, and fail-closed gateway support matrix. Do not claim P6 full PASS.

P5 real Commerce runtime is also NOT PASS: all six draft promotions OFF; M13 uses synthetic native Woo coupon amounts to prove reconciliation in isolated local orders, not live automatic coupon, gift, stock or fulfillment lifecycle.
