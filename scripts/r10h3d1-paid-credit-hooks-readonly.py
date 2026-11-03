#!/usr/bin/env python3
"""Read-only local contract probe for licensed vendor credit lifecycle.
Never loads WordPress, executes PHP, writes vendor files or creates orders.
Use ONLY with an authorized, disposable exact-version vendor plugin stack.
"""
from pathlib import Path
import argparse
import re
import sys

def read(root: Path, relative: str) -> str:
    path = (root / relative).resolve()
    if not path.is_relative_to(root.resolve()) or not path.is_file():
        raise FileNotFoundError(relative)
    return path.read_text(encoding="utf-8", errors="replace")

def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--plugins", required=True, type=Path)
    args = parser.parse_args()
    root = args.plugins.resolve()
    paths = {
        "webtoffee": "wt-smart-coupon-pro/public/modules/store_credit/classes/class-store-credit-apply.php",
        "storeapps": "woocommerce-smart-coupons/includes/class-wc-sc-coupon-process.php",
    }
    try:
        code = {name: read(root, rel) for name, rel in paths.items()}
    except (OSError, ValueError) as err:
        print(f"H3D1_SOURCE_UNAVAILABLE={type(err).__name__}")
        return 2

    # Presence is static source inventory only, NOT proof that hooks execute.
    expectations = {
        "WT_CLASSIC_ORDER_CREDIT_UPDATE":
            ("webtoffee", r"add_action\s*\(\s*['\"]woocommerce_new_order['\"]\s*,\s*array\(\$this,\s*['\"]update_credit_amount['\"]"),
        "WT_STORE_API_ORDER_CREDIT_UPDATE":
            ("webtoffee", r"add_action\s*\(\s*['\"]woocommerce_store_api_checkout_order_processed['\"]\s*,\s*array\(\s*\$this,\s*['\"]update_credit_amount['\"]"),
        "WT_PERSISTENT_COUPON_AMOUNT_WRITE":
            ("webtoffee", r"\$coupon->set_amount\("),
        "WT_PERSISTENT_COUPON_SAVE":
            ("webtoffee", r"\$coupon->save\("),
        "WT_ORDER_DEDUP_READ":
            ("webtoffee", r"get_credit_used_for_order\("),
        "WT_CREDIT_HISTORY_WRITE":
            ("webtoffee", r"add_credit_history\("),
        "SA_ORDER_PROCESSING_CREDIT_UPDATE":
            ("storeapps", r"woocommerce_order_status_pending_to_processing"),
        "SA_ORDER_COMPLETED_CREDIT_UPDATE":
            ("storeapps", r"woocommerce_order_status_pending_to_completed"),
        "SA_REFUND_CREDIT_RESTORE":
            ("storeapps", r"woocommerce_order_status_processing_to_refunded"),
        "SA_CREDIT_BALANCE_HANDLER":
            ("storeapps", r"update_smart_coupon_balance"),
    }
    failed = []
    for name, (vendor, pattern) in expectations.items():
        ok = re.search(pattern, code[vendor], re.MULTILINE) is not None
        print(f"H3D1_{name}={'PRESENT' if ok else 'MISSING'}")
        if not ok:
            failed.append(name)
    print(f"H3D1_STATIC_HOOK_INVENTORY={len(expectations)-len(failed)}/{len(expectations)}")
    print("H3D1_RUNTIME_REDEMPTION=NOT_TESTED")
    print("H3D1_CREDIT_ROLLBACK=NOT_TESTED")
    return 1 if failed else 0

if __name__ == "__main__":
    sys.exit(main())
