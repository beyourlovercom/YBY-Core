#!/usr/bin/env bash
# Strict R10B no-write WP preflight. Must only run against disposable Woo.
set -euo pipefail
if [[ $# -ne 1 ]]; then
  echo "USAGE: $0 /absolute/path/to/disposable-woocommerce" >&2
  exit 2
fi
ROOT="$(cd "$1" && pwd -P)"
[[ -f "$ROOT/.andy-r10b-isolated-local" ]] || { echo "STOP: isolation marker absent" >&2; exit 2; }
[[ -f "$ROOT/wp-config.php" ]] || { echo "STOP: WordPress config absent" >&2; exit 2; }
case "$ROOT" in
  */public_html*|*/htdocs/www*|*/wp-content/plugins*) echo "STOP: potentially hosted WordPress" >&2; exit 2 ;;
esac
command -v wp >/dev/null || { echo "STOP: wp-cli missing" >&2; exit 2; }
BASE="$(cd "$(dirname "$0")/.." && pwd -P)"
test -f "$BASE/packages/andy-commerce/tests/sandbox/r10b-vendor-runtime-readonly.php"
wp eval-file "$BASE/packages/andy-commerce/tests/sandbox/r10b-vendor-runtime-readonly.php" \
  --path="$ROOT" --skip-themes
