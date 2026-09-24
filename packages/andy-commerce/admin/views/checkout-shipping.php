<?php
/** Checkout / Shipping settings. @package Andy_Commerce */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$settings = Andy_Commerce_Shipping_Promotion_Policy::get_settings();
$preset = Andy_Commerce_Shipping_Promotion_Policy::preset();
?>
<div class="wrap">
<h1>Andy Commerce</h1>
<nav class="nav-tab-wrapper">
<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>">Overview</a>
<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">Order Export</a>
<a class="nav-tab nav-tab-active" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'checkout-shipping' ), admin_url( 'admin.php' ) ) ); ?>">Checkout / Shipping</a>
<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">Settings</a>
</nav>
<p><strong>Preset:</strong> <?php echo esc_html( strtoupper( (string) $preset['id'] ) ); ?>. Policy settings only; existing WooCommerce free-shipping method settings are never written by this page.</p>
<form method="post" action="options.php">
<?php settings_fields( Andy_Commerce_Shipping_Promotion_Policy::SETTINGS_GROUP ); ?>
<h2>Free Shipping Promotion</h2>
<table class="form-table" role="presentation">
<tr><th scope="row"><label for="andy-commerce-mode">Mode</label></th><td><select id="andy-commerce-mode" name="<?php echo esc_attr( Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME ); ?>[mode]"><option value="automatic" <?php selected( $settings['mode'], 'automatic' ); ?>>Automatic free shipping (recommended)</option><option value="require_code" <?php selected( $settings['mode'], 'require_code' ); ?>>Require code for free shipping</option></select></td></tr>
<tr><th scope="row"><label for="andy-commerce-code">Free-shipping code</label></th><td><input id="andy-commerce-code" class="regular-text" name="<?php echo esc_attr( Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME ); ?>[free_shipping_code]" value="<?php echo esc_attr( $settings['free_shipping_code'] ); ?>" <?php disabled( $settings['mode'], 'automatic' ); ?>><p class="description">Used only when require-code mode is selected.</p></td></tr>
<tr><th scope="row"><label for="andy-commerce-threshold">Global threshold</label></th><td><input id="andy-commerce-threshold" type="number" min="0" step="0.01" name="<?php echo esc_attr( Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME ); ?>[global_threshold]" value="<?php echo esc_attr( number_format( $settings['global_threshold'], 2, '.', '' ) ); ?>"></td></tr>
<tr><th scope="row">Eligibility basis</th><td><code><?php echo esc_html( Andy_Commerce_Shipping_Promotion_Policy::ELIGIBILITY_BASIS ); ?></code></td></tr>
</table>
<div id="andy-commerce-code-warning" class="notice notice-warning inline" <?php echo 'require_code' === $settings['mode'] ? '' : 'hidden'; ?>><p><strong>Important:</strong> require-code mode consumes the order's only promo-code slot; affiliate or other promo codes cannot be used simultaneously.</p></div>
<h2>Zone thresholds</h2>
<table class="widefat striped"><thead><tr><th>Zone</th><th>Setting</th><th>Threshold</th><th>Effective threshold</th></tr></thead><tbody>
<?php foreach ( Andy_Commerce_Shipping_Promotion_Policy::zone_catalog() as $zone_id => $zone ) :
$config = $settings['zone_overrides'][(string) $zone_id] ?? array( 'mode' => 'global', 'threshold' => $settings['global_threshold'] );
$prefix = Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME . '[zone_overrides][' . $zone_id . ']'; ?>
<tr><td><?php echo esc_html( $zone['name'] ); ?> <code><?php echo esc_html( (string) $zone_id ); ?></code></td><td><select name="<?php echo esc_attr( $prefix ); ?>[mode]"><option value="global" <?php selected( $config['mode'], 'global' ); ?>>Use global threshold</option><option value="override" <?php selected( $config['mode'], 'override' ); ?>>Override</option></select></td><td><input type="number" min="0" step="0.01" name="<?php echo esc_attr( $prefix ); ?>[threshold]" value="<?php echo esc_attr( number_format( (float) $config['threshold'], 2, '.', '' ) ); ?>"></td><td><?php echo esc_html( number_format( 'override' === $config['mode'] ? (float) $config['threshold'] : (float) $settings['global_threshold'], 2 ) ); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php submit_button(); ?>
</form>
<script>(function(){var mode=document.getElementById('andy-commerce-mode'),code=document.getElementById('andy-commerce-code'),warning=document.getElementById('andy-commerce-code-warning');if(!mode||!code)return;function sync(){var required=mode.value==='require_code';code.disabled=!required;if(warning)warning.hidden=!required;}mode.addEventListener('change',sync);sync();}());</script>
</div>