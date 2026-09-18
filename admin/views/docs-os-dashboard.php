<?php
/**
 * Docs OS dashboard view.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cards = array(
	array( 'media-document', $data['docs_total'], '全部文档', 'green' ),
	array( 'yes-alt', $data['published'], '已发布', 'green' ),
	array( 'edit', $data['drafts'], '草稿', 'amber' ),
	array( 'category', $data['categories'], '分类', 'blue' ),
	array( 'tag', $data['tags'], 'Tags', 'purple' ),
	array( 'editor-help', $data['faq'], 'FAQ', 'blue' ),
	array( 'welcome-learn-more', $data['tutorials'], 'Tutorial', 'purple' ),
);
?>
<div class="wrap yby-docs-dashboard">
	<div class="yby-docs-head">
		<div class="yby-docs-title-row">
			<h1>Andy Core <span>v<?php echo esc_html( YBY_CORE_VERSION ); ?></span></h1>
			<i></i><strong>Docs OS</strong><small>Build a Help Center People Love</small>
		</div>
		<div class="yby-docs-head-actions">
			<a class="button yby-outline-button" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/' ) ); ?>">↗ 查看站点</a>
			<a class="yby-head-link" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">? 帮助文档</a>
		</div>
	</div>

	<section class="yby-docs-hero">
		<div class="yby-hero-mark" aria-hidden="true"><span>◆</span></div>
		<div class="yby-hero-copy">
			<h2>打造简单、清晰、好用的帮助中心 <span>🌱</span></h2>
			<p>让客户快速找到答案，减少重复咨询，提升转化。</p>
		</div>
		<a class="yby-primary-button" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">预览 Docs 首页 <span>→</span></a>
	</section>

	<section class="yby-metric-grid">
		<?php foreach ( $cards as $card ) : ?>
			<div class="yby-metric-card">
				<div class="yby-metric-icon <?php echo esc_attr( 'is-' . $card[3] ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $card[0] ); ?>"></span></div>
				<div><strong><?php echo esc_html( (string) $card[1] ); ?></strong><span><?php echo esc_html( $card[2] ); ?></span></div>
			</div>
		<?php endforeach; ?>
	</section>

	<div class="yby-dashboard-grid">
		<div class="yby-dashboard-main">
			<section class="yby-panel yby-recent-panel">
				<header><h2>最近更新的文档</h2><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=docs' ) ); ?>">查看全部 →</a></header>
				<div class="yby-table-wrap">
					<table class="yby-docs-table">
						<thead><tr><th>标题</th><th>类型</th><th>分类</th><th>状态</th><th>更新时间</th><th>操作</th></tr></thead>
						<tbody>
						<?php foreach ( $data['recent'] as $doc ) : ?>
							<tr>
								<td class="yby-doc-title">
									<?php
									$thumb = get_the_post_thumbnail( $doc->ID, array( 34, 34 ), array( 'class' => 'yby-doc-thumb' ) );
									echo $thumb ? wp_kses_post( $thumb ) : '<span class="yby-doc-thumb is-empty"><span class="dashicons dashicons-media-document"></span></span>';
									?>
									<a href="<?php echo esc_url( $this->editor_url( $doc->ID ) ); ?>"><?php echo esc_html( get_the_title( $doc ) ); ?></a>
								</td>
								<td>Docs</td>
								<td><?php echo esc_html( $this->primary_category_name( $doc->ID ) ); ?></td>
								<td><span class="yby-status <?php echo 'publish' === $doc->post_status ? 'is-published' : 'is-draft'; ?>"><?php echo 'publish' === $doc->post_status ? '已发布' : '草稿'; ?></span></td>
								<td><?php echo esc_html( get_date_from_gmt( $doc->post_modified_gmt, 'Y-m-d H:i' ) ); ?></td>
								<td class="yby-actions"><a href="<?php echo esc_url( $this->editor_url( $doc->ID ) ); ?>">编辑</a><a target="_blank" rel="noopener" href="<?php echo esc_url( 'publish' === $doc->post_status ? get_permalink( $doc ) : get_preview_post_link( $doc ) ); ?>">预览</a><span>•••</span></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<div class="yby-lower-grid">
				<section class="yby-panel yby-category-panel">
					<header><h2>文档分类分布</h2></header>
					<div class="yby-category-body">
						<div class="yby-donut" style="<?php echo esc_attr( $data['donut_style'] ); ?>"><div><strong><?php echo esc_html( (string) $data['docs_total'] ); ?></strong><span>全部文档</span></div></div>
						<div class="yby-category-legend">
							<?php foreach ( $data['category_distribution'] as $index => $term ) : ?>
								<div><i class="dot dot-<?php echo esc_attr( (string) ( $index % 7 ) ); ?>"></i><span><?php echo esc_html( $term->name ); ?></span><strong><?php echo esc_html( (string) $term->count ); ?></strong></div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="yby-panel yby-trend-panel">
					<header><h2>最近 7 天访问趋势</h2><a href="<?php echo esc_url( admin_url( 'admin.php?page=betterdocs-analytics' ) ); ?>">查看详细分析 →</a></header>
					<?php $this->render_trend_chart( $data['trend'] ); ?>
				</section>
			</div>
		</div>

		<aside class="yby-dashboard-side">
			<section class="yby-panel yby-quick-panel">
				<header><h2>快速操作</h2><a class="yby-new-doc" href="<?php echo esc_url( $this->editor_url() ); ?>">＋ 新建文档</a></header>
				<div class="yby-quick-grid">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG ) ); ?>"><span class="dashicons dashicons-category"></span>管理分类</a>
					<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=doc_tag&post_type=docs' ) ); ?>"><span class="dashicons dashicons-tag"></span>管理 Tags</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=yby-docs-faq' ) ); ?>"><span class="dashicons dashicons-editor-help"></span>新建 FAQ</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=yby-docs-tutorial' ) ); ?>"><span class="dashicons dashicons-welcome-learn-more"></span>新建 Tutorial</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::SETTINGS_PAGE_SLUG ) ); ?>"><span class="dashicons dashicons-admin-generic"></span>Docs 设置</a>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/docs/' ) ); ?>"><span class="dashicons dashicons-migrate"></span>预览 Docs 首页</a>
				</div>
			</section>

			<section class="yby-panel yby-r2-panel">
				<header><h2>R2 资源状态</h2><span class="yby-connected <?php echo $data['r2_connected'] ? '' : 'is-off'; ?>"><i></i><?php echo $data['r2_connected'] ? 'Connected' : 'Not configured'; ?></span></header>
				<dl>
					<div><dt>↑ 资源域名</dt><dd><?php echo $data['r2_domain'] ? '<a target="_blank" rel="noopener" href="' . esc_url( $data['r2_domain'] ) . '">' . esc_html( $data['r2_domain'] ) . '</a>' : '—'; ?></dd></div>
					<div><dt>● Broken Assets</dt><dd><strong><?php echo esc_html( (string) $data['broken_assets'] ); ?></strong></dd></div>
					<div><dt>◷ 最后检查</dt><dd><?php echo esc_html( current_time( 'Y-m-d H:i' ) ); ?></dd></div>
				</dl>
				<a class="button yby-check-button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::PAGE_SLUG ) ); ?>">检查资源</a>
			</section>

			<section class="yby-panel yby-system-panel">
				<header><h2>系统状态</h2></header>
				<div class="yby-system-row"><span><i class="dashicons dashicons-wordpress"></i> WordPress</span><b><?php echo esc_html( get_bloginfo( 'version' ) ); ?></b><em>正常</em></div>
				<div class="yby-system-row"><span><i class="dashicons dashicons-welcome-learn-more"></i> Andy Core</span><b>v<?php echo esc_html( YBY_CORE_VERSION ); ?></b><em>正常</em></div>
				<div class="yby-system-row"><span><i class="dashicons dashicons-database"></i> 数据库</span><b>Connected</b><em>正常</em></div>
			</section>
		</aside>
	</div>

	<footer class="yby-docs-footer">
		<div><strong>Andy Core v<?php echo esc_html( YBY_CORE_VERSION ); ?></strong><span>|</span><strong>Docs OS</strong><span>Build a Help Center People Love</span></div>
		<small>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Beyourlover. All rights reserved.</small>
	</footer>
</div>
