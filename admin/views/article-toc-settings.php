<?php
/**
 * Article TOC settings.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$modules_url = add_query_arg(
	array(
		'page' => YBY_Helpers::admin_page_slug(),
		'tab' => 'modules',
	),
	admin_url( 'admin.php' )
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Andy Core', 'yby-core' ); ?></h1>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( YBY_Admin::settings_tabs() as $key => $label ) : ?>
			<a class="nav-tab <?php echo 'article-toc' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</h2>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<div class="card" style="max-width:900px;margin-top:20px;">
		<h2><?php esc_html_e( '文章目录', 'yby-core' ); ?></h2>
		<p><?php esc_html_e( '自动为博客文章生成 Inline Summary 与桌面悬浮目录，并提供 H2 锚点和 Scroll Spy。', 'yby-core' ); ?></p>
		<p>
			<strong><?php esc_html_e( '模块状态：', 'yby-core' ); ?></strong>
			<?php echo $module_enabled ? esc_html__( '已开启', 'yby-core' ) : esc_html__( '已关闭', 'yby-core' ); ?>
			<a href="<?php echo esc_url( $modules_url ); ?>" style="margin-left:10px;"><?php esc_html_e( '前往模块管理', 'yby-core' ); ?></a>
		</p>
		<?php if ( ! $module_enabled ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( '当前模块为 OFF。设置会保留，但前台不会 boot、不会生成目录，也不会加载 Article TOC CSS/JS。', 'yby-core' ); ?></p></div>
		<?php endif; ?>
	</div>

	<form method="post" style="max-width:900px;">
		<?php wp_nonce_field( 'yby_article_toc_settings_save', 'yby_article_toc_settings_nonce' ); ?>
		<input type="hidden" name="yby_article_toc_settings_submit" value="1">

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-article-toc-title"><?php esc_html_e( '目录标题', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-article-toc-title" class="regular-text" type="text" name="yby_article_toc_settings[inline_title]" value="<?php echo esc_attr( $options['inline_title'] ); ?>">
						<p class="description"><?php esc_html_e( '默认：Summary。Inline 与 Floating TOC 使用同一标题。', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-article-toc-min-h2"><?php esc_html_e( '最少 H2 数量', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-article-toc-min-h2" type="number" min="2" max="12" step="1" name="yby_article_toc_settings[min_h2_count]" value="<?php echo esc_attr( $options['min_h2_count'] ); ?>">
						<p class="description"><?php esc_html_e( '默认 2。正文 H2 少于此数量时不生成目录。', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-article-toc-breakpoint"><?php esc_html_e( '桌面悬浮目录断点', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-article-toc-breakpoint" type="number" min="900" max="1920" step="10" name="yby_article_toc_settings[floating_breakpoint]" value="<?php echo esc_attr( $options['floating_breakpoint'] ); ?>"> px
						<p class="description"><?php esc_html_e( '默认 1200px。低于断点时隐藏 Floating TOC，但保留 Inline Summary。', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post Type', 'yby-core' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="yby_article_toc_settings[post_types][]" value="post" <?php checked( in_array( 'post', $options['post_types'], true ) ); ?>>
							<?php esc_html_e( 'Posts / 文章', 'yby-core' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'V1 默认且仅支持 WordPress Posts。Page、Landing Page、Andy Docs、Product 均不启用。', 'yby-core' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( '保存文章目录设置', 'yby-core' ); ?></button></p>
	</form>
</div>
