<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Andy Commerce', 'andy-commerce' ); ?></h1>
	<p><?php esc_html_e( 'Reusable B2C / WooCommerce Addon for Andy Core.', 'andy-commerce' ); ?></p>
	<table class="widefat striped" style="max-width:760px">
		<tbody>
			<tr><th>Andy Commerce</th><td><?php echo esc_html( ANDY_COMMERCE_VERSION ); ?></td></tr>
			<tr><th>Andy Core</th><td><?php echo $status['core_loaded'] ? esc_html( YBY_CORE_VERSION ) : 'Missing'; ?></td></tr>
			<tr><th>Core Compatibility</th><td><?php echo $status['core_compatible'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>WooCommerce</th><td><?php echo $status['woo_loaded'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>Runtime</th><td><?php echo $status['ready'] ? 'Ready' : 'Blocked'; ?></td></tr>
		</tbody>
	</table>
	<p class="description">Order Export and Checkout / Shipping modules are migrated in later ACV1 stages. This foundation intentionally contains no BYL-only business logic.</p>
</div>
