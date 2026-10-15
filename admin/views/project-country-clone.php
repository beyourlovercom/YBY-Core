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

	<?php if ( is_array( $clone_result ) ) : ?>
		<div class="notice notice-success inline">
			<p><strong><?php esc_html_e( 'Germany 独立草稿已就绪 / Drafts Ready', 'yby-core' ); ?></strong>
				<?php echo esc_html( ! empty( $clone_result['reused'] ) ? '（已复用原草稿，未重复创建）' : '（已生成两个独立新草稿）' ); ?>
			</p>
			<p><a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $clone_result['project_id'] ) . '&action=edit' ) ); ?>"><?php echo esc_html( 'Project #' . absint( $clone_result['project_id'] ) ); ?></a>
				 | <a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $clone_result['page_id'] ) . '&action=edit' ) ); ?>"><?php echo esc_html( 'Landing Page #' . absint( $clone_result['page_id'] ) ); ?></a></p>
			<p><?php esc_html_e( '仅生成 Draft，不发布、不发送邮件、不启动 Google Ads。', 'yby-core' ); ?></p>
		</div>
	<?php elseif ( ! empty( $clone_error ) ) : ?>
		<div class="notice notice-error inline"><p><?php echo esc_html( $clone_error ); ?></p></div>
	<?php endif; ?>

	<?php if ( null !== $preflight ) : ?>
		<?php
		$can_dev_create = defined( 'YBY_COUNTRY_CLONE_V1_DEV_DRAFT_ENABLED' ) &&
			true === constant( 'YBY_COUNTRY_CLONE_V1_DEV_DRAFT_ENABLED' ) &&
			in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) &&
			in_array( strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ), array( 'localdev.ybyirrigation.com', 'dev.ybyirrigation.com' ), true ) &&
			518 === $post_id && 292 === $submitted_landing_id &&
			! empty( $preflight['source_fingerprint'] );
			$can_production_create = defined( 'YBY_COUNTRY_CLONE_V1_PRODUCTION_DRAFT_ENABLED' ) &&
				true === constant( 'YBY_COUNTRY_CLONE_V1_PRODUCTION_DRAFT_ENABLED' ) &&
				'production' === wp_get_environment_type() &&
				'ybyirrigation.com' === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) &&
				! empty( $preflight['source_fingerprint'] );
		?>
		<?php if ( $can_dev_create || $can_production_create ) : ?>
			<form method="post" action="<?php echo esc_url( YBY_Project_Studio::studio_url( 'clone', $post_id ) ); ?>">
				<?php wp_nonce_field( 'yby_country_clone_create_' . $post_id ); ?>
				<input type="hidden" name="source_project_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
				<input type="hidden" name="source_landing_id" value="<?php echo esc_attr( (string) $submitted_landing_id ); ?>">
				<input type="hidden" name="source_fingerprint" value="<?php echo esc_attr( $preflight['source_fingerprint'] ); ?>">
				<p class="description"><?php esc_html_e( '本按钮仅在环境专属开关明确授权时生成或打开 Germany Draft；不会发布页面或启动广告。源数据、签名、权限和回滚检查仍须通过。', 'yby-core' ); ?></p>
				<?php submit_button( __( '生成 / 打开 Germany 草稿（受控）', 'yby-core' ), 'primary', 'yby_country_clone_create', false ); ?>
			</form>
		<?php endif; ?>
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
		<?php if ( ! empty( $preflight['transform_plan'] ) ) : ?>
			<h3><?php esc_html_e( '国家字段分类 / Country Transform Review', 'yby-core' ); ?></h3>
			<table class="widefat striped">
				<tbody>
				<?php foreach ( array(
					'form_country_defaults' => '表单国家默认值 / Form Country Inputs',
					'country_pills' => '国家选择按钮 / Country Pills',
					'scenario_headings' => '项目案例标题（需人工核实） / Scenario Country',
					'unclassified_code_refs' => '未分类国家引用（阻断） / Unclassified References',
				) as $code => $label ) : ?>
				<tr><th><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( (string) (int) ( $preflight['transform_plan'][ $code ] ?? 0 ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<h3><?php esc_html_e( 'Bricks 页面级脚本与国家选择器 / Page-level Scripts & Country Selectors', 'yby-core' ); ?></h3>
		<table class="widefat striped">
			<tbody>
				<tr><th><?php esc_html_e( '页面脚本源国家引用', 'yby-core' ); ?></th><td><?php echo esc_html( (string) (int) $preflight['page_script_country_mentions'] ); ?></td></tr>
				<tr><th><?php esc_html_e( '页面脚本 PROJECT_SCENARIOS 引用', 'yby-core' ); ?></th><td><?php echo esc_html( (string) (int) $preflight['page_script_scenario_mentions'] ); ?></td></tr>
				<tr><th><?php esc_html_e( '国家选择器组数', 'yby-core' ); ?></th><td><?php echo esc_html( (string) (int) $preflight['country_selector_groups'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Germany 国家按钮数', 'yby-core' ); ?></th><td><?php echo esc_html( (string) (int) $preflight['target_country_buttons'] ); ?></td></tr>
			</tbody>
		</table>
		<p><strong><?php esc_html_e( 'Bricks 元数据键（不显示敏感值）：', 'yby-core' ); ?></strong>
			<?php echo esc_html( implode( ', ', $preflight['bricks_keys'] ) ); ?>
		</p>
	<?php endif; ?>
</div>
