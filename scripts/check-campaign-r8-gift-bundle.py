#!/usr/bin/env python3
"""Read-only guard: BF Campaign installable ZIP includes exact reviewed R8 safety guard."""
from __future__ import annotations

import argparse
from pathlib import Path
import sys
from zipfile import ZipFile, BadZipFile

PREFIX = "black-friday-2026/"
BOOT = PREFIX + "black-friday-2026.php"
FILES = (
    PREFIX + "includes/class-byl-bf2026-r8-readiness.php",
    PREFIX + "includes/class-byl-bf2026-gifts.php",
)
SOURCE_RELATIVE = {
    BOOT: "black-friday-2026.php",
    FILES[0]: "includes/class-byl-bf2026-r8-readiness.php",
    FILES[1]: "includes/class-byl-bf2026-gifts.php",
}
REQUIRES = (
    "require_once __DIR__ . '/includes/class-byl-bf2026-r8-readiness.php';",
    "require_once __DIR__ . '/includes/class-byl-bf2026-gifts.php';",
)


def check(zip_path: str, source_root: str | None = None) -> list[str]:
    failures: list[str] = []
    with ZipFile(zip_path) as z:
        paths = z.namelist()
        names = set(paths)
        for name in SOURCE_RELATIVE:
            if name not in names:
                failures.append("missing_file:" + name)
            if paths.count(name) != 1:
                failures.append("duplicate_or_missing_zip_member:" + name)
        if BOOT not in names:
            return failures
        bootstrap = z.read(BOOT).decode("utf-8")
        for line in REQUIRES:
            if bootstrap.count(line) != 1:
                failures.append("missing_or_duplicate_required_class:" + line)
        if "array( 'BYL_BF2026_Gifts', 'ready' )" not in bootstrap:
            failures.append("missing_gift_readiness_registration")
        if source_root:
            for name, relative in SOURCE_RELATIVE.items():
                file = Path(source_root) / relative
                if not file.is_file():
                    failures.append("source_missing:" + relative)
                elif name in names and z.read(name) != file.read_bytes():
                    failures.append("source_mismatch:" + name)
    return failures


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("zip", help="local Black Friday preview ZIP; never a live plugin")
    p.add_argument("--source-root", help="reviewed packages/andy-campaign-packs/black-friday-2026")
    args = p.parse_args()
    try:
        errors = check(args.zip, args.source_root)
    except (OSError, BadZipFile, UnicodeDecodeError) as exc:
        print("R8_GIFT_BUNDLE_FAIL=" + type(exc).__name__, file=sys.stderr)
        return 2
    if errors:
        for e in errors:
            print("R8_GIFT_BUNDLE_FAIL=" + e, file=sys.stderr)
        return 1
    print("R8_GIFT_BUNDLE_PASS=3_FILES_2_REQUIRES"
          + ("_SOURCE_PARITY" if args.source_root else ""))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
