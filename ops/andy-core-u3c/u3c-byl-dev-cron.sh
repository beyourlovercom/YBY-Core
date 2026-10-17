#!/usr/bin/env bash
# Andy Core U3C / BYL DEV ONLY. Draft: DO NOT DEPLOY until Owner gate.
set -Eeuo pipefail
umask 077
export PATH=/usr/local/bin:/usr/bin:/bin

readonly DOMAIN_ROOT=/home/u247343654/domains/dev.beyourlover.com
readonly WP_PATH="${DOMAIN_ROOT}/public_html"
readonly WP=/usr/local/bin/wp
readonly HOST=https://dev.beyourlover.com
readonly EXPECTED_INSTALLED=2.2.8
readonly EXPECTED_TARGET=2.2.9
readonly LOCK_FILE="${DOMAIN_ROOT}/.aicc-andy-core-u3c.lock"
mode="${1:---check}"
if [[ "$#" -gt 1 || ( "$mode" != "--check" && "$mode" != "--execute" ) ]]; then
  echo "Usage: $0 [--check|--execute]" >&2
  exit 64
fi

log() { printf '%s %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$*"; }
die() { log "FAIL-CLOSED: $*"; exit 70; }
wp_ro() { "$WP" --path="$WP_PATH" --skip-plugins --skip-themes "$@"; }
wp_live() { "$WP" --path="$WP_PATH" --skip-themes "$@"; }

[[ -x "$WP" && -f "$WP_PATH/wp-config.php" ]] || die "WordPress CLI/site path absent"
home="$(wp_ro option get home)"
[[ "$home" == "$HOST" ]] || die "wrong WordPress home"
version="$(wp_ro plugin get yby-core --field=version)"
status="$(wp_ro plugin get yby-core --field=status)"
channel="$(wp_ro option get yby_core_update_channel)"
auto="$(wp_ro option get yby_core_automatic_updates)"
cron_disabled="$(wp_ro config get DISABLE_WP_CRON)"
core_version="$(wp_ro core version)"
[[ "$status" == "active" && "$channel" == "canary" ]] || die "plugin not active or non-canary channel"
[[ "$cron_disabled" == "1" ]] || die "global WP-Cron policy drift"
[[ "$core_version" == "7.1.3" ]] || die "WordPress core baseline drift"
log "SITE=$home CORE=$core_version ANDY=$version CANARY=$channel AUTO=$auto MODE=$mode"

if [[ "$mode" == "--check" ]]; then
  log "CHECK-ONLY PASS: no site writes, no job scheduling, no cron execution"
  exit 0
fi

# Explicit one-time UAT activation; no running when automatic updates are OFF.
[[ "$version" == "$EXPECTED_INSTALLED" ]] || die "source plugin version drift"
if [[ "$auto" != "1" ]]; then
  log "SAFE-SKIP: Andy Core auto-update is OFF; no mutations"
  exit 0
fi

# Once opted-in and executing, always fail closed to OFF on normal exit/failure.
restore_off() {
  local rc="$?"
  trap - EXIT
  if ! wp_ro option update yby_core_automatic_updates 0 >/dev/null 2>&1; then
    log "URGENT: failed to restore Andy Core automatic updates OFF" >&2
    exit 75
  fi
  log "AUTO-OFF-RESTORED"
  exit "$rc"
}
trap restore_off EXIT

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  die "another isolated U3C run owns the lock"
fi

# WordPress native update discovery: checks only, does NOT install a plugin.
wp_live eval 'delete_site_transient("update_plugins"); wp_update_plugins();' >/dev/null
row="$(wp_live plugin list --fields=name,version,update_version --format=csv | grep '^yby-core,' || true)"
IFS=, read -r plugin installed offered <<<"$row"
[[ "$plugin" == "yby-core" && "$installed" == "$EXPECTED_INSTALLED" && "$offered" == "$EXPECTED_TARGET" ]] ||
  die "signed WordPress candidate missing or target version not exactly $EXPECTED_TARGET"

log "SIGNED-OFFER-CHECK PASS: $EXPECTED_INSTALLED -> $EXPECTED_TARGET"
# Schedule exactly this event if absent; never run --due-now, --all or wp-cron.php.
if ! wp_ro cron event list --field=hook | grep -Fxq wp_maybe_auto_update; then
  wp_ro cron event schedule wp_maybe_auto_update now
fi
log "NATIVE-CRON-HOOK-ONLY: wp_maybe_auto_update"
timeout 180 "$WP" --path="$WP_PATH" --skip-themes cron event run wp_maybe_auto_update
final="$(wp_ro plugin get yby-core --field=version)"
[[ "$final" == "$EXPECTED_TARGET" ]] || die "WordPress automatic upgrader did not install the expected version"
log "NATIVE-UPDATER-PASS: installed=$final (not a manual ZIP/Update Now)"
