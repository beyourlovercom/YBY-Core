#!/usr/bin/env bash
set -euo pipefail
repo="$(cd "$(dirname "$0")/.." && pwd)"
dest="$1";sha="$(git -C "$repo" rev-parse HEAD)"
mkdir -p "$(dirname "$dest")"
tmp="$(mktemp -d)";trap 'rm -rf "$tmp"' EXIT
mkdir "$tmp/andy-commerce"
rsync -a --exclude tests/ --exclude AGENTS.md "$repo/packages/andy-commerce/" "$tmp/andy-commerce/"
(cd "$tmp" && zip -qr "$dest" andy-commerce)
bash "$repo/scripts/check-andy-commerce-zip-contract.sh" "$dest" "$sha"
