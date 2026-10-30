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
assert len(m["owner_selected_free_gifts"]) == 2
assert len({x["product_id"] for x in m["owner_selected_free_gifts"]}) == 2
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
assert len(m["still_needed"]) > 0 and "confirm_VX609A_to_609A_ERP_warehouse_SKU_and_current_provider_stock" in m["still_needed"]
b = m["owner_selected_free_gifts"][1]
assert b["product_id"] == 25045 and b["sku"] == "609A" and b["owner_requested_sku"] == "VX609A"
assert b["product"] == "ROSEY" and b["upgrade_minor"] == 0
assert b["sku_alias_mapping_status"] == "PENDING_WAREHOUSE_CONFIRMATION"
assert b["erp_product_id_candidate"] == 1333
assert b["historic_source_cost_cny"] == "26.1012"
assert b["purchase_cost_usd_minor"] is None and b["landed_cost_usd_minor"] is None
assert b["warehouse_code"] is None and b["erp_inventory_warehouse_code"] is None
assert b["country_allowlist"] == []
assert b["reported_packaged_weight_g"] is None and b["verified_packed_weight_g"] is None
assert b["provider_verified_available_qty"] is None and b["provider_verified_at"] is None
assert b["reviewed_by"] is None and b["approved_for_redeem"] is False
# A 35 mL bottle with a purported 10 g total packed mass needs a real scale
# reading. This does not invent a conversion or an approved weight.
assert a["reported_packaged_weight_g"] < a["net_liquid_ml"] * 0.8
print("R8_OWNER_GIFT_A_SELECTION_PENDING_VERIFICATION_PASS=16")
print("R8_OWNER_GIFT_B_SELECTION_PENDING_VERIFICATION_PASS=12")
