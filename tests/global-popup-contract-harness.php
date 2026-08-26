<?php
$root = dirname( __DIR__ );
$assert = function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL {$message}\n" ); exit( 1 ); } };
$popup = file_get_contents( $root . '/inc/class-yby-global-popup.php' ); $dock = file_get_contents( $root . '/inc/class-yby-global-popup-dock.php' ); $renderer = file_get_contents( $root . '/inc/class-yby-inquiry-renderer.php' ); $js = file_get_contents( $root . '/public/js/yby-global-popup.js' ); $view = file_get_contents( $root . '/admin/views/popup-settings.php' ); $core = file_get_contents( $root . '/inc/class-yby-core.php' );
$assert( strpos( $core, 'class-yby-global-popup.php' ) !== false && strpos( $core, 'class-yby-global-popup-dock.php' ) !== false, 'Both popup layers must be loaded.' );
$assert( strpos( $dock, 'render_modal' ) !== false && strpos( $dock, 'YBY_Global_Popup::settings' ) !== false, 'Inquiry must remain in the canonical dock and renderer.' );
$assert( strpos( $popup, 'data-yby-subscribe-contract' ) !== false && strpos( $popup, 'not configured' ) !== false, 'Subscribe contract must be explicit and non-submitting.' );
$assert( strpos( $renderer, 'yby-inquiry-modal__subtitle' ) !== false, 'Optional subtitle must use the canonical renderer.' );
$assert( strpos( $view, 'yby-media-button' ) !== false && strpos( $view, 'yby_popup_preview' ) !== false, 'Media controls and separate previews are required.' );
$assert( strpos( $js, 'window.YBYInquiry.open' ) !== false && strpos( $js, 'data-yby-subscribe-contract' ) !== false && strpos( $js, 'trigger_source' ) !== false, 'Runtime ownership and context contract missing.' );
$assert( strpos( $js, 'event.key === "Escape"' ) !== false && strpos( $js, 'YBYInquiry.close' ) !== false, 'Close and Inquiry delegation contract missing.' );
echo "PASS global-popup-contract-harness\n";
