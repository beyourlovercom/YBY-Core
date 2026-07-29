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
	 * Register the public shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'yby_social_login', array( $this, 'render' ) );
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
			$output .= '<div id="yby-google-identity-config"';
			$output .= ' class="g_id_onload"';
			$output .= ' data-client_id="' . esc_attr( $settings['client_id'] ) . '"';
			$output .= ' data-login_uri="' . esc_url( rest_url( 'yby/v1/auth/google' ) ) . '"';
			$output .= ' data-ux_mode="redirect"';
			$output .= ' data-auto_select="false"';
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

		$errors = array(
			'google_not_configured',
			'google_login_disabled',
			'invalid_request',
			'invalid_csrf',
			'invalid_nonce',
			'invalid_google_token',
			'email_required',
			'email_verification_required',
			'existing_account_requires_login',
			'identity_conflict',
			'role_not_allowed',
			'registration_failed',
			'login_failed',
			'verification_unavailable',
		);

		if ( ! in_array( $code, $errors, true ) ) {
			return '';
		}

		return '<p class="yby-social-login__message yby-social-login__message--error">' . esc_html__( 'Google sign-in could not be completed. Please use another sign-in method or try again.', 'yby-core' ) . '</p>';
	}

	/**
	 * Reset request-level state for deterministic tests.
	 *
	 * @return void
	 */
	public static function reset_request_state() {
		self::$configuration_rendered = false;
		self::$login_nonce            = '';
	}
}
