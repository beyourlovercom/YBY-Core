# Andy Commerce 1.0.1 — BYL Platform consolidation

## Approval and boundaries
Owner approved folding the BYL Platform first-party plugin into Andy Commerce.
GitHub main remains the source of truth. Changes are code-only until separately
verified on Dev. Never deploy this work to current WWW or mutate customer orders.

## Already in Commerce
- Shipping promotion policy, region thresholds, BYL49 site preset
- One-promo-code and Woo Store API runtime hooks, with legacy coexistence guard
- Admin / Checkout Shipping settings page

## Additional compatibility in 1.0.1
- Read the existing valid BYL Platform option directly when the new Commerce option
  is absent. This is read-only; no implicit front-end DB changes.
- If Commerce settings exist but are invalid, never silently fall back to old
  settings. Checkout shipping promotion fails closed.
- Keep the existing `byl-shipping-promotion` Store API extension namespace on
  beyourlover.com hosts, for the unmodified BYL V3 Cart and Checkout clients.
- Non-BYL sites continue to use `andy-commerce-shipping-promotion`.
- Preserve the historical no-op `V3_DB_001` and the
  `byl_v3_applied_migrations` ledger. The old CLI command `wp byl migrate`
  remains supported when BYL Platform is inactive, alongside `wp andy-commerce migrate`.
  If legacy BYL Platform is still active, do not double-register its old CLI command.
- Existing legacy BYL Platform coexistence guard prevents double-running hooks.

## Controlled Dev cutover (not executed by this source PR)
1. Confirm the exact installed Core/Commerce/Platform versions and back up the
   relevant Dev plugin files and both shipping settings options.
2. Confirm no in-progress Checkout, G5 or Templates deploy on the same runtime.
3. Deploy tested Andy Commerce 1.0.1 on **Dev only**, initially alongside BYL Platform.
4. Confirm existing coupon/threshold values and Store API state. Run nontransactional
   Cart, Checkout, shipping zone and coupon negative/positive checks.
5. After the above passes, deactivate BYL Platform on Dev only; verify there is
   exactly one free-shipping/coupon hook owner, both cart and checkout still work,
   no unexpected orders, no external mail/webhooks, and no PHP fatal errors.
6. Owner UAT gate; then update V3 repo legacy plugin allowlist/tests/build.
   Only after Dev PASS and explicit release gate may the old plugin be retired
   from the final WWW cutover package.

## Rollback
Re-enable the previously backed-up BYL Platform plugin, restore the prior Andy
Commerce package, and invalidate relevant caches. No database deletion or option
rewrites are part of rollback. Do not rerun migrations or edit the migration
ledger. Dev Safety MU plugin stays installed and G2 test gate stays closed.

## Regression
`php packages/andy-commerce/tests/byl-platform-retirement-harness.php`
`php packages/andy-commerce/tests/shipping-promotion-migration-harness.php`
`php packages/andy-commerce/tests/shipping-legacy-coexistence-harness.php`
`php packages/andy-commerce/tests/shipping-default-selection-harness.php`
