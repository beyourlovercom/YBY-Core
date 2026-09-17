<?php
$root = dirname( __DIR__ );
$assert = function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL {$message}\n" ); exit( 1 ); } };
$admin = file_get_contents( $root . '/admin/class-yby-admin.php' );
$general = file_get_contents( $root . '/admin/views/settings-page.php' );
$inquiry = file_get_contents( $root . '/admin/views/inquiry-settings.php' );
$status = file_get_contents( $root . '/admin/views/system-status.php' );
$view = file_get_contents( $root . '/admin/views/inquiry-notification-settings.php' );
$assert( false !== strpos( $admin, "'inquiry-notification'" ) && false !== strpos( $admin, 'render_inquiry_notification_settings' ), 'Settings routing for inquiry notification missing.' );
$assert( false !== strpos( $admin, "\$tabs['inquiry-notification'] = '询盘通知'" ) && false !== strpos( $admin, "is_enabled( 'inquiry_os' )" ), '询盘通知 must remain part of Inquiry OS dynamic Settings tabs.' );
foreach ( array( $general, $inquiry, $status, $view ) as $nav_source ) {
    $assert( false !== strpos( $nav_source, 'YBY_Admin::settings_tabs()' ), 'Settings nav must use the canonical dynamic tab registry.' );
}
$assert( false === strpos( $general, 'id="yby-whatsapp-number"' ) && false === strpos( $general, "'Email Notification'" ), 'WhatsApp and Email Notification controls must leave General.' );
$assert( false !== strpos( $view, 'id="yby-whatsapp-number"' ) && false !== strpos( $view, 'id="yby-whatsapp-message-template"' ), 'WhatsApp controls missing from 询盘通知.' );
$assert( false !== strpos( $view, 'Primary Recipient Email' ) && false !== strpos( $view, 'CC Recipient Emails' ) && false !== strpos( $view, 'BCC Recipient Emails' ) && false !== strpos( $view, 'Reply-To Policy' ), 'Email recipient controls missing from 询盘通知.' );
$assert( false !== strpos( $view, 'Subject Template' ) && false !== strpos( $view, 'Inquiry Email Title' ) && false !== strpos( $view, 'Legacy Email Company Name Override' ), 'Email presentation controls missing from 询盘通知.' );
$assert( false !== strpos( $admin, 'array_merge( $current, $raw )' ) && false !== strpos( $admin, "'whatsapp_number', 'whatsapp_message_template'" ), 'Partial-save preservation contract missing.' );
echo "PASS inquiry-notification-tab-harness\n";