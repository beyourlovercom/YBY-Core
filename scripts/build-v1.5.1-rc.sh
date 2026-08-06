#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${root_dir}/releases/v1.5.1-rc"
stage_dir="$(mktemp -d)"
package_name="andy-core-v1.5.1-rc.zip"

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
  echo '# Andy Core v1.5.1 RC Build'
  echo
  echo "Source commit: $(git -C "${root_dir}" rev-parse HEAD)"
  echo 'Plugin version: 1.5.1-dev'
  echo 'Database migration target: 1.3.0'
  echo "Package: ${package_name}"
  echo "SHA-256: $(cat "${output_dir}/SHA256.txt")"
  echo 'Verification: normalized archive paths use /; the only top-level directory is yby-core/; directories are 0755 and files are 0644 before packaging.'
  echo 'This is a Dev/Owner UAT artifact only; it is not a release, tag, or deployment.'
} > "${output_dir}/BUILD_INFO.md"

zipinfo -1 "${output_dir}/${package_name}" | awk -F/ 'NF && $1 != "yby-core" { exit 1 }'
