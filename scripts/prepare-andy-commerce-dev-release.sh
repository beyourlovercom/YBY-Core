#!/usr/bin/env bash
# Sole release preparer for the integrated Dev-only Andy Commerce plugin.
# Deliberately NO WordPress install, Woo mutations, uploads or deployment here.
set -euo pipefail
repo="$(cd "$(dirname "$0")/.." && pwd)"
if [[ "$#" -ne 1 || -z "${1:-}" ]]; then
  echo "USAGE: $0 /absolute/path/andy-commerce-dev.zip" >&2; exit 2
fi
dest="$1"
case "$dest" in /*) ;; *) echo "RELEASE_DENIED: absolute destination required" >&2; exit 2;; esac
canonical="feat/marketing-m10-policy-m9-bridge-dev-20261023"
currency="feat/currency-c2-c3-shadow-preview-20261023"
cd "$repo"
source_url="https://github.com/beyourlovercom/YBY-Core.git"
head="$(git rev-parse HEAD)"
if [[ -n "$(git status --porcelain)" ]]; then
  echo "RELEASE_DENIED: dirty checkout (commit and review exact source first)" >&2; exit 3
fi
release_head="$(git ls-remote --exit-code "$source_url" "refs/heads/$canonical" | awk '{print $1}')"
if [[ -z "$release_head" || "$head" != "$release_head" ]]; then
  echo "RELEASE_DENIED: only canonical PR #213 exact remote HEAD may prepare Dev release" >&2; exit 3
fi
currency_head="$(git ls-remote --exit-code "$source_url" "refs/heads/$currency" | awk '{print $1}')"
if [[ -z "$currency_head" ]]; then
  echo "RELEASE_DENIED: cannot verify latest PR #207 Currency HEAD" >&2; exit 3
fi
git fetch -q --no-tags "$source_url" "$currency" >/dev/null
currency_paths=(
  packages/andy-commerce/admin/views/currency-c2c3-readonly-panel.php
  packages/andy-commerce/assets/currency-c3-dev-preview.js
  packages/andy-commerce/includes/class-andy-commerce-c3-cart-snapshot-adapter.php
  packages/andy-commerce/includes/class-andy-commerce-currency-c2c3-preview.php
  packages/andy-commerce/tests/currency-c2c3-reconcile-harness.php
  packages/andy-commerce/tests/currency-c3-cart-snapshot-harness.php
  packages/andy-commerce/tests/currency-c3-coupon-boundary-harness.php
  packages/andy-commerce/tests/currency-c3-dev-browser-guard-harness.php
  packages/andy-commerce/tests/currency-c3-dev-zone-evidence-harness.php
)
for path in "${currency_paths[@]}"; do
  if [[ ! -f "$path" ]]; then
    echo "RELEASE_DENIED: PR #207 Currency content not integrated: $path" >&2; exit 4
  fi
  upstream="$(git show "$currency_head:$path" | shasum -a 256 | awk '{print $1}')"
  local_hash="$(shasum -a 256 "$path" | awk '{print $1}')"
  if [[ "$upstream" != "$local_hash" ]]; then
    echo "RELEASE_DENIED: PR #207 Currency file differs; explicitly reconcile $path" >&2; exit 4
  fi
done
# R8/R9 intentionally extend these TWO PR #207 sources, so byte-for-byte
# equality with the frozen historical PR is no longer a valid integration gate.
# All other Currency PR #207 files remain exact-hash protected above.
# Validate the accepted V2 tab/shortcode contract and scoped new entry points.
grep -Fq "'fx'=>'汇率核算'" packages/andy-commerce/admin/views/currency-os-v2-admin.php ||
  { echo "RELEASE_DENIED: Currency V2 admin FX tab lost" >&2; exit 4; }
grep -Fq 'Andy_Commerce_Currency_Runtime_Dev_V1::settings' packages/andy-commerce/admin/views/currency-os-v2-admin.php ||
  { echo "RELEASE_DENIED: R8 admin runtime status lost" >&2; exit 4; }
grep -Fq "add_shortcode('andy_currency_switcher'" packages/andy-commerce/includes/class-andy-commerce-currency-v2-dev.php ||
  { echo "RELEASE_DENIED: Currency shortcode lost" >&2; exit 4; }
grep -Fq 'Andy_Commerce_Currency_Runtime_Dev_V1::shortcode()' packages/andy-commerce/includes/class-andy-commerce-currency-v2-dev.php ||
  { echo "RELEASE_DENIED: R9 shopper currency form lost" >&2; exit 4; }
php -l packages/andy-commerce/includes/class-andy-commerce-currency-runtime-dev-v1.php >/dev/null
php -l packages/andy-commerce/admin/views/currency-os-v2-admin.php >/dev/null
php -l packages/andy-commerce/includes/class-andy-commerce-currency-v2-dev.php >/dev/null
# The controlled Core L3 registry is frozen; Commerce may change its own adapter.
base="38e651d6567721900e80eaedc8311148af882368"
core_changes="$(git diff --name-only "$base" HEAD -- admin/ inc/ modules/ assets/js/andy-core-native-l3-v2.js)"
if [[ -n "$core_changes" ]]; then
  echo "RELEASE_DENIED: frozen Andy Core files changed; review independently" >&2
  echo "$core_changes" >&2; exit 4
fi
for harness in \
  currency-os-v2-admin-shortcode-harness \
  currency-c2c3-reconcile-harness \
  currency-c3-cart-snapshot-harness \
  currency-c3-coupon-boundary-harness \
  currency-c3-dev-browser-guard-harness \
  currency-c3-dev-zone-evidence-harness \
  marketing-v2-visual-rule-harness \
  marketing-m7-draft-security-harness \
  marketing-m8-engine-harness \
  marketing-m9-usd-multirule-harness \
  marketing-m9-dev-page-render-harness \
  marketing-m10-rules-v1-harness \
  marketing-m10-policy-fields-harness \
  marketing-m10-policy-evaluator-harness \
  marketing-m10-six-type-matrix-harness \
  marketing-m10-lifecycle-harness \
  marketing-m10-page-render-harness \
  marketing-p1p2-revocation-harness \
  marketing-integrator-dev-uat-gate-harness
do
  php "$repo/packages/andy-commerce/tests/$harness.php" >/dev/null
done
bash "$repo/scripts/build-andy-commerce-dev-candidate.sh" "$dest"
# R7 release invariant: successful tests are insufficient if a packaging or
# overwrite step silently removes R13/verified USD basis source files.
# This gate is read-only; it never installs or changes Dev, Woo or Stripe.
if ! python3 "$repo/scripts/check-commerce-r7-bundle.py" "$dest" --source-root "$repo/packages/andy-commerce"; then
  echo "RELEASE_DENIED: R13/Commerce USD basis payload or bootstrap missing" >&2
  rm -f "$dest"
  exit 5
fi
zip_sha="$(shasum -a 256 "$dest" | awk '{print $1}')"
controller_sha="$(unzip -p "$dest" andy-commerce/admin/class-andy-commerce-admin.php | shasum -a 256 | awk '{print $1}')"
printf 'mode=DEV_ONLY_INSTALL_PREPARED\nsource_HEAD=%s\nCurrency_PR207_HEAD=%s\nzip_SHA256=%s\ncontroller_SHA256=%s\nR7_R13_Bundle_Contract=PASS\nDev_Installed=NOT_VERIFIED\n' \
 "$head" "$currency_head" "$zip_sha" "$controller_sha" > "$dest.attestation.txt"
echo "DEV_RELEASE_PREPARED head=$head currency=$currency_head sha256=$zip_sha"
echo "INSTALL_AND_UAT_STILL_REQUIRED: Dev upload and readback are separate authorized actions"
