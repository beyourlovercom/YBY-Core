<?php
/**
 * Docs OS All Docs workbench.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$query = $data['query'];
?>
<div class="wrap yby-docs-dashboard yby-docs-list-page">
	<div class="yby-docs-head">
		<div class="yby-docs-title-row">
			<h1>Andy Core <span>v<?php echo esc_html( YBY_CORE_VERSION ); ?></span></h1>
			<i></i><strong>Docs OS</strong><small>全部文档</small>
		</div>
		<div class="yby-docs-head-actions">
			<a class="yby-primary-button" href="<?php echo esc_url( $this->editor_url() ); ?>">＋ 新建文档</a>
		</div>
	</div>

	<section class="yby-panel yby-all-docs-panel">
		<header><div><h2>全部文档</h2><p>管理所有帮助中心文档，支持搜索、筛选和批量状态操作。</p></div></header>
		<div class="yby-doc-view-tabs">
			<a class="<?php echo '' === $data['status'] && '' === $data['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::ALL_PAGE_SLUG ) ); ?>">全部 (<?php echo esc_html( (string) $data['counts']['all'] ); ?>)</a>
			<a class="<?php echo 'publish' === $data['status'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::ALL_PAGE_SLUG, 'post_status' => 'publish' ), admin_url( 'admin.php' ) ) ); ?>">已发布 (<?php echo esc_html( (string) $data['counts']['publish'] ); ?>)</a>
			<a class="<?php echo 'draft' === $data['status'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::ALL_PAGE_SLUG, 'post_status' => 'draft' ), admin_url( 'admin.php' ) ) ); ?>">草稿 (<?php echo esc_html( (string) $data['counts']['draft'] ); ?>)</a>
			<a class="<?php echo 'faq' === $data['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::ALL_PAGE_SLUG, 'content_view' => 'faq' ), admin_url( 'admin.php' ) ) ); ?>">FAQ (<?php echo esc_html( (string) $data['counts']['faq'] ); ?>)</a>
			<a class="<?php echo 'tutorial' === $data['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::ALL_PAGE_SLUG, 'content_view' => 'tutorial' ), admin_url( 'admin.php' ) ) ); ?>">Tutorial (<?php echo esc_html( (string) $data['counts']['tutorial'] ); ?>)</a>
		</div>
		<form class="yby-doc-filters" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( YBY_Docs_OS_Admin::ALL_PAGE_SLUG ); ?>">
			<select name="doc_type"><option value="">全部类型</option><option selected>Docs</option></select>
			<select name="doc_category">
				<option value="">全部分类</option>
				<?php foreach ( $data['categories'] as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $data['category'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="post_status">
				<option value="">全部状态</option>
				<option value="publish" <?php selected( $data['status'], 'publish' ); ?>>已发布</option>
				<option value="draft" <?php selected( $data['status'], 'draft' ); ?>>草稿</option>
			</select>
			<div class="yby-filter-search"><input type="search" name="s" value="<?php echo esc_attr( $data['search'] ); ?>" placeholder="搜索文档标题…"><button class="button button-primary">搜索</button></div>
		</form>

		<form method="post" class="yby-bulk-form">
			<?php wp_nonce_field( 'yby_docs_bulk_action', 'yby_docs_bulk_nonce' ); ?>
			<div class="yby-table-wrap">
			<table class="yby-docs-table yby-all-docs-table">
				<thead>
					<tr><th class="check-column"><input type="checkbox" data-yby-select-all></th><th>标题</th><th>类型</th><th>分类</th><th>标签</th><th>状态</th><th>SEO</th><th>图片</th><th>更新时间</th><th>操作</th></tr>
				</thead>
				<tbody>
				<?php if ( $query->have_posts() ) : foreach ( $query->posts as $doc ) : ?>
					<?php
					$score = (int) get_post_meta( $doc->ID, 'rank_math_seo_score', true );
					$tag_names = wp_get_post_terms( $doc->ID, 'doc_tag', array( 'fields' => 'names' ) );
					$tag_names = is_wp_error( $tag_names ) ? array() : array_slice( $tag_names, 0, 3 );
					$image_url = esc_url_raw( get_post_meta( $doc->ID, 'yby_docs_featured_r2_url', true ) );
					if ( ! $image_url ) { $image_url = get_the_post_thumbnail_url( $doc->ID, 'thumbnail' ); }
					?>
					<tr>
						<td class="check-column"><input type="checkbox" name="doc_ids[]" value="<?php echo esc_attr( (string) $doc->ID ); ?>" data-yby-doc-select></td>
						<td class="yby-doc-title">
							<span class="yby-doc-thumb is-empty"><span class="dashicons dashicons-media-document"></span></span>
							<div><a href="<?php echo esc_url( $this->editor_url( $doc->ID ) ); ?>"><?php echo esc_html( get_the_title( $doc ) ); ?></a><small>#<?php echo esc_html( (string) $doc->ID ); ?> · /<?php echo esc_html( $doc->post_name ); ?>/</small></div>
						</td>
						<td>Docs</td>
						<td><?php echo esc_html( $this->primary_category_name( $doc->ID ) ); ?></td>
						<td class="yby-tag-cell"><?php if ( $tag_names ) : foreach ( $tag_names as $tag_name ) : ?><span><?php echo esc_html( $tag_name ); ?></span><?php endforeach; else : ?>—<?php endif; ?></td>
						<td><span class="yby-status <?php echo 'publish' === $doc->post_status ? 'is-published' : 'is-draft'; ?>"><?php echo 'publish' === $doc->post_status ? '已发布' : '草稿'; ?></span></td>
						<td><span class="yby-seo-score <?php echo $score >= 80 ? 'is-good' : ( $score >= 60 ? 'is-ok' : 'is-low' ); ?>"><?php echo esc_html( (string) $score ); ?></span></td>
						<td><?php if ( $image_url ) : ?><img class="yby-list-thumb" src="<?php echo esc_url( $image_url ); ?>" alt=""><?php else : ?>—<?php endif; ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $doc->post_modified_gmt, 'Y-m-d H:i' ) ); ?></td>
						<td class="yby-actions">
							<a href="<?php echo esc_url( $this->editor_url( $doc->ID ) ); ?>">编辑</a>
							<a target="_blank" rel="noopener" href="<?php echo esc_url( 'publish' === $doc->post_status ? get_permalink( $doc ) : get_preview_post_link( $doc ) ); ?>">预览</a>
							<a href="#" class="yby-copy-link" data-copy="<?php echo esc_attr( get_permalink( $doc ) ); ?>">复制链接</a>
						</td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="10" class="yby-empty-row">没有符合筛选条件的文档。</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="yby-list-footer">
			<div class="yby-bulk-controls"><select name="bulk_action"><option value="">批量操作</option><option value="publish">设为已发布</option><option value="draft">设为草稿</option></select><button type="submit" class="button" name="yby_docs_bulk_submit" value="1">应用</button></div>
			<span>共 <?php echo esc_html( (string) $query->found_posts ); ?> 条</span>
			<div class="yby-pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base' => add_query_arg( 'paged', '%#%' ),
							'format' => '',
							'current' => $data['paged'],
							'total' => max( 1, (int) $query->max_num_pages ),
							'prev_text' => '‹',
							'next_text' => '›',
						)
					)
				);
				?>
			</div>
			<span>每页 10 条</span>
		</div>
		</form>
	</section>
</div>
