# P6 readiness audit — 2026-10-25

## Live local evidence
Read-only verification on the authorized US Mac mini local-only WooCommerce instance returned:

| Property | Verified |
|---|---|
| WordPress | 7.1.3 |
| WooCommerce | 11.2.1 |
| Andy Core | 2.2.11 |
| Andy Commerce | 1.0.8 |
| Home | http://127.0.0.1:18973 |
| WooCommerce HPOS | yes |
| Allowed gateway identifiers | **cod only** |
| Automatic Marketing M14 runtime | **OFF** |
| Sandbox outbound HTTP | blocked by local MU guard |
| Sandbox mail | intercepted by local MU guard |

The installed plugin list includes Andy Commerce, Andy Core, WooCommerce and two local safety/test MU plugins; **it has no Stripe, WooPayments or PayPal gateway plugin**, and provider test credentials were not obtained or verified.

## What is already PASS
M15/M16 isolated local USD/HPOS evidence under Issue #216:
- Actual native Woo orders #58 (BOGO allocation) and #59 (Bundle allocation).
- A 12-process local DB quota test: exactly 3 accepted against cap 3.
- Local orders #60 and #61 each durably claimed campaign quota after order creation; abort-before-order release tested.
- Local order #62 proved persisted-order quota recovery and idempotent replay.
- Prior sandbox-only offline Woo refund accounting; **not a provider-initiated refund**.
- M14 has isolated Woo Store API and Classic Checkout six-type local COD evidence, and it was returned OFF.

## P6 required acceptance evidence (all gated)
| Gate | Required proof | Current result |
|---|---|---|
| P6-A synthetic callback/FX | Offline signature, replay, state and amount mismatch matrix | Not yet independently passed |
| P6-B local Woo status | Genuine HPOS order and independently verified synthetic status/refund ledgers | Not yet passed |
| P6-C provider account | Dedicated test-mode credentials and test-only webhook destination, redacted inventory | **BLOCKED: not connected** |
| P6-D capture and 3DS | Provider test object IDs for successful/failed challenge, declines and cancellations | **NOT TESTED** |
| P6-E multicurrency | Verified USD-ledger-to-provider charges & settle currency across USD/EUR/GBP/AUD/CAD where supported | **NOT TESTED** |
| P6-F refunds and replay | Provider test-mode partial/full refund responses, duplicate and out-of-order webhook audit | **NOT TESTED** |

## Explicit stop/go controls
1. No use of production Stripe, WooPayments or PayPal credentials; never read or copy real API secrets into GitHub.
2. Provider test account's explicit test-mode state and supported currencies must be proven before any provider request.
3. Test callbacks must be isolated from WWW/Dev and should not trigger emails, stock, shipments, ERP writes or real customer updates.
4. No live capture, real refund, main merge, Stable publish or WWW deployment.
5. An unavailable provider cannot be represented as a passed P6 gate. Existing COD transactions must remain labeled **offline native Woo**, not real card settlement.
6. A platform security gate stopped attempts to introduce a programmatic synthetic gateway event simulation in this run; **do not record those attempted tests as completed**. This document therefore captures only verified read-only P6 readiness and test plan.

## Applicable external developer guidance
WooCommerce payment gateways process checkout in their own gateway classes, with process_payment and process_refund behavior; Store API/Blocks also supports a separate payment-processing integration. Stripe test environments can simulate declines, 3DS and refunds. This plan must use the relevant current provider integration contracts, not assume COD processing status means funds settled.

Links: [Commerce integration PR #213](https://github.com/beyourlovercom/YBY-Core/pull/213), [P5–P6 issue #216](https://github.com/beyourlovercom/YBY-Core/issues/216), [security gate #208](https://github.com/beyourlovercom/YBY-Core/issues/208).
