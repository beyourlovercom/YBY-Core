<?php
/**
 * Email OS legacy customizer governance panel.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$legacy_warnings = (array) ( $legacy_governance['warnings'] ?? array() );
$legacy_active = ! empty( $legacy_governance['active'] );
?>
<div class="yby-email-os__panel yby-email-os__legacy-governance">
	<div class="yby-email-os__panel-head">
		<h3><?php esc_html_e( 'Legacy Customizer 治理', 'yby-core' ); ?></h3>
		<span class="yby-email-os__status <?php echo empty( $legacy_warnings ) ? 'is-ready' : 'is-bridge'; ?>">
			<?php echo empty( $legacy_warnings ) ? esc_html__( 'Healthy', 'yby-core' ) : esc_html__( 'Review', 'yby-core' ); ?>
		</span>
	</div>
	<p><?php esc_html_e( '只读扫描 VillaTheme 的真实 Published 映射与 Rule Variants；不会禁用、重排、发布或修改 Legacy 模板。', 'yby-core' ); ?></p>
	<div class="yby-email-os__legacy-grid">
		<div><strong><?php echo $legacy_active ? esc_html__( 'Active', 'yby-core' ) : esc_html__( 'Not detected', 'yby-core' ); ?></strong><span>VillaTheme <?php echo esc_html( $legacy_governance['version'] ?? '' ); ?></span></div>
		<div><strong><?php echo absint( $legacy_governance['default_count'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Published Default', 'yby-core' ); ?></span></div>
		<div><strong><?php echo absint( $legacy_governance['rule_variant_types'] ?? 0 ); ?></strong><span><?php esc_html_e( 'Rule Variant 类型', 'yby-core' ); ?></span></div>
		<div><strong><?php echo count( (array) ( $legacy_governance['legacy_special_types'] ?? array() ) ); ?></strong><span><?php esc_html_e( 'Legacy 扩展类型', 'yby-core' ); ?></span></div>
	</div>
	<?php if ( ! empty( $legacy_governance['legacy_special_types'] ) ) : ?>
		<div class="yby-email-os__legacy-special"><strong><?php esc_html_e( 'VillaTheme 扩展类型', 'yby-core' ); ?></strong><p><?php esc_html_e( '这些类型由 VillaTheme 自身或已激活第三方插件正式注册，不属于孤儿映射。', 'yby-core' ); ?></p>
		<?php foreach ( (array) $legacy_governance['legacy_special_types'] as $legacy_type => $legacy_meta ) : ?><a href="<?php echo esc_url( $legacy_meta['editor_url'] ?? '' ); ?>"><code><?php echo esc_html( $legacy_type ); ?></code><span><?php echo absint( $legacy_meta['published_count'] ?? 0 ); ?> Published<?php echo ! empty( $legacy_meta['rule_count'] ) ? ' · Rule' : ''; ?></span></a><?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $legacy_warnings ) ) : ?>
		<div class="yby-email-os__legacy-warnings">
			<strong><?php esc_html_e( '需要人工检查', 'yby-core' ); ?></strong>
			<?php foreach ( $legacy_warnings as $warning ) : ?>
				<?php if ( 'multiple_default_templates' === $warning ) : ?>
					<p>• <?php esc_html_e( '存在多个 Published Default 模板；Email OS 不自动删除或调整优先级。', 'yby-core' ); ?></p>
				<?php elseif ( 0 === strpos( $warning, 'multiple_unconditional:' ) ) : ?>
					<p>• <?php echo esc_html( str_replace( 'multiple_unconditional:', '', $warning ) ); ?>：<?php esc_html_e( '同一邮件类型存在多个无条件 Published 模板，请检查优先级。', 'yby-core' ); ?></p>
				<?php elseif ( 'unmatched_published_types' === $warning ) : ?>
					<p>• <?php esc_html_e( '存在未被当前 Woo / VillaTheme / 已注册第三方声明的 Published 类型：', 'yby-core' ); ?> <code><?php echo esc_html( implode( ', ', (array) ( $legacy_governance['unmatched_types'] ?? array() ) ) ); ?></code></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="description"><?php esc_html_e( '当前未发现需要人工处理的 Legacy 映射异常。', 'yby-core' ); ?></p>
	<?php endif; ?>
	<p><a class="button button-small" href="<?php echo esc_url( $legacy_governance['list_url'] ?? '' ); ?>"><?php esc_html_e( '打开 VillaTheme 模板列表', 'yby-core' ); ?></a></p>
</div>
