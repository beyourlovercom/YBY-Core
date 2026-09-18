<?php
/** Native Docs OS shadow runtime. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Docs_Runtime {
	const PREVIEW_VAR = 'yby_docs_preview';
	const SURFACES = array( 'home', 'category', 'document', 'faq', 'tutorial' );
	const CANONICAL_OPTION = 'yby_docs_os_canonical_v1';
	protected $canonical_render = false;

	protected function contract() {
		$defaults = array(
			'post_type' => 'docs',
			'taxonomy' => 'doc_category',
			'base_path' => 'docs',
			'related_meta_key' => '_betterdocs_related_articles',
		);
		$contract = apply_filters( 'yby_docs_os_contract', $defaults );
		return wp_parse_args( is_array( $contract ) ? $contract : array(), $defaults );
	}

	protected function post_type() { $c = $this->contract(); return sanitize_key( $c['post_type'] ); }
	protected function taxonomy() { $c = $this->contract(); return sanitize_key( $c['taxonomy'] ); }
	protected function related_meta_key() { $c = $this->contract(); return sanitize_key( $c['related_meta_key'] ); }

	protected function settings() {
		$defaults = array(
			'site_title' => 'Help Center', 'site_description' => 'Find answers to common questions about orders, shipping, returns, and more.',
			'home_layout' => 'cards', 'category_limit' => 8, 'docs_per_category' => 6, 'sort' => 'doc_count_desc',
			'show_search' => 1, 'show_categories' => 1, 'show_recent' => 1, 'show_toc' => 1, 'show_related' => 1, 'schema_enabled' => 1,
		);
		$stored = get_option( 'yby_docs_os_settings_v1', array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
	}

	public function register_query_var( $vars ) {
		$vars[] = self::PREVIEW_VAR;
		return $vars;
	}

	public function is_preview_request() {
		return in_array( sanitize_key( get_query_var( self::PREVIEW_VAR ) ), self::SURFACES, true );
	}

	public function canonical_enabled() {
		if ( ! (bool) get_option( self::CANONICAL_OPTION, false ) ) { return false; }
		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) { return true; }
		return defined( 'YBY_DOCS_OS_CANONICAL_PRODUCTION_ENABLED' ) && YBY_DOCS_OS_CANONICAL_PRODUCTION_ENABLED;
	}

	public function is_canonical_request() {
		if ( ! $this->canonical_enabled() ) { return false; }
		if ( is_singular( $this->post_type() ) || is_tax( $this->taxonomy() ) || is_post_type_archive( $this->post_type() ) ) { return true; }
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' );
		$contract = $this->contract();
		return trim( $contract['base_path'], '/' ) === $path;
	}

	public function maybe_render_preview() {
		$surface = sanitize_key( get_query_var( self::PREVIEW_VAR ) );
		if ( ! in_array( $surface, self::SURFACES, true ) ) { return; }
		$local = function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type();
		if ( ! $local && ! current_user_can( 'andy_core_settings_manage' ) ) { status_header( 404 ); return; }
		$this->render_document( $surface, false, array() );
		exit;
	}

	public function maybe_render_canonical() {
		if ( ! $this->is_canonical_request() ) { return; }
		$context = array();
		if ( is_singular( $this->post_type() ) ) {
			$surface = 'document';
			$context['doc_id'] = get_queried_object_id();
			$context['allow_unpublished'] = is_preview() && current_user_can( 'edit_post', $context['doc_id'] );
		} elseif ( is_tax( $this->taxonomy() ) ) {
			$surface = 'category';
			$term = get_queried_object();
			$context['doc_category'] = $term && isset( $term->slug ) ? $term->slug : '';
		} else {
			$surface = 'home';
		}
		$this->render_document( $surface, true, $context );
		exit;
	}

	protected function render_document( $surface, $canonical = false, $context = array() ) {
		$this->canonical_render = (bool) $canonical;
		$data = $this->build_surface_data( $surface, $context );
		status_header( 200 );
		nocache_headers();
		if ( ! $canonical ) { header( 'X-Robots-Tag: noindex, nofollow, noarchive', true ); }

		$title_filter = static function () use ( $data ) { return $data['title']; };
		$body_filter  = static function ( $classes ) use ( $surface ) {
			$classes[] = 'yby-docs-preview';
			$classes[] = 'yby-docs-preview-' . sanitize_html_class( $surface );
			return $classes;
		};
		$settings = $this->settings();
		$schema_action = function () use ( $surface, $data ) { $this->render_schema( $surface, $data ); };
		add_filter( 'pre_get_document_title', $title_filter, 99 );
		add_filter( 'body_class', $body_filter, 99 );
		if ( ! $canonical && ! empty( $settings['schema_enabled'] ) ) { add_action( 'wp_head', $schema_action, 2 ); }

		get_header();
		$this->close_theme_archive_wrappers();
		?><main class="yby-docs-page"><div class="yby-docs-shell">
			<?php if ( ! $canonical ) : ?><div class="yby-docs-preview-bar">Docs OS Preview</div><?php endif; ?>
			<?php $this->render_surface( $surface, $data ); ?>
		</div></main><?php
		get_footer();

		if ( ! $canonical && ! empty( $settings['schema_enabled'] ) ) { remove_action( 'wp_head', $schema_action, 2 ); }
		remove_filter( 'body_class', $body_filter, 99 );
		remove_filter( 'pre_get_document_title', $title_filter, 99 );
	}

	protected function close_theme_archive_wrappers() {
		if ( function_exists( 'get_template' ) && 'shoptimizer' === get_template() ) {
			echo '</div></div>';
		}
	}

	public function enqueue_assets() {
		if ( ! $this->is_preview_request() && ! $this->is_canonical_request() ) { return; }
		$css = YBY_CORE_PLUGIN_DIR . 'public/css/yby-docs-os.css';
		$js  = YBY_CORE_PLUGIN_DIR . 'public/js/yby-docs-os.js';
		$css_version = YBY_CORE_VERSION . '.' . ( is_readable( $css ) ? filemtime( $css ) : '0' );
		$js_version  = YBY_CORE_VERSION . '.' . ( is_readable( $js ) ? filemtime( $js ) : '0' );
		wp_enqueue_style( 'yby-docs-os', YBY_CORE_PLUGIN_URL . 'public/css/yby-docs-os.css', array(), $css_version );
		wp_enqueue_script( 'yby-docs-os', YBY_CORE_PLUGIN_URL . 'public/js/yby-docs-os.js', array(), $js_version, true );
	}
	protected function preview_url( $surface, $args = array() ) {
		return add_query_arg( array_merge( array( self::PREVIEW_VAR => $surface ), $args ), home_url( '/' ) );
	}

	protected function docs_home_url() {
		return home_url( '/' . trim( $this->contract()['base_path'], '/' ) . '/' );
	}

	protected function surface_url( $surface, $args = array(), $object = null ) {
		if ( ! $this->canonical_render ) { return $this->preview_url( $surface, $args ); }
		if ( 'document' === $surface && $object instanceof WP_Post ) { return get_permalink( $object ); }
		if ( 'category' === $surface && $object instanceof WP_Term ) {
			$url = get_term_link( $object );
			return is_wp_error( $url ) ? $this->docs_home_url() : $url;
		}
		return $this->docs_home_url();
	}

	protected function build_surface_data( $surface, $context = array() ) {
		$settings = $this->settings();
		$data = array( 'title' => $settings['site_title'], 'description' => $settings['site_description'], 'settings' => $settings, 'categories' => array(), 'docs' => array(), 'post' => null, 'term' => null, 'query' => '' );
		$query = sanitize_text_field( isset( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : '' );
		$data['query'] = $query;
		if ( 'home' === $surface ) {
			$term_args = array( 'taxonomy' => $this->taxonomy(), 'hide_empty' => false, 'number' => max( 1, absint( $settings['category_limit'] ) ) );
			if ( 'manual' === $settings['sort'] ) { $term_args['meta_key'] = 'doc_category_order'; $term_args['orderby'] = 'meta_value_num'; $term_args['order'] = 'ASC'; }
			elseif ( 'name' === $settings['sort'] ) { $term_args['orderby'] = 'name'; $term_args['order'] = 'ASC'; }
			else { $term_args['orderby'] = 'count'; $term_args['order'] = 'DESC'; }
			$data['categories'] = get_terms( $term_args );
			if ( is_wp_error( $data['categories'] ) ) { $data['categories'] = array(); }
			$args = array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => '' !== $query ? 50 : max( 1, absint( $settings['docs_per_category'] ) ), 'orderby' => '' !== $query ? 'title' : 'modified', 'order' => '' !== $query ? 'ASC' : 'DESC' );
			if ( '' !== $query ) { $args['s'] = $query; }
			$data['docs'] = get_posts( $args );
			return $data;
		}
		if ( 'category' === $surface ) {
			$slug = sanitize_title( isset( $context['doc_category'] ) ? $context['doc_category'] : ( isset( $_GET['doc_category'] ) ? wp_unslash( $_GET['doc_category'] ) : '' ) );
			$term = $slug ? get_term_by( 'slug', $slug, $this->taxonomy() ) : false;
			$data['term'] = $term;
			$data['title'] = $term ? $term->name : __( 'Docs Category', 'yby-core' );
			$args = array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' );
			if ( $term ) { $args['tax_query'] = array( array( 'taxonomy' => $this->taxonomy(), 'field' => 'term_id', 'terms' => $term->term_id ) ); }
			if ( '' !== $query ) { $args['s'] = $query; }
			$data['docs'] = $term ? get_posts( $args ) : array();
			return $data;
		}
		if ( 'faq' === $surface ) {
			$data['title'] = __( 'Frequently Asked Questions', 'yby-core' );
			$data['docs'] = get_posts( array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => -1, 's' => 'faq', 'orderby' => 'title', 'order' => 'ASC' ) );
			return $data;
		}
		if ( 'tutorial' === $surface ) {
			$data['title'] = __( 'Tutorials', 'yby-core' );
			$data['docs'] = get_posts( array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => -1, 's' => 'tutorial', 'orderby' => 'title', 'order' => 'ASC' ) );
			return $data;
		}
		$id = absint( isset( $context['doc_id'] ) ? $context['doc_id'] : ( isset( $_GET['doc_id'] ) ? $_GET['doc_id'] : 0 ) );
		$post = $id ? get_post( $id ) : null;
		$allow_unpublished = ! empty( $context['allow_unpublished'] ) && $post && current_user_can( 'edit_post', $post->ID );
		if ( ! $post || $this->post_type() !== $post->post_type || ( 'publish' !== $post->post_status && ! $allow_unpublished ) ) {
			$fallback = get_posts( array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => 1 ) );
			$post = $fallback ? $fallback[0] : null;
		}
		$data['post'] = $post;
		$data['title'] = $post ? get_the_title( $post ) : __( 'Document', 'yby-core' );
		$data['related'] = $post ? $this->related_docs( $post ) : array();
		$data['terms'] = $post ? wp_get_post_terms( $post->ID, $this->taxonomy() ) : array();
		return $data;
	}

	protected function related_docs( $post ) {
		$ids = get_post_meta( $post->ID, 'yby_docs_related_ids', true );
		$ids = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
		if ( $ids ) { return get_posts( array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'numberposts' => 6 ) ); }
		$raw = get_post_meta( $post->ID, $this->related_meta_key(), true );
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
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( $ids ) { return get_posts( array( 'post_type' => $this->post_type(), 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'numberposts' => 6 ) ); }
		$terms = wp_get_post_terms( $post->ID, 'doc_category', array( 'fields' => 'ids' ) );
		if ( ! $terms ) { return array(); }
		return get_posts( array(
			'post_type' => $this->post_type(), 'post_status' => 'publish', 'numberposts' => 4,
			'post__not_in' => array( $post->ID ),
			'tax_query' => array( array( 'taxonomy' => $this->taxonomy(), 'field' => 'term_id', 'terms' => $terms ) ),
		) );
	}

	protected function render_search( $query = '' ) {
		$action = $this->canonical_render ? $this->docs_home_url() : home_url( '/' );
		echo '<form class="yby-docs-search-form" method="get" action="' . esc_url( $action ) . '">';
		if ( ! $this->canonical_render ) { echo '<input type="hidden" name="' . esc_attr( self::PREVIEW_VAR ) . '" value="home">'; }
		echo '<input class="yby-docs-search" type="search" name="q" value="' . esc_attr( $query ) . '" placeholder="' . esc_attr__( 'Search documentation', 'yby-core' ) . '" data-yby-docs-search>';
		echo '<button type="submit">' . esc_html__( 'Search', 'yby-core' ) . '</button></form>';
	}

	protected function render_doc_list( $docs ) {
		if ( ! $docs ) { echo '<div class="yby-docs-empty">' . esc_html__( 'No documentation matched this view.', 'yby-core' ) . '</div>'; return; }
		echo '<div class="yby-docs-list">';
		foreach ( $docs as $post ) {
			$url = $this->surface_url( 'document', array( 'doc_id' => $post->ID ), $post );
			echo '<a href="' . esc_url( $url ) . '"><strong>' . esc_html( get_the_title( $post ) ) . '</strong><span>→</span></a>';
		}
		echo '</div>';
	}
	protected function render_surface( $surface, $data ) {
		if ( 'home' === $surface ) {
			$settings = $data['settings'];
			echo '<section class="yby-docs-hero"><p class="yby-docs-eyebrow">Docs OS</p><h1>' . esc_html( $data['title'] ) . '</h1><p>' . esc_html( $data['description'] ) . '</p>';
			if ( ! empty( $settings['show_search'] ) ) { $this->render_search( $data['query'] ); } echo '</section>';
			if ( ! $this->canonical_render ) { echo '<nav class="yby-docs-quick"><a href="' . esc_url( $this->preview_url( 'faq' ) ) . '">FAQ</a><a href="' . esc_url( $this->preview_url( 'tutorial' ) ) . '">Tutorials</a></nav>'; }
			if ( ! empty( $settings['show_categories'] ) ) { echo '<section class="yby-docs-grid yby-docs-grid-' . esc_attr( $settings['home_layout'] ) . '">'; foreach ( $data['categories'] as $term ) { $url = $this->surface_url( 'category', array( 'doc_category' => $term->slug ), $term ); echo '<a class="yby-docs-card" href="' . esc_url( $url ) . '"><strong>' . esc_html( $term->name ) . '</strong><span>' . esc_html( (string) $term->count ) . ' docs</span></a>'; } echo '</section>'; }
			if ( '' !== $data['query'] || ! empty( $settings['show_recent'] ) ) { echo '<section class="yby-docs-content"><h2>' . esc_html( '' !== $data['query'] ? __( 'Search results', 'yby-core' ) : __( 'Latest documentation', 'yby-core' ) ) . '</h2>'; $this->render_doc_list( $data['docs'] ); echo '</section>'; }
			return;
		}
		if ( in_array( $surface, array( 'category', 'faq', 'tutorial' ), true ) ) {
			echo '<section class="yby-docs-content"><p class="yby-docs-eyebrow">' . esc_html( ucfirst( $surface ) ) . '</p><h1>' . esc_html( $data['title'] ) . '</h1>';
			if ( 'category' === $surface ) { $this->render_search( $data['query'] ); }
			$this->render_doc_list( $data['docs'] ); echo '</section>'; return;
		}
		$post = $data['post'];
		if ( ! $post ) { echo '<section class="yby-docs-content"><h1>' . esc_html__( 'Document unavailable', 'yby-core' ) . '</h1></section>'; return; }
		echo '<nav class="yby-docs-breadcrumb"><a href="' . esc_url( $this->surface_url( 'home' ) ) . '">Docs</a>';
		foreach ( $data['terms'] as $term ) { echo '<span>/</span><a href="' . esc_url( $this->surface_url( 'category', array( 'doc_category' => $term->slug ), $term ) ) . '">' . esc_html( $term->name ) . '</a>'; }
		$settings = $data['settings'];
		echo '</nav><article class="yby-docs-document' . ( empty( $settings['show_toc'] ) ? ' yby-docs-document-no-toc' : '' ) . '">';
		if ( ! empty( $settings['show_toc'] ) ) { echo '<aside class="yby-docs-toc" data-yby-docs-toc><strong>' . esc_html__( 'Table of Contents', 'yby-core' ) . '</strong><ol></ol></aside>'; }
		$rendered_content = apply_filters( 'the_content', $post->post_content );
		$rendered_content = $this->resolve_asset_urls( $post, $rendered_content );
		echo '<div class="yby-docs-article"><p class="yby-docs-eyebrow">Document</p><h1>' . esc_html( get_the_title( $post ) ) . '</h1><div class="yby-docs-entry" data-yby-docs-entry>' . $rendered_content . '</div>';
		if ( ! empty( $settings['show_related'] ) && ! empty( $data['related'] ) ) { echo '<section class="yby-docs-related"><h2>' . esc_html__( 'Related Docs', 'yby-core' ) . '</h2>'; $this->render_doc_list( $data['related'] ); echo '</section>'; }
		echo '</div></article>';
	}

	protected function resolve_asset_urls( $post, $html ) {
		if ( ! $post instanceof WP_Post || false === stripos( $html, '<img' ) ) { return $html; }
		$credentials = get_option( 'advmo_credentials', array() );
		$domain = isset( $credentials['cloudflare_r2']['domain'] ) ? esc_url_raw( $credentials['cloudflare_r2']['domain'] ) : '';
		if ( ! $domain ) { return $html; }

		$file_map = array();
		if ( preg_match_all( '/<!--\s*wp:image\s+({.*?})\s*-->(.*?)<!--\s*\/wp:image\s*-->/is', $post->post_content, $blocks, PREG_SET_ORDER ) ) {
			foreach ( $blocks as $block ) {
				$config = json_decode( $block[1], true );
				$attachment_id = is_array( $config ) && isset( $config['id'] ) ? absint( $config['id'] ) : 0;
				if ( ! $attachment_id || ! get_post_meta( $attachment_id, 'advmo_offloaded', true ) ) { continue; }
				$path = trim( (string) get_post_meta( $attachment_id, 'advmo_path', true ), '/' );
				if ( '' === $path ) { continue; }

				$meta = wp_get_attachment_metadata( $attachment_id );
				$files = array();
				$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
				if ( $attached_file ) { $files[] = wp_basename( $attached_file ); }
				if ( is_array( $meta ) ) {
					if ( ! empty( $meta['file'] ) ) { $files[] = wp_basename( $meta['file'] ); }
					if ( ! empty( $meta['original_image'] ) ) { $files[] = wp_basename( $meta['original_image'] ); }
					if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
						foreach ( $meta['sizes'] as $size ) { if ( ! empty( $size['file'] ) ) { $files[] = wp_basename( $size['file'] ); } }
					}
				}
				if ( preg_match_all( '/(?:src|data-src)=["\']([^"\']+)["\']/i', $block[2], $sources ) ) {
					foreach ( $sources[1] as $source ) { $files[] = wp_basename( (string) wp_parse_url( $source, PHP_URL_PATH ) ); }
				}
				foreach ( array_unique( array_filter( $files ) ) as $file ) {
					$file_map[ $file ] = rtrim( $domain, '/' ) . '/' . $path . '/' . rawurlencode( $file );
				}
			}
		}
		if ( ! $file_map ) { return $html; }

		return preg_replace_callback(
			'#https?://[^\s"\'<>]+/([^/\s"\'<>?,]+)(?:\?[^\s"\'<>]*)?#i',
			static function ( $match ) use ( $file_map ) {
				$file = rawurldecode( $match[1] );
				return isset( $file_map[ $file ] ) ? $file_map[ $file ] : $match[0];
			},
			$html
		);
	}

	protected function render_schema( $surface, $data ) {
		$schema = array( '@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $data['title'], 'url' => $this->preview_url( $surface ) );
		if ( 'document' === $surface && ! empty( $data['post'] ) ) {
			$schema['@type'] = 'TechArticle';
			$schema['headline'] = get_the_title( $data['post'] );
			$schema['dateModified'] = get_post_modified_time( DATE_W3C, true, $data['post'] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
	}
}
