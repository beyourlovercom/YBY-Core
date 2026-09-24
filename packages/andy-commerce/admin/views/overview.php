<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Andy Commerce', 'andy-commerce' ); ?></h1>
	<nav class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>">Overview</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">Order Export</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">Settings</a>
	</nav>
	<p><?php esc_html_e( 'Reusable B2C / WooCommerce Addon for Andy Core.', 'andy-commerce' ); ?></p>
	<table class="widefat striped" style="max-width:760px">
		<tbody>
			<tr><th>Andy Commerce</th><td><?php echo esc_html( ANDY_COMMERCE_VERSION ); ?></td></tr>
			<tr><th>Andy Core</th><td><?php echo $status['core_loaded'] ? esc_html( YBY_CORE_VERSION ) : 'Missing'; ?></td></tr>
			<tr><th>Core Compatibility</th><td><?php echo $status['core_compatible'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>WooCommerce</th><td><?php echo $status['woo_loaded'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>Runtime</th><td><?php echo $status['ready'] ? 'Ready' : 'Blocked'; ?></td></tr>
			<tr><th>Order Export</th><td><?php echo $export_registered ? ( $export_enabled ? 'Enabled' : 'Installed / Disabled' ) : 'Not Registered'; ?></td></tr>
		</tbody>
	</table>
	<p class="description">Woo Order Export is now owned by Andy Commerce. Checkout / Shipping migration remains a later ACV1 stage.</p>
</div>
