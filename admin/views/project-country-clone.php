<?php
/**
 * Project Studio Country Clone V1: review-only Germany preflight UI.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<h1><?php esc_html_e( '复制到新国家 / Country Clone V1', 'yby-core' ); ?></h1>
	<p><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'list' ) ); ?>">&larr; <?php esc_html_e( '返回 Project Studio', 'yby-core' ); ?></a></p>
	<div class="notice notice-info inline">
		<p><?php esc_html_e( 'Germany（德国）目前仅可执行只读预检，不会新建 Project、Landing Page、签名、修改原站或启动广告。', 'yby-core' ); ?></p>
	</div>

	<form method="post" action="<?php echo esc_url( YBY_Project_Studio::studio_url( 'clone', $post_id ) ); ?>">
		<?php wp_nonce_field( 'yby_country_clone_preflight_' . $post_id ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( '源项目 / Source Project', 'yby-core' ); ?></th>
					<td>
						<strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong>
						<code>#<?php echo esc_html( (string) $post_id ); ?></code>
						<input type="hidden" name="source_project_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-clone-source-landing"><?php esc_html_e( '源 Landing Page ID', 'yby-core' ); ?></label></th>
					<td>
						<input type="number" min="1" step="1" id="yby-clone-source-landing"
							name="source_landing_id" class="regular-text"
							value="<?php echo esc_attr( $submitted_landing_id ? (string) $submitted_landing_id : '' ); ?>"
							placeholder="例如：292" required>
						<p class="description"><?php esc_html_e( '必须填写后台核实过的源页面 ID；不可使用 US 798 代替 Tanzania 源。', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '目标国家 / Target Country', 'yby-core' ); ?></th>
					<td><strong>Germany · DE</strong> <span class="description"><?php esc_html_e( '只读预检；语言和广告配置暂不自动推断。', 'yby-core' ); ?></span></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '目标状态 / New Status', 'yby-core' ); ?></th>
					<td><code>draft</code> <?php esc_html_e( '未来创建必须为独立新 ID；现阶段不执行。', 'yby-core' ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php submit_button( __( '只读预检 / Run Read-Only Preflight', 'yby-core' ), 'primary', 'yby_country_clone_preflight' ); ?>
	</form>

	<?php if ( null !== $preflight ) : ?>
		<h2><?php esc_html_e( '预检报告 / Preflight Evidence', 'yby-core' ); ?></h2>
		<p><strong><?php esc_html_e( '结果：BLOCKED（正式克隆写入未开放）', 'yby-core' ); ?></strong></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( '项目 / Check', 'yby-core' ); ?></th><th><?php esc_html_e( '结果 / Result', 'yby-core' ); ?></th><th><?php esc_html_e( '说明 / Detail', 'yby-core' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $preflight['checks'] as $check ) : ?>
				<tr>
					<td><code><?php echo esc_html( $check['code'] ); ?></code></td>
					<td><?php echo esc_html( $check['passed'] ? 'PASS' : 'BLOCKED' ); ?></td>
					<td><?php echo esc_html( $check['message'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<h3><?php esc_html_e( '只读结构统计 / Structure Summary', 'yby-core' ); ?></h3>
		<p>
			<?php echo esc_html( sprintf( 'Bricks elements: %d; Code/SVG elements: %d; Country mentions in Bricks data: %d',
				(int) $preflight['bricks_elements'], (int) $preflight['bricks_code_elements'], (int) $preflight['country_mentions'] ) ); ?>
		</p>
		<p><strong><?php esc_html_e( 'Bricks 元数据键（不显示敏感值）：', 'yby-core' ); ?></strong>
			<?php echo esc_html( implode( ', ', $preflight['bricks_keys'] ) ); ?>
		</p>
	<?php endif; ?>
</div>
