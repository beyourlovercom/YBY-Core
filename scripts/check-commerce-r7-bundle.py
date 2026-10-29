#!/usr/bin/env python3
"""R7 regression: fail closed on missing Currency R13 files in a Commerce ZIP.

Read-only build gate. Runs on a locally generated candidate only. Never touches a
WordPress installation, Dev credentials, payment providers, or remote hosts.
"""
from __future__ import annotations

import argparse
import sys
import zipfile

PREFIX = "andy-commerce/includes/"
REQUIRED_FILES = (
    "class-andy-commerce-currency-r13-dual-ledger.php",
    "class-andy-commerce-currency-r13-native-tax-shipping.php",
    "class-andy-commerce-currency-r13-settlement-audit.php",
    "class-andy-commerce-currency-r13-fx-quote-guard.php",
    "class-andy-commerce-currency-r13-dev-canary.php",
    "class-andy-commerce-currency-r13-stripe-refund-ledger.php",
    "class-andy-commerce-campaign-usd-basis-v1.php",
)
BOOTSTRAP = PREFIX + "class-andy-commerce.php"
CANARY_BOOT = "Andy_Commerce_Currency_R13_Dev_Canary::boot();"


def check_bundle(path: str) -> list[str]:
    problems: list[str] = []
    with zipfile.ZipFile(path) as z:
        members = set(z.namelist())
        if BOOTSTRAP not in members:
            return ["missing_bootstrap"]
        bootstrap = z.read(BOOTSTRAP).decode("utf-8")
        for name in REQUIRED_FILES:
            full_path = PREFIX + name
            if full_path not in members:
                problems.append("missing_file:" + name)
                continue
            blob = z.read(full_path)
            if not blob.strip().startswith(b"<?php"):
                problems.append("empty_or_invalid_php:" + name)
            needle = "require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/" + name + "';"
            if bootstrap.count(needle) != 1:
                problems.append("missing_or_duplicate_bootstrap_require:" + name)
        if bootstrap.count(CANARY_BOOT) != 1:
            problems.append("missing_or_duplicate_canary_boot")
    return problems


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("zip", help="local Commerce release ZIP, NOT a live plugin")
    args = parser.parse_args()
    try:
        problems = check_bundle(args.zip)
    except (zipfile.BadZipFile, OSError, UnicodeDecodeError) as exc:
        print("R7_COMMERCE_BUNDLE_FAIL=" + type(exc).__name__, file=sys.stderr)
        return 2
    if problems:
        for p in problems:
            print("R7_COMMERCE_BUNDLE_FAIL=" + p, file=sys.stderr)
        return 1
    print("R7_COMMERCE_BUNDLE_CONTRACT_PASS=7_FILES_7_REQUIRES_CANARY_BOOT")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
