<?php
/**
 * Read-only Marketing / Promotions V1 admin design shell.
 *
 * IMPORTANT: No campaign persistence, activation, discount hooks or eligibility
 * calculations are implemented by this view. Runtime stays completely OFF.
 *
 * @package Andy_Commerce
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<?php if ( ! class_exists( 'YBY_Andy_Admin_Shell_V22' ) || ! YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs( Andy_Commerce_Admin::PAGE_SLUG ) ) : ?>
	<h1><?php esc_html_e( 'Andy Commerce', 'andy-commerce' ); ?></h1>
	<nav class="nav-tab-wrapper" aria-label="Andy Commerce sections">
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>">Overview</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">Order Export</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'checkout-shipping' ), admin_url( 'admin.php' ) ) ); ?>">Checkout / Shipping</a>
		<a class="nav-tab nav-tab-active" aria-current="page" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'marketing-promotions' ), admin_url( 'admin.php' ) ) ); ?>">Marketing / Promotions</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">Settings</a>
	</nav>
	<?php endif; ?>

	<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Design preview only — all new promotions are OFF.', 'andy-commerce' ); ?></strong>
		<?php esc_html_e( 'The existing shipping and coupon policy is unchanged. Draft editing, scheduling and publishing are not available in this phase.', 'andy-commerce' ); ?></p></div>

	<h2><?php esc_html_e( 'Marketing / Promotions', 'andy-commerce' ); ?></h2>
	<p><?php esc_html_e( 'Operators plan campaigns here. Andy Commerce will be the only server-side discount and eligibility engine; WooCommerce retains authority for cart, shipping, tax, coupons and orders.', 'andy-commerce' ); ?></p>

	<table class="widefat striped" style="max-width:1000px">
		<caption class="screen-reader-text"><?php esc_html_e( 'Planned Andy Commerce promotion categories. All inactive.', 'andy-commerce' ); ?></caption>
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Campaign type', 'andy-commerce' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Future configuration', 'andy-commerce' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Status', 'andy-commerce' ); ?></th>
		</tr></thead>
		<tbody>
			<tr><th scope="row">BOGO</th><td>Buy X get Y; discounted second item; variant and quantity rules</td><td><strong>OFF</strong> — Not implemented</td></tr>
			<tr><th scope="row">Bundles</th><td>Named product sets; fixed price or percentage off; stock safeguards</td><td><strong>OFF</strong> — Not implemented</td></tr>
			<tr><th scope="row">Spend &amp; Save</th><td>Order threshold, fixed / percent discount, qualifying products and regions</td><td><strong>OFF</strong> — Not implemented</td></tr>
			<tr><th scope="row">Tiered Discounts</th><td>Quantity tiers and deterministic discount priority</td><td><strong>OFF</strong> — Not implemented</td></tr>
			<tr><th scope="row">Free Gift</th><td>Reward SKU, inventory, selection, exclusion, revocation and returns</td><td><strong>OFF</strong> — Requires separate Owner policy</td></tr>
			<tr><th scope="row">Free Shipping Progress</th><td>Read Woo selected rate and Andy Commerce's effective policy only</td><td><strong>OFF</strong> — Pending legacy eligibility migration</td></tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Eligibility policy — proposed V2', 'andy-commerce' ); ?></h2>
	<p><code>post_discount_merchandise_subtotal_excluding_tax_and_shipping</code></p>
	<p class="description"><?php esc_html_e( 'Owner-approved default for NEW campaigns, not yet applied to existing BYL49. First reconcile the active pre-discount shipping policy before any progress bar or offer claims to be unlocked.', 'andy-commerce' ); ?></p>
	<p class="description"><?php esc_html_e( 'Future activation will require explicit Owner approval, WooCommerce source-of-truth validation, audit controls and a separately authorized configuration release.', 'andy-commerce' ); ?></p>
</div>
