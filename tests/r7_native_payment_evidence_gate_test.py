#!/usr/bin/env python3
"""Only synthesized local fixtures; never report these as actual R7 receipts."""
import copy
import importlib.util
import pathlib
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "r7_gate", ROOT / "scripts" / "r7-native-payment-evidence-gate.py")
gate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(gate)


def fixture():
    acct = "acct_FAKE1234567890"
    currencies = ["USD", "EUR", "GBP", "AUD", "CAD"]
    rows = []
    for i, code in enumerate(currencies):
        rows.append({
            "currency": code, "woo_order_id": 80001+i,
            "woo_order_currency": code, "woo_total_minor": 2000+i,
            "provider_currency": code.lower(), "provider_amount_minor": 2000+i,
            "provider_intent_id": f"pi_SYNTHETIC{i}", "provider_account": acct,
            "provider_livemode": False, "woo_status": "processing",
            "hpos_persisted": True, "webhook_event_id": f"evt_SYNTHETIC{i}",
            "webhook_signature_verified": True, "webhook_deliveries": 2 if i == 0 else 1,
            "webhook_effects": 1, "thank_you_confirmed": True,
            "customer_order_view_confirmed": True
        })
    refunds = []
    for kind, index, amount in [("full", 0, 2000), ("partial", 1, 500)]:
        refunds.append({
            "kind": kind, "woo_order_id": rows[index]["woo_order_id"],
            "woo_refund_id": 90001+index,
            "provider_refund_id": f"re_SYNTHETIC{index}",
            "currency": rows[index]["currency"],
            "provider_currency": rows[index]["currency"].lower(),
            "amount_minor": amount, "provider_amount_minor": amount,
            "remaining_minor": rows[index]["woo_total_minor"]-amount,
            "provider_account": acct, "provider_livemode": False,
            "provider_status": "succeeded", "duplicate_refund_blocked": True
        })
    return {
        "scope": "dev.beyourlover.com",
        "evidence_origin": "authorized_provider_and_woo_readback",
        "synthetic": False,
        "environment": {
            "host": "dev.beyourlover.com", "woo_base_currency": "USD",
            "yaycurrency_pro_active": True, "woo_native_multicurrency": False,
            "checkout_different_currency": True,
            "enabled_checkout_gateways": ["woocommerce_payments"],
            "independent_stripe_enabled": False, "provider_livemode": False,
            "provider_test_account": acct, "provider_identity_readback": True,
            "hpos_enabled": True
        },
        "payments": rows,
        "negative_paths": [{
            "kind": kind, "observed_on_test_provider": True,
            "no_duplicate_intent": True
        } for kind in sorted(gate.NEGATIVE)],
        "refunds": refunds,
        "recovery": {
            "legacy_canary_off": True, "campaign_master_off": True,
            "test_switches_restored": True, "no_www_changes": True,
            "owner_signoff": True
        }
    }


class R7EvidenceTests(unittest.TestCase):
    def test_synthetic_shape_fixture_only(self):
        self.assertEqual([], gate.validate(fixture()))

    def reject(self, change, expected):
        data = fixture()
        change(data)
        self.assertIn(expected, gate.validate(data))

    def test_wrong_merchant(self):
        self.reject(lambda d: d["payments"][2].update(provider_account="acct_OTHER"),
                    "payment_2_provider_authority")

    def test_amount_mismatch(self):
        self.reject(lambda d: d["payments"][1].update(provider_amount_minor=4),
                    "payment_1_amount")

    def test_currency_mismatch(self):
        self.reject(lambda d: d["payments"][3].update(provider_currency="usd"),
                    "payment_3_provider_currency")

    def test_live_charge_rejected(self):
        self.reject(lambda d: d["payments"][0].update(provider_livemode=True),
                    "payment_0_provider_authority")

    def test_webhook_duplicate_effect(self):
        self.reject(lambda d: d["payments"][0].update(webhook_effects=2),
                    "payment_0_webhook_idempotency")

    def test_missing_3ds(self):
        self.reject(lambda d: d["negative_paths"].pop(), "missing_failure_paths")

    def test_refund_currency_mismatch(self):
        self.reject(lambda d: d["refunds"][0].update(currency="CAD"),
                    "refund_0_currency_mismatch")

    def test_partial_overrefund(self):
        self.reject(lambda d: d["refunds"][1].update(
            amount_minor=3000, provider_amount_minor=3000), "refund_1_refund_size")

    def test_unverified_gateway(self):
        self.reject(lambda d: d["environment"].update(
            enabled_checkout_gateways=["woocommerce_payments", "stripe"]), "gateway_allowlist")

    def test_synthetic_claim_rejected(self):
        self.reject(lambda d: d.update(synthetic=True), "synthetic_not_allowed")

    def test_malformed_currency_fails_closed(self):
        self.reject(lambda d: d["payments"][0].update(currency=["USD"]),
                    "payment_0_currency")

    def test_malformed_order_id_fails_closed(self):
        self.reject(lambda d: d["payments"][0].update(woo_order_id={"x": 1}),
                    "payment_0_order")

    def test_malformed_failure_kind_fails_closed(self):
        self.reject(lambda d: d["negative_paths"][0].update(kind=["declined"]),
                    "negative_kind_or_duplicate")

    def test_missing_owner_signoff(self):
        self.reject(lambda d: d["recovery"].update(owner_signoff=False),
                    "recovery_owner_signoff")


if __name__ == "__main__":
    unittest.main(verbosity=2)
