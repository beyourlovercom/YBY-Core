<?php
/**
 * Docs OS Editor.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$post_id = $data['post_id'];
$editor_url = add_query_arg(
	array_filter( array( 'page' => YBY_Docs_OS_Admin::EDITOR_PAGE_SLUG, 'post_id' => $post_id ?: null ) ),
	admin_url( 'admin.php' )
);
?>
<div class="wrap yby-docs-dashboard yby-docs-editor-page">
	<div class="yby-docs-head">
		<div class="yby-docs-title-row">
			<h1>Andy Core <span>v<?php echo esc_html( YBY_CORE_VERSION ); ?></span></h1>
			<i></i><strong>Docs OS</strong><small><?php echo $post_id ? '编辑文档' : '新建文档'; ?></small>
		</div>
		<div class="yby-docs-head-actions">
			<a class="button yby-outline-button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::ALL_PAGE_SLUG ) ); ?>">← 返回全部文档</a>
		</div>
	</div>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible yby-editor-notice"><p>文档已保存。Post ID、Slug、分类关系和原始内容模型保持不变。</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $editor_url ); ?>" class="yby-docs-editor-form" data-yby-docs-editor-form>
		<?php wp_nonce_field( 'yby_docs_editor_save', 'yby_docs_editor_nonce' ); ?>
		<input type="hidden" name="yby_docs_editor_submit" value="1">

		<div class="yby-editor-grid">
			<main class="yby-editor-main">
				<section class="yby-panel yby-editor-title-card">
					<label for="yby-docs-title">标题 <span>*</span></label>
					<input id="yby-docs-title" type="text" name="yby_docs_title" value="<?php echo esc_attr( $data['title'] ); ?>" placeholder="输入文档标题…" required>
					<div class="yby-slug-row">
						<label for="yby-docs-slug">Slug</label>
						<input id="yby-docs-slug" type="text" name="yby_docs_slug" value="<?php echo esc_attr( $data['slug'] ); ?>" placeholder="document-slug">
					</div>
					<p class="yby-permalink-preview">永久链接：<a target="_blank" rel="noopener" href="<?php echo esc_url( $data['permalink'] ); ?>"><?php echo esc_html( $data['permalink'] ); ?></a></p>
				</section>
				<section class="yby-panel yby-editor-content-card">
					<div class="yby-editor-mode-tabs" role="tablist">
						<button type="button" class="is-active" data-yby-editor-mode="visual">可视化编辑</button>
						<button type="button" data-yby-editor-mode="html">HTML 源码</button>
						<button type="button" data-yby-editor-mode="preview">预览</button>
					</div>
					<div class="yby-editor-native" data-yby-editor-native>
						<?php
						wp_editor(
							$data['content'],
							'yby_docs_content_editor',
							array(
								'textarea_name' => 'yby_docs_content',
								'textarea_rows' => 24,
								'media_buttons' => true,
								'tinymce' => array( 'wpautop' => false ),
								'quicktags' => true,
							)
						);
						?>
					</div>
					<div class="yby-editor-preview" data-yby-editor-preview hidden>
						<iframe title="Docs HTML Preview" sandbox="allow-same-origin" data-yby-editor-preview-frame></iframe>
					</div>
					<div class="yby-html-contract">
						<strong>HTML Contract</strong>
						<span>支持正文 HTML、表格、链接、图片和 HTTPS iframe；R2 绝对地址原样保存。不允许 html / head / body / script 标签。</span>
					</div>
				</section>
			</main>
			<aside class="yby-editor-side">
				<section class="yby-panel yby-editor-publish-card">
					<header><h2>发布</h2></header>
					<div class="yby-editor-card-body">
						<label for="yby-docs-status">状态</label>
						<select id="yby-docs-status" name="yby_docs_status">
							<option value="draft" <?php selected( $data['status'], 'draft' ); ?>>草稿</option>
							<option value="publish" <?php selected( $data['status'], 'publish' ); ?>>已发布</option>
						</select>
						<div class="yby-editor-publish-meta">
							<span>Post ID</span><strong><?php echo $post_id ? esc_html( (string) $post_id ) : '保存后生成'; ?></strong>
						</div>
						<div class="yby-editor-publish-meta">
							<span>SEO Score</span><strong><?php echo esc_html( (string) $data['seo_score'] ); ?></strong>
						</div>
						<div class="yby-editor-publish-actions">
							<button type="submit" class="button yby-save-draft" data-yby-save-draft>保存草稿</button>
							<?php if ( $post_id ) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $data['preview_url'] ); ?>">前台预览</a><?php endif; ?>
							<button type="submit" class="button button-primary yby-update-doc"><?php echo 'publish' === $data['status'] ? '更新' : '保存'; ?></button>
						</div>
					</div>
				</section>
				<section class="yby-panel yby-editor-tax-card">
					<header><h2>分类</h2></header>
					<div class="yby-editor-card-body yby-checkbox-list">
						<?php foreach ( $data['categories'] as $term ) : ?>
							<label>
								<input type="checkbox" name="yby_docs_categories[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php checked( in_array( (int) $term->term_id, $data['selected_categories'], true ) ); ?>>
								<span><?php echo esc_html( $term->name ); ?></span>
								<small><?php echo esc_html( (string) $term->count ); ?></small>
							</label>
						<?php endforeach; ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG ) ); ?>">＋ 新建 / 管理分类</a>
					</div>
				</section>

				<section class="yby-panel yby-editor-tags-card">
					<header><h2>Tags</h2></header>
					<div class="yby-editor-card-body">
						<input type="text" name="yby_docs_tags" value="<?php echo esc_attr( $data['tags'] ); ?>" placeholder="tracking, order, shipping">
						<p class="description">多个 Tags 使用英文逗号分隔。</p>
					</div>
				</section>
				<section class="yby-panel yby-editor-r2-card">
					<header><h2>特色图片（R2）</h2><span class="yby-connected <?php echo $data['r2_domain'] ? '' : 'is-off'; ?>"><i></i><?php echo $data['r2_domain'] ? 'Connected' : 'Not configured'; ?></span></header>
					<div class="yby-editor-card-body">
						<?php if ( $data['r2_image'] ) : ?>
							<div class="yby-r2-image-preview"><img src="<?php echo esc_url( $data['r2_image'] ); ?>" alt=""></div>
						<?php endif; ?>
						<label for="yby-docs-r2-image">图片 URL（R2）</label>
						<input id="yby-docs-r2-image" type="url" name="yby_docs_featured_r2_url" value="<?php echo esc_attr( $data['r2_image'] ); ?>" placeholder="<?php echo esc_attr( rtrim( $data['r2_domain'], '/' ) . '/docs/image.webp' ); ?>">
						<p class="description">直接使用 R2 绝对地址，不重新上传到 WordPress。</p>
					</div>
				</section>

				<section class="yby-panel yby-editor-related-card">
					<header><h2>Related Docs</h2><span><?php echo esc_html( (string) count( $data['related_selected'] ) ); ?>/6</span></header>
					<div class="yby-editor-card-body yby-related-list">
						<?php foreach ( $data['related_candidates'] as $related ) : ?>
							<label><input type="checkbox" name="yby_docs_related[]" value="<?php echo esc_attr( (string) $related->ID ); ?>" <?php checked( in_array( (int) $related->ID, $data['related_selected'], true ) ); ?>><span><?php echo esc_html( get_the_title( $related ) ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</section>
				<section class="yby-panel yby-editor-seo-card">
					<header><h2>SEO 设置</h2><span class="yby-seo-score <?php echo $data['seo_score'] >= 80 ? 'is-good' : ( $data['seo_score'] >= 60 ? 'is-ok' : 'is-low' ); ?>"><?php echo esc_html( (string) $data['seo_score'] ); ?></span></header>
					<div class="yby-editor-card-body yby-seo-fields">
						<label>SEO 标题<input type="text" name="yby_docs_seo_title" value="<?php echo esc_attr( $data['seo_title'] ); ?>" placeholder="<?php echo esc_attr( $data['title'] ); ?>"></label>
						<label>SEO 描述<textarea name="yby_docs_seo_description" rows="3"><?php echo esc_textarea( $data['seo_description'] ); ?></textarea></label>
						<label>焦点关键词<input type="text" name="yby_docs_focus_keyword" value="<?php echo esc_attr( $data['focus_keyword'] ); ?>"></label>
						<small>直接读取 / 保存 Rank Math meta，不创建第二套 SEO 数据。</small>
					</div>
				</section>
			</aside>
		</div>
	</form>
</div>
