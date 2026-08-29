<?php
/** @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap yby-connector-page">
	<h1>Andy Core</h1>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( array( 'general' => '常规', 'inquiry' => '询盘', 'inquiry-notification' => '询盘通知', 'wp-api' => 'WP-API', 'system-status' => '系统状态' ) as $key => $label ) : ?>
			<a class="nav-tab <?php echo 'wp-api' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</h2>
	<div class="yby-connector-header">
		<div>
			<h2>WP-API</h2>
			<p class="yby-connector-kicker">ERP 接口设置 <span>Andy Core v1.5.3</span></p>
		</div>
		<div class="yby-connector-header-actions">
			<span class="yby-status-badge yby-status-<?php echo esc_attr( YBY_Connector::status_class( $status ) ); ?>"><?php echo esc_html( YBY_Connector::status_label( $status ) ); ?></span>
			<form method="post" class="yby-connector-self-check">
				<?php wp_nonce_field( 'yby_connector_self_check', 'yby_connector_check_nonce' ); ?>
				<button type="submit" class="button" name="yby_connector_self_check">测试连接</button>
			</form>
			<button type="submit" class="button button-primary" name="yby_connector_submit" value="1" form="yby-connector-settings-form">保存设置</button>
		</div>
	</div>
	<?php if ( $notice ) : ?><div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<div class="yby-connector-status-grid">
		<?php $cards = array_merge( array( 'connector' => array( 'name' => 'ERP 连接器', 'status' => $status, 'version' => '', 'show_version' => false ) ), $providers ); ?>
		<?php foreach ( $cards as $card ) : ?>
			<div class="yby-connector-card"><h2><?php echo esc_html( $card['name'] ); ?></h2><span class="yby-status-badge yby-status-<?php echo esc_attr( YBY_Connector::status_class( $card['status'] ) ); ?>"><?php echo esc_html( YBY_Connector::status_label( $card['status'] ) ); ?></span><?php if ( ( ! isset( $card['show_version'] ) || $card['show_version'] ) && $card['version'] ) : ?><small>版本 <?php echo esc_html( $card['version'] ); ?></small><?php endif; ?></div>
		<?php endforeach; ?>
	</div>
	<div class="yby-connector-columns">
		<div class="yby-connector-panel">
			<h2>连接设置</h2>
			<form method="post" id="yby-connector-settings-form">
				<?php wp_nonce_field( 'yby_connector_save', 'yby_connector_nonce' ); ?>
				<div class="yby-connector-fields">
					<label><span>启用连接器</span><input type="checkbox" name="yby_connector_options[enabled]" value="1" <?php checked( $options['enabled'] ); ?>><small>启用本地 ERP 连接功能。</small></label>
					<label><span>站点地址</span><input type="url" value="<?php echo esc_attr( YBY_Connector::site_url() ); ?>" readonly><small>当前 WordPress 地址。</small></label>
					<label><span>连接标识</span><input id="yby-connector-key" maxlength="128" name="yby_connector_options[connection_key]" type="text" value="<?php echo esc_attr( $options['connection_key'] ); ?>" autocomplete="off"><small>例如：beyourlover.com</small></label>
					<label><span>协议版本</span><input type="text" value="1" readonly></label>
					<label><span>密钥 ID</span><input id="yby-connector-key-id" maxlength="64" name="yby_connector_options[key_id]" type="text" value="<?php echo esc_attr( $options['key_id'] ); ?>" autocomplete="off"><small>下一阶段安全验证使用。</small></label>
				</div>
				<p class="yby-connector-security-note">安全密钥将在下一阶段配置；当前不会连接 ERP。</p>
			</form>
		</div>
		<div class="yby-connector-panel">
			<h2>接口状态</h2>
			<table class="widefat striped"><thead><tr><th>接口</th><th>方法</th><th>状态</th></tr></thead><tbody>
			<?php foreach ( $endpoints as $endpoint ) : ?><tr><td><?php echo esc_html( $endpoint['path'] ); ?><br><small><?php echo esc_html( $endpoint['label'] ); ?></small></td><td><?php echo esc_html( $endpoint['method'] ); ?></td><td><span class="yby-status-badge yby-status-<?php echo esc_attr( YBY_Connector::status_class( $endpoint['status'] ) ); ?>"><?php echo esc_html( YBY_Connector::status_label( $endpoint['status'] ) ); ?></span></td></tr><?php endforeach; ?>
			</tbody></table>
			<p class="description">M1 仅展示接口清单，尚未开放调用。</p>
		</div>
	</div>
</div>