<?php
/**
 * Google-backed WordPress user authentication.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps verified Google identities to restricted WordPress users.
 */
class YBY_Google_Auth_Service {

	/**
	 * Authenticate a verified Google identity.
	 *
	 * @param array<string, mixed> $claims Verified token claims.
	 * @param array<string, mixed> $settings Sanitized Google settings.
	 * @return \WP_User|\WP_Error
	 */
	public function authenticate( $claims, $settings ) {
		$sub   = (string) $claims['sub'];
		$email = (string) $claims['email'];
		$users = get_users(
			array(
				'meta_key'   => '_yby_google_sub',
				'meta_value' => $sub,
				'number'     => 2,
				'fields'     => 'all',
			)
		);

		if ( count( $users ) > 1 ) {
			return new \WP_Error( 'identity_conflict' );
		}

		if ( 1 === count( $users ) ) {
			$user = reset( $users );

			if ( ! $user instanceof \WP_User ) {
				return new \WP_Error( 'login_failed' );
			}

			return $this->login_existing_user( $user, $settings );
		}

		if ( ! $this->is_authoritative_registration_email( $email, $claims ) ) {
			return new \WP_Error( 'email_verification_required' );
		}

		$existing_user = get_user_by( 'email', $email );

		if ( $existing_user ) {
			if ( empty( $settings['auto_link_existing_accounts'] ) ) {
				return new \WP_Error( 'existing_account_requires_login' );
			}

			if ( ! $existing_user instanceof \WP_User ) {
				return new \WP_Error( 'identity_conflict' );
			}

			return $this->associate_existing_user( $existing_user, $claims, $settings );
		}

		return $this->register_user( $claims, $settings );
	}

	/**
	 * Log in a mapped returning user.
	 *
	 * @param \WP_User            $user Existing user.
	 * @param array<string, mixed> $settings Google settings.
	 * @return \WP_User|\WP_Error
	 */
	protected function login_existing_user( $user, $settings ) {
		$disabled_roles = isset( $settings['disabled_roles'] ) && is_array( $settings['disabled_roles'] ) ? $settings['disabled_roles'] : array();

		if ( array_intersect( $user->roles, $disabled_roles ) ) {
			return new \WP_Error( 'role_not_allowed' );
		}

		update_user_meta( $user->ID, '_yby_social_last_login_at', gmdate( 'c' ) );

		return $this->establish_session( $user );
	}

	/**
	 * Associate a verified identity with one existing safe-role account.
	 *
	 * @param \WP_User            $user Existing WordPress user.
	 * @param array<string, mixed> $claims Verified token claims.
	 * @param array<string, mixed> $settings Google settings.
	 * @return \WP_User|\WP_Error
	 */
	protected function associate_existing_user( $user, $claims, $settings ) {
		$safe_roles     = YBY_Social_Login::allowed_registration_roles();
		$user_roles     = array_values( array_unique( array_map( 'sanitize_key', (array) $user->roles ) ) );
		$disabled_roles = isset( $settings['disabled_roles'] ) && is_array( $settings['disabled_roles'] ) ? $settings['disabled_roles'] : array();

		if ( empty( $user_roles ) || array_diff( $user_roles, $safe_roles ) || array_intersect( $user_roles, $disabled_roles ) ) {
			return new \WP_Error( 'role_not_allowed' );
		}

		$sub            = (string) $claims['sub'];
		$existing_sub   = (string) get_user_meta( $user->ID, '_yby_google_sub', true );
		$mapped_users   = $this->get_users_by_sub( $sub );

		if ( '' !== $existing_sub && ! hash_equals( $existing_sub, $sub ) ) {
			return new \WP_Error( 'identity_conflict' );
		}

		if ( ! empty( $mapped_users ) ) {
			return new \WP_Error( 'identity_conflict' );
		}

		if ( ! add_user_meta( $user->ID, '_yby_google_sub', $sub, true ) ) {
			return new \WP_Error( 'identity_conflict' );
		}

		$mapped_users = $this->get_users_by_sub( $sub );

		if ( 1 !== count( $mapped_users ) || (int) $mapped_users[0]->ID !== (int) $user->ID ) {
			delete_user_meta( $user->ID, '_yby_google_sub', $sub );
			return new \WP_Error( 'identity_conflict' );
		}

		$timestamp = gmdate( 'c' );
		update_user_meta( $user->ID, '_yby_social_provider', 'google' );
		update_user_meta( $user->ID, '_yby_social_registered_at', $timestamp );
		update_user_meta( $user->ID, '_yby_social_last_login_at', $timestamp );

		$picture = $this->sanitize_picture_url( $claims['picture'] ?? '' );

		if ( '' !== $picture ) {
			update_user_meta( $user->ID, '_yby_social_avatar_url', $picture );
		}

		return $this->establish_session( $user );
	}

	/**
	 * Return at most two users mapped to a Google subject.
	 *
	 * @param string $sub Google subject.
	 * @return array<int, \WP_User>
	 */
	protected function get_users_by_sub( $sub ) {
		return get_users(
			array(
				'meta_key'   => '_yby_google_sub',
				'meta_value' => $sub,
				'number'     => 2,
				'fields'     => 'all',
			)
		);
	}

	/**
	 * Register and log in one safely restricted user.
	 *
	 * @param array<string, mixed> $claims Verified token claims.
	 * @param array<string, mixed> $settings Google settings.
	 * @return \WP_User|\WP_Error
	 */
	protected function register_user( $claims, $settings ) {
		$allowed_roles = YBY_Social_Login::allowed_registration_roles();
		$role          = isset( $settings['default_role'] ) ? sanitize_key( $settings['default_role'] ) : 'subscriber';

		if ( ! in_array( $role, $allowed_roles, true ) ) {
			$role = 'subscriber';
		}

		$disabled_roles = isset( $settings['disabled_roles'] ) && is_array( $settings['disabled_roles'] ) ? $settings['disabled_roles'] : array();

		if ( in_array( $role, $disabled_roles, true ) ) {
			return new \WP_Error( 'role_not_allowed' );
		}

		$username = $this->generate_unique_username( $settings );

		if ( '' === $username ) {
			return new \WP_Error( 'registration_failed' );
		}

		$first_name = $this->sanitize_name( $claims['given_name'] ?? '' );
		$last_name  = $this->sanitize_name( $claims['family_name'] ?? '' );
		$name       = $this->sanitize_name( $claims['name'] ?? '' );
		$user_id    = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'user_email'   => (string) $claims['email'],
				'role'         => $role,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => '' !== $name ? $name : $username,
			)
		);

		if ( is_wp_error( $user_id ) || ! $user_id ) {
			return new \WP_Error( 'registration_failed' );
		}

		$timestamp = gmdate( 'c' );
		update_user_meta( $user_id, '_yby_google_sub', (string) $claims['sub'] );
		update_user_meta( $user_id, '_yby_social_provider', 'google' );
		update_user_meta( $user_id, '_yby_social_registered_at', $timestamp );
		update_user_meta( $user_id, '_yby_social_last_login_at', $timestamp );

		$picture = $this->sanitize_picture_url( $claims['picture'] ?? '' );

		if ( '' !== $picture ) {
			update_user_meta( $user_id, '_yby_social_avatar_url', $picture );
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'login_failed' );
		}

		return $this->establish_session( $user );
	}

	/**
	 * Establish the standard WordPress authentication session.
	 *
	 * @param \WP_User $user User to log in.
	 * @return \WP_User|\WP_Error
	 */
	protected function establish_session( $user ) {
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );

		return $user;
	}

	/**
	 * Check Google's authoritative-email rule for automatic registration.
	 *
	 * @param string               $email Verified email.
	 * @param array<string, mixed> $claims Verified claims.
	 * @return bool
	 */
	protected function is_authoritative_registration_email( $email, $claims ) {
		if ( 1 === preg_match( '/@gmail\.com$/i', $email ) ) {
			return true;
		}

		return true === ( $claims['email_verified'] ?? false ) && isset( $claims['hd'] ) && is_string( $claims['hd'] ) && '' !== trim( $claims['hd'] );
	}

	/**
	 * Generate a non-identifying unique username.
	 *
	 * @param array<string, mixed> $settings Google settings.
	 * @return string
	 */
	protected function generate_unique_username( $settings ) {
		$prefixes = array(
			(string) ( $settings['username_prefix'] ?? 'google_' ),
			(string) ( $settings['fallback_prefix'] ?? 'user_' ),
		);

		foreach ( $prefixes as $prefix ) {
			$prefix = substr( preg_replace( '/[^a-z0-9_]/', '', strtolower( $prefix ) ), 0, 32 );

			for ( $attempt = 0; $attempt < 10; $attempt++ ) {
				$suffix   = strtolower( wp_generate_password( 12, false, false ) );
				$username = sanitize_user( $prefix . $suffix, true );

				if ( '' !== $username && ! username_exists( $username ) ) {
					return $username;
				}
			}
		}

		return '';
	}

	/**
	 * Sanitize a provider name claim.
	 *
	 * @param mixed $value Name claim.
	 * @return string
	 */
	protected function sanitize_name( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return substr( sanitize_text_field( (string) $value ), 0, 100 );
	}

	/**
	 * Retain a valid HTTPS provider picture URL.
	 *
	 * @param mixed $value Picture claim.
	 * @return string
	 */
	protected function sanitize_picture_url( $value ) {
		if ( ! is_string( $value ) || strlen( $value ) > 2048 ) {
			return '';
		}

		$url    = esc_url_raw( $value, array( 'https' ) );
		$parsed = wp_parse_url( $url );

		return is_array( $parsed ) && 'https' === strtolower( $parsed['scheme'] ?? '' ) && ! empty( $parsed['host'] ) ? $url : '';
	}
}
