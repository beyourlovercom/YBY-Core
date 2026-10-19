<?php
/**
 * Dev-only interactive native Landing Pages review bridge for PR #168.
 * Remove after Owner UAT; not part of a public WordPress release.
 * No data writes, no new routes, no editor copies.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'all_admin_notices', static function () {
    if ( ! is_admin() || ! current_user_can( 'edit_pages' ) ) { return; }
    if ( get_option( 'home' ) !== 'https://dev.beyourlover.com' ) { return; }
    if ( ! class_exists( 'YBY_Andy_Admin_Shell_V22' ) ) { return; }
    YBY_Andy_Admin_Shell_V22::render_native_landing_navigation();
}, 20 );
