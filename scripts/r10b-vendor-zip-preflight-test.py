#!/usr/bin/env python3
"""Synthetic ZIP contract unit tests: never opens real vendor plugins or WordPress."""
import importlib.util
import tempfile
import zipfile
from pathlib import Path

here = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location("preflight", here / "r10b-vendor-zip-preflight.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

CASES = [
    ("webtoffee", "wt-smart-coupon-pro", "Smart Coupons for WooCommerce Pro", "3.2.0"),
    ("storeapps", "woocommerce-smart-coupons", "WooCommerce Smart Coupons", "9.51.0"),
    ("yaycurrency", "yaycurrency-pro", "YayCurrency Pro", "3.3.5"),
]
def archive(path, root, name, version, bad=False):
    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr(f"{root}/{root}.php", "<?php\n/**\nPlugin Name: "+name+"\nVersion: "+version+"\n*/\n")
        if bad:
            z.writestr("../evil.php", "<?php")

def expect_bad(fn, msg):
    try:
        fn()
    except (ValueError, OSError):
        return
    raise AssertionError("FAIL_CLOSED_MISSING: "+msg)

with tempfile.TemporaryDirectory(prefix="andy-r10b-fixture-") as d:
    for kind, root, name, version in CASES:
        p=Path(d)/f"{kind}.zip"
        archive(p,root,name,version)
        result=module.scan(kind,str(p))
        assert result["version"] == version and result["validated"], kind
        archive(p,root,name,"0.0.0")
        expect_bad(lambda:module.scan(kind,str(p)),kind+"_mismatched_version")
        archive(p,root,name,version,bad=True)
        expect_bad(lambda:module.scan(kind,str(p)),kind+"_path_escape")
    expect_bad(lambda:module.scan("storeapps", str(Path(d)/"not_present.zip")), "missing_zip")
print("R10B_VENDOR_ZIP_PREFLIGHT_TEST_PASS=10_EXACT_AND_FAIL_CLOSED_CHECKS")
