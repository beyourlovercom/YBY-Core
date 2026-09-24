#!/usr/bin/env bash
set -euo pipefail

script_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
root_dir="${RELEASE_SOURCE_DIR:-${script_root}}"
version="1.9.0"
database_version="1.4.0"
runtime_database_version="1.5.0"
signing_public_key_b64="LcPq5x+fNa96aC+cXyC1ZbwRoGXCihF9YSG+iMHtjKU="
signing_secret="${ANDY_CORE_UPDATE_SIGNING_SECRET:-}"
output_dir="${RELEASE_OUTPUT_DIR:-${root_dir}/releases/v${version}}"
package_name="andy-core-v${version}.zip"
source_ref="${SOURCE_BRANCH:-${GITHUB_HEAD_REF:-$(git -C "${script_root}" branch --show-current)}}"
source_commit="${SOURCE_COMMIT:-$(git -C "${script_root}" rev-parse HEAD)}"
build_context="${BUILD_CONTEXT:-RELEASE_CANDIDATE}"
php_bin="${PHP_BIN:-php}"
repository_remote="$(git -C "${script_root}" remote get-url origin 2>/dev/null || echo UNKNOWN)"
build_date_utc="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"

case "${build_context}" in
  RELEASE_CANDIDATE|FINAL_RELEASE) ;;
  *) echo "Unsupported BUILD_CONTEXT: ${build_context}" >&2; exit 1 ;;
esac
if [[ "${build_context}" == "FINAL_RELEASE" && -z "${signing_secret}" ]]; then echo "Missing ANDY_CORE_UPDATE_SIGNING_SECRET for FINAL_RELEASE" >&2; exit 1; fi

grep -Fq "Version:           ${version}" "${root_dir}/yby-core.php"
grep -Fq "define( 'YBY_CORE_VERSION', '${version}' );" "${root_dir}/yby-core.php"
grep -Fq "define( 'YBY_DATABASE_VERSION', '${database_version}' );" "${root_dir}/yby-core.php"
grep -Fq "define( 'YBY_RUNTIME_DATABASE_VERSION', '${runtime_database_version}' );" "${root_dir}/yby-core.php"
grep -Fq "Stable tag: ${version}" "${root_dir}/readme.txt"

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

git -C "${script_root}" diff --check
git_diff_check_status='PASS'
file_count="$(find "${stage_dir}/yby-core" -type f | wc -l | tr -d ' ')"

rm -f "${output_dir}/${package_name}" "${output_dir}/SHA256.txt" "${output_dir}/update-metadata.json" "${output_dir}/update-metadata.sig" "${output_dir}/BUILD_INFO.md"
if command -v zip >/dev/null 2>&1; then
  ( cd "${stage_dir}" && zip -X -r "${output_dir}/${package_name}" yby-core >/dev/null )
else
  "${PYTHON_BIN:-python}" - "${stage_dir}" "${output_dir}/${package_name}" <<'PYZIP'
import os, sys, zipfile
stage, output = sys.argv[1], sys.argv[2]
root = os.path.join(stage, 'yby-core')
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as z:
    for base, dirs, files in os.walk(root):
        dirs.sort(); files.sort()
        for name in files:
            path = os.path.join(base, name)
            arc = os.path.relpath(path, stage).replace(os.sep, '/')
            z.write(path, arc)
PYZIP
fi

sha256="$(sha256sum "${output_dir}/${package_name}" | awk '{print $1}')"
printf '%s  %s\n' "${sha256}" "${package_name}" > "${output_dir}/SHA256.txt"
printf '{\n  "schema_version": 1,\n  "version": "%s",\n  "database_version": "%s",\n  "runtime_database_version": "%s",\n  "package": "%s",\n  "sha256": "%s"\n}\n' \
  "${version}" "${database_version}" "${runtime_database_version}" "${package_name}" "${sha256}" > "${output_dir}/update-metadata.json"

signature_status='NOT_SIGNED'
if [[ -n "${signing_secret}" ]]; then
  ANDY_CORE_UPDATE_SIGNING_SECRET="${signing_secret}" "${php_bin}" "${root_dir}/scripts/sign-update-metadata.php" "${output_dir}/update-metadata.json" "${output_dir}/update-metadata.sig" "${signing_public_key_b64}"
  signature_status='PASS'
fi
if [[ "${build_context}" == "FINAL_RELEASE" && "${signature_status}" != "PASS" ]]; then echo 'Release signature generation failed' >&2; exit 1; fi

{
  echo "# Andy Core v${version} Build"
  echo
  echo 'Product: Andy Core'
  echo "Version: ${version}"
  echo "Updater Compatibility Database Version: ${database_version}"
  echo "Runtime Database Version: ${runtime_database_version}"
  echo 'Release Stage: Stable'
  echo "Build Context: ${build_context}"
  echo "Repository Remote: ${repository_remote}"
  echo "Source Ref: ${source_ref}"
  echo "Source Commit: ${source_commit}"
  echo "Build Date UTC: ${build_date_utc}"
  echo "Package: ${package_name}"
  echo "File Count: ${file_count}"
  echo "SHA-256: ${sha256}"
  echo "PHP Lint: ${php_lint_status}"
  echo "git diff --check: ${git_diff_check_status}"
  echo 'Updater Metadata: update-metadata.json schema_version=1'
  echo "Ed25519 Signature: ${signature_status}"
  echo 'Verification: normalized archive paths use /; only yby-core/ is the top-level directory; development-only paths are excluded.'
} > "${output_dir}/BUILD_INFO.md"

zipinfo -1 "${output_dir}/${package_name}" | awk -F/ 'NF && $1 != "yby-core" { exit 1 }'
zipinfo -1 "${output_dir}/${package_name}" | grep -Fx 'yby-core/yby-core.php' >/dev/null
! zipinfo -1 "${output_dir}/${package_name}" | grep -E '(^|/)(\.git|\.github|tests|docs|releases|scripts)(/|$)' >/dev/null
unzip -p "${output_dir}/${package_name}" yby-core/yby-core.php | grep -F "Version:           ${version}" >/dev/null
unzip -p "${output_dir}/${package_name}" yby-core/yby-core.php | grep -F "define( 'YBY_CORE_VERSION', '${version}' );" >/dev/null
unzip -p "${output_dir}/${package_name}" yby-core/yby-core.php | grep -F "define( 'YBY_DATABASE_VERSION', '${database_version}' );" >/dev/null
unzip -p "${output_dir}/${package_name}" yby-core/yby-core.php | grep -F "define( 'YBY_RUNTIME_DATABASE_VERSION', '${runtime_database_version}' );" >/dev/null
