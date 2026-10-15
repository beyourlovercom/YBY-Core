<?php
/** U1-U4 fail-closed channel, WordPress update integration and guard contracts. */
$root = dirname( __DIR__ );
$src = file_get_contents( $root . '/inc/class-yby-updater.php' );
$assert = static function ($ok, $msg) { if (!$ok) { throw new RuntimeException($msg); } };
$assert(strpos($src, "CHANNEL_OPTION = 'yby_core_update_channel'") !== false, 'channel option');
$assert(strpos($src, "AUTO_OPTION = 'yby_core_automatic_updates'") !== false, 'auto option');
$assert(strpos($src, "get_option( self::CHANNEL_OPTION, 'disabled' )") !== false, 'default disabled');
$assert(strpos($src, "if ( 'canary' === " . '$channel' . " ) { return false; }") !== false, 'no canary fallback');
$assert(strpos($src, "'www.beyourlover.com'") === false, 'www must never be allowlisted');
$assert(strpos($src, "'ybybottle.com', 'ybyirrigation.com'") !== false, 'known target allowlist');
$assert(strpos($src, "add_filter( 'auto_update_plugin'") !== false, 'WordPress auto-update hook');
$assert(strpos($src, 'check_admin_referer( self::CHANNEL_NONCE') !== false, 'CSRF protection');
$assert(strpos($src, "sanitize_text_field( wp_unslash(") !== false, 'sanitized admin setting');
$assert(strpos($src, 'unset( $transient->response[ $plugin ] );') !== false, 'no update shown on excluded sites');
$assert(strpos($src, "yby_update_site_denied") !== false, 'download path denied when disabled');
$assert(strpos($src, 'YBY_Update_Backup::create') !== false, 'backup still enforced');
$assert(strpos($src, 'YBY_Update_Verifier::validate_zip') !== false, 'signature/zip verifier still enforced');
echo "PASS U1-U4 update channels source fail-closed contract\n";
