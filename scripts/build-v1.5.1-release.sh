#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${RELEASE_OUTPUT_DIR:-${root_dir}/releases/v1.5.1}"
stage_dir="$(mktemp -d)"
package_name="andy-core-v1.5.1.zip"
source_branch="${SOURCE_BRANCH:-${GITHUB_HEAD_REF:-$(git -C "${root_dir}" branch --show-current)}}"
source_commit="${SOURCE_COMMIT:-$(git -C "${root_dir}" rev-parse HEAD)}"
build_context="${BUILD_CONTEXT:-RELEASE_CANDIDATE}"

case "${build_context}" in
  RELEASE_CANDIDATE)
    context_note='This package is release-candidate evidence only and is not the final GitHub Release artifact.'
    ;;
  FINAL_RELEASE)
    context_note='This package was regenerated from the final merged main commit for Andy Core v1.5.1 release.'
    ;;
  *)
    echo "Unsupported BUILD_CONTEXT: ${build_context}" >&2
    echo 'Allowed values: RELEASE_CANDIDATE, FINAL_RELEASE' >&2
    exit 1
    ;;
esac

cleanup() {
  rm -rf "${stage_dir}"
}
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

rm -f "${output_dir}/${package_name}"
( cd "${stage_dir}" && zip -X -r "${output_dir}/${package_name}" yby-core >/dev/null )

sha256sum "${output_dir}/${package_name}" | awk '{print $1}' > "${output_dir}/SHA256.txt"
{
  echo '# Andy Core v1.5.1 Build'
  echo
  echo 'Product: Andy Core'
  echo 'Version: 1.5.1'
  echo 'Database Version: 1.3.0'
  echo 'Build Type: RELEASE'
  echo "Build Context: ${build_context}"
  echo "Source Branch: ${source_branch}"
  echo "Source Commit: ${source_commit}"
  echo "Package: ${package_name}"
  echo "SHA-256: $(cat "${output_dir}/SHA256.txt")"
  echo 'Verification: normalized archive paths use /; the only top-level directory is yby-core/; directories are 0755 and files are 0644 before packaging.'
  echo "${context_note}"
} > "${output_dir}/BUILD_INFO.md"

zipinfo -1 "${output_dir}/${package_name}" | awk -F/ 'NF && $1 != "yby-core" { exit 1 }'
zipinfo -1 "${output_dir}/${package_name}" | grep -Fx 'yby-core/yby-core.php' >/dev/null
if zipinfo -1 "${output_dir}/${package_name}" | grep -E '(^|/)(\.git|\.github|tests|docs|releases)(/|$)' >/dev/null; then
  echo 'Release package contains excluded development paths.' >&2
  exit 1
fi
