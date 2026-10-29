<?php
/**
 * Login OS V1. The WordPress login engine remains authoritative.
 * The custom address is an alias: wp-login.php remains the emergency fallback.
 */
defined( 'ABSPATH' ) || exit;

final class YBY_Login_OS {
	const OPTION_KEY = 'yby_login_os_options_v1';

	public static function defaults() {
		return array(
			'enabled' => false,
			'url_enabled' => false,
			'hide_legacy' => false,
			'slug' => 'secure-login',
			'logo_url' => '',
			'accent_color' => '',
			'background_color' => '#f6f7fb',
		);
	}

	public static function get_options() {
		$stored = get_option( self::OPTION_KEY, array() );
		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	public static function valid_slug( $slug ) {
		$slug = is_scalar( $slug ) ? trim( (string) $slug, '/' ) : '';
		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]{2,49}$/D', $slug ) ) {
			return false;
		}
		return ! in_array( $slug, array(
			'wp-admin', 'wp-login', 'wp-json', 'admin', 'login', 'logout',
			'register', 'lost-password', 'my-account', 'checkout', 'cart',
			'wp-content', 'wp-includes', 'xmlrpc', 'feed', 'robots', 'sitemap',
		), true );
	}

	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$slug = isset( $input['slug'] ) && is_scalar( $input['slug'] )
			? strtolower( trim( (string) $input['slug'], '/' ) ) : $defaults['slug'];
		$accent = isset( $input['accent_color'] ) && is_scalar( $input['accent_color'] )
			? sanitize_hex_color( (string) $input['accent_color'] ) : '';
		$background = isset( $input['background_color'] ) && is_scalar( $input['background_color'] )
			? sanitize_hex_color( (string) $input['background_color'] ) : '';
		return array(
			'enabled' => ! empty( $input['enabled'] ),
			'url_enabled' => ! empty( $input['url_enabled'] ) && self::valid_slug( $slug ),
			'hide_legacy' => ! empty( $input['url_enabled'] ) && ! empty( $input['hide_legacy'] ) && self::valid_slug( $slug ),
			'slug' => self::valid_slug( $slug ) ? $slug : $defaults['slug'],
			'logo_url' => isset( $input['logo_url'] ) && is_scalar( $input['logo_url'] )
				? esc_url_raw( (string) $input['logo_url'], array( 'https', 'http' ) ) : '',
			'accent_color' => $accent ?: '',
			'background_color' => $background ?: $defaults['background_color'],
		);
	}

	public static function conflicting_plugins() {
		$active = get_option( 'active_plugins', array() );
		$active = is_array( $active ) ? $active : array();
		if ( is_multisite() ) {
			$network = get_site_option( 'active_sitewide_plugins', array() );
			$active = array_merge( $active, is_array( $network ) ? array_keys( $network ) : array() );
		}
		$known = array(
			'wps-hide-login/wps-hide-login.php' => 'WPS Hide Login',
			'rename-wp-login/rename-wp-login.php' => 'Rename wp-login.php',
		);
		$result = array();
		foreach ( $known as $plugin => $name ) {
			if ( in_array( $plugin, $active, true ) ) {
				$result[] = $name;
			}
		}
		return $result;
	}

	public static function slug_available( $slug ) {
		if ( ! self::valid_slug( $slug ) ) {
			return false;
		}
		// A real published page must never be replaced by an auth endpoint.
		return ! function_exists( 'get_page_by_path' ) || ! get_page_by_path( $slug );
	}

	public static function save_section( $section, $input ) {
		$before = self::get_options();
		if ( ! is_array( $input ) ) {
			return new WP_Error( 'invalid_settings', __( 'Invalid settings request.', 'yby-core' ) );
		}
		$allowed = 'appearance' === $section
			? array( 'enabled', 'logo_url', 'accent_color', 'background_color' )
			: ( 'url' === $section ? array( 'url_enabled', 'hide_legacy', 'slug' ) : array() );
		if ( ! $allowed ) {
			return new WP_Error( 'invalid_section', __( 'Unknown Login OS section.', 'yby-core' ) );
		}
		foreach ( $allowed as $key ) {
			$before[ $key ] = isset( $input[ $key ] ) ? $input[ $key ] : '';
		}
		if ( 'url' === $section ) {
			$slug = is_scalar( $before['slug'] )
				? strtolower( trim( (string) $before['slug'], '/' ) ) : '';
			if ( ! self::slug_available( $slug ) ) {
				return new WP_Error( 'invalid_slug', __( 'Choose another available login path.', 'yby-core' ) );
			}
			if ( ! empty( $before['url_enabled'] ) && is_multisite() ) {
				return new WP_Error( 'multisite_unsupported', __( 'Login URL alias is not supported on Multisite in V1.', 'yby-core' ) );
			}
			if ( ! empty( $before['url_enabled'] ) && self::conflicting_plugins() ) {
				return new WP_Error( 'login_conflict', __( 'Disable the other login URL plugin before enabling this alias.', 'yby-core' ) );
			}
		}
		$clean = self::sanitize( $before );
		update_option( self::OPTION_KEY, $clean, false );
		return $clean;
	}

	public static function alias_active() {
		$options = self::get_options();
		return ! is_multisite()
			&& ! empty( $options['url_enabled'] )
			&& self::slug_available( $options['slug'] )
			&& ! self::conflicting_plugins();
	}

	/** Hiding the old login route is explicitly opt-in for existing sites. */
	public static function stealth_active() {
		$options = self::get_options();
		return ! empty( $options['hide_legacy'] ) && self::alias_active();
	}

	/** Decode encoded paths, without considering user-controlled query strings. */
	public static function request_path() {
		if ( empty( $_SERVER['REQUEST_URI'] ) || ! is_scalar( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}
		$path = wp_parse_url( wp_unslash( (string) $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return '';
		}
		for ( $i = 0; $i < 3; $i++ ) {
			$decoded = rawurldecode( $path );
			if ( $decoded === $path ) {
				break;
			}
			$path = $decoded;
		}
		return '/' . ltrim( $path, '/' );
	}

	/**
	 * Only legacy login and non-authenticated admin paths are concealed.
	 * Admin AJAX, admin-post and core asset endpoints stay operational.
	 */
	public static function blocked_legacy_route( $path, $logged_in = false ) {
		$normalized = strtolower( preg_replace( '~/{2,}~', '/', '/' . ltrim( (string) $path, '/' ) ) );
		$normalized = rtrim( $normalized, '/' );
		if ( '/wp-login.php' === $normalized ) {
			return true;
		}
		if ( '/admin' === $normalized ) {
			return ! $logged_in;
		}
		if ( '/wp-admin' !== $normalized && 0 !== strpos( $normalized, '/wp-admin/' ) ) {
			return false;
		}
		$public_endpoints = array(
			'/wp-admin/admin-ajax.php',
			'/wp-admin/admin-post.php',
			'/wp-admin/load-scripts.php',
			'/wp-admin/load-styles.php',
		);
		if ( in_array( $normalized, $public_endpoints, true ) ) {
			return false;
		}
		return ! $logged_in;
	}

	/** A generic 404 must not disclose or redirect to the private login slug. */
	public static function maybe_block_legacy() {
		if ( ! self::stealth_active() ) {
			return;
		}
		$path = self::request_path();
		if ( ! $path || ! self::blocked_legacy_route( $path, is_user_logged_in() ) ) {
			return;
		}
		status_header( 404 );
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
			header( 'Content-Type: text/plain; charset=UTF-8', true );
		}
		echo '404 Not Found';
		exit;
	}

	/** No public index or browser/proxy cache of the private login screen. */
	public static function private_login_headers() {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );
		}
	}

	/** Password-reset emails generate login URLs via network_site_url. */
	public static function filter_network_site_url( $url, $path, $scheme ) {
		if ( ! self::alias_active() || ! in_array( $scheme, array( 'login', 'login_post' ), true ) ) {
			return $url;
		}
		if ( ! preg_match( '~^/?wp-login\.php(?:\?|$)~', (string) $path ) ) {
			return $url;
		}
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		return self::alias_url() . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}

	public static function alias_url() {
		$options = self::get_options();
		return home_url( '/' . $options['slug'] . '/' );
	}

	/** Replace only login-related site_url() calls, preserving query strings. */
	public static function filter_site_url( $url, $path, $scheme, $blog_id ) {
		unset( $blog_id );
		if ( ! self::alias_active() || ! in_array( $scheme, array( 'login', 'login_post' ), true ) ) {
			return $url;
		}
		if ( ! preg_match( '~^/?wp-login\.php(?:\?|$)~', (string) $path ) ) {
			return $url;
		}
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		return self::alias_url() . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}

	public static function filter_login_url( $url, $redirect, $force_reauth ) {
		unset( $redirect, $force_reauth );
		if ( ! self::alias_active() ) {
			return $url;
		}
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		return self::alias_url() . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}

	/** Intercept the alias before WordPress resolves normal page templates. */
	public static function maybe_load_alias() {
		if ( ! self::alias_active() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'POST', 'HEAD' ), true ) ) {
			return;
		}
		$request_path = wp_parse_url( wp_unslash( (string) $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
		$alias_path = wp_parse_url( self::alias_url(), PHP_URL_PATH );
		if ( ! is_string( $request_path ) || ! is_string( $alias_path )
			|| untrailingslashit( $request_path ) !== untrailingslashit( $alias_path ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		self::private_login_headers();
		if ( ! headers_sent() ) {
			nocache_headers();
		}
		$GLOBALS['pagenow'] = 'wp-login.php';
		require ABSPATH . 'wp-login.php';
		exit;
	}

	public static function login_body_classes( $classes ) {
		$options = self::get_options();
		if ( ! empty( $options['enabled'] ) ) {
			$classes[] = 'andy-login-os-enabled';
		}
		return $classes;
	}

	public static function enqueue_login_style() {
		$options = self::get_options();
		if ( empty( $options['enabled'] ) ) {
			return;
		}
		wp_enqueue_style( 'andy-core-login-os', YBY_CORE_PLUGIN_URL . 'public/css/yby-login-os.css', array(), YBY_CORE_VERSION );
		$color = $options['accent_color'];
		if ( ! $color && class_exists( 'YBY_Brand_OS' ) ) {
			$brand = YBY_Brand_OS::get( 'primary_color' );
			$color = sanitize_hex_color( $brand );
		}
		$color = $color ?: '#9b3749';
		wp_add_inline_style( 'andy-core-login-os',
			'body.andy-login-os-enabled{--andy-login-brand:' . $color .
			';--andy-login-background:' . $options['background_color'] . ';}' );
	}

	public static function render_logo( $message ) {
		$options = self::get_options();
		if ( empty( $options['enabled'] ) ) {
			return $message;
		}
		$logo = $options['logo_url'];
		if ( ! $logo && class_exists( 'YBY_Brand_OS' ) ) {
			$logo = YBY_Brand_OS::get_logo_default();
		}
		if ( ! $logo ) {
			return '<div class="andy-login-os-logo andy-login-os-logo--text">'
				. esc_html( get_bloginfo( 'name' ) ) . '</div>' . $message;
		}
		return '<div class="andy-login-os-logo"><img src="' . esc_url( $logo ) .
			'" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '"></div>' . $message;
	}
}
