# P6 follow-up: local cleanup, Woo Test OAuth gate, prepared 3DS/decline tests (2026-10-26)

## Safe cleanup verified
- On authorized US Mac mini, the independent temporary P6 webhook receiver process and its Cloudflare **quick** tunnel were terminated via self-built DC `kill_process`. Read-only process/listener check verified no listener on `127.0.0.1:19318`.
- The matching Stripe **test-only** Webhook endpoint was independently read back as `disabled` (`livemode=false`). All other operating tunnels and four READY computers were unchanged.
- No webhook signing secret or opaque receiver URL is committed to GitHub.

## Connected Stripe account evidence
- Only the connected **Test** Stripe account is used (`livemode=false`).
- Existing USD 11340 minor-unit Checkout Session has `status=complete`, `payment_status=paid`. Linked test PaymentIntent `pi_3UUd26CxlWhQx8Rb00bHWmbl` was confirmed `succeeded,amount_received=11340,livemode=false` and Stripe Events includes `charge.succeeded`, `payment_intent.succeeded`, `checkout.session.completed`.
- This provider-side PASS is **not a WooCommerce payment**. An attempted Stripe test refund was platform-security blocked; read-only refunds returned zero. Do not report provider partial-refund PASS.
- Stripe USD/EUR/GBP/AUD/CAD sessions created, but only the USD one has provider payment success; four foreign currency sessions remain unpaid and FX settlement is unverified.

## Additional provider test scenarios prepared but NOT completed
- Dedicated Stripe Test Checkout for a **required 3DS card challenge**, 121 USD cents, session `cs_test_a1Zw1ecz4xtb2Lei99Nt0fqWWII9cprmGB49zcy4fXHv6VTvjxMY7RkISr`, initially `open/unpaid`. Test card per Stripe's official 3DS docs: 4000 0000 0000 3220; verify challenge and terminal PaymentIntent after Owner enters test card.
- Dedicated Stripe Test Checkout for a **decline**, 122 USD cents, session `cs_test_a1vMQMjhgzdYVAJpey4zbZBrGPBqHIz3dOmLKI2S49Mt2nZPQgSYLEJmFu`, initially `open/unpaid`. Must independently read back test failure / decline events after Owner enters a documented Stripe decline test card; do not infer it from browser error.
- No actual card interaction has occurred with these two sessions. Browser automation of hosted Stripe payment screens was previously intercepted by platform safety; do not attempt to bypass.

## Critical WooCommerce Test OAuth blocker
- Independent Woo sandbox `http://127.0.0.1:18973` contains official WooCommerce Stripe Gateway 11.1.1, installed **inactive** with `woocommerce_stripe_settings` empty and payment gateway allowlist **COD only**.
- Its local MU safety guard blocks **all external WP HTTP** and Webhook delivery; direct OAuth and test charges will not function. Do not globally remove the guard to make a provider connection.
- Current official WooCommerce guidance requires a publicly accessible HTTPS site for Stripe OAuth and a separately confirmed Test-mode account. The temporary **webhook-only** quick tunnel must not be repurposed into a public WordPress admin frontend.
- A separate authenticated public HTTPS staging site and narrowly controlled WP HTTPS requests are the required engineering prerequisites before genuine Woo ↔ Stripe / HPOS order UAT.

## P6-F truth
- Stripe standalone test payment success: **PASS**; paid 3DS, declined test, Woo Stripe OAuth, real Woo provider checkout, actual Webhook delivery receipt, partial refund, five-currency provider FX/settlement: **OPEN/NOT VERIFIED**.
- Draft P6 PR #225 only; sole Commerce integration Draft PR #213 and Issue #216 remain open. Main, Stable, installed Dev Commerce, WWW and production Stripe untouched.
