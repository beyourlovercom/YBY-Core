<?php
/**
 * Email Template OS Settings view.
 *
 * Source-only foundation. No save/publish actions are wired in P3 Foundation.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = add_query_arg(
	array(
		'page' => 'yby-core-popups',
		'tab'  => 'email_templates',
	),
	admin_url( 'admin.php' )
);
?>
<div class="yby-email-os">
	<div class="yby-email-os__header">
		<div>
			<h2><?php esc_html_e( '邮件模板', 'yby-core' ); ?></h2>
			<p><?php esc_html_e( '统一发现与治理全站邮件；WooCommerce 模板继续由 WooCommerce / Mailonix 编辑，WordPress / Andy Core 原生邮件由 Email OS 管理。', 'yby-core' ); ?></p>
		</div>
		<span class="yby-email-os__badge">Email OS v1.1 Pivot</span>
	</div>

	<nav class="yby-email-os__tabs" aria-label="Email Template OS">
		<?php foreach ( $sections as $key => $label ) : ?>
			<a class="<?php echo $section === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'email_section', $key, $base_url ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( YBY_Email_Template_Admin::SECTION_OVERVIEW === $section ) : ?>
		<?php
		$counts = array( 'woocommerce' => 0, 'wordpress' => 0, 'andy_core' => 0 );
		foreach ( $templates as $template ) {
			$provider = $template['provider'] ?? '';
			if ( isset( $counts[ $provider ] ) ) {
				$counts[ $provider ]++;
			}
		}
		?>
		<div class="yby-email-os__panel yby-email-os__governance"><strong><?php esc_html_e( 'Email OS = 统一控制中心', 'yby-core' ); ?></strong><span><?php esc_html_e( 'WooCommerce 邮件不重复造编辑器；Email OS 负责发现、治理、状态、诊断与跳转。WordPress / Andy Core 业务邮件才由 Email OS 原生编辑与发布。', 'yby-core' ); ?></span></div>

		<div class="yby-email-os__stats">
			<div class="yby-email-os__stat"><strong><?php echo esc_html( count( $templates ) ); ?></strong><span><?php esc_html_e( '已发现模板', 'yby-core' ); ?></span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $counts['woocommerce'] ); ?></strong><span>WooCommerce</span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $counts['wordpress'] ); ?></strong><span>WordPress</span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $counts['andy_core'] ); ?></strong><span>Andy Core</span></div>
		</div>

		<div class="yby-email-os__panel">
			<div class="yby-email-os__panel-head">
				<h3><?php esc_html_e( '模板 Registry', 'yby-core' ); ?></h3>
				<span><?php esc_html_e( 'Runtime-discovered · Governance / Bridge', 'yby-core' ); ?></span>
			</div>
			<table class="widefat striped yby-email-os__table">
				<thead><tr><th><?php esc_html_e( '模板', 'yby-core' ); ?></th><th><?php esc_html_e( '来源', 'yby-core' ); ?></th><th><?php esc_html_e( '受众', 'yby-core' ); ?></th><th><?php esc_html_e( 'Runtime', 'yby-core' ); ?></th><th><?php esc_html_e( '管理模式', 'yby-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $templates as $template ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $template['label'] ); ?></strong><br><code><?php echo esc_html( $template['template_key'] ); ?></code></td>
						<td><?php echo esc_html( $template['provider'] ); ?></td>
						<td><?php echo esc_html( $template['audience'] ); ?></td>
						<td><?php echo ! empty( $template['runtime_available'] ) ? '<span class="yby-email-os__status is-ready">Ready</span>' : '<span class="yby-email-os__status">Unavailable</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php if ( 'woocommerce' === ( $template['provider'] ?? '' ) ) : ?><span class="yby-email-os__status is-bridge">Woo / Mailonix Bridge</span><?php else : ?><span class="yby-email-os__status is-native">Andy Core Native</span><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php elseif ( YBY_Email_Template_Admin::SECTION_BRAND === $section ) : ?>
		<div class="yby-email-os__workspace">
			<div class="yby-email-os__panel">
				<h3><?php esc_html_e( 'Global Email VI', 'yby-core' ); ?></h3>
				<p><?php esc_html_e( 'P3 Foundation 当前为只读预览；保存动作将在 Runtime Activation Gate 后启用。', 'yby-core' ); ?></p>
				<div class="yby-email-os__tokens">
					<?php foreach ( array( 'canvas_color', 'surface_color', 'primary_color', 'text_color', 'muted_color', 'border_color' ) as $token ) : ?>
						<div><span style="background:<?php echo esc_attr( $design[ $token ] ); ?>"></span><code><?php echo esc_html( $token ); ?></code><strong><?php echo esc_html( $design[ $token ] ); ?></strong></div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="yby-email-os__preview" style="background:<?php echo esc_attr( $design['canvas_color'] ); ?>">
				<div class="yby-email-os__mail" style="max-width:<?php echo esc_attr( $design['content_width'] ); ?>px;border-color:<?php echo esc_attr( $design['border_color'] ); ?>;border-radius:<?php echo esc_attr( $design['card_radius'] ); ?>px">
					<div class="yby-email-os__mail-logo">YBY</div>
					<div class="yby-email-os__mail-body">
						<h2 style="color:<?php echo esc_attr( $design['text_color'] ); ?>">Thank you for your order!</h2>
						<p style="color:<?php echo esc_attr( $design['text_color'] ); ?>">Hi &#123;customer_name&#125;, your order is being prepared.</p>
						<a style="background:<?php echo esc_attr( $design['primary_color'] ); ?>;color:<?php echo esc_attr( $design['primary_text_color'] ); ?>;border-radius:<?php echo esc_attr( $design['cta_radius'] ); ?>px">View Your Order</a>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>
		<div class="yby-email-os__panel">
			<h3><?php echo esc_html( $sections[ $section ] ); ?></h3>
			<p><?php esc_html_e( 'WooCommerce 区域将在 P4 提供 Registry、当前编辑器识别、状态诊断与跳转，不接管 Woo Runtime；WordPress / Andy Core 区域将在 P5 启用原生结构化编辑、预览、测试与发布。', 'yby-core' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<style>
.yby-email-os{max-width:1320px;margin:18px 0 40px}.yby-email-os__header{display:flex;align-items:center;justify-content:space-between;margin:0 0 18px}.yby-email-os__header h2{font-size:28px;margin:0 0 6px}.yby-email-os__header p{margin:0;color:#646970}.yby-email-os__badge{padding:7px 11px;border-radius:999px;background:#f3e8ec;color:#7d293b;font-weight:600}.yby-email-os__tabs{display:flex;gap:6px;padding:6px;background:#fff;border:1px solid #e4e7e5;border-radius:12px;margin-bottom:18px}.yby-email-os__tabs a{text-decoration:none;padding:9px 13px;border-radius:8px;color:#3c434a;font-weight:600}.yby-email-os__tabs a.is-active{background:#9B3749;color:#fff}.yby-email-os__governance{display:flex;gap:14px;align-items:flex-start;margin-bottom:16px;background:#fff8fa;border-color:#f0d7df}.yby-email-os__governance strong{color:#9B3749;white-space:nowrap}.yby-email-os__governance span{color:#5f6762;line-height:1.6}.yby-email-os__stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}.yby-email-os__stat,.yby-email-os__panel{background:#fff;border:1px solid #e4e7e5;border-radius:12px;padding:18px}.yby-email-os__stat strong{display:block;font-size:26px;color:#17211B}.yby-email-os__stat span{color:#6B746E}.yby-email-os__panel-head{display:flex;justify-content:space-between;align-items:center}.yby-email-os__panel-head h3{margin:0 0 14px}.yby-email-os__panel-head span{color:#6B746E}.yby-email-os__table code{font-size:11px}.yby-email-os__status{display:inline-block;padding:3px 7px;border-radius:999px;background:#f0f0f1;color:#646970;font-size:11px}.yby-email-os__status.is-ready{background:#e7f5ec;color:#176b35}.yby-email-os__status.is-off{background:#f7f0f2;color:#8b3a4d}.yby-email-os__status.is-bridge{background:#fff4e5;color:#8a4b08}.yby-email-os__status.is-native{background:#eef5ff;color:#2463a6}.yby-email-os__workspace{display:grid;grid-template-columns:minmax(360px,.85fr) minmax(520px,1.15fr);gap:18px}.yby-email-os__tokens{display:grid;gap:9px}.yby-email-os__tokens div{display:grid;grid-template-columns:28px 1fr auto;align-items:center;gap:10px}.yby-email-os__tokens span{width:28px;height:28px;border-radius:8px;border:1px solid #dcdcde}.yby-email-os__preview{border:1px solid #e4e7e5;border-radius:12px;padding:32px;min-height:480px}.yby-email-os__mail{margin:0 auto;background:#fff;border:1px solid;overflow:hidden}.yby-email-os__mail-logo{padding:22px 26px;font-size:24px;letter-spacing:6px;border-bottom:1px solid #E5E7E6}.yby-email-os__mail-body{padding:30px 26px}.yby-email-os__mail-body h2{font-size:28px}.yby-email-os__mail-body p{font-size:15px;line-height:1.7}.yby-email-os__mail-body a{display:inline-block;margin-top:12px;padding:12px 20px;text-decoration:none;font-weight:600}@media(max-width:900px){.yby-email-os__stats{grid-template-columns:repeat(2,1fr)}.yby-email-os__workspace{grid-template-columns:1fr}.yby-email-os__tabs{overflow:auto}}
</style>
