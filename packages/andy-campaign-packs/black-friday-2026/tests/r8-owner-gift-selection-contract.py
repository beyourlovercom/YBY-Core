#!/usr/bin/env python3
"""R8 owner input audit: proposal data must never silently enable fulfillment."""
from pathlib import Path
import json

DATA = Path(__file__).resolve().parents[1] / "config/r8-owner-gift-selection-20261029.json"
m = json.loads(DATA.read_text(encoding="utf-8"))
assert m["schema_version"] == 1
assert m["campaign_id"] == "byl-black-friday-2026"
assert m["status"] == "OWNER_SELECTED_NOT_FULFILLMENT_APPROVED"
assert m["runtime_auto_load"] is False
assert len(m["owner_selected_free_gifts"]) == 1
a = m["owner_selected_free_gifts"][0]
assert a["product_id"] == 30454 and a["sku"] == "BY489A-Lubes"
assert a["erp_product_id"] == 1346
assert a["upgrade_minor"] == 0, "owner-approved free gift must not collect upgrade fee"
assert a["purchase_cost_usd_minor"] == 100
assert a["landed_cost_usd_minor"] is None, "purchasing is not landed/fulfillment cost"
assert a["warehouse_code"] == "us-moreplus" and a["erp_inventory_warehouse_code"] == "us-la"
assert a["country_allowlist"] == ["US"]
assert a["reported_packaged_weight_g"] == 10
assert a["net_liquid_ml"] == 35
assert a["weight_needs_remeasurement"] is True
assert a["verified_packed_weight_g"] is None
assert a["provider_verified_available_qty"] is None
assert a["provider_verified_at"] is None
assert a["approved_for_redeem"] is False
assert len(m["still_needed"]) > 0 and "at_least_one_more_distinct_approved_free_sku" in m["still_needed"]
# A 35 mL bottle with a purported 10 g total packed mass needs a real scale
# reading. This does not invent a conversion or an approved weight.
assert a["reported_packaged_weight_g"] < a["net_liquid_ml"] * 0.8
print("R8_OWNER_GIFT_A_SELECTION_PENDING_VERIFICATION_PASS=16")
