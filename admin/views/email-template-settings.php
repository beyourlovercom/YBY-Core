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
			<p><?php esc_html_e( '统一发现与治理全站邮件；WooCommerce 模板继续由 WooCommerce / 当前已激活编辑器管理，WordPress / Andy Core 原生邮件由 Email OS 管理。', 'yby-core' ); ?></p>
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
						<td><?php if ( 'woocommerce' === ( $template['provider'] ?? '' ) ) : ?><span class="yby-email-os__status is-bridge"><?php echo esc_html( $template['editor_label'] ?: 'WooCommerce Bridge' ); ?></span><?php else : ?><span class="yby-email-os__status is-native">Andy Core Native</span><?php endif; ?></td>
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

	<?php elseif ( YBY_Email_Template_Admin::SECTION_WOOCOMMERCE === $section ) : ?>
		<?php
		$woo_templates = array_values( array_filter( $templates, static function ( $item ) { return 'woocommerce' === ( $item['provider'] ?? '' ); } ) );
		$woo_villatheme = count( array_filter( $woo_templates, static function ( $item ) { return 'villatheme' === ( $item['current_editor'] ?? '' ); } ) );
		$woo_native = count( $woo_templates ) - $woo_villatheme;
		$woo_ready = count( array_filter( $woo_templates, static function ( $item ) { return ! empty( $item['runtime_available'] ); } ) );
		?>
		<div class="yby-email-os__panel yby-email-os__governance"><strong><?php esc_html_e( 'WooCommerce = Native Editor Bridge', 'yby-core' ); ?></strong><span><?php esc_html_e( 'Woo 模板继续由 WooCommerce / VillaTheme Customizer 编辑；Email OS 只负责发现、状态、诊断与正确跳转，不接管 Woo 邮件 Runtime。', 'yby-core' ); ?></span></div>
		<div class="yby-email-os__stats">
			<div class="yby-email-os__stat"><strong><?php echo esc_html( count( $woo_templates ) ); ?></strong><span><?php esc_html_e( 'Woo 模板', 'yby-core' ); ?></span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $woo_villatheme ); ?></strong><span><?php esc_html_e( 'VillaTheme 管理', 'yby-core' ); ?></span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $woo_native ); ?></strong><span><?php esc_html_e( 'Woo 原生管理', 'yby-core' ); ?></span></div>
			<div class="yby-email-os__stat"><strong><?php echo esc_html( $woo_ready ); ?></strong><span><?php esc_html_e( 'Runtime Ready', 'yby-core' ); ?></span></div>
		</div>
		<div class="yby-email-os__panel">
			<div class="yby-email-os__panel-head"><h3><?php esc_html_e( 'WooCommerce 邮件 Registry', 'yby-core' ); ?></h3><span><?php echo defined( 'VIWEC_VER' ) ? esc_html( 'VillaTheme v' . VIWEC_VER . ' · Active' ) : esc_html__( 'WooCommerce Native', 'yby-core' ); ?></span></div>
			<table class="widefat striped yby-email-os__table">
				<thead><tr><th><?php esc_html_e( '模板', 'yby-core' ); ?></th><th><?php esc_html_e( '收件人', 'yby-core' ); ?></th><th><?php esc_html_e( '当前编辑器', 'yby-core' ); ?></th><th>Runtime</th><th><?php esc_html_e( '状态', 'yby-core' ); ?></th><th><?php esc_html_e( '操作', 'yby-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $woo_templates as $template ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $template['label'] ); ?></strong><br><code><?php echo esc_html( $template['source_id'] ); ?></code></td>
						<td><?php echo esc_html( $template['recipient'] ?: '—' ); ?></td>
						<td><span class="yby-email-os__status is-bridge"><?php echo esc_html( $template['editor_label'] ); ?></span></td>
						<td><span class="yby-email-os__status is-ready">WooCommerce</span></td>
						<td><?php if ( ! empty( $template['is_manual'] ) ) : ?><span class="yby-email-os__status">Manual</span><?php elseif ( ! empty( $template['runtime_enabled'] ) ) : ?><span class="yby-email-os__status is-ready">Enabled</span><?php else : ?><span class="yby-email-os__status is-off">Disabled</span><?php endif; ?></td>
						<td class="yby-email-os__actions"><a class="button button-small" href="<?php echo esc_url( $template['settings_url'] ); ?>"><?php esc_html_e( 'Woo 设置', 'yby-core' ); ?></a><?php if ( 'villatheme' === ( $template['current_editor'] ?? '' ) ) : ?><a class="button button-small" href="<?php echo esc_url( $template['editor_url'] ); ?>"><?php esc_html_e( 'Customizer 编辑', 'yby-core' ); ?></a><?php endif; ?><a class="button button-small" href="<?php echo esc_url( $template['preview_test_url'] ); ?>"><?php esc_html_e( '预览 / 测试', 'yby-core' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="yby-email-os__diagnostics">
			<div class="yby-email-os__panel"><strong><?php esc_html_e( '当前渲染来源', 'yby-core' ); ?></strong><p><?php esc_html_e( 'WooCommerce / 当前已激活编辑器', 'yby-core' ); ?></p></div>
			<div class="yby-email-os__panel"><strong><?php esc_html_e( 'Legacy Customizer', 'yby-core' ); ?></strong><p><?php echo defined( 'VIWEC_VER' ) ? esc_html( 'VillaTheme v' . VIWEC_VER . ' Active' ) : esc_html__( 'Not detected', 'yby-core' ); ?></p></div>
			<div class="yby-email-os__panel"><strong><?php esc_html_e( '架构保护', 'yby-core' ); ?></strong><p><?php esc_html_e( 'Andy Core 不注册 Woo outbound runtime override hooks。', 'yby-core' ); ?></p></div>
		</div>

\t<?php elseif ( in_array( $section, array( YBY_Email_Template_Admin::SECTION_WORDPRESS, YBY_Email_Template_Admin::SECTION_ANDY_CORE ), true ) ) : ?>
		<?php $payload = (array) ( $selected_state['payload'] ?? array() ); ?>
		<?php if ( ! empty( $native_notice ) ) : ?><div class="notice notice-<?php echo 'success' === ( $native_notice['type'] ?? '' ) ? 'success' : 'error'; ?> inline"><p><?php echo esc_html( $native_notice['message'] ?? '' ); ?></p></div><?php endif; ?>
		<div class="yby-email-os__native-layout">
			<aside class="yby-email-os__panel yby-email-os__native-list">
				<h3><?php echo esc_html( $sections[ $section ] ); ?></h3>
				<p><?php esc_html_e( '仅管理 Email OS 原生模板；不会改变 WooCommerce Runtime。', 'yby-core' ); ?></p>
				<?php foreach ( $native_templates as $key => $identity ) : ?>
					<a class="yby-email-os__native-item <?php echo $selected_key === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'email_section' => $section, 'template_key' => $key ), $base_url ) ); ?>">
						<strong><?php echo esc_html( $identity['label'] ); ?></strong><code><?php echo esc_html( $key ); ?></code>
					</a>
				<?php endforeach; ?>
			</aside>
			<?php if ( $selected_identity ) : ?>
			<form method="post" class="yby-email-os__panel yby-email-os__native-editor">
				<?php wp_nonce_field( 'yby_email_template_edit', 'yby_email_template_nonce' ); ?>
				<input type="hidden" name="template_key" value="<?php echo esc_attr( $selected_key ); ?>">
				<div class="yby-email-os__native-head">
					<div><h3><?php echo esc_html( $selected_identity['label'] ); ?></h3><code><?php echo esc_html( $selected_key ); ?></code></div>
					<div><span class="yby-email-os__status is-native"><?php echo esc_html( ucfirst( $selected_state['status'] ?? 'draft' ) ); ?></span><?php if ( ! empty( $selected_state['published_version'] ) ) : ?><span class="yby-email-os__status is-ready">v<?php echo absint( $selected_state['published_version'] ); ?></span><?php endif; ?></div>
				</div>
				<div class="yby-email-os__native-actions"><button class="button" name="yby_email_template_action" value="save"><?php esc_html_e( '保存草稿', 'yby-core' ); ?></button><button class="button button-primary" name="yby_email_template_action" value="publish"><?php esc_html_e( '发布新版本', 'yby-core' ); ?></button></div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '邮件主题', 'yby-core' ); ?></label><input class="regular-text" name="payload[subject]" value="<?php echo esc_attr( $payload['subject'] ?? '' ); ?>"></div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '预标题', 'yby-core' ); ?></label><input class="regular-text" name="payload[preheader]" value="<?php echo esc_attr( $payload['preheader'] ?? '' ); ?>"></div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '标题', 'yby-core' ); ?></label><input class="regular-text" name="payload[heading]" value="<?php echo esc_attr( $payload['heading'] ?? '' ); ?>"></div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '摘要 / 正文开头', 'yby-core' ); ?></label><textarea name="payload[intro_copy]" rows="4"><?php echo esc_textarea( $payload['intro_copy'] ?? '' ); ?></textarea></div>
				<div class="yby-email-os__field-grid">
					<div class="yby-email-os__field"><label><?php esc_html_e( '主按钮文字', 'yby-core' ); ?></label><input name="payload[primary_cta][label]" value="<?php echo esc_attr( $payload['primary_cta']['label'] ?? '' ); ?>"></div>
					<div class="yby-email-os__field"><label><?php esc_html_e( '主按钮 URL', 'yby-core' ); ?></label><input name="payload[primary_cta][url]" value="<?php echo esc_attr( $payload['primary_cta']['url'] ?? '' ); ?>"></div>
				</div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '补充说明', 'yby-core' ); ?></label><textarea name="payload[secondary_copy]" rows="3"><?php echo esc_textarea( $payload['secondary_copy'] ?? '' ); ?></textarea></div>
				<div class="yby-email-os__field"><label><?php esc_html_e( '附加内容', 'yby-core' ); ?></label><textarea name="payload[additional_content]" rows="3"><?php echo esc_textarea( $payload['additional_content'] ?? '' ); ?></textarea></div>
				<?php if ( ! empty( $payload['dynamic_sections'] ) ) : ?>
				<div class="yby-email-os__dynamic"><strong><?php esc_html_e( '动态字段', 'yby-core' ); ?></strong>
					<?php foreach ( (array) $payload['dynamic_sections'] as $i => $row ) : ?>
						<div class="yby-email-os__field-grid"><input name="payload[dynamic_sections][<?php echo absint( $i ); ?>][label]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>"><input name="payload[dynamic_sections][<?php echo absint( $i ); ?>][value]" value="<?php echo esc_attr( $row['value'] ?? '' ); ?>"></div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
				<div class="yby-email-os__variables"><strong><?php esc_html_e( '可用变量', 'yby-core' ); ?></strong><?php foreach ( (array) ( $selected_identity['variables'] ?? array() ) as $variable ) : ?><code>{<?php echo esc_html( $variable ); ?>}</code><?php endforeach; ?></div>
				<?php if ( ! empty( $selected_state['published_hash'] ) ) : ?><p class="description">SHA-256: <code><?php echo esc_html( $selected_state['published_hash'] ); ?></code></p><?php endif; ?>
			</form>
			<section class="yby-email-os__panel yby-email-os__native-preview">
				<div class="yby-email-os__panel-head"><h3><?php esc_html_e( '实时预览', 'yby-core' ); ?></h3><span><?php esc_html_e( '同一 Renderer · Sample Context', 'yby-core' ); ?></span></div>
				<?php if ( ! empty( $preview['valid'] ) ) : ?>
					<div class="yby-email-os__preview-subject"><strong><?php esc_html_e( 'Subject', 'yby-core' ); ?>:</strong> <?php echo esc_html( $preview['subject'] ?? '' ); ?></div>
					<iframe title="Email preview" class="yby-email-os__preview-frame" srcdoc="<?php echo esc_attr( $preview['html'] ?? '' ); ?>"></iframe>
				<?php else : ?>
					<div class="notice notice-error inline"><p><?php echo esc_html( implode( ', ', (array) ( $preview['diagnostics'] ?? array( 'Preview unavailable' ) ) ) ); ?></p></div>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( '真实测试发送属于 P6 Transport/Test Gate；P5A 不发送任何邮件。', 'yby-core' ); ?></p>
			</section>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div class="yby-email-os__panel"><h3><?php echo esc_html( $sections[ $section ] ); ?></h3><p><?php esc_html_e( '发布与测试总览将在后续 Gate 汇总 Native Publish、Transport、Test 与 Health 状态。', 'yby-core' ); ?></p></div>
	<?php endif; ?>
</div>

<style>
.yby-email-os{max-width:1320px;margin:18px 0 40px}.yby-email-os__header{display:flex;align-items:center;justify-content:space-between;margin:0 0 18px}.yby-email-os__header h2{font-size:28px;margin:0 0 6px}.yby-email-os__header p{margin:0;color:#646970}.yby-email-os__badge{padding:7px 11px;border-radius:999px;background:#f3e8ec;color:#7d293b;font-weight:600}.yby-email-os__tabs{display:flex;gap:6px;padding:6px;background:#fff;border:1px solid #e4e7e5;border-radius:12px;margin-bottom:18px}.yby-email-os__tabs a{text-decoration:none;padding:9px 13px;border-radius:8px;color:#3c434a;font-weight:600}.yby-email-os__tabs a.is-active{background:#9B3749;color:#fff}.yby-email-os__governance{display:flex;gap:14px;align-items:flex-start;margin-bottom:16px;background:#fff8fa;border-color:#f0d7df}.yby-email-os__governance strong{color:#9B3749;white-space:nowrap}.yby-email-os__governance span{color:#5f6762;line-height:1.6}.yby-email-os__stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}.yby-email-os__stat,.yby-email-os__panel{background:#fff;border:1px solid #e4e7e5;border-radius:12px;padding:18px}.yby-email-os__stat strong{display:block;font-size:26px;color:#17211B}.yby-email-os__stat span{color:#6B746E}.yby-email-os__panel-head{display:flex;justify-content:space-between;align-items:center}.yby-email-os__panel-head h3{margin:0 0 14px}.yby-email-os__panel-head span{color:#6B746E}.yby-email-os__table code{font-size:11px}.yby-email-os__status{display:inline-block;padding:3px 7px;border-radius:999px;background:#f0f0f1;color:#646970;font-size:11px}.yby-email-os__status.is-ready{background:#e7f5ec;color:#176b35}.yby-email-os__status.is-off{background:#f7f0f2;color:#8b3a4d}.yby-email-os__status.is-bridge{background:#fff4e5;color:#8a4b08}.yby-email-os__status.is-native{background:#eef5ff;color:#2463a6}.yby-email-os__workspace{display:grid;grid-template-columns:minmax(360px,.85fr) minmax(520px,1.15fr);gap:18px}.yby-email-os__tokens{display:grid;gap:9px}.yby-email-os__tokens div{display:grid;grid-template-columns:28px 1fr auto;align-items:center;gap:10px}.yby-email-os__tokens span{width:28px;height:28px;border-radius:8px;border:1px solid #dcdcde}.yby-email-os__preview{border:1px solid #e4e7e5;border-radius:12px;padding:32px;min-height:480px}.yby-email-os__mail{margin:0 auto;background:#fff;border:1px solid;overflow:hidden}.yby-email-os__mail-logo{padding:22px 26px;font-size:24px;letter-spacing:6px;border-bottom:1px solid #E5E7E6}.yby-email-os__mail-body{padding:30px 26px}.yby-email-os__mail-body h2{font-size:28px}.yby-email-os__mail-body p{font-size:15px;line-height:1.7}.yby-email-os__mail-body a{display:inline-block;margin-top:12px;padding:12px 20px;text-decoration:none;font-weight:600}.yby-email-os__actions{white-space:nowrap}.yby-email-os__actions .button{margin:2px 4px 2px 0}.yby-email-os__diagnostics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}.yby-email-os__diagnostics p{margin:8px 0 0;color:#646970;line-height:1.5}.yby-email-os__native-layout{display:grid;grid-template-columns:260px minmax(440px,1fr) minmax(420px,1fr);gap:14px}.yby-email-os__native-list p{color:#6B746E}.yby-email-os__native-item{display:block;text-decoration:none;border:1px solid #e4e7e5;border-radius:10px;padding:11px;margin:8px 0;color:#17211B}.yby-email-os__native-item.is-active{border-color:#9B3749;background:#fff8fa}.yby-email-os__native-item code{display:block;margin-top:4px;font-size:10px}.yby-email-os__native-head{display:flex;justify-content:space-between;gap:12px}.yby-email-os__native-head h3{margin:0 0 5px}.yby-email-os__native-actions{display:flex;gap:8px;margin:16px 0}.yby-email-os__field{margin:0 0 14px}.yby-email-os__field label{display:block;font-weight:600;margin-bottom:5px}.yby-email-os__field input,.yby-email-os__field textarea{width:100%;max-width:none}.yby-email-os__field-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.yby-email-os__dynamic{margin:14px 0}.yby-email-os__variables{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:14px 0}.yby-email-os__variables code{padding:4px 7px;border-radius:6px;background:#f6f7f7}.yby-email-os__preview-subject{padding:9px 0 12px}.yby-email-os__preview-frame{width:100%;height:620px;border:1px solid #e4e7e5;border-radius:10px;background:#F5F5F7}@media(max-width:1100px){.yby-email-os__native-layout{grid-template-columns:220px 1fr}.yby-email-os__native-preview{grid-column:1/-1}}@media(max-width:900px){.yby-email-os__diagnostics{grid-template-columns:1fr}.yby-email-os__stats{grid-template-columns:repeat(2,1fr)}.yby-email-os__workspace{grid-template-columns:1fr}.yby-email-os__tabs{overflow:auto}}
</style>
