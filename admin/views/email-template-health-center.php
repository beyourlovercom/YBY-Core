<?php
/** Email OS P6 Transport / Test / Health Center. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$last_test_status = empty( $last_test ) ? 'Not run' : ( ! empty( $last_test['success'] ) ? 'PASS' : 'FAIL' );
$published_label = absint( $native_health['published'] ?? 0 ) . ' / ' . absint( $native_health['total'] ?? 0 );
?>
<?php if ( ! empty( $test_notice ) ) : ?>
	<div class="notice notice-<?php echo ! empty( $test_notice['success'] ) ? 'success' : 'error'; ?> inline">
		<p><?php echo esc_html( (string) ( $test_notice['message'] ?? '' ) ); ?></p>
	</div>
<?php endif; ?>

<div class="yby-email-os__panel yby-email-os__governance">
	<strong><?php esc_html_e( 'Transport / SMTP 仍由外部 Provider 管理', 'yby-core' ); ?></strong>
	<span><?php esc_html_e( 'Email OS 只读取非敏感的 Transport 健康信息；只有人工点击测试发送时才调用 WordPress wp_mail。SMTP / API 凭据始终留在当前 Transport 来源 内。', 'yby-core' ); ?></span>
</div>

<div class="yby-email-os__stats">
	<div class="yby-email-os__stat"><strong><?php echo ! empty( $transport_health['wp_mail_ready'] ) ? 'Ready' : 'Blocked'; ?></strong><span>wp_mail</span></div>
	<div class="yby-email-os__stat"><strong class="yby-email-os__stat-text"><?php echo esc_html( $transport_health['provider'] ?? 'Unknown' ); ?></strong><span><?php echo esc_html( $transport_health['plugin'] ?? '' ); ?></span></div>
	<div class="yby-email-os__stat"><strong><?php echo esc_html( $published_label ); ?></strong><span><?php esc_html_e( 'Native 已发布', 'yby-core' ); ?></span></div>
	<div class="yby-email-os__stat"><strong><?php echo esc_html( $last_test_status ); ?></strong><span><?php esc_html_e( '最近测试', 'yby-core' ); ?></span></div>
</div>

<div class="yby-email-os__p6-grid">
	<section class="yby-email-os__panel">
		<div class="yby-email-os__panel-head"><h3><?php esc_html_e( 'Transport 健康', 'yby-core' ); ?></h3><span><?php esc_html_e( '只读', 'yby-core' ); ?></span></div>
		<table class="widefat striped yby-email-os__health-table"><tbody>
			<tr><td>WordPress wp_mail</td><td><?php echo ! empty( $transport_health['wp_mail_ready'] ) ? '<span class="yby-email-os__status is-ready">Ready</span>' : '<span class="yby-email-os__status is-off">Unavailable</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
			<tr><td><?php esc_html_e( 'Transport 插件', 'yby-core' ); ?></td><td><?php echo esc_html( $transport_health['plugin'] ?? 'WordPress Core' ); ?></td></tr>
			<tr><td><?php esc_html_e( 'Mailer / 来源', 'yby-core' ); ?></td><td><code><?php echo esc_html( $transport_health['provider_slug'] ?? 'mail' ); ?></code> <?php echo esc_html( $transport_health['provider'] ?? '' ); ?></td></tr>
			<tr><td><?php esc_html_e( '来源 configuration', 'yby-core' ); ?></td><td><?php echo ! empty( $transport_health['configured'] ) ? '<span class="yby-email-os__status is-ready">Configured</span>' : '<span class="yby-email-os__status is-off">Needs review</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
			<tr><td><?php esc_html_e( 'Native 已发布 snapshots', 'yby-core' ); ?></td><td><?php echo esc_html( $published_label ); ?></td></tr>
		</tbody></table>
		<?php if ( ! empty( $transport_health['settings_url'] ) ) : ?>
			<p><a class="button" href="<?php echo esc_url( $transport_health['settings_url'] ); ?>"><?php esc_html_e( '打开 Transport 设置', 'yby-core' ); ?></a></p>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( '此页面不会读取或展示 SMTP 密码、API Key、OAuth Secret 或其他 来源 凭据。', 'yby-core' ); ?></p>
	</section>

	<section class="yby-email-os__panel">
		<div class="yby-email-os__panel-head"><h3><?php esc_html_e( 'Native 测试发送', 'yby-core' ); ?></h3><span><?php esc_html_e( '仅手动触发', 'yby-core' ); ?></span></div>
		<p><?php esc_html_e( 'Only 已发布 WordPress / Andy Core snapshots can be sent. The message uses Sample Context and the same canonical Renderer as Runtime.', 'yby-core' ); ?></p>
		<form method="post" class="yby-email-os__test-form">
			<?php wp_nonce_field( 'yby_email_test_send', 'yby_email_test_nonce' ); ?>
			<label><strong><?php esc_html_e( '模板', 'yby-core' ); ?></strong></label>
			<select name="test_template_key" <?php disabled( empty( $native_health['published'] ) ); ?>>
				<?php foreach ( (array) ( $native_health['rows'] ?? array() ) as $key => $row ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php disabled( empty( $row['published'] ) ); ?>><?php echo esc_html( $row['label'] . ( ! empty( $row['published'] ) ? ' · v' . absint( $row['version_number'] ) : ' · 未发布' ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><strong><?php esc_html_e( '测试收件人', 'yby-core' ); ?></strong></label>
			<input type="email" name="test_recipient" value="<?php echo esc_attr( $test_recipient_default ); ?>" placeholder="name@example.com" <?php disabled( empty( $native_health['published'] ) ); ?>>
			<button class="button button-primary" name="yby_email_test_action" value="send" <?php disabled( empty( $native_health['published'] ) ); ?>><?php esc_html_e( '发送测试邮件', 'yby-core' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( '页面加载不会发送任何邮件；只有具备权限的用户点击按钮后才会发送。测试邮件主题统一增加 [Email OS Test] 前缀。', 'yby-core' ); ?></p>
	</section>
</div>

<div class="yby-email-os__p6-grid yby-email-os__p6-grid--bottom">
	<section class="yby-email-os__panel">
		<div class="yby-email-os__panel-head"><h3><?php esc_html_e( 'Native 发布健康', 'yby-core' ); ?></h3><span><?php echo esc_html( $published_label ); ?></span></div>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( '模板', 'yby-core' ); ?></th><th><?php esc_html_e( '来源', 'yby-core' ); ?></th><th><?php esc_html_e( '已发布', 'yby-core' ); ?></th></tr></thead><tbody>
		<?php foreach ( (array) ( $native_health['rows'] ?? array() ) as $row ) : ?>
			<tr><td><?php echo esc_html( $row['label'] ); ?></td><td><code><?php echo esc_html( $row['provider'] ); ?></code></td><td><?php if ( ! empty( $row['published'] ) ) : ?><span class="yby-email-os__status is-ready">v<?php echo absint( $row['version_number'] ); ?></span><?php else : ?><span class="yby-email-os__status">未发布</span><?php endif; ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</section>

	<section class="yby-email-os__panel">
		<div class="yby-email-os__panel-head"><h3><?php esc_html_e( '最近测试 Result', 'yby-core' ); ?></h3><span><?php echo esc_html( $last_test_status ); ?></span></div>
		<?php if ( empty( $last_test ) ) : ?>
			<p><?php esc_html_e( '尚未进行 Email OS 测试发送。', 'yby-core' ); ?></p>
		<?php else : ?>
			<p><span class="yby-email-os__status <?php echo ! empty( $last_test['success'] ) ? 'is-ready' : 'is-off'; ?>"><?php echo ! empty( $last_test['success'] ) ? 'PASS' : 'FAIL'; ?></span></p>
			<p><strong><?php echo esc_html( $last_test['template_label'] ?? $last_test['template_key'] ?? '' ); ?></strong><?php if ( ! empty( $last_test['version_number'] ) ) : ?> · v<?php echo absint( $last_test['version_number'] ); ?><?php endif; ?></p>
			<p><?php echo esc_html( $last_test['message'] ?? '' ); ?></p>
			<p class="description"><?php echo esc_html( $last_test['tested_at'] ?? '' ); ?><?php if ( ! empty( $last_test['error_code'] ) ) : ?> · <code><?php echo esc_html( $last_test['error_code'] ); ?></code><?php endif; ?></p>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Email OS 测试历史不会保存收件人地址。', 'yby-core' ); ?></p>
	</section>
</div>

<style>
.yby-email-os__p6-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}.yby-email-os__p6-grid--bottom{margin-top:0}.yby-email-os__health-table td:first-child{width:42%;font-weight:600}.yby-email-os__test-form{display:grid;grid-template-columns:1fr;gap:8px;margin-top:16px}.yby-email-os__test-form select,.yby-email-os__test-form input{width:100%;max-width:560px}.yby-email-os__test-form .button{width:max-content;margin-top:4px}.yby-email-os__stat-text{font-size:18px!important;line-height:1.25;word-break:break-word}@media(max-width:1000px){.yby-email-os__p6-grid{grid-template-columns:1fr}}
</style>
