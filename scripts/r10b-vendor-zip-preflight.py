#!/usr/bin/env python3
"""R10-B: strict local ZIP preflight for vendor-licensed test copies.

No network, WordPress side effects, plugin install, secrets, or archive extraction.
This script never considers a different plugin version equivalent.
"""
import argparse
import hashlib
import json
import os
import re
import sys
import zipfile
from pathlib import Path

REQUIRED = {
    "webtoffee": {
        "version": "3.2.0",
        "roots": {"wt-smart-coupon-pro", "wt-smart-coupon-for-woo-pro"},
        "names": {"smart coupons for woocommerce pro", "smart coupons for woocommerce"},
    },
    "storeapps": {
        "version": "9.51.0",
        "roots": {"woocommerce-smart-coupons"},
        "names": {"woocommerce smart coupons"},
    },
    "yaycurrency": {
        "version": "3.3.5",
        "roots": {"yaycurrency-pro", "yay-currency-pro"},
        "names": {"yaycurrency pro", "yaycurrency"},
    },
}
MAX_ZIP = 100 * 1024 * 1024
MAX_PLUGIN_FILE = 512 * 1024
VERSION = re.compile(r"(?im)^\s*\*?\s*Version:\s*([0-9][0-9A-Za-z.+_-]*)\s*$")
NAME = re.compile(r"(?im)^\s*\*?\s*Plugin Name:\s*(.+?)\s*$")

def scan(kind, path):
    expected = REQUIRED[kind]
    raw = Path(path).expanduser()
    if raw.is_symlink():
        raise ValueError("Symlink archive paths are not accepted")
    f = raw.resolve(strict=True)
    if not f.is_file() or f.stat().st_size > MAX_ZIP:
        raise ValueError("Not a permitted local ZIP file")
    digest = hashlib.sha256()
    with f.open("rb") as binary:
        for chunk in iter(lambda: binary.read(1024 * 1024), b""):
            digest.update(chunk)
    with zipfile.ZipFile(f) as archive:
        entries = archive.infolist()
        if not entries or len(entries) > 9000:
            raise ValueError("Unbounded or empty archive")
        for entry in entries:
            pieces = Path(entry.filename).parts
            if (entry.flag_bits & 1) or Path(entry.filename).is_absolute() or ".." in pieces:
                raise ValueError("Invalid or encrypted ZIP entry")
        main_headers = []
        for member in entries:
            parts = Path(member.filename).parts
            if len(parts) != 2 or not member.filename.lower().endswith(".php"):
                continue
            if parts[0].lower() not in expected["roots"] or member.file_size > MAX_PLUGIN_FILE:
                continue
            header = archive.read(member)[:10000].decode("utf-8", errors="replace")
            ver, name = VERSION.search(header), NAME.search(header)
            if name and ver:
                main_headers.append((member.filename, name.group(1).strip().lower(), ver.group(1)))
        matches = [x for x in main_headers if x[1] in expected["names"]]
        if len(matches) != 1:
            raise ValueError("Plugin identity missing or ambiguous")
        plugin_path, name, version = matches[0]
        if version != expected["version"]:
            raise ValueError(f"VERSION_MISMATCH actual={version}, required={expected['version']}")
        return {
            "plugin": kind, "name": name, "version": version,
            "plugin_main": plugin_path, "zip_sha256": digest.hexdigest(),
            "file_bytes": f.stat().st_size, "archive_entries": len(entries),
            "validated": True,
        }

def main():
    ap = argparse.ArgumentParser(description=__doc__)
    for k in REQUIRED:
        ap.add_argument(f"--{k}", required=True, help="local purchased ZIP, never a URL")
    args = vars(ap.parse_args())
    result = {"gate": "R10B_VENDOR_EXACT_VERSION", "status": "BLOCKED", "plugins": []}
    try:
        for kind in ("webtoffee", "storeapps", "yaycurrency"):
            result["plugins"].append(scan(kind, args[kind]))
        result["status"] = "READY_FOR_ISOLATED_INSTALL_NOT_PARITY_PASS"
    except (OSError, ValueError, zipfile.BadZipFile, RuntimeError) as exc:
        result["failure"] = f"{kind}: {str(exc)}"
    print(json.dumps(result, ensure_ascii=False, sort_keys=True, indent=2))
    return 0 if result["status"].startswith("READY_FOR_") else 2

if __name__ == "__main__":
    sys.exit(main())
