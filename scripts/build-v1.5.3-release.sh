#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${RELEASE_OUTPUT_DIR:-${root_dir}/releases/v1.5.3}"
package_name="andy-core-v1.5.3.zip"
source_ref="${SOURCE_BRANCH:-${GITHUB_HEAD_REF:-$(git -C "${root_dir}" branch --show-current)}}"
source_commit="${SOURCE_COMMIT:-$(git -C "${root_dir}" rev-parse HEAD)}"
build_context="${BUILD_CONTEXT:-RELEASE_CANDIDATE}"
php_bin="${PHP_BIN:-php}"
repository_remote="$(git -C "${root_dir}" remote get-url origin 2>/dev/null || echo UNKNOWN)"
build_date_utc="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"

case "${build_context}" in
  RELEASE_CANDIDATE)
    context_note='This package is release-candidate evidence only and is not the final GitHub Release artifact.'
    ;;
  FINAL_RELEASE)
    context_note='This package was regenerated from the final tagged commit for Andy Core v1.5.3.'
    ;;
  *)
    echo "Unsupported BUILD_CONTEXT: ${build_context}" >&2
    echo 'Allowed values: RELEASE_CANDIDATE, FINAL_RELEASE' >&2
    exit 1
    ;;
esac

metadata_error=0
if ! grep -Fq 'Version:           1.5.3' "${root_dir}/yby-core.php"; then
  echo "Release metadata mismatch: yby-core.php must contain 'Version:           1.5.3'." >&2
  metadata_error=1
fi
if ! grep -Fq "define( 'YBY_CORE_VERSION', '1.5.3' );" "${root_dir}/yby-core.php"; then
  echo "Release metadata mismatch: yby-core.php must contain YBY_CORE_VERSION 1.5.3." >&2
  metadata_error=1
fi
if ! grep -Fq "define( 'YBY_DATABASE_VERSION', '1.4.0' );" "${root_dir}/yby-core.php"; then
  echo "Release metadata mismatch: yby-core.php must contain YBY_DATABASE_VERSION 1.4.0." >&2
  metadata_error=1
fi
if ! grep -Fq 'Stable tag: 1.5.3' "${root_dir}/readme.txt"; then
  echo "Release metadata mismatch: readme.txt must contain 'Stable tag: 1.5.3'." >&2
  metadata_error=1
fi
if (( metadata_error != 0 )); then
  echo 'Release build aborted before staging: required source metadata did not match.' >&2
  exit 1
fi

stage_dir="$(mktemp -d)"

cleanup() { rm -rf "${stage_dir}"; }
trap cleanup EXIT

mkdir -p "${stage_dir}/yby-core" "${output_dir}"

for item in yby-core.php readme.txt README.md CHANGELOG.md VERSION.md uninstall.php; do
  cp "${root_dir}/${item}" "${stage_dir}/yby-core/"
done

for directory in admin assets inc languages modules public templates; do
  cp -R "${root_dir}/${directory}" "${stage_dir}/yby-core/"
done

find "${stage_dir}/yby-core" -type d -exec chmod 0755 {} +
find "${stage_dir}/yby-core" -type f -exec chmod 0644 {} +

"${php_bin}" -v >/dev/null
while IFS= read -r -d '' file; do
  "${php_bin}" -l "${file}" >/dev/null
done < <(find "${stage_dir}/yby-core" -name '*.php' -print0)
php_lint_status='PASS'

git -C "${root_dir}" diff --check
git_diff_check_status='PASS'
file_count="$(find "${stage_dir}/yby-core" -type f | wc -l | tr -d ' ')"

rm -f "${output_dir}/${package_name}"
( cd "${stage_dir}" && zip -X -r "${output_dir}/${package_name}" yby-core >/dev/null )

sha256sum "${output_dir}/${package_name}" | awk '{print $1}' > "${output_dir}/SHA256.txt"
{
  echo '# Andy Core v1.5.3 Build'
  echo
  echo 'Product: Andy Core'
  echo 'Version: 1.5.3'
  echo 'Database Version: 1.4.0'
  echo 'Release Stage: Stable'
  echo 'Build Type: RELEASE'
  echo "Build Context: ${build_context}"
  echo "Repository Remote: ${repository_remote}"
  echo "Source Ref: ${source_ref}"
  echo "Source Commit: ${source_commit}"
  echo "Build Date UTC: ${build_date_utc}"
  echo "Package: ${package_name}"
  echo "File Count: ${file_count}"
  echo "SHA-256: $(cat "${output_dir}/SHA256.txt")"
  echo "PHP Lint: ${php_lint_status}"
  echo "git diff --check: ${git_diff_check_status}"
  echo 'Verification: normalized archive paths use /; the only top-level directory is yby-core/; directories are 0755 and files are 0644 before packaging.'
  echo "${context_note}"
} > "${output_dir}/BUILD_INFO.md"

zipinfo -1 "${output_dir}/${package_name}" | awk -F/ 'NF && $1 != "yby-core" { exit 1 }'
zipinfo -1 "${output_dir}/${package_name}" | grep -Fx 'yby-core/yby-core.php' >/dev/null
if zipinfo -1 "${output_dir}/${package_name}" | grep -E '(^|/)(\.git|\.github|tests|docs|releases)(/|$)' >/dev/null; then
  echo 'Release package contains excluded development paths.' >&2
  exit 1
fi
