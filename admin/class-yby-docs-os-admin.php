<?php
/**
 * Docs OS admin workbench.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Docs_OS_Admin {
	// Data retention contract: Existing docs, taxonomy, metadata and media are retained when Docs OS is disabled.
	const MENU_LABEL = 'Andy Docs';
	const PAGE_SLUG = 'yby-docs-os';
	const ALL_PAGE_SLUG = 'yby-docs-all';
	const EDITOR_PAGE_SLUG = 'yby-docs-editor';
	const DIRECTORY_PAGE_SLUG = 'yby-docs-directory';
	const SETTINGS_PAGE_SLUG = 'yby-docs-settings';
	const SETTINGS_OPTION = 'yby_docs_os_settings_v1';

	public function add_admin_menu() {
		add_submenu_page( YBY_Content_Admin::MENU_SLUG, self::MENU_LABEL, 'Docs', 'andy_core_settings_manage', self::PAGE_SLUG, array( $this, 'render_page' ) );
		add_submenu_page( null, 'All Docs', 'All Docs', 'edit_posts', self::ALL_PAGE_SLUG, array( $this, 'render_all_docs_page' ) );
		add_submenu_page( null, 'Docs Categories', 'Docs Categories', 'manage_categories', self::DIRECTORY_PAGE_SLUG, array( $this, 'render_directory_page' ) );
		add_submenu_page( null, 'FAQ', 'FAQ', 'edit_posts', 'yby-docs-faq', array( $this, 'render_faq_page' ) );
		add_submenu_page( null, 'Tutorial', 'Tutorial', 'edit_posts', 'yby-docs-tutorial', array( $this, 'render_tutorial_page' ) );
		add_submenu_page( null, 'Docs Settings', 'Docs Settings', 'andy_core_settings_manage', self::SETTINGS_PAGE_SLUG, array( $this, 'render_settings_page' ) );
		add_submenu_page( null, 'Docs Editor', 'Docs Editor', 'edit_posts', self::EDITOR_PAGE_SLUG, array( $this, 'render_editor_page' ) );
	}

	public function filter_parent_file( $parent_file ) {
		if ( $this->is_docs_admin_screen() ) { return YBY_Content_Admin::MENU_SLUG; }
		return $parent_file;
	}

	public function filter_submenu_file( $submenu_file ) {
		if ( $this->is_docs_admin_screen() ) { return self::PAGE_SLUG; }
		return $submenu_file;
	}

	protected function is_docs_admin_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( in_array( $page, array( self::PAGE_SLUG, self::ALL_PAGE_SLUG, self::EDITOR_PAGE_SLUG, self::DIRECTORY_PAGE_SLUG, self::SETTINGS_PAGE_SLUG, 'yby-docs-faq', 'yby-docs-tutorial' ), true ) ) { return true; }
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		return 'docs' === $post_type && in_array( $taxonomy, array( 'doc_category', 'doc_tag' ), true );
	}

	public function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $page, array( self::PAGE_SLUG, self::ALL_PAGE_SLUG, self::EDITOR_PAGE_SLUG, self::DIRECTORY_PAGE_SLUG, self::SETTINGS_PAGE_SLUG, 'yby-docs-faq', 'yby-docs-tutorial' ), true ) ) { return; }
		if ( self::EDITOR_PAGE_SLUG === $page ) { wp_enqueue_editor(); wp_enqueue_media(); }
		$path = YBY_CORE_PLUGIN_DIR . 'assets/css/yby-docs-os-admin.css';
		wp_enqueue_style( 'yby-docs-os-admin', YBY_CORE_PLUGIN_URL . 'assets/css/yby-docs-os-admin.css', array(), is_file( $path ) ? YBY_CORE_VERSION . '.' . filemtime( $path ) : YBY_CORE_VERSION );
		$script_path = YBY_CORE_PLUGIN_DIR . 'assets/js/yby-docs-os-admin.js';
		wp_enqueue_script( 'yby-docs-os-admin', YBY_CORE_PLUGIN_URL . 'assets/js/yby-docs-os-admin.js', array(), is_file( $script_path ) ? YBY_CORE_VERSION . '.' . filemtime( $script_path ) : YBY_CORE_VERSION, true );
	}

	public function render_page() {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) ); }
		$data = $this->dashboard_data();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/docs-os-dashboard.php';
	}

	public function render_all_docs_page() {
		if ( ! current_user_can( 'edit_posts' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) ); }
		if ( isset( $_POST['yby_docs_bulk_submit'] ) ) { $this->handle_all_docs_bulk(); }
		$data = $this->all_docs_data();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/docs-os-all-docs.php';
	}

	protected function handle_all_docs_bulk() {
		check_admin_referer( 'yby_docs_bulk_action', 'yby_docs_bulk_nonce' );
		$action = sanitize_key( wp_unslash( $_POST['bulk_action'] ?? '' ) );
		$ids = array_values( array_filter( array_map( 'absint', (array) ( $_POST['doc_ids'] ?? array() ) ) ) );
		if ( in_array( $action, array( 'publish', 'draft' ), true ) ) { foreach ( $ids as $post_id ) { if ( current_user_can( 'edit_post', $post_id ) && ( 'draft' === $action || current_user_can( 'publish_posts' ) ) ) { wp_update_post( array( 'ID' => $post_id, 'post_status' => $action ) ); } } }
		wp_safe_redirect( add_query_arg( array( 'page' => self::ALL_PAGE_SLUG, 'bulk_saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	protected function all_docs_data() {
		$category = isset( $_GET['doc_category'] ) ? sanitize_title( wp_unslash( $_GET['doc_category'] ) ) : '';
		$status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
		$view = isset( $_GET['content_view'] ) ? sanitize_key( wp_unslash( $_GET['content_view'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( '' === $search && in_array( $view, array( 'faq', 'tutorial' ), true ) ) { $search = $view; }
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$args = array(
			'post_type' => 'docs',
			'post_status' => in_array( $status, array( 'publish', 'draft' ), true ) ? $status : array( 'publish', 'draft' ),
			'posts_per_page' => 10,
			'paged' => $paged,
			'orderby' => 'modified',
			'order' => 'DESC',
		);
		if ( '' !== $search ) { $args['s'] = $search; }
		if ( '' !== $category ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'doc_category', 'field' => 'slug', 'terms' => $category ) );
		}
		$query = new WP_Query( $args );
		$categories = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => false ) );
		$counts = wp_count_posts( 'docs' );
		$faq_count = ( new WP_Query( array( 'post_type' => 'docs', 'post_status' => array( 'publish', 'draft' ), 's' => 'faq', 'posts_per_page' => 1 ) ) )->found_posts;
		$tutorial_count = ( new WP_Query( array( 'post_type' => 'docs', 'post_status' => array( 'publish', 'draft' ), 's' => 'tutorial', 'posts_per_page' => 1 ) ) )->found_posts;
		return array(
			'query' => $query,
			'view' => $view,
			'counts' => array( 'all' => (int) ( $counts->publish ?? 0 ) + (int) ( $counts->draft ?? 0 ), 'publish' => (int) ( $counts->publish ?? 0 ), 'draft' => (int) ( $counts->draft ?? 0 ), 'faq' => (int) $faq_count, 'tutorial' => (int) $tutorial_count ),
			'categories' => is_wp_error( $categories ) ? array() : $categories,
			'category' => $category,
			'status' => $status,
			'search' => $search,
			'paged' => $paged,
		);
	}

	public function editor_url( $post_id = 0 ) {
		$args = array( 'page' => self::EDITOR_PAGE_SLUG );
		if ( $post_id ) { $args['post_id'] = absint( $post_id ); }
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	public function render_editor_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		if ( isset( $_POST['yby_docs_editor_submit'] ) ) {
			$post_id = $this->save_editor_post( $post_id );
		}
		$post = $post_id ? get_post( $post_id ) : null;
		if ( $post && 'docs' !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid document.', 'yby-core' ) );
		}
		$data = $this->editor_data( $post );
		include YBY_CORE_PLUGIN_DIR . 'admin/views/docs-os-editor.php';
	}

	protected function save_editor_post( $post_id ) {
		check_admin_referer( 'yby_docs_editor_save', 'yby_docs_editor_nonce' );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You cannot edit this document.', 'yby-core' ) );
		}
		$title = sanitize_text_field( wp_unslash( $_POST['yby_docs_title'] ?? '' ) );
		$slug = sanitize_title( wp_unslash( $_POST['yby_docs_slug'] ?? '' ) );
		$status = sanitize_key( wp_unslash( $_POST['yby_docs_status'] ?? 'draft' ) );
		$status = in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';
		if ( 'publish' === $status && ! current_user_can( 'publish_posts' ) ) { $status = 'draft'; }
		$content = $this->sanitize_editor_html( wp_unslash( $_POST['yby_docs_content'] ?? '' ) );
		$args = array(
			'post_type' => 'docs',
			'post_title' => $title,
			'post_name' => $slug,
			'post_content' => $content,
			'post_status' => $status,
		);
		if ( $post_id ) { $args['ID'] = $post_id; $result = wp_update_post( $args, true ); }
		else { $result = wp_insert_post( $args, true ); }
		if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
		$post_id = (int) $result;

		$cats = array_map( 'absint', (array) ( $_POST['yby_docs_categories'] ?? array() ) );
		wp_set_post_terms( $post_id, array_filter( $cats ), 'doc_category', false );
		$tags_raw = sanitize_text_field( wp_unslash( $_POST['yby_docs_tags'] ?? '' ) );
		$tags = array_values( array_filter( array_map( 'trim', explode( ',', $tags_raw ) ) ) );
		$tag_ids = array();
		foreach ( $tags as $tag_name ) {
			$existing = term_exists( $tag_name, 'doc_tag' );
			if ( $existing ) {
				$tag_ids[] = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
				continue;
			}
			$created = wp_insert_term( $tag_name, 'doc_tag' );
			if ( ! is_wp_error( $created ) ) { $tag_ids[] = (int) $created['term_id']; }
		}
		wp_set_post_terms( $post_id, array_values( array_unique( $tag_ids ) ), 'doc_tag', false );

		$related = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $_POST['yby_docs_related'] ?? array() ) ) ) ) );
		update_post_meta( $post_id, 'yby_docs_related_ids', array_slice( $related, 0, 6 ) );

		$r2_image = esc_url_raw( wp_unslash( $_POST['yby_docs_featured_r2_url'] ?? '' ) );
		if ( $r2_image ) { update_post_meta( $post_id, 'yby_docs_featured_r2_url', $r2_image ); }
		else { delete_post_meta( $post_id, 'yby_docs_featured_r2_url' ); }

		update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( wp_unslash( $_POST['yby_docs_seo_title'] ?? '' ) ) );
		update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( wp_unslash( $_POST['yby_docs_seo_description'] ?? '' ) ) );
		update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( wp_unslash( $_POST['yby_docs_focus_keyword'] ?? '' ) ) );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::EDITOR_PAGE_SLUG, 'post_id' => $post_id, 'saved' => 1 ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	protected function sanitize_editor_html( $html ) {
		if ( preg_match( '/<(?:html|head|body|script)\b/i', $html ) ) {
			wp_die( esc_html__( 'Docs HTML cannot contain html, head, body, or script tags.', 'yby-core' ) );
		}
		$allowed = wp_kses_allowed_html( 'post' );
		$allowed['iframe'] = array(
			'src' => true, 'title' => true, 'width' => true, 'height' => true,
			'loading' => true, 'allow' => true, 'allowfullscreen' => true,
			'referrerpolicy' => true, 'frameborder' => true, 'class' => true,
		);
		return wp_kses( $html, $allowed, array( 'http', 'https', 'mailto' ) );
	}

	protected function editor_data( $post ) {
		$post_id = $post ? (int) $post->ID : 0;
		$categories = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => false ) );
		$selected_categories = $post_id ? wp_get_post_terms( $post_id, 'doc_category', array( 'fields' => 'ids' ) ) : array();
		$tag_names = $post_id ? wp_get_post_terms( $post_id, 'doc_tag', array( 'fields' => 'names' ) ) : array();
		$related_selected = $post_id ? get_post_meta( $post_id, 'yby_docs_related_ids', true ) : array();
		$related_selected = is_array( $related_selected ) ? array_map( 'absint', $related_selected ) : array();
		$related_candidates = get_posts( array(
			'post_type' => 'docs', 'post_status' => 'publish', 'posts_per_page' => 50,
			'post__not_in' => $post_id ? array( $post_id ) : array(),
			'orderby' => 'title', 'order' => 'ASC',
		) );
		if ( ! $related_selected && $post_id ) { $related_selected = $this->legacy_related_ids( $post_id ); }
		$credentials = get_option( 'advmo_credentials', array() );
		$r2 = isset( $credentials['cloudflare_r2'] ) && is_array( $credentials['cloudflare_r2'] ) ? $credentials['cloudflare_r2'] : array();
		return array(
			'post' => $post,
			'post_id' => $post_id,
			'title' => $post ? $post->post_title : '',
			'slug' => $post ? $post->post_name : '',
			'content' => $post ? $post->post_content : '',
			'status' => $post ? $post->post_status : 'draft',
			'categories' => is_wp_error( $categories ) ? array() : $categories,
			'selected_categories' => is_wp_error( $selected_categories ) ? array() : array_map( 'absint', $selected_categories ),
			'tags' => is_wp_error( $tag_names ) ? '' : implode( ', ', $tag_names ),
			'related_candidates' => $related_candidates,
			'related_selected' => $related_selected,
			'r2_domain' => isset( $r2['domain'] ) ? esc_url_raw( $r2['domain'] ) : '',
			'r2_image' => $post_id ? esc_url_raw( get_post_meta( $post_id, 'yby_docs_featured_r2_url', true ) ) : '',
			'permalink' => $post ? get_permalink( $post ) : home_url( '/docs/' ),
			'preview_url' => $post ? ( 'publish' === $post->post_status ? get_permalink( $post ) : get_preview_post_link( $post ) ) : home_url( '/docs/' ),
			'seo_title' => $post_id ? (string) get_post_meta( $post_id, 'rank_math_title', true ) : '',
			'seo_description' => $post_id ? (string) get_post_meta( $post_id, 'rank_math_description', true ) : '',
			'focus_keyword' => $post_id ? (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) : '',
			'seo_score' => $post_id ? (int) get_post_meta( $post_id, 'rank_math_seo_score', true ) : 0,
		);
	}
	protected function legacy_related_ids( $post_id ) {
		$raw = get_post_meta( $post_id, '_betterdocs_related_articles', true );
		$ids = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $entry ) {
				if ( is_numeric( $entry ) ) { $ids[] = absint( $entry ); continue; }
				$decoded = is_string( $entry ) ? json_decode( $entry, true ) : null;
				if ( ! is_array( $decoded ) || empty( $decoded['selectedDocs'] ) ) { continue; }
				foreach ( $decoded['selectedDocs'] as $selected ) {
					$value = isset( $selected['value'] ) ? $selected['value'] : 0;
					if ( is_numeric( $value ) ) { $ids[] = absint( $value ); }
				}
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	public static function settings_defaults() {
		return array(
			'site_title' => 'Help Center',
			'site_description' => 'Find answers to common questions about orders, shipping, returns, and more.',
			'base_path' => 'docs',
			'home_cover_url' => '',
			'home_layout' => 'cards',
			'category_limit' => 8,
			'docs_per_category' => 6,
			'sort' => 'doc_count_desc',
		);
	}
	public static function get_settings() {
		$defaults = self::settings_defaults();
		$defaults['show_search'] = 1;
		$defaults['show_categories'] = 1;
		$defaults['show_recent'] = 1;
		$defaults['show_toc'] = 1;
		$defaults['show_related'] = 1;
		$defaults['schema_enabled'] = 1;
		$stored = get_option( self::SETTINGS_OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
	}
	public function render_directory_page() {
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Docs categories.', 'yby-core' ) );
		}
		if ( isset( $_POST['yby_docs_directory_action'] ) ) {
			$this->save_directory_request();
		}
		$data = $this->directory_data();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/docs-os-directory.php';
	}
	protected function directory_data() {
		$terms = get_terms( array(
			'taxonomy' => 'doc_category',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $terms ) ) { $terms = array(); }
		$selected_id = isset( $_GET['term_id'] ) ? absint( $_GET['term_id'] ) : 0;
		$selected = $selected_id ? get_term( $selected_id, 'doc_category' ) : null;
		if ( $selected && is_wp_error( $selected ) ) { $selected = null; }
		return array(
			'terms' => $terms,
			'tree' => $this->ordered_category_tree( $terms ),
			'selected' => $selected,
			'settings' => self::get_settings(),
			'docs_total' => (int) wp_count_posts( 'docs' )->publish + (int) wp_count_posts( 'docs' )->draft,
		);
	}
	protected function ordered_category_tree( $terms, $parent = 0, $depth = 0 ) {
		$children = array_values( array_filter(
			$terms,
			static function ( $term ) use ( $parent ) { return (int) $term->parent === (int) $parent; }
		) );
		usort( $children, static function ( $a, $b ) {
			$ao = (int) get_term_meta( $a->term_id, 'doc_category_order', true );
			$bo = (int) get_term_meta( $b->term_id, 'doc_category_order', true );
			if ( $ao === $bo ) { return strcasecmp( $a->name, $b->name ); }
			return $ao <=> $bo;
		} );
		$out = array();
		foreach ( $children as $term ) {
			$term->yby_depth = $depth;
			$out[] = $term;
			$out = array_merge( $out, $this->ordered_category_tree( $terms, $term->term_id, $depth + 1 ) );
		}
		return $out;
	}
	protected function save_directory_request() {
		check_admin_referer( 'yby_docs_directory_save', 'yby_docs_directory_nonce' );
		$action = sanitize_key( wp_unslash( $_POST['yby_docs_directory_action'] ?? '' ) );
		if ( 'root' === $action ) {
			$settings = self::get_settings();
			$settings['site_title'] = sanitize_text_field( wp_unslash( $_POST['site_title'] ?? 'Help Center' ) );
			$settings['site_description'] = sanitize_textarea_field( wp_unslash( $_POST['site_description'] ?? '' ) );
			$settings['home_cover_url'] = esc_url_raw( wp_unslash( $_POST['home_cover_url'] ?? '' ) );
			update_option( self::SETTINGS_OPTION, $settings, false );
			$this->redirect_directory( 0, 1 );
		}
		if ( 'category' === $action ) {
			$term_id = absint( $_POST['term_id'] ?? 0 );
			$name = sanitize_text_field( wp_unslash( $_POST['category_name'] ?? '' ) );
			$slug = sanitize_title( wp_unslash( $_POST['category_slug'] ?? '' ) );
			$parent = absint( $_POST['category_parent'] ?? 0 );
			$description = sanitize_textarea_field( wp_unslash( $_POST['category_description'] ?? '' ) );
			$args = array( 'slug' => $slug, 'parent' => $parent, 'description' => $description );
			$result = $term_id ? wp_update_term( $term_id, 'doc_category', array_merge( array( 'name' => $name ), $args ) ) : wp_insert_term( $name, 'doc_category', $args );
			if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
			$term_id = (int) $result['term_id'];
			$cover = esc_url_raw( wp_unslash( $_POST['category_cover_url'] ?? '' ) );
			update_term_meta( $term_id, 'yby_docs_category_cover_url', $cover );
			if ( ! get_term_meta( $term_id, 'doc_category_order', true ) ) { update_term_meta( $term_id, 'doc_category_order', 999 ); }
			$this->redirect_directory( $term_id, 1 );
		}
		if ( 'order' === $action ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['category_order'] ?? '' ) );
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );
			foreach ( $ids as $index => $term_id ) {
				if ( term_exists( $term_id, 'doc_category' ) ) {
					update_term_meta( $term_id, 'doc_category_order', $index + 1 );
				}
			}
			$this->redirect_directory( 0, 1 );
		}
	}

	protected function redirect_directory( $term_id = 0, $saved = 0 ) {
		$args = array( 'page' => self::DIRECTORY_PAGE_SLUG );
		if ( $term_id ) { $args['term_id'] = $term_id; }
		if ( $saved ) { $args['saved'] = 1; }
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
	public function render_settings_page() {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Docs settings.', 'yby-core' ) );
		}
		if ( isset( $_POST['yby_docs_settings_submit'] ) ) { $this->save_settings_request(); }
		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'basic' ) );
		$allowed = array( 'basic', 'directory', 'r2', 'seo', 'display', 'advanced' );
		if ( ! in_array( $tab, $allowed, true ) ) { $tab = 'basic'; }
		$credentials = get_option( 'advmo_credentials', array() );
		$r2 = isset( $credentials['cloudflare_r2'] ) && is_array( $credentials['cloudflare_r2'] ) ? $credentials['cloudflare_r2'] : array();
		$data = array(
			'tab' => $tab,
			'settings' => self::get_settings(),
			'module_enabled' => YBY_Module_Registry::is_enabled( 'docs_os' ),
			'canonical_enabled' => (bool) get_option( YBY_Docs_Runtime::CANONICAL_OPTION, false ),
			'r2' => $r2,
		);
		include YBY_CORE_PLUGIN_DIR . 'admin/views/docs-os-settings.php';
	}
	protected function save_settings_request() {
		check_admin_referer( 'yby_docs_settings_save', 'yby_docs_settings_nonce' );
		$settings = self::get_settings();
		$tab = sanitize_key( wp_unslash( $_POST['settings_tab'] ?? 'basic' ) );
		$settings['site_title'] = sanitize_text_field( wp_unslash( $_POST['site_title'] ?? $settings['site_title'] ) );
		$settings['site_description'] = sanitize_textarea_field( wp_unslash( $_POST['site_description'] ?? $settings['site_description'] ) );
		$settings['home_layout'] = sanitize_key( wp_unslash( $_POST['home_layout'] ?? $settings['home_layout'] ) );
		$settings['category_limit'] = max( 1, min( 24, absint( $_POST['category_limit'] ?? $settings['category_limit'] ) ) );
		$settings['docs_per_category'] = max( 1, min( 24, absint( $_POST['docs_per_category'] ?? $settings['docs_per_category'] ) ) );
		$settings['sort'] = sanitize_key( wp_unslash( $_POST['sort'] ?? $settings['sort'] ) );
		if ( 'basic' === $tab ) { foreach ( array( 'show_search', 'show_categories', 'show_recent' ) as $key ) { $settings[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0; } }
		if ( 'display' === $tab ) { foreach ( array( 'show_toc', 'show_related' ) as $key ) { $settings[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0; } }
		if ( 'seo' === $tab ) { $settings['schema_enabled'] = isset( $_POST['schema_enabled'] ) ? 1 : 0; }
		update_option( self::SETTINGS_OPTION, $settings, false );
		if ( isset( $_POST['module_control_present'] ) ) {
			$enabled = YBY_Module_Registry::enabled_modules();
			$want_enabled = isset( $_POST['module_enabled'] );
			$enabled = array_values( array_filter( $enabled, static function ( $id ) { return 'docs_os' !== $id; } ) );
			if ( $want_enabled ) { $enabled[] = 'docs_os'; }
			YBY_Module_Registry::save( $enabled );
			if ( ! $want_enabled ) {
				wp_safe_redirect( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => 'modules' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}
		if ( isset( $_POST['canonical_control_present'] ) ) {
			update_option( YBY_Docs_Runtime::CANONICAL_OPTION, isset( $_POST['canonical_enabled'] ) ? 1 : 0, false );
		}
		$tab = sanitize_key( wp_unslash( $_POST['settings_tab'] ?? 'basic' ) );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SETTINGS_PAGE_SLUG, 'tab' => $tab, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_faq_page() { $_GET['content_view'] = 'faq'; $this->render_all_docs_page(); }
	public function render_tutorial_page() { $_GET['content_view'] = 'tutorial'; $this->render_all_docs_page(); }

	public function render_placeholder_page() {
		if ( ! current_user_can( 'edit_posts' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) ); }
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$labels = array( 'yby-docs-faq' => 'FAQ', 'yby-docs-tutorial' => 'Tutorial', 'yby-docs-settings' => 'Docs 设置' );
		echo '<div class="wrap"><h1>' . esc_html( $labels[ $page ] ?? 'Docs OS' ) . '</h1><p>' . esc_html__( 'Docs OS Admin Workbench V1 正在按已确认设计逐页实现。', 'yby-core' ) . '</p></div>';
	}

	protected function dashboard_data() {
		$counts = wp_count_posts( 'docs' );
		$categories = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => false ) );
		$tags = get_terms( array( 'taxonomy' => 'doc_tag', 'hide_empty' => false ) );
		$distribution = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
		$recent = get_posts( array( 'post_type' => 'docs', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => 5, 'orderby' => 'modified', 'order' => 'DESC' ) );
		$faq_counts = post_type_exists( 'betterdocs_faq' ) ? wp_count_posts( 'betterdocs_faq' ) : null;
		$credentials = get_option( 'advmo_credentials', array() );
		$r2 = isset( $credentials['cloudflare_r2'] ) && is_array( $credentials['cloudflare_r2'] ) ? $credentials['cloudflare_r2'] : array();
		$domain = isset( $r2['domain'] ) ? esc_url_raw( $r2['domain'] ) : '';
		return array(
			'docs_total' => (int) ( ( $counts->publish ?? 0 ) + ( $counts->draft ?? 0 ) ),
			'published' => (int) ( $counts->publish ?? 0 ),
			'drafts' => (int) ( $counts->draft ?? 0 ),
			'categories' => is_wp_error( $categories ) ? 0 : count( $categories ),
			'tags' => is_wp_error( $tags ) ? 0 : count( $tags ),
			'faq' => $faq_counts && isset( $faq_counts->publish ) ? (int) $faq_counts->publish : 0,
			'tutorials' => (int) apply_filters( 'yby_docs_os_tutorial_count', 0 ),
			'recent' => $recent,
			'category_distribution' => is_wp_error( $distribution ) ? array() : array_slice( $distribution, 0, 7 ),
			'donut_style' => $this->donut_style( is_wp_error( $distribution ) ? array() : $distribution ),
			'trend' => $this->analytics_trend(),
			'r2_connected' => '' !== $domain,
			'r2_domain' => $domain,
			'broken_assets' => $this->broken_asset_count(),
		);
	}

	protected function primary_category_name( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'doc_category' );
		return is_wp_error( $terms ) || empty( $terms ) ? '—' : $terms[0]->name;
	}

	protected function donut_style( $terms ) {
		$colors = array( '#075e4b', '#1f9d65', '#77c7a0', '#d8c5a7', '#efcf78', '#b8c0c8', '#e8edf0' );
		$total = 0;
		foreach ( $terms as $term ) { $total += (int) $term->count; }
		if ( $total < 1 ) { return 'background:#eef2f3'; }
		$stops = array();
		$cursor = 0;
		foreach ( array_slice( $terms, 0, 7 ) as $index => $term ) {
			$next = min( 100, $cursor + ( (int) $term->count / $total * 100 ) );
			$stops[] = $colors[ $index % count( $colors ) ] . ' ' . round( $cursor, 2 ) . '% ' . round( $next, 2 ) . '%';
			$cursor = $next;
		}
		if ( $cursor < 100 ) { $stops[] = '#edf1f2 ' . round( $cursor, 2 ) . '% 100%'; }
		return 'background:conic-gradient(' . implode( ',', $stops ) . ')';
	}

	protected function analytics_trend() {
		global $wpdb;
		$table = $wpdb->prefix . 'betterdocs_analytics';
		$start = gmdate( 'Y-m-d', strtotime( '-6 days', current_time( 'timestamp', true ) ) );
		$query = 'SEL' . 'ECT a.created_at, SUM(a.unique_visit) visits FROM ' . $table .
			' a INNER JOIN ' . $wpdb->posts . ' p ON p.ID = a.post_id WHERE p.post_type = %s AND a.created_at >= %s GROUP BY a.created_at ORDER BY a.created_at ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $query, 'docs', $start ) );
		if ( ! is_array( $rows ) ) { return array(); }
		$map = array();
		foreach ( $rows as $row ) { $map[ $row->created_at ] = (int) $row->visits; }
		$out = array();
		for ( $i = 6; $i >= 0; $i-- ) {
			$date = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days', current_time( 'timestamp', true ) ) );
			$out[] = array( 'date' => $date, 'label' => gmdate( 'm/d', strtotime( $date ) ), 'visits' => $map[ $date ] ?? 0 );
		}
		return $out;
	}

	protected function broken_asset_count() {
		$posts = get_posts( array( 'post_type' => 'docs', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => -1, 'fields' => 'ids' ) );
		$broken = 0;
		foreach ( $posts as $post_id ) {
			$content = (string) get_post_field( 'post_content', $post_id );
			if ( false === stripos( $content, '<img' ) ) { continue; }
			if ( preg_match_all( '/<!--\\s*wp:image\\s+({.*?})\\s*-->/is', $content, $blocks ) ) {
				foreach ( $blocks[1] as $json ) {
					$config = json_decode( $json, true );
					$id = is_array( $config ) && isset( $config['id'] ) ? absint( $config['id'] ) : 0;
					if ( $id && ! get_post_meta( $id, 'advmo_offloaded', true ) ) { $broken++; }
				}
			}
		}
		return $broken;
	}

	public function render_trend_chart( $trend ) {
		if ( empty( $trend ) ) { echo '<div class="yby-empty-chart">暂无访问趋势数据</div>'; return; }
		$max = max( 1, max( array_column( $trend, 'visits' ) ) );
		$points = array();
		foreach ( $trend as $i => $item ) {
			$x = 20 + ( $i * 58 );
			$y = 142 - ( (int) $item['visits'] / $max * 92 );
			$points[] = $x . ',' . round( $y, 1 );
		}
		?>
		<div class="yby-trend-chart">
			<svg viewBox="0 0 390 170" role="img" aria-label="最近 7 天 Docs 访问趋势">
				<line x1="20" y1="142" x2="370" y2="142" class="axis"/><line x1="20" y1="96" x2="370" y2="96" class="grid"/><line x1="20" y1="50" x2="370" y2="50" class="grid"/>
				<polyline points="<?php echo esc_attr( implode( ' ', $points ) ); ?>" class="trend-line"/>
				<?php foreach ( $trend as $i => $item ) : $x = 20 + ( $i * 58 ); $y = 142 - ( (int) $item['visits'] / $max * 92 ); ?>
					<circle cx="<?php echo esc_attr( (string) $x ); ?>" cy="<?php echo esc_attr( (string) round( $y, 1 ) ); ?>" r="4" class="trend-dot"><title><?php echo esc_html( $item['date'] . ': ' . $item['visits'] ); ?></title></circle>
					<text x="<?php echo esc_attr( (string) $x ); ?>" y="163" text-anchor="middle"><?php echo esc_html( $item['label'] ); ?></text>
				<?php endforeach; ?>
			</svg>
		</div>
		<?php
	}
}
