<?php
/**
 * Andy Content overview.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Andy Content', 'yby-core' ); ?></h1>
	<p><?php esc_html_e( '统一管理 Andy Core 创建的独立页面与内容型产品。', 'yby-core' ); ?></p>

	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;max-width:1100px;margin-top:20px;">
		<?php foreach ( $modules as $id => $module ) : ?>
			<div class="card" style="max-width:none;margin:0;padding:20px;">
				<h2 style="margin-top:0;"><?php echo esc_html( $module['name'] ); ?></h2>
				<p><?php echo esc_html( $module['description'] ); ?></p>
				<p>
					<strong><?php esc_html_e( 'Status:', 'yby-core' ); ?></strong>
					<?php echo YBY_Module_Registry::is_enabled( $id ) ? esc_html__( 'Enabled', 'yby-core' ) : esc_html__( 'Disabled', 'yby-core' ); ?>
				</p>
				<?php $url = YBY_Module_Registry::settings_url( $id ); ?>
				<?php if ( $url && YBY_Module_Registry::is_enabled( $id ) ) : ?>
					<p><a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Open', 'yby-core' ); ?></a></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
