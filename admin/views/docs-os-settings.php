<?php
/**
 * Docs OS settings.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$s = $data['settings'];
$tabs = array(
	'basic' => array( 'admin-generic', '基础设置' ),
	'directory' => array( 'category', '目录设置' ),
	'r2' => array( 'cloud', 'R2 设置' ),
	'seo' => array( 'chart-area', 'SEO & 结构化数据' ),
	'display' => array( 'visibility', '显示设置' ),
	'advanced' => array( 'admin-tools', '高级' ),
);
?>
<div class="wrap yby-docs-dashboard yby-settings-page">
	<div class="yby-docs-head">
		<div class="yby-docs-title-row"><h1>设置</h1><small>配置 Docs OS 的各项功能和显示效果</small></div>
		<div class="yby-docs-head-actions">
			<a class="button yby-outline-button" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">↗ 查看站点</a>
		</div>
	</div>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible yby-editor-notice"><p>Docs OS 设置已保存。</p></div>
	<?php endif; ?>

	<nav class="yby-settings-tabs">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<a class="<?php echo $data['tab'] === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::SETTINGS_PAGE_SLUG, 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>">
				<span class="dashicons dashicons-<?php echo esc_attr( $tab[0] ); ?>"></span><?php echo esc_html( $tab[1] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" class="yby-settings-form">
		<?php wp_nonce_field( 'yby_docs_settings_save', 'yby_docs_settings_nonce' ); ?>
		<input type="hidden" name="yby_docs_settings_submit" value="1">
		<input type="hidden" name="settings_tab" value="<?php echo esc_attr( $data['tab'] ); ?>">
		<?php if ( 'basic' === $data['tab'] ) : ?>
			<div class="yby-settings-layout">
				<section class="yby-panel yby-settings-card">
					<header><h2>站点信息</h2></header>
					<div class="yby-settings-fields">
						<label>Docs 首页标题
							<input type="text" name="site_title" value="<?php echo esc_attr( $s['site_title'] ); ?>">
						</label>
						<label>Docs 首页描述
							<textarea name="site_description" rows="3"><?php echo esc_textarea( $s['site_description'] ); ?></textarea>
						</label>
						<label>Docs Base URL
							<input type="text" value="/<?php echo esc_attr( $s['base_path'] ); ?>" readonly>
							<small>v1.6.0 固定为 /docs；避免与现有 BetterDocs permalink 冲突。</small>
						</label>
						<input type="hidden" name="module_control_present" value="1">
						<label class="yby-toggle-row"><span><strong>启用 Docs OS</strong><small>关闭后隐藏 Docs Runtime / 菜单 / Assets；文档数据不会删除。</small></span><input type="checkbox" name="module_enabled" value="1" <?php checked( $data['module_enabled'] ); ?>><i></i></label>
					</div>
				</section>
				<section class="yby-panel yby-settings-card">
					<header><h2>首页展示选项</h2></header>
					<div class="yby-settings-fields">
						<label>首页布局
							<select name="home_layout"><option value="cards" <?php selected( $s['home_layout'], 'cards' ); ?>>卡片网格</option><option value="list" <?php selected( $s['home_layout'], 'list' ); ?>>列表</option></select>
						</label>
						<label>首页显示分类数
							<select name="category_limit"><?php foreach ( array( 4, 6, 8, 12, 16, 24 ) as $n ) : ?><option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( (int) $s['category_limit'], $n ); ?>><?php echo esc_html( (string) $n ); ?></option><?php endforeach; ?></select>
						</label>
						<label>每个分类显示文档数
							<select name="docs_per_category"><?php foreach ( array( 3, 4, 6, 8, 12, 24 ) as $n ) : ?><option value="<?php echo esc_attr( (string) $n ); ?>" <?php selected( (int) $s['docs_per_category'], $n ); ?>><?php echo esc_html( (string) $n ); ?></option><?php endforeach; ?></select>
						</label>
						<label>排序方式
							<select name="sort"><option value="doc_count_desc" <?php selected( $s['sort'], 'doc_count_desc' ); ?>>按文档数量（从高到低）</option><option value="manual" <?php selected( $s['sort'], 'manual' ); ?>>按分类目录排序</option><option value="name" <?php selected( $s['sort'], 'name' ); ?>>按名称</option></select>
						</label>
						<?php foreach ( array( 'show_search' => '显示搜索框', 'show_categories' => '显示热门分类', 'show_recent' => '显示最新文档' ) as $key => $label ) : ?>
							<label class="yby-toggle-row compact"><span><strong><?php echo esc_html( $label ); ?></strong></span><input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $s[ $key ] ) ); ?>><i></i></label>
						<?php endforeach; ?>
					</div>
				</section>
				<section class="yby-panel yby-settings-preview">
					<header><h2>预览效果</h2></header>
					<div class="yby-mini-docs-preview">
						<div class="yby-mini-cover"><div class="yby-mini-search">⌕ 搜索帮助文档...</div></div>
						<div class="yby-mini-grid">
							<?php
							$preview_terms = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => true, 'number' => 4, 'orderby' => 'count', 'order' => 'DESC' ) );
							if ( is_wp_error( $preview_terms ) ) { $preview_terms = array(); }
							foreach ( $preview_terms as $term ) :
							?>
								<div><span class="dashicons dashicons-category"></span><strong><?php echo esc_html( $term->name ); ?></strong><small><?php echo esc_html( (string) $term->count ); ?> 篇文档</small></div>
							<?php endforeach; ?>
						</div>
					</div>
					<a class="yby-preview-link" target="_blank" rel="noopener" href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">查看完整预览 →</a>
				</section>
			</div>
		<?php elseif ( 'directory' === $data['tab'] ) : ?>
			<section class="yby-panel yby-settings-single">
				<header><h2>目录设置</h2><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG ) ); ?>">打开分类目录 →</a></header>
				<div class="yby-settings-fields two-col">
					<label>首页显示分类数<input type="number" min="1" max="24" name="category_limit" value="<?php echo esc_attr( (string) $s['category_limit'] ); ?>"></label>
					<label>每个分类显示文档数<input type="number" min="1" max="24" name="docs_per_category" value="<?php echo esc_attr( (string) $s['docs_per_category'] ); ?>"></label>
					<label>分类排序
						<select name="sort"><option value="manual" <?php selected( $s['sort'], 'manual' ); ?>>目录手动排序</option><option value="doc_count_desc" <?php selected( $s['sort'], 'doc_count_desc' ); ?>>文档数量</option><option value="name" <?php selected( $s['sort'], 'name' ); ?>>名称</option></select>
					</label>
					<div class="yby-contract-note"><strong>默认页面生成</strong><p>/docs/ 与分类目录页由 Docs Runtime 自动生成，不创建额外 WordPress Page。</p></div>
				</div>
			</section>
		<?php elseif ( 'r2' === $data['tab'] ) : ?>
			<section class="yby-panel yby-settings-single">
				<header><h2>R2 设置</h2><span class="yby-connected <?php echo ! empty( $data['r2']['domain'] ) ? '' : 'is-off'; ?>"><i></i><?php echo ! empty( $data['r2']['domain'] ) ? 'Connected' : 'Not configured'; ?></span></header>
				<div class="yby-settings-fields">
					<label>Provider<input type="text" value="Cloudflare R2" readonly></label>
					<label>公开资源域名<input type="text" value="<?php echo esc_attr( $data['r2']['domain'] ?? '' ); ?>" readonly></label>
					<div class="yby-contract-note"><strong>R2 Contract</strong><p>Docs OS 读取 Advanced Media Offloader 的现有 R2 配置，不保存第二份账号、Bucket 或密钥。文章 HTML 中的 R2 绝对 URL 原样保留。</p></div>
				</div>
			</section>

		<?php elseif ( 'seo' === $data['tab'] ) : ?>
			<section class="yby-panel yby-settings-single">
				<header><h2>SEO & 结构化数据</h2></header>
				<div class="yby-settings-fields">
					<label class="yby-toggle-row"><span><strong>启用 Docs Schema</strong><small>文档页输出 TechArticle；Docs 首页输出 WebPage。Rank Math 文章字段继续保留。</small></span><input type="checkbox" name="schema_enabled" value="1" <?php checked( ! empty( $s['schema_enabled'] ) ); ?>><i></i></label>
					<div class="yby-contract-note"><strong>SEO Source</strong><p>标题、描述和关键词继续使用现有 Rank Math post meta；Docs OS 不复制一套 SEO 数据。</p></div>
				</div>
			</section>
		<?php elseif ( 'display' === $data['tab'] ) : ?>
			<section class="yby-panel yby-settings-single">
				<header><h2>显示设置</h2></header>
				<div class="yby-settings-fields">
					<label class="yby-toggle-row"><span><strong>显示 Table of Contents</strong><small>文档页自动从正文标题生成目录。</small></span><input type="checkbox" name="show_toc" value="1" <?php checked( ! empty( $s['show_toc'] ) ); ?>><i></i></label>
					<label class="yby-toggle-row"><span><strong>显示 Related Docs</strong><small>优先使用 Docs OS 关联文档，兼容 BetterDocs legacy meta。</small></span><input type="checkbox" name="show_related" value="1" <?php checked( ! empty( $s['show_related'] ) ); ?>><i></i></label>
					<label>首页布局
						<select name="home_layout"><option value="cards" <?php selected( $s['home_layout'], 'cards' ); ?>>卡片网格</option><option value="list" <?php selected( $s['home_layout'], 'list' ); ?>>列表</option></select>
					</label>
				</div>
			</section>

		<?php elseif ( 'advanced' === $data['tab'] ) : ?>
			<section class="yby-panel yby-settings-single">
				<header><h2>高级</h2></header>
				<div class="yby-settings-fields">
					<input type="hidden" name="canonical_control_present" value="1">
					<label class="yby-toggle-row"><span><strong>Canonical Frontend</strong><small>Local 可直接切换；Production 仍必须额外满足 YBY_DOCS_OS_CANONICAL_PRODUCTION_ENABLED 常量，保持 fail-close。</small></span><input type="checkbox" name="canonical_enabled" value="1" <?php checked( $data['canonical_enabled'] ); ?>><i></i></label>
					<div class="yby-contract-note"><strong>Runtime Contract</strong><p>post_type=docs · taxonomy=doc_category · base_path=/docs · related=Docs OS → BetterDocs legacy fallback。</p></div>
				</div>
			</section>
		<?php endif; ?>
		<div class="yby-settings-actions">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . YBY_Docs_OS_Admin::PAGE_SLUG ) ); ?>">返回概览</a>
			<button type="submit" class="button button-primary">保存设置</button>
		</div>
	</form>
	<footer class="yby-docs-footer">
		<div><strong>Andy Core v<?php echo esc_html( YBY_CORE_VERSION ); ?></strong><span>|</span><strong>Docs OS</strong><span>Build a Help Center People Love</span></div>
		<small>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Beyourlover. All rights reserved.</small>
	</footer>
</div>
