<?php
/** Focused WooCommerce completed-delivered order status harness. */
define( 'ABSPATH', __DIR__ );

function _n_noop( $single, $plural, $domain = null ) {
	return array( $single, $plural, $domain );
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $registered_hooks;
	$registered_hooks['actions'][] = array( $hook, $callback, $priority, $accepted_args );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $registered_hooks;
	$registered_hooks['filters'][] = array( $hook, $callback, $priority, $accepted_args );
}

function register_post_status( $key, $args ) {
	global $registered_post_status;
	$registered_post_status = array( $key, $args );
}

require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';

function woo_status_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$connector = new YBY_Connector();

global $registered_hooks;
woo_status_assert( array( 'init', array( 'YBY_Connector', 'register_completed_delivered_status' ), 10, 0 ) === $registered_hooks['actions'][0], 'Connector must register the status on init.' );
woo_status_assert( array( 'wc_order_statuses', array( 'YBY_Connector', 'add_completed_delivered_status' ), 10, 1 ) === $registered_hooks['filters'][0], 'Connector must register the WooCommerce status filter.' );

YBY_Connector::register_completed_delivered_status();

global $registered_post_status;
woo_status_assert( 'wc-completed-delivered' === $registered_post_status[0], 'Status key must be wc-completed-delivered.' );
woo_status_assert( 'Completed & Delivered / 完结&送达' === $registered_post_status[1]['label'], 'Status label must match the owner-facing label.' );
woo_status_assert( true === $registered_post_status[1]['public'], 'Status must be public.' );
woo_status_assert( true === $registered_post_status[1]['show_in_admin_status_list'], 'Status must show in the admin status list.' );
woo_status_assert( true === $registered_post_status[1]['show_in_admin_all_list'], 'Status must show in the admin all list.' );
woo_status_assert( false === $registered_post_status[1]['exclude_from_search'], 'Status must not be excluded from search.' );
woo_status_assert( array( 'Completed & Delivered / 完结&送达 <span class="count">(%s)</span>', 'Completed & Delivered / 完结&送达 <span class="count">(%s)</span>', 'yby-core' ) === $registered_post_status[1]['label_count'], 'Status label_count must use _n_noop.' );

$existing = array(
	'wc-pending'   => 'Pending payment',
	'wc-completed' => 'Completed',
	'wc-cancelled' => 'Cancelled',
);
$updated = YBY_Connector::add_completed_delivered_status( $existing );
woo_status_assert( array( 'wc-pending', 'wc-completed', 'wc-completed-delivered', 'wc-cancelled' ) === array_keys( $updated ), 'Status must be inserted immediately after completed without disturbing existing order.' );
woo_status_assert( 'Completed & Delivered / 完结&送达' === $updated['wc-completed-delivered'], 'WooCommerce status list label must match the owner-facing label.' );

$without_completed = array( 'wc-pending' => 'Pending payment', 'wc-cancelled' => 'Cancelled' );
$fallback          = YBY_Connector::add_completed_delivered_status( $without_completed );
woo_status_assert( array( 'wc-pending', 'wc-cancelled', 'wc-completed-delivered' ) === array_keys( $fallback ), 'Status must append when completed is absent and preserve existing statuses.' );

echo "WooCommerce completed-delivered status harness passed.\n";
