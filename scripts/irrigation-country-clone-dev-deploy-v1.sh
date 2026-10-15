#!/usr/bin/env bash
# Dev-only allowlisted Country Clone V1 patch. No Production/Ads changes.
set -euo pipefail
umask 077
BASE="$HOME/.byl-uat/germany-clone-v1"
STAGE="$BASE/stage"
ROOT="$HOME/domains/dev.ybyirrigation.com/public_html"
PLUGIN="$ROOT/wp-content/plugins/yby-core"
BACKUP="$BASE/runtime-before"
fail() { printf 'CLONE_DEV_DEPLOY_BLOCKED=%s\n' "$1" >&2; exit 2; }
hash_of() { sha256sum "$1" | cut -d ' ' -f1; }

test -d "$ROOT" && test -d "$PLUGIN" && test -d "$STAGE" && test -d "$BACKUP" || fail "missing_verified_dev_paths"
test "$(wp --path="$ROOT" option get siteurl)" = "https://dev.ybyirrigation.com" || fail "wrong_siteurl"
test "$(wp --path="$ROOT" config get WP_ENVIRONMENT_TYPE --type=constant)" = "development" || fail "not_development"
# Fail closed if parallel changes touched the three original Dev files.
test "$(hash_of "$PLUGIN/inc/class-yby-core.php")" = "623a75f82c176e1b937827b4c8e46fd0f881486fe66918a784f63cfcf7084141" || fail "dev_core_drift"
test "$(hash_of "$PLUGIN/admin/class-yby-project-studio.php")" = "725abef1e70d5594239239587eee49d6ea1cc9abe67f8e2ea07a396fd6e7f5d9" || fail "dev_studio_drift"
test "$(hash_of "$PLUGIN/admin/views/project-list.php")" = "ba23f128cff7c58d13b84bebcf863389cb773acec867fca7c616f6eb68ae9c0f" || fail "dev_project_list_drift"
test -f "$BACKUP/inc/class-yby-core.php" || fail "core_rollback_missing"
test -f "$BACKUP/admin/class-yby-project-studio.php" || fail "studio_rollback_missing"
test -f "$BACKUP/admin/views/project-list.php" || fail "list_rollback_missing"

NEW_FILES="inc/class-yby-country-transform-registry.php inc/class-yby-country-clone-preflight.php inc/class-yby-germany-clone-transform.php inc/class-yby-germany-clone-bricks-signer.php inc/class-yby-country-clone-draft-service.php admin/views/project-country-clone.php"
CHANGED_FILES="inc/class-yby-core.php admin/class-yby-project-studio.php admin/views/project-list.php"
for relative in $NEW_FILES; do
  test -f "$STAGE/$relative" && test ! -e "$PLUGIN/$relative" || fail "new_file_preexists_$relative"
done
for relative in $CHANGED_FILES; do test -f "$STAGE/$relative" || fail "missing_staged_$relative"; done
for relative in $NEW_FILES $CHANGED_FILES; do
  php -l "$STAGE/$relative" >/dev/null || fail "php_lint_$relative"
done

rollback() {
  set +e
  cp -p "$BACKUP/inc/class-yby-core.php" "$PLUGIN/inc/class-yby-core.php"
  cp -p "$BACKUP/admin/class-yby-project-studio.php" "$PLUGIN/admin/class-yby-project-studio.php"
  cp -p "$BACKUP/admin/views/project-list.php" "$PLUGIN/admin/views/project-list.php"
  for relative in $NEW_FILES; do rm -f "$PLUGIN/$relative"; done
  printf 'CLONE_DEV_DEPLOY_ROLLED_BACK=YES\n' >&2
}
trap rollback ERR

# Add dependencies first, then switch the bootstrap last.
for relative in $NEW_FILES; do
  cp "$STAGE/$relative" "$PLUGIN/$relative"
  chmod 0644 "$PLUGIN/$relative"
done
cp "$STAGE/admin/views/project-list.php" "$PLUGIN/admin/views/project-list.php"
cp "$STAGE/admin/class-yby-project-studio.php" "$PLUGIN/admin/class-yby-project-studio.php"
cp "$STAGE/inc/class-yby-core.php" "$PLUGIN/inc/class-yby-core.php"
chmod 0644 "$PLUGIN/admin/views/project-list.php" "$PLUGIN/admin/class-yby-project-studio.php" "$PLUGIN/inc/class-yby-core.php"

# WP bootstrap and plugin version must both remain intact.
test "$(wp --path="$ROOT" option get siteurl)" = "https://dev.ybyirrigation.com"
test "$(wp --path="$ROOT" plugin get yby-core --field=version)" = "1.8.1"
wp --path="$ROOT" eval 'if (!class_exists("YBY_Germany_Clone_Transform") || !class_exists("YBY_Country_Clone_Draft_Service")) { exit(1); }' >/dev/null
trap - ERR
printf 'CLONE_DEV_DEPLOY=PASS\nDEV_RUNTIME_ALLOWLIST=9\nDEV_PLUGIN_VERSION=1.8.1\n'
