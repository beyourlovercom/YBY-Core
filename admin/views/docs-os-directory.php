<?php
/**
 * Docs OS Directory / Categories.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$is_new = isset( $_GET['new'] ) && '1' === (string) $_GET['new'];
$selected = $data['selected'];
$is_root = ! $is_new && ! $selected;
$settings = $data['settings'];
?>
<div class="wrap yby-docs-dashboard yby-directory-page">
	<div class="yby-docs-head">
		<div class="yby-docs-title-row">
			<h1>分类目录</h1>
			<small>管理帮助中心的分类结构，支持拖拽排序。分类页面会自动生成，无需手动创建 Page。</small>
		</div>
		<div class="yby-docs-head-actions">
			<div class="yby-directory-search"><span class="dashicons dashicons-search"></span><input type="search" placeholder="搜索分类名称…" data-yby-category-search></div>
			<a class="yby-primary-button" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG, 'new' => 1 ), admin_url( 'admin.php' ) ) ); ?>">＋ 新建分类</a>
		</div>
	</div>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible yby-editor-notice"><p>分类目录已保存。</p></div>
	<?php endif; ?>
	<div class="yby-directory-grid">
		<section class="yby-panel yby-directory-tree-panel">
			<header><h2>分类结构 <small>（拖拽排序）</small></h2><span><?php echo esc_html( (string) count( $data['terms'] ) ); ?> 个分类</span></header>
			<div class="yby-category-tree" data-yby-category-tree>
				<a class="yby-tree-row yby-tree-root <?php echo $is_root ? 'is-selected' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG ), admin_url( 'admin.php' ) ) ); ?>" data-name="<?php echo esc_attr( strtolower( $settings['site_title'] ) ); ?>">
					<span class="dashicons dashicons-open-folder"></span>
					<strong><?php echo esc_html( $settings['site_title'] ); ?></strong>
					<em><?php echo esc_html( (string) $data['docs_total'] ); ?></em>
					<span class="yby-more">•••</span>
				</a>
				<?php foreach ( $data['tree'] as $term ) : ?>
					<?php
					$term_url = add_query_arg(
						array( 'page' => YBY_Docs_OS_Admin::DIRECTORY_PAGE_SLUG, 'term_id' => $term->term_id ),
						admin_url( 'admin.php' )
					);
					?>
					<a class="yby-tree-row <?php echo $selected && (int) $selected->term_id === (int) $term->term_id ? 'is-selected' : ''; ?>" draggable="true" href="<?php echo esc_url( $term_url ); ?>" data-yby-term-id="<?php echo esc_attr( (string) $term->term_id ); ?>" data-parent="<?php echo esc_attr( (string) $term->parent ); ?>" data-name="<?php echo esc_attr( strtolower( $term->name ) ); ?>" style="--depth:<?php echo esc_attr( (string) $term->yby_depth ); ?>">
						<span class="yby-drag-handle">⠿</span>
						<span class="dashicons dashicons-category"></span>
						<strong><?php echo esc_html( $term->name ); ?></strong>
						<em><?php echo esc_html( (string) $term->count ); ?></em>
						<span class="yby-more">•••</span>
					</a>
				<?php endforeach; ?>
			</div>
			<form method="post" class="yby-order-form" data-yby-category-order-form>
				<?php wp_nonce_field( 'yby_docs_directory_save', 'yby_docs_directory_nonce' ); ?>
				<input type="hidden" name="yby_docs_directory_action" value="order">
				<input type="hidden" name="category_order" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $data['tree'], 'term_id' ) ) ); ?>" data-yby-category-order>
				<button type="submit" class="button">保存排序</button>
			</form>
		</section>
		<section class="yby-panel yby-directory-editor-panel">
			<header><h2><?php echo $is_root ? '编辑 Help Center' : ( $is_new ? '新建分类' : '编辑分类' ); ?></h2></header>
			<form method="post" class="yby-directory-editor-form">
				<?php wp_nonce_field( 'yby_docs_directory_save', 'yby_docs_directory_nonce' ); ?>
				<input type="hidden" name="yby_docs_directory_action" value="<?php echo $is_root ? 'root' : 'category'; ?>">
				<?php if ( $selected ) : ?><input type="hidden" name="term_id" value="<?php echo esc_attr( (string) $selected->term_id ); ?>"><?php endif; ?>
				<div class="yby-directory-fields">
					<label>名称 <span>*</span>
						<input type="text" name="<?php echo $is_root ? 'site_title' : 'category_name'; ?>" value="<?php echo esc_attr( $is_root ? $settings['site_title'] : ( $selected ? $selected->name : '' ) ); ?>" required>
					</label>
					<label>Slug <span>*</span>
						<input type="text" name="<?php echo $is_root ? 'root_slug' : 'category_slug'; ?>" value="<?php echo esc_attr( $is_root ? $settings['base_path'] : ( $selected ? $selected->slug : '' ) ); ?>" <?php echo $is_root ? 'readonly' : 'required'; ?>>
					</label>
					<?php if ( ! $is_root ) : ?>
						<label>父级分类
							<select name="category_parent">
								<option value="0">无（顶级分类）</option>
								<?php foreach ( $data['tree'] as $term ) : if ( $selected && (int) $selected->term_id === (int) $term->term_id ) { continue; } ?>
									<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $selected ? (int) $selected->parent : 0, (int) $term->term_id ); ?>><?php echo esc_html( str_repeat( '— ', (int) $term->yby_depth ) . $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endif; ?>
					<label>描述
						<textarea name="<?php echo $is_root ? 'site_description' : 'category_description'; ?>" rows="5"><?php echo esc_textarea( $is_root ? $settings['site_description'] : ( $selected ? $selected->description : '' ) ); ?></textarea>
					</label>
				</div>
				<?php
				$cover = $is_root ? $settings['home_cover_url'] : ( $selected ? esc_url_raw( get_term_meta( $selected->term_id, 'yby_docs_category_cover_url', true ) ) : '' );
				$preview_url = $is_root ? home_url( '/docs/' ) : ( $selected ? get_term_link( $selected ) : home_url( '/docs/' ) );
				if ( is_wp_error( $preview_url ) ) { $preview_url = home_url( '/docs/' ); }
				?>
				<div class="yby-directory-sidecards">
					<div class="yby-directory-cover">
						<label>分类封面图（可选）</label>
						<?php if ( $cover ) : ?><img src="<?php echo esc_url( $cover ); ?>" alt=""><?php else : ?><div class="yby-cover-placeholder"><span class="dashicons dashicons-format-image"></span> 暂无封面</div><?php endif; ?>
						<input type="url" name="<?php echo $is_root ? 'home_cover_url' : 'category_cover_url'; ?>" value="<?php echo esc_attr( $cover ); ?>" placeholder="https://img.beyourlover.com/...">
						<small>推荐 1200 × 300 px；支持 R2 绝对 URL。</small>
					</div>
					<div class="yby-directory-preview-card">
						<strong>前台预览</strong>
						<h3><?php echo esc_html( $is_root ? $settings['site_title'] : ( $selected ? $selected->name : 'New Category' ) ); ?></h3>
						<p><?php echo esc_html( $is_root ? $settings['site_description'] : ( $selected ? $selected->description : '分类保存后自动生成前台目录页。' ) ); ?></p>
						<a target="_blank" rel="noopener" href="<?php echo esc_url( $preview_url ); ?>">查看前台 →</a>
					</div>
				</div>
				<div class="yby-directory-actions"><button type="submit" class="button button-primary">保存</button></div>
			</form>
		</section>
	</div>
</div>
