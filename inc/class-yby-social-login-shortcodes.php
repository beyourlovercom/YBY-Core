<?php
/**
 * Public Social Login shortcodes.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Google Identity Services HTML integration.
 */
class YBY_Social_Login_Shortcodes {

	/**
	 * Whether the page-level Google configuration has been rendered.
	 *
	 * @var bool
	 */
	protected static $configuration_rendered = false;

	/**
	 * Shared request nonce for every Google button on the page.
	 *
	 * @var string
	 */
	protected static $login_nonce = '';

	/**
	 * Whether this request has already been marked non-cacheable.
	 *
	 * @var bool
	 */
	protected static $cache_disabled = false;

	/**
	 * Register the public shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'yby_social_login', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'maybe_disable_page_cache' ), 0 );
	}

	/**
	 * Disable page caching before headers when the current page stores a Google login shortcode.
	 *
	 * @return void
	 */
	public function maybe_disable_page_cache() {
		if ( is_admin() || ! is_singular() || ! $this->google_login_is_enabled() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();

		if ( $post_id < 1 ) {
			return;
		}

		$content = (string) get_post_field( 'post_content', $post_id );
		$bricks  = get_post_meta( $post_id, '_bricks_page_content_2', true );

		if ( $this->contains_google_shortcode( $content ) || $this->contains_google_shortcode( $bricks ) ) {
			$this->disable_page_cache();
		}
	}

	/**
	 * Render one Google login button.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	public function render( $attributes = array() ) {
		$attributes = shortcode_atts(
			array( 'provider' => '' ),
			is_array( $attributes ) ? $attributes : array(),
			'yby_social_login'
		);
		$provider   = sanitize_key( $attributes['provider'] );

		if ( 'google' !== $provider || is_user_logged_in() ) {
			return '';
		}

		$settings = YBY_Social_Login::get_google_options();

		if ( empty( $settings['enabled'] ) || '' === $settings['client_id'] ) {
			return '';
		}

		$this->disable_page_cache();

		if ( '' === self::$login_nonce ) {
			self::$login_nonce = YBY_Social_Login::issue_login_nonce();
		}

		if ( '' === self::$login_nonce ) {
			return '';
		}

		wp_enqueue_script(
			'yby-google-identity-services',
			'https://accounts.google.com/gsi/client',
			array(),
			null,
			true
		);

		$output = '';

		if ( ! self::$configuration_rendered ) {
			$output .= $this->render_status_message();
			$output .= '<div id="g_id_onload"';
			$output .= ' data-client_id="' . esc_attr( $settings['client_id'] ) . '"';
			$output .= ' data-login_uri="' . esc_url( rest_url( 'yby/v1/auth/google' ) ) . '"';
			$output .= ' data-ux_mode="redirect"';
			$output .= ' data-auto_prompt="false"';
			$output .= ' data-auto_select="false"';
			$output .= ' data-button_auto_select="false"';
			$output .= ' data-nonce="' . esc_attr( self::$login_nonce ) . '"';
			$output .= '></div>';
			self::$configuration_rendered = true;
		}

		$output .= '<div class="yby-social-login yby-social-login--google">';
		$output .= '<div class="g_id_signin"';
		$output .= ' data-type="standard"';
		$output .= ' data-shape="rectangular"';
		$output .= ' data-theme="outline"';
		$output .= ' data-text="signin_with"';
		$output .= ' data-size="large"';
		$output .= ' data-logo_alignment="left"';
		$output .= ' data-width="280"';
		$output .= ' data-locale="en"';
		$output .= '></div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a generic, non-identifying status message.
	 *
	 * @return string
	 */
	protected function render_status_message() {
		$code = isset( $_GET['yby_social_login'] ) ? sanitize_key( wp_unslash( $_GET['yby_social_login'] ) ) : '';

		if ( 'success' === $code ) {
			return '<p class="yby-social-login__message yby-social-login__message--success">' . esc_html__( 'You are signed in.', 'yby-core' ) . '</p>';
		}

		$messages = array(
			'google_not_configured'            => 'Google sign-in is not configured.',
			'google_login_disabled'            => 'Google sign-in is currently unavailable.',
			'invalid_request'                  => 'The Google login response was incomplete. Refresh the page and try again.',
			'invalid_csrf'                     => 'The login security check failed. Refresh the page and try again.',
			'invalid_nonce'                    => 'The login session expired. Refresh the page and try again.',
			'invalid_google_token'             => 'Google identity verification failed.',
			'email_required'                   => 'Google did not provide an email address for this account.',
			'email_verification_required'      => 'Google could not confirm this account email.',
			'existing_account_requires_login' => 'An account already exists with this email. Sign in using the existing account first.',
			'identity_conflict'                => 'This Google account could not be matched safely. Please contact site support.',
			'role_not_allowed'                 => 'Google login is not available for this account role.',
			'registration_failed'              => 'The account could not be created.',
			'login_failed'                     => 'The account was verified, but sign-in could not be completed.',
			'verification_unavailable'         => 'Google verification is temporarily unavailable. Please try again later.',
		);

		if ( ! isset( $messages[ $code ] ) ) {
			return '';
		}

		return '<p class="yby-social-login__message yby-social-login__message--error">' . esc_html__( $messages[ $code ], 'yby-core' ) . '</p>';
	}

	/**
	 * Determine whether Google login has complete active settings.
	 *
	 * @return bool
	 */
	protected function google_login_is_enabled() {
		$settings = YBY_Social_Login::get_google_options();

		return ! empty( $settings['enabled'] ) && '' !== $settings['client_id'];
	}

	/**
	 * Recursively inspect content or Bricks data for an active Google shortcode.
	 *
	 * @param mixed $value Content or builder data.
	 * @return bool
	 */
	protected function contains_google_shortcode( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( $this->contains_google_shortcode( $item ) ) {
					return true;
				}
			}

			return false;
		}

		if ( ! is_string( $value ) || false === stripos( $value, '[yby_social_login' ) ) {
			return false;
		}

		if ( ! preg_match_all( '/\[yby_social_login\b([^\]]*)\]/i', $value, $matches ) ) {
			return false;
		}

		foreach ( $matches[1] as $attribute_string ) {
			$attributes = shortcode_parse_atts( $attribute_string );

			if ( is_array( $attributes ) && 'google' === sanitize_key( $attributes['provider'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Mark only the active Google login request as non-cacheable.
	 *
	 * @return void
	 */
	protected function disable_page_cache() {
		if ( self::$cache_disabled ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( ! headers_sent() ) {
			nocache_headers();
		}

		do_action( 'litespeed_control_set_nocache', 'Andy Core Google login nonce' );
		self::$cache_disabled = true;
	}

	/**
	 * Reset request-level state for deterministic tests.
	 *
	 * @return void
	 */
	public static function reset_request_state() {
		self::$configuration_rendered = false;
		self::$login_nonce            = '';
		self::$cache_disabled         = false;
	}
}
