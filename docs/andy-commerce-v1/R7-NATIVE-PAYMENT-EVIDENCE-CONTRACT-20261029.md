# R7-3 to R7-8 Native WooPayments evidence contract (offline only)

## Purpose
This tool is **not** a payment gateway, FX bridge, checkout hook, Woo plugin,
order writer, Stripe credential reader or live-Dev audit.
Currency OS source-USD compatibility is CLOSED at Dev engineering level.
The separate native WooPayments TEST acceptance remains OPEN / NO-GO
under YBY-Core Issue #252. Do not revive the self-built R13 Canary.

The auditor uses only Python standard libraries and performs no network,
WordPress, provider, API, browser, DB or GitHub actions. Its input is a
privately held JSON evidence bundle collected through a separately approved,
permitted Provider/Woo readback path. Never commit real evidence JSON,
customer data, API keys, webhook secrets or raw provider responses.

## Invocation
From repository root, run the offline mock-only tests:

    PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests -p r7_native_payment_evidence_gate_test.py -v

Run against *separately collected, real and approved* evidence:

    python3 scripts/r7-native-payment-evidence-gate.py /private/path/r7-evidence.json

Exit 0 means only that supplied fields satisfy a consistency contract.
It does NOT authenticate the underlying provider or Woo observations.
Independently verify Provider records, webhook signatures, HPOS order
readback and Owner signoff before Issue #252 may be closed.

## Required evidence structure
- Top-level: scope dev.beyourlover.com, evidence_origin
  authorized_provider_and_woo_readback and synthetic false.
- environment: exact host, Woo base USD, active YayCurrency Pro, native
  WooPayments Multi-Currency OFF, checkout-different-currency ON,
  enabled_checkout_gateways exactly ["woocommerce_payments"], independent
  Stripe OFF, TEST merchant ID, provider livemode false, identity readback
  and HPOS enabled.
- payments: exactly five distinct USD/EUR/GBP/AUD/CAD successful Woo
  orders with distinct Provider Payment Intents, matched order/provider
  currency and amount in minor units, TEST-only account, HPOS persistence,
  thank-you/customer views and signed webhook IDs. At least one event
  must have two or more deliveries and exactly one status effect.
- negative_paths: declined, 3ds_success, 3ds_cancel, provider_error,
  expired_session. Each must have provider-observed evidence, with no
  duplicated payment intent.
- refunds: full and partial TEST provider refund, each attached to an
  accepted Woo order, same account/currency, matching exact minor amount,
  original total, remaining amount and duplicate refund prevention.
- recovery: legacy Canary OFF, Campaign master OFF, switches restored,
  no WWW changes and explicit Owner signoff.

## Hard limits
- All local fixture records are **FAKE**; they cannot prove a real charge.
- Claimed true/false fields in JSON cannot prove Provider identity.
- The auditor does not enable foreign Checkout or change a gateway.
- The previous safety block remains in force; no alternate transport
  or hidden credential route to Dev account verification.
- No main, Stable, WWW or actual payment write is authorized by this PR.
- PR #213 remains the only Commerce Dev plugin publisher; this tool is
  a standalone uninstalled QA artifact.

## Recording
Use the existing GitHub Issue #252 for future authorized provider receipt
IDs and evidence. Keep R7 OPEN until all real payment, failure path, HPOS,
webhook/dedup, full/partial refund and rollback checks are accepted.
