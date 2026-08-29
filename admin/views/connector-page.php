<?php
/** @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap yby-connector-page">
	<h1>WP-API</h1>
	<p class="yby-connector-kicker">ERP 接口设置 · Andy Core v1.5.3</p>
	<nav class="nav-tab-wrapper" aria-label="WP-API 设置">
		<?php foreach ( array( 'general' => '常规', 'inquiry' => '询盘', 'inquiry-notification' => '询盘通知', 'wp-api' => 'WP-API', 'system-status' => '系统状态' ) as $key => $label ) : ?>
			<a class="nav-tab <?php echo 'wp-api' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php if ( $notice ) : ?><div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<div class="yby-connector-columns">
		<section class="yby-connector-panel">
			<h2>连接设置</h2>
			<p class="yby-connector-panel-description">设置站点与 ERP 的基础连接信息。</p>
			<form method="post" id="yby-connector-settings-form">
				<?php wp_nonce_field( 'yby_connector_save', 'yby_connector_nonce' ); ?>
				<?php wp_nonce_field( 'yby_connector_self_check', 'yby_connector_check_nonce' ); ?>
				<div class="yby-connector-fields">
					<label class="yby-connector-toggle"><span><input type="checkbox" name="yby_connector_options[enabled]" value="1" <?php checked( $options['enabled'] ); ?>><strong>启用连接器</strong></span><small>启用本地 ERP 连接功能。</small></label>
					<label><span>站点地址</span><input type="url" value="<?php echo esc_attr( YBY_Connector::site_url() ); ?>" readonly></label>
					<label><span>连接标识</span><input id="yby-connector-key" maxlength="128" name="yby_connector_options[connection_key]" type="text" value="<?php echo esc_attr( $options['connection_key'] ); ?>" autocomplete="off"><small>例如：beyourlover.com</small></label>
					<label><span>协议版本</span><input type="text" value="1" readonly></label>
					<label><span>密钥 ID</span><input id="yby-connector-key-id" maxlength="64" name="yby_connector_options[key_id]" type="text" value="<?php echo esc_attr( $options['key_id'] ); ?>" autocomplete="off"><small>当前用于接口签名身份验证。</small></label>
				</div>
				<p class="yby-connector-security-note">M2 已启用接口安全验证；当前仅开放健康检查。</p>
				<div class="yby-connector-secret-panel">
					<h2>安全密钥</h2>
					<p>密钥使用站点盐加密保存，仅在生成/轮换后显示一次。</p>
					<?php if ( $revealed_secret ) : ?><p><strong>请立即复制此密钥：</strong> <code><?php echo esc_html( $revealed_secret ); ?></code></p><?php endif; ?>
					<?php wp_nonce_field( 'yby_connector_secret', 'yby_connector_secret_nonce' ); ?>
					<button type="submit" class="button" name="yby_connector_secret_action" value="generate">生成 / 轮换密钥</button>
				</div>
				<div class="yby-connector-form-actions">
					<button type="submit" class="button" name="yby_connector_self_check">测试连接</button>
					<button type="submit" class="button button-primary" name="yby_connector_submit" value="1">保存设置</button>
				</div>
			</form>
		</section>

		<div class="yby-connector-side">
			<section class="yby-connector-panel yby-connector-provider-panel">
				<h2>连接器状态</h2>
				<p class="yby-connector-panel-description">当前 Provider 与连接器状态。</p>
				<?php $cards = array_merge( array( 'connector' => array( 'name' => 'ERP 连接器', 'status' => $status, 'version' => '', 'show_version' => false ) ), $providers ); ?>
				<?php foreach ( $cards as $card ) : ?>
					<div class="yby-connector-provider-row"><span><?php echo esc_html( $card['name'] ); ?></span><span><span class="yby-status-badge yby-status-<?php echo esc_attr( YBY_Connector::status_class( $card['status'] ) ); ?>"><?php echo esc_html( YBY_Connector::status_label( $card['status'] ) ); ?></span><?php if ( ( ! isset( $card['show_version'] ) || $card['show_version'] ) && $card['version'] ) : ?><small>版本 <?php echo esc_html( $card['version'] ); ?></small><?php endif; ?></span></div>
				<?php endforeach; ?>
			</section>

			<section class="yby-connector-panel">
				<h2>接口状态</h2>
				<p class="yby-connector-panel-description">M2 已启用；配置完成后仅开放 /health，其他业务接口尚未开放。</p>
				<table class="widefat striped"><thead><tr><th>接口</th><th>方法</th><th>状态</th></tr></thead><tbody>
				<?php foreach ( $endpoints as $endpoint ) : ?><tr><td><?php echo esc_html( $endpoint['path'] ); ?></td><td><?php echo esc_html( $endpoint['method'] ); ?></td><td><span class="yby-status-badge yby-status-<?php echo esc_attr( YBY_Connector::status_class( $endpoint['status'] ) ); ?>"><?php echo esc_html( YBY_Connector::status_label( $endpoint['status'] ) ); ?></span></td></tr><?php endforeach; ?>
				</tbody></table>
			</section>
		</div>
	</div>
</div>
