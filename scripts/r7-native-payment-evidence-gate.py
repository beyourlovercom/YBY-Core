#!/usr/bin/env python3
"""Offline R7 evidence STRUCTURE gate. Never invokes provider, Woo, or Dev.
Passing this validator is not proof that source records are genuine.
Do not commit exported customer records, merchant secrets, or API credentials.
"""
import json
import re
import sys

CURRENCIES = ("USD", "EUR", "GBP", "AUD", "CAD")
NEGATIVE = {"declined", "3ds_success", "3ds_cancel", "provider_error", "expired_session"}
ACCT = re.compile(r"^acct_[A-Za-z0-9]{10,}$")
PI = re.compile(r"^pi_[A-Za-z0-9]+$")
REFUND = re.compile(r"^re_[A-Za-z0-9]+$")


def validate(data):
    errors = []

    def check(ok, label):
        if not ok:
            errors.append(label)

    def obj(x):
        return x if isinstance(x, dict) else {}

    def pos(x):
        return type(x) is int and x > 0

    if not isinstance(data, dict):
        return ["root_not_object"]
    env = obj(data.get("environment"))
    check(data.get("scope") == "dev.beyourlover.com", "wrong_scope")
    check(data.get("evidence_origin") == "authorized_provider_and_woo_readback", "origin_missing")
    check(data.get("synthetic") is False, "synthetic_not_allowed")
    check(env.get("host") == "dev.beyourlover.com", "wrong_host")
    check(env.get("woo_base_currency") == "USD", "base_not_usd")
    check(env.get("yaycurrency_pro_active") is True, "pro_inactive")
    check(env.get("woo_native_multicurrency") is False, "dual_fx")
    check(env.get("checkout_different_currency") is True, "checkout_currency_off")
    check(env.get("enabled_checkout_gateways") == ["woocommerce_payments"], "gateway_allowlist")
    check(env.get("independent_stripe_enabled") is False, "standalone_stripe_active")
    check(env.get("provider_livemode") is False, "live_provider")
    acct = env.get("provider_test_account")
    check(isinstance(acct, str) and bool(ACCT.fullmatch(acct)), "merchant_unverified")
    check(env.get("provider_identity_readback") is True, "merchant_proof_missing")
    check(env.get("hpos_enabled") is True, "hpos_not_enabled")
    payments = data.get("payments")
    if not isinstance(payments, list):
        payments = []
    check(len(payments) == 5, "five_payments_required")
    seen_cur, seen_order, seen_intents, seen_events = set(), set(), set(), set()
    order_map = {}
    duplicate_confirmed = False
    for i, raw in enumerate(payments):
        row = obj(raw)
        cur = row.get("currency") if isinstance(row.get("currency"), str) else None
        order_id = row.get("woo_order_id") if pos(row.get("woo_order_id")) else None
        intent = row.get("provider_intent_id") if isinstance(row.get("provider_intent_id"), str) else None
        event = row.get("webhook_event_id") if isinstance(row.get("webhook_event_id"), str) else None
        amount = row.get("woo_total_minor")
        prefix = f"payment_{i}_"
        check(cur in CURRENCIES and cur not in seen_cur, prefix + "currency")
        seen_cur.add(cur)
        check(pos(order_id) and order_id not in seen_order, prefix + "order")
        seen_order.add(order_id)
        check(isinstance(intent, str) and bool(PI.fullmatch(intent)) and intent not in seen_intents,
              prefix + "intent")
        seen_intents.add(intent)
        check(isinstance(event, str) and event.startswith("evt_") and event not in seen_events,
              prefix + "event")
        seen_events.add(event)
        check(pos(amount) and row.get("provider_amount_minor") == amount, prefix + "amount")
        check(row.get("provider_currency") == str(cur).lower(), prefix + "provider_currency")
        check(row.get("woo_order_currency") == cur, prefix + "woo_currency")
        check(row.get("provider_account") == acct and row.get("provider_livemode") is False,
              prefix + "provider_authority")
        check(row.get("woo_status") in ("processing", "completed"), prefix + "status")
        for field in ("hpos_persisted", "webhook_signature_verified", "thank_you_confirmed",
                      "customer_order_view_confirmed"):
            check(row.get(field) is True, prefix + field)
        deliveries, effects = row.get("webhook_deliveries"), row.get("webhook_effects")
        check(pos(deliveries) and effects == 1, prefix + "webhook_idempotency")
        duplicate_confirmed |= type(deliveries) is int and deliveries >= 2 and effects == 1
        if pos(order_id):
            order_map[order_id] = row
    check(seen_cur == set(CURRENCIES), "currency_coverage")
    check(duplicate_confirmed, "duplicate_webhook_proof")
    negatives = data.get("negative_paths")
    if not isinstance(negatives, list):
        negatives = []
    seen_negative = set()
    for row in negatives:
        r = obj(row)
        kind = r.get("kind") if isinstance(r.get("kind"), str) else None
        check(kind in NEGATIVE and kind not in seen_negative, "negative_kind_or_duplicate")
        seen_negative.add(kind)
        check(r.get("observed_on_test_provider") is True, "negative_not_provider_observed")
        check(r.get("no_duplicate_intent") is True, "negative_duplicate_intent")
    check(seen_negative == NEGATIVE, "missing_failure_paths")
    refunds = data.get("refunds")
    if not isinstance(refunds, list):
        refunds = []
    seen_kinds, seen_refunds = set(), set()
    for i, raw in enumerate(refunds):
        row = obj(raw)
        kind = row.get("kind") if isinstance(row.get("kind"), str) else None
        order_id = row.get("woo_order_id") if pos(row.get("woo_order_id")) else None
        original = order_map.get(order_id)
        prefix = f"refund_{i}_"
        check(kind in ("full", "partial") and kind not in seen_kinds, prefix + "kind")
        seen_kinds.add(kind)
        check(original is not None, prefix + "order_missing")
        check(pos(row.get("woo_refund_id")), prefix + "woo_refund")
        provider_ref = row.get("provider_refund_id") if isinstance(row.get("provider_refund_id"), str) else None
        check(isinstance(provider_ref, str) and bool(REFUND.fullmatch(provider_ref))
              and provider_ref not in seen_refunds, prefix + "provider_refund")
        seen_refunds.add(provider_ref)
        amt = row.get("amount_minor")
        check(pos(amt) and amt == row.get("provider_amount_minor"), prefix + "refund_amount")
        check(row.get("provider_status") == "succeeded", prefix + "provider_status")
        check(row.get("provider_currency") == str(row.get("currency")).lower(), prefix + "provider_currency")
        check(row.get("provider_account") == acct and row.get("provider_livemode") is False,
              prefix + "provider_authority")
        check(row.get("duplicate_refund_blocked") is True, prefix + "duplicate_refund")
        if original:
            total = original.get("woo_total_minor")
            check(row.get("currency") == original.get("currency"), prefix + "currency_mismatch")
            if pos(total) and pos(amt):
                check((amt == total) if kind == "full" else (amt < total),
                      prefix + "refund_size")
                check(row.get("remaining_minor") == total - amt, prefix + "remaining")
    check(seen_kinds == {"full", "partial"}, "full_and_partial_required")
    recovery = obj(data.get("recovery"))
    for field in ("legacy_canary_off", "campaign_master_off", "test_switches_restored",
                  "no_www_changes", "owner_signoff"):
        check(recovery.get(field) is True, "recovery_" + field)
    return errors


def main():
    if len(sys.argv) != 2:
        print("usage: r7-native-payment-evidence-gate.py /private/path/evidence.json", file=sys.stderr)
        return 2
    try:
        with open(sys.argv[1], encoding="utf-8") as f:
            payload = json.load(f)
    except (OSError, ValueError) as exc:
        print("R7_EVIDENCE_INPUT_ERROR=" + type(exc).__name__, file=sys.stderr)
        return 2
    errors = validate(payload)
    if errors:
        print("R7_EVIDENCE_STRUCTURE_NO_GO=" + ",".join(errors), file=sys.stderr)
        return 1
    print("R7_EVIDENCE_STRUCTURE_PASS; PROVIDER_AUTHENTICITY_NOT_VALIDATED; NOT_R7_CLOSED")
    return 0


if __name__ == "__main__":
    sys.exit(main())
