<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<?php if ( ! class_exists( 'YBY_Andy_Admin_Shell_V22' ) || ! YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs( Andy_Commerce_Admin::PAGE_SLUG ) ) : ?>
<h1><?php esc_html_e( 'Andy Commerce', 'andy-commerce' ); ?></h1>
	<nav class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>">Overview</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">Order Export</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'checkout-shipping' ), admin_url( 'admin.php' ) ) ); ?>">Checkout / Shipping</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'marketing-promotions' ), admin_url( 'admin.php' ) ) ); ?>">Marketing / Promotions</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">Settings</a>
	</nav>
<?php endif; ?>
	<p><?php esc_html_e( 'Reusable B2C / WooCommerce Addon for Andy Core.', 'andy-commerce' ); ?></p>
	<table class="widefat striped" style="max-width:760px">
		<tbody>
			<tr><th>Andy Commerce</th><td><?php echo esc_html( ANDY_COMMERCE_VERSION ); ?></td></tr>
			<tr><th>Andy Core</th><td><?php echo $status['core_loaded'] ? esc_html( YBY_CORE_VERSION ) : 'Missing'; ?></td></tr>
			<tr><th>Core Compatibility</th><td><?php echo $status['core_compatible'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>WooCommerce</th><td><?php echo $status['woo_loaded'] ? 'PASS' : 'FAIL'; ?></td></tr>
			<tr><th>Runtime</th><td><?php echo $status['ready'] ? 'Ready' : 'Blocked'; ?></td></tr>
			<tr><th>Order Export</th><td><?php echo $export_registered ? ( $export_enabled ? 'Enabled' : 'Installed / Disabled' ) : 'Not Registered'; ?></td></tr>
			<tr><th>Checkout / Shipping</th><td><?php echo Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() ? 'Active' : 'Configured / Inactive'; ?></td></tr>
		</tbody>
	</table>
	<p class="description">Woo Order Export and Checkout / Shipping promotion policy are owned by Andy Commerce. Site-specific values are supplied by the active Commerce preset and saved site configuration.</p>
    <?php
    // Core Registry remains the *single source* of ON/OFF state.
    $orders_module_states = Andy_Commerce_Orders_Features::statuses();
    $can_toggle_orders = Andy_Commerce_Orders_Features::can_manage();
    ?>
    <section class="ac-orders-feature-settings" aria-labelledby="ac-orders-feature-title">
        <h2 id="ac-orders-feature-title">Orders UI · 功能开关</h2>
        <p class="description">复用 Andy Core 模块开关。启用后生效于当前网站的 WooCommerce 原生订单界面；关闭后恢复对应原生展示，不删除订单或已保存的个人列布局。</p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="andy_commerce_orders_modules_save">
            <?php wp_nonce_field( Andy_Commerce_Orders_Features::NONCE, 'andy_commerce_orders_modules_nonce' ); ?>
            <table class="widefat striped" style="max-width:760px">
                <thead><tr><th>模块</th><th>功能说明</th><th>启用</th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>Orders List UI</strong></td>
                        <td>标准 / 紧凑 / 卡片模式、列排序及 <code>39936 bear</code> 这样的真实 SKU 显示</td>
                        <td>
                            <label class="ac-orders-feature-switch">
                                <input type="checkbox" name="andy_commerce_orders_modules[]" value="<?php echo esc_attr( Andy_Commerce_Orders_Features::LIST_MODULE ); ?>" <?php checked( $orders_module_states[ Andy_Commerce_Orders_Features::LIST_MODULE ] ); ?> <?php disabled( ! $can_toggle_orders ); ?>>
                                <span class="ac-orders-feature-switch__track" aria-hidden="true"></span>
                                <span class="screen-reader-text">Orders List UI</span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Order Detail Label</strong></td>
                        <td>订单详情页仅显示 <code>订单 39936</code>；不改变订单数据库与交易信息</td>
                        <td>
                            <label class="ac-orders-feature-switch">
                                <input type="checkbox" name="andy_commerce_orders_modules[]" value="<?php echo esc_attr( Andy_Commerce_Orders_Features::DETAIL_MODULE ); ?>" <?php checked( $orders_module_states[ Andy_Commerce_Orders_Features::DETAIL_MODULE ] ); ?> <?php disabled( ! $can_toggle_orders ); ?>>
                                <span class="ac-orders-feature-switch__track" aria-hidden="true"></span>
                                <span class="screen-reader-text">Order Detail Label</span>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php if ( $can_toggle_orders ) : ?>
                <p class="submit"><button type="submit" class="button button-primary">保存开关</button></p>
            <?php else : ?>
                <p class="description">需要 Andy Core 模块管理权限才能修改此设置。</p>
            <?php endif; ?>
        </form>
    </section>
    <style>
        .ac-orders-feature-settings{margin-top:24px}
        .ac-orders-feature-settings .widefat th,.ac-orders-feature-settings .widefat td{padding:13px 15px;vertical-align:middle}
        .ac-orders-feature-switch{position:relative;display:inline-flex;align-items:center;cursor:pointer}
        .ac-orders-feature-switch input{position:absolute;width:38px;height:22px;opacity:0;margin:0}
        .ac-orders-feature-switch__track{position:relative;display:inline-block;width:38px;height:22px;border-radius:20px;background:#aeb4bb;transition:background .15s;pointer-events:none}
        .ac-orders-feature-switch__track:before{content:"";position:absolute;left:3px;top:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .15s}
        .ac-orders-feature-switch input:checked + .ac-orders-feature-switch__track{background:#17634b}
        .ac-orders-feature-switch input:checked + .ac-orders-feature-switch__track:before{transform:translateX(16px)}
        .ac-orders-feature-switch input:focus-visible + .ac-orders-feature-switch__track{outline:2px solid #135e96;outline-offset:3px}
        .ac-orders-feature-switch input:disabled + .ac-orders-feature-switch__track{opacity:.5;cursor:not-allowed}
    </style>
    <?php if ( isset( $_GET['orders_saved'] ) && '1' === $_GET['orders_saved'] ) : ?>
        <div class="notice notice-success is-dismissible"><p>Orders UI 模块开关已保存。</p></div>
    <?php endif; ?>

</div>
