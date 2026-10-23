<?php
/**
 * Read-only 营销活动 V1 admin design shell.
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
	<nav class="nav-tab-wrapper" aria-label="Andy Commerce 页面导航">
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>">总览</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">订单导出</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'checkout-shipping' ), admin_url( 'admin.php' ) ) ); ?>">结账与物流</a>
		<a class="nav-tab nav-tab-active" aria-current="page" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'marketing-promotions' ), admin_url( 'admin.php' ) ) ); ?>">营销活动</a>
		<a class="nav-tab" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">设置</a>
	</nav>
	<?php endif; ?>

	<div class="notice notice-info inline"><p><strong><?php esc_html_e( '仅供设计预览，所有新活动均未启用。', 'andy-commerce' ); ?></strong>
		<?php esc_html_e( '现行运费和优惠券规则保持不变，暂不支持自动发布。获得授权的 Dev 管理员可在下方编辑未启用草稿。', 'andy-commerce' ); ?></p></div>

	<h2><?php esc_html_e( '营销活动', 'andy-commerce' ); ?></h2>
	<p><?php esc_html_e( '此处管理活动方案；Andy Commerce 负责活动资格与折扣规划，WooCommerce 仍是购物车、运费、税费、优惠券及订单的数据权威。', 'andy-commerce' ); ?></p>

	<table class="widefat striped" style="max-width:1000px">
		<caption class="screen-reader-text"><?php esc_html_e( 'Andy Commerce 计划中的营销活动类型，全部未启用。', 'andy-commerce' ); ?></caption>
		<thead><tr>
			<th scope="col"><?php esc_html_e( '活动类型', 'andy-commerce' ); ?></th>
			<th scope="col"><?php esc_html_e( '未来配置方向', 'andy-commerce' ); ?></th>
			<th scope="col"><?php esc_html_e( '执行状态', 'andy-commerce' ); ?></th>
		</tr></thead>
		<tbody>
			<tr><th scope="row">BOGO</th><td>买 X 赠 Y；支持商品变体、数量条件和第二件折扣</td><td><strong>未启用</strong> · 未开放真实执行</td></tr>
			<tr><th scope="row">Bundles</th><td>指定商品组合；固定金额或百分比优惠；库存校验</td><td><strong>未启用</strong> · 未开放真实执行</td></tr>
			<tr><th scope="row">Spend &amp; Save</th><td>订单金额门槛；固定或百分比优惠；适用商品与国家地区</td><td><strong>未启用</strong> · 未开放真实执行</td></tr>
			<tr><th scope="row">Tiered Discounts</th><td>数量阶梯与优惠优先级</td><td><strong>未启用</strong> · 未开放真实执行</td></tr>
			<tr><th scope="row">Free Gift</th><td>赠品 SKU、库存、选品、排除条件、撤销和退货</td><td><strong>未启用</strong> · 需单独授权</td></tr>
			<tr><th scope="row">Free Shipping Progress</th><td>仅读取 Woo 已选运费及当前有效的业务规则</td><td><strong>未启用</strong> · 旧规则待衔接</td></tr>
		</tbody>
	</table>

	<?php if ( class_exists('Andy_Commerce_M7_Draft_Studio') && class_exists('Andy_Commerce_M8_Promotion_Evaluator') ) include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m7-dev-studio.php'; ?>

	<h2><?php esc_html_e( '金额资格核算规则（V2 草案）', 'andy-commerce' ); ?></h2>
	<p><code>post_discount_merchandise_subtotal_excluding_tax_and_shipping</code></p>
	<p class="description"><?php esc_html_e( '新活动拟采用折扣后 USD 合格商品金额；现行 BYL49 不受本页面影响。真实免邮进度必须先核对原 Woo 运费政策。', 'andy-commerce' ); ?></p>
	<p class="description"><?php esc_html_e( '未来启用真实活动须另行获得授权，并通过 WooCommerce 数据校验、审计记录和发布门禁。', 'andy-commerce' ); ?></p>
</div>
