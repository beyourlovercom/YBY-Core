#!/usr/bin/env python3
"""R7 read-only release and installed-file parity audit for Commerce R13.

Never changes WordPress, credentials, payment providers, or hosts. The installed
folder, if supplied, is examined read-only against the exact reviewed ZIP.
"""
from __future__ import annotations

import argparse
import sys
import zipfile
from pathlib import Path

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


def check_bundle(
    path: str,
    source_root: str | None = None,
    installed_root: str | None = None,
) -> list[str]:
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
        # Prevent a correctly named, but stale/modified class from being
        # substituted while the main Commerce release ZIP is being built.
        # Source must be the exact reviewed checkout. No network calls.
        if source_root:
            root = Path(source_root)
            for member in (BOOTSTRAP, *(PREFIX + n for n in REQUIRED_FILES)):
                source = root / member.removeprefix("andy-commerce/")
                if not source.is_file():
                    problems.append("source_file_missing:" + member)
                elif member in members and z.read(member) != source.read_bytes():
                    problems.append("source_payload_mismatch:" + member)
        # A valid ZIP does not prove the running plugin still has those files.
        # Post-install proof is read-only and cannot activate or patch Woo.
        if installed_root:
            root = Path(installed_root)
            if not root.is_dir():
                problems.append("installed_plugin_dir_missing")
            else:
                for member in (BOOTSTRAP, *(PREFIX + n for n in REQUIRED_FILES)):
                    target = root / member.removeprefix("andy-commerce/")
                    if not target.is_file():
                        problems.append("installed_file_missing:" + member)
                    elif member in members and target.read_bytes() != z.read(member):
                        problems.append("installed_payload_mismatch:" + member)
    return problems


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("zip", help="local Commerce release ZIP, NOT a live plugin")
    parser.add_argument("--source-root", help="exact reviewed packages/andy-commerce source root")
    parser.add_argument("--installed-root", help="read-only installed Commerce plugin root to verify")
    args = parser.parse_args()
    try:
        problems = check_bundle(args.zip, args.source_root, args.installed_root)
    except (zipfile.BadZipFile, OSError, UnicodeDecodeError) as exc:
        print("R7_COMMERCE_BUNDLE_FAIL=" + type(exc).__name__, file=sys.stderr)
        return 2
    if problems:
        for p in problems:
            print("R7_COMMERCE_BUNDLE_FAIL=" + p, file=sys.stderr)
        return 1
    print("R7_COMMERCE_BUNDLE_CONTRACT_PASS=7_FILES_7_REQUIRES_CANARY_BOOT"
          + ("_AND_SOURCE_PARITY" if args.source_root else "")
          + ("_AND_INSTALLED_PARITY" if args.installed_root else ""))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
