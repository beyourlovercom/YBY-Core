<?php
/**
 * Social Login configuration foundation.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and validates Social Login provider settings.
 */
class YBY_Social_Login {

	/**
	 * Login nonce lifetime in seconds.
	 */
	const LOGIN_NONCE_TTL = 600;

	/**
	 * Return the Social Login option key.
	 *
	 * @return string
	 */
	public static function option_key() {
		return 'yby_social_login_options';
	}

	/**
	 * Return defaults for every supported provider.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'add_to_login_page' => false,
			'google'            => self::google_defaults(),
		);
	}

	/**
	 * Return Google provider defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function google_defaults() {
		$disabled_roles = array_intersect(
			array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager', 'social_manager' ),
			array_keys( self::registered_roles() )
		);

		return array(
			'enabled'                     => false,
			'client_id'                   => '',
			'select_account'              => true,
			'auto_link_existing_accounts' => false,
			'one_tap_enabled'             => false,
			'username_prefix'             => 'google_',
			'fallback_prefix'             => 'user_',
			'profile_image_size'          => 'default',
			'default_role'                => 'subscriber',
			'disabled_roles'              => array_values( $disabled_roles ),
			'redirect_url'                => '',
		);
	}

	/**
	 * Return all stored Social Login options with defaults applied.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options() {
		$options = get_option( self::option_key(), array() );
		$options = is_array( $options ) ? $options : array();
		$options = wp_parse_args( $options, self::defaults() );

		$options['google'] = wp_parse_args(
			isset( $options['google'] ) && is_array( $options['google'] ) ? $options['google'] : array(),
			self::google_defaults()
		);

		return self::sanitize( $options );
	}

	/**
	 * Return stored Google settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_google_options() {
		$options = self::get_options();

		return $options['google'];
	}

	/**
	 * Create a short-lived nonce while retaining only its keyed hash.
	 *
	 * @return string
	 */
	public static function issue_login_nonce() {
		$nonce = wp_generate_password( 48, false, false );

		if ( '' === $nonce ) {
			return '';
		}

		set_transient( self::login_nonce_key( $nonce ), 1, self::LOGIN_NONCE_TTL );

		return $nonce;
	}

	/**
	 * Determine whether a login nonce is still available.
	 *
	 * @param mixed $nonce Raw nonce claim.
	 * @return bool
	 */
	public static function is_login_nonce_valid( $nonce ) {
		if ( ! is_string( $nonce ) || '' === $nonce || strlen( $nonce ) > 128 ) {
			return false;
		}

		return false !== get_transient( self::login_nonce_key( $nonce ) );
	}

	/**
	 * Consume a login nonce after a successful login.
	 *
	 * @param string $nonce Verified nonce claim.
	 * @return void
	 */
	public static function consume_login_nonce( $nonce ) {
		delete_transient( self::login_nonce_key( $nonce ) );
	}

	/**
	 * Build a non-reversible transient key for a login nonce.
	 *
	 * @param string $nonce Raw nonce.
	 * @return string
	 */
	protected static function login_nonce_key( $nonce ) {
		return 'yby_google_nonce_' . hash_hmac( 'sha256', $nonce, wp_salt( 'nonce' ) );
	}

	/**
	 * Sanitize all Social Login settings.
	 *
	 * @param mixed $options Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $options ) {
		$options = is_array( $options ) ? $options : array();

		return array(
			'add_to_login_page' => self::sanitize_checkbox( $options['add_to_login_page'] ?? false ),
			'google'            => self::sanitize_google( $options['google'] ?? array() ),
		);
	}

	/**
	 * Sanitize Google settings.
	 *
	 * @param mixed $options Raw Google settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize_google( $options ) {
		$options  = is_array( $options ) ? $options : array();
		$defaults = self::google_defaults();
		$client   = self::sanitize_client_id( $options['client_id'] ?? '' );
		$sizes    = array( 'small', 'default', 'medium', 'large', 'extra_large', 'original' );
		$size_raw = $options['profile_image_size'] ?? $defaults['profile_image_size'];
		$role_raw = $options['default_role'] ?? $defaults['default_role'];
		$size     = sanitize_key( is_scalar( $size_raw ) ? (string) $size_raw : $defaults['profile_image_size'] );
		$role     = sanitize_key( is_scalar( $role_raw ) ? (string) $role_raw : $defaults['default_role'] );
		$roles    = isset( $options['disabled_roles'] ) && is_array( $options['disabled_roles'] ) ? $options['disabled_roles'] : array();
		$roles    = array_filter( $roles, 'is_scalar' );
		$roles    = array_map( 'sanitize_key', $roles );
		$roles    = array_values( array_intersect( array_unique( $roles ), array_keys( self::registered_roles() ) ) );

		if ( ! in_array( $size, $sizes, true ) ) {
			$size = $defaults['profile_image_size'];
		}

		if ( ! in_array( $role, self::allowed_registration_roles(), true ) ) {
			$role = $defaults['default_role'];
		}

		return array(
			'enabled'                     => self::sanitize_checkbox( $options['enabled'] ?? false ) && '' !== $client,
			'client_id'                   => $client,
			'select_account'              => self::sanitize_checkbox( $options['select_account'] ?? false ),
			'auto_link_existing_accounts' => self::sanitize_checkbox( $options['auto_link_existing_accounts'] ?? false ),
			'one_tap_enabled'             => self::sanitize_checkbox( $options['one_tap_enabled'] ?? false ),
			'username_prefix'             => self::sanitize_prefix( $options['username_prefix'] ?? '', $defaults['username_prefix'] ),
			'fallback_prefix'             => self::sanitize_prefix( $options['fallback_prefix'] ?? '', $defaults['fallback_prefix'] ),
			'profile_image_size'          => $size,
			'default_role'                => $role,
			'disabled_roles'              => $roles,
			'redirect_url'                => self::sanitize_internal_redirect( $options['redirect_url'] ?? '' ),
		);
	}

	/**
	 * Save sanitized settings with autoload disabled.
	 *
	 * @param mixed $options Raw settings.
	 * @return bool
	 */
	public static function save( $options ) {
		$options  = self::sanitize( $options );
		$existing = get_option( self::option_key(), null );

		if ( null === $existing ) {
			return add_option( self::option_key(), $options, '', false );
		}

		return update_option( self::option_key(), $options, false );
	}

	/**
	 * Return the Google provider status label.
	 *
	 * @param string $provider Provider key.
	 * @return string
	 */
	public static function provider_status( $provider ) {
		if ( 'google' !== sanitize_key( (string) $provider ) ) {
			return 'Coming Soon';
		}

		$options = self::get_google_options();

		if ( '' === $options['client_id'] ) {
			return 'Not Configured';
		}

		return $options['enabled'] ? 'Enabled' : 'Disabled';
	}

	/**
	 * Return roles allowed for newly registered users.
	 *
	 * @return array<int, string>
	 */
	public static function allowed_registration_roles() {
		$roles   = self::registered_roles();
		$allowed = array( 'subscriber' );

		if ( isset( $roles['customer'] ) ) {
			$allowed[] = 'customer';
		}

		return $allowed;
	}

	/**
	 * Return currently registered roles.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function registered_roles() {
		$role_registry = wp_roles();

		return $role_registry && is_array( $role_registry->roles ) ? $role_registry->roles : array();
	}

	/**
	 * Sanitize a same-site redirect path or URL.
	 *
	 * An empty result means the site homepage.
	 *
	 * @param mixed $value Raw redirect.
	 * @return string
	 */
	public static function sanitize_internal_redirect( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( '/' === $value || ( '/' === substr( $value, 0, 1 ) && '//' !== substr( $value, 0, 2 ) && false === strpos( $value, '\\' ) ) ) {
			return $value;
		}

		$target = wp_parse_url( $value );
		$home   = wp_parse_url( home_url( '/' ) );

		if (
			! is_array( $target ) ||
			! is_array( $home ) ||
			! isset( $target['scheme'], $target['host'], $home['host'] ) ||
			! in_array( strtolower( $target['scheme'] ), array( 'http', 'https' ), true ) ||
			strtolower( $target['host'] ) !== strtolower( $home['host'] ) ||
			(int) ( $target['port'] ?? 0 ) !== (int) ( $home['port'] ?? 0 )
		) {
			return '';
		}

		return esc_url_raw( $value );
	}

	/**
	 * Validate a Google OAuth client ID.
	 *
	 * @param mixed $value Raw client ID.
	 * @return bool
	 */
	public static function is_valid_client_id( $value ) {
		$value = (string) $value;

		return 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*\.apps\.googleusercontent\.com$/', $value );
	}

	/**
	 * Sanitize a client ID without logging it.
	 *
	 * @param mixed $value Raw client ID.
	 * @return string
	 */
	protected static function sanitize_client_id( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value     = trim( (string) $value );
		$sanitized = sanitize_text_field( $value );

		if ( $value !== strip_tags( $value ) || strlen( $sanitized ) > 255 || ! self::is_valid_client_id( $sanitized ) ) {
			return '';
		}

		return $sanitized;
	}

	/**
	 * Sanitize a username prefix.
	 *
	 * @param mixed  $value Raw prefix.
	 * @param string $fallback Safe fallback.
	 * @return string
	 */
	protected static function sanitize_prefix( $value, $fallback ) {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$value = strtolower( sanitize_key( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9_]/', '', $value );
		$value = substr( (string) $value, 0, 32 );

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Sanitize a checkbox value without accepting arrays or arbitrary strings.
	 *
	 * @param mixed $value Raw checkbox value.
	 * @return bool
	 */
	protected static function sanitize_checkbox( $value ) {
		return in_array( $value, array( true, 1, '1', 'on' ), true );
	}
}
