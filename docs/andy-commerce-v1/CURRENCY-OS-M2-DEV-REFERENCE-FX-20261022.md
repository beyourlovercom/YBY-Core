# Andy Commerce Currency OS M2 — Dev-only reference FX adapter (2026-10-22)

## Status & scope
- Owner wants to remove YayCurrency eventually and test completely on **dev.beyourlover.com** first. No WWW, main, Stable, paid orders or payment captures.
- Stacked on Currency OS M1 Draft PR #192 exact source HEAD `6ee911f212b20527f6fb37a0a998c11a4177e493`, itself preserving Marketing M4–M8 Draft PR #191 exact M7 nonce-fix commit.
- Dev has no YayCurrency; M1 Andy Commerce 1.0.5 was confirmed via authenticated read-only WP editor to have four SHA256-exact PHP source matches to #192. Five synthetic Dev preview pages USD/EUR/GBP/AUD/CAD each HTTP 200 and expected totals. No migration/deactivation is necessary on Dev.
- This M2 code increments Dev-only Andy Commerce candidate to **1.0.6** and adds only reference-FX **source+explicit administrator preview**.

## Currency rate data contract
- Trusted fixed remote destination (no user-controlled URL): `https://api.frankfurter.dev/v2/rates?base=USD&quotes=EUR,GBP,AUD,CAD`.
- Server-side WordPress `wp_safe_remote_get`, 8s timeout, **zero redirects**, ≤16 KiB response, expected HTTP 200 JSON list. No API key, HTTP bearer token, user data or payment metadata transmitted.
- Require exactly one observation for **each** EUR/GBP/AUD/CAD in USD base, valid ISO date ≤7 days old and not more than 1 day in future, numeric/finite/bounded rate >0.1 and <10. Reject stale, missing, duplicate, malformed, non-USD or anomalous data; **never silently substitute stale/synthetic rate in 'reference' mode**. PHP 8.0 compatible.
- Snapshot stores rate in 1e6 USD minor conversion scale, original per-quote observation date, read UTC time, immutable source label `FRANKFURTER_REFERENCE`; fixed USD=1.
- Explicit admin-only, Dev-only GET `?page=andy-commerce&tab=currency-os&currency=EUR&fx_source=reference` fetches live reference observation, renders converted quote in **shadow mode only**. Default `fx_source=synthetic` retains prior M1 QA fixtures. No background cron, cache writes, Woo hooks or live pricing changes.

## On-device source and runtime evidence
- US Mac mini via self-hosted official OAuth DC: fetched Frankfurter real USD four-pair endpoint at 2026-10-22, HTTP **200**, expected array of **4** quote codes AUD/CAD/EUR/GBP and observation date 2026-10-22. Source response not used for actual payments.
- Offline PHP M2 parser and mocked WordPress fetch: **36 assertions PASS**; M1 82 PASS. P0 negative cases: network/HTTP failure, wrong JSON, oversized body, wrong base/quote, missing or duplicated quote, stale/future/invalid calendar, negative/non-finite/text/outlier rate.
- Frankfurter official docs https://frankfurter.dev/ state free commercial use, daily rates, no API key, suitable for reports/accounting, NOT live trading or settlement. Rate source is only a reference for M2.
- MUST complete strict Dev plugin version and source parity, 1.0.5 rollback archive, 1.0.6 package integrity and Dev readback before accepting live preview as installed.

## Still NOT solved by M2
- No actual WooCommerce product price, cart discount, coupon, tax, shipping or chosen customer currency state; no selected gateway currency or payment charge changes; no order/refund snapshots. These require M3–M6.
- Actual Stripe Gateway v11.1.0 plugin has official `wc_stripe_available_store_currencies` filter, but its availability does not prove full refund/payment/3DS parity. WooPayments and PayPal require their own separate sandbox validation.
- No USD $99 demo override of current BYL $69+ region policy. Free shipping remains governed by real Woo zones and discounted USD eligible goods only.
