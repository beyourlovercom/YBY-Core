<?php
/** Deferred, native AffiliateWP welcome email for explicitly opted-in ERP onboarding. */
defined( 'ABSPATH' ) || exit;

final class YBY_Connector_Affiliate_Welcome {
	private static $context = null;

	public static function enabled() {
		return defined( 'YBY_ERP_NATIVE_WELCOME_ENABLED' ) && true === YBY_ERP_NATIVE_WELCOME_ENABLED;
	}

	public static function ready() {
		return self::enabled() && function_exists( 'affwp_notify_on_approval' )
			&& function_exists( 'affwp_email_notification_enabled' ) && function_exists( 'affwp_get_affiliate_email' )
			&& function_exists( 'wp_check_password' ) && class_exists( 'Affiliate_WP_Emails' );
	}

	public static function tags( $tags ) {
		if ( ! self::enabled() ) return $tags;
		$tags[] = array( 'tag' => 'initial_password', 'description' => 'Initial password (new ERP welcome only)', 'function' => array( __CLASS__, 'password_tag' ) );
		$tags[] = array( 'tag' => 'coupon_code', 'description' => 'Verified WooCommerce affiliate coupon code', 'function' => array( __CLASS__, 'coupon_tag' ) );
		return $tags;
	}

	public static function password_tag( $id ) {
		return self::$context && self::$context['id'] === (int) $id ? esc_html( self::$context['password'] ) : 'Use your existing password or reset it on login.';
	}

	private static function valid_coupon( $coupon, $id ) {
		$expiry = $coupon->get_date_expires();
		return $coupon->get_id() && 'publish' === get_post_status( $coupon->get_id() )
			&& (int) get_post_meta( $coupon->get_id(), 'affwp_discount_affiliate', true ) === (int) $id
			&& 'percent' === $coupon->get_discount_type() && 20.0 === (float) $coupon->get_amount()
			&& ( ! $expiry || $expiry->getTimestamp() > time() );
	}

	public static function coupon_tag( $id ) {
		if ( self::$context && self::$context['id'] === (int) $id ) return esc_html( self::$context['coupon'] );
		if ( ! class_exists( 'WC_Coupon' ) || (int) $id < 1 ) return '';
		// Preview/non-ERP emails: use a unique live matching coupon, never guess among multiple codes.
		$ids = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'fields' => 'ids',
			'posts_per_page' => 2, 'meta_key' => 'affwp_discount_affiliate', 'meta_value' => (string) (int) $id ) );
		if ( count( $ids ) !== 1 ) return '';
		try { $coupon = new WC_Coupon( $ids[0] ); return self::valid_coupon( $coupon, $id ) ? esc_html( $coupon->get_code() ) : ''; }
		catch ( Throwable $e ) { return ''; }
	}

	public static function enroll( $input, $result ) {
		if ( 'active' !== ( $result['affiliate_status'] ?? '' ) ) return false;
		$id = (int) $result['affiliate_id'];
		$pending = array( 'connection' => $input['connection_key'], 'kol' => (int) $input['erp_kol_id'], 'user' => (int) $result['wp_user_id'] );
		$key = 'yby_erp_welcome_pending_' . $id;
		$previous = get_option( $key, false );
		if ( is_array( $previous ) ) return array_intersect_key( $previous, $pending ) === $pending;
		if ( empty( $result['created_user'] ) || empty( $result['created_affiliate'] ) ) return false;
		$pending['created_at'] = time();
		return add_option( $key, $pending, '', false );
	}

	// Password is supplied again by the same live ERP create request; never stored here.
	public static function complete( $input, $result, $password ) {
		if ( ! self::enabled() ) return null;
		$id = (int) ( $result['affiliate_id'] ?? 0 );
		$pending = get_option( 'yby_erp_welcome_pending_' . $id, false );
		if ( ! is_array( $pending ) ) return null === $password ? null : 'needs_review';
		if ( $pending['connection'] !== $input['connection_key'] || $pending['kol'] !== (int) $input['erp_kol_id'] ) return 'needs_review';
		$key = 'yby_erp_welcome_dispatch_' . $id;
		$state = get_option( $key, false );
		if ( false !== $state ) return 'mail_accepted' === $state ? $state : 'needs_review';
		if ( ! self::ready() || ! is_string( $password ) || '' === $password || (int) ( $pending['created_at'] ?? 0 ) < time() - 900 ) return 'needs_review';
		$binding = YBY_Connector_Affiliate_Bindings::by_kol( $input['connection_key'], $input['erp_kol_id'] );
		$cb = YBY_Connector_Coupon_Bindings::by_code( $input['normalized_code'] );
		$a = affwp_get_affiliate( $id );
		$u = get_user_by( 'id', $pending['user'] );
		$coupon = new WC_Coupon( (int) $result['coupon_id'] );
		if ( ! $binding || 'ready' !== $binding['state'] || (int) $binding['affiliate_id'] !== $id || (int) $binding['wp_user_id'] !== $pending['user']
			|| ! $cb || 'ready' !== $cb['state'] || $cb['connection_key'] !== $pending['connection'] || (int) $cb['erp_kol_id'] !== $pending['kol']
			|| (int) $cb['affiliate_id'] !== $id || (int) $cb['coupon_id'] !== (int) $result['coupon_id']
			|| ! $a || is_wp_error( $a ) || 'active' !== $a->status || (int) $a->user_id !== $pending['user']
			|| ! $u || ! is_email( $u->user_email ) || 0 !== strcasecmp( $u->user_email, affwp_get_affiliate_email( $id ) )
			|| ! wp_check_password( $password, $u->user_pass, $u->ID ) || ! self::valid_coupon( $coupon, $id )
			|| 0 !== strcasecmp( $coupon->get_code(), $input['normalized_code'] )
			|| ! affwp_email_notification_enabled( 'affiliate_application_accepted_email' ) || get_user_meta( $u->ID, 'affwp_disable_affiliate_email', true ) ) return 'needs_review';
		$body = (string) affiliate_wp()->settings->get( 'accepted_email', '' );
		$subject = (string) affiliate_wp()->settings->get( 'accepted_subject', '' );
		if ( false === strpos( $body, '{initial_password}' ) || false === strpos( $body, '{coupon_code}' ) || false !== strpos( $subject, '{initial_password}' ) ) return 'needs_review';

		// Unique option_name is an atomic claim; interrupted/ambiguous sends are never retried.
		global $wpdb;
		if ( 1 !== $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')", $key, 'sending' ) ) ) return 'needs_review';
		wp_cache_delete( $key, 'options' ); wp_cache_delete( 'notoptions', 'options' );
		$accepted = false; $seen = null;
		// Only the expected native message may pass during this scoped call. Respect an existing veto.
		$match = static function ( $mail ) use ( $u, $password, $coupon ) {
			$to = (array) ( $mail['to'] ?? array() );
			$body = (string) ( $mail['message'] ?? '' );
			$headers = implode( "\n", (array) ( $mail['headers'] ?? array() ) );
			return ! preg_match( '/(?:^|[\r\n])\s*(?:cc|bcc)\s*:/i', $headers ) && count( $to ) === 1 && 0 === strcasecmp( trim( (string) reset( $to ) ), $u->user_email )
				&& false !== strpos( $body, esc_html( $password ) ) && false !== strpos( $body, esc_html( $coupon->get_code() ) );
		};
		$guard = static function ( $pre, $mail ) use ( $match, &$seen ) {
			if ( null !== $pre ) return $pre;
			if ( null !== $seen || ! $match( $mail ) ) return false;
			$seen = hash( 'sha256', (string) $mail['message'] );
			return null;
		};
		$observe = static function ( $mail ) use ( &$accepted, &$seen, $match ) {
			if ( $seen && $match( $mail ) && hash_equals( $seen, hash( 'sha256', (string) $mail['message'] ) ) ) $accepted = true;
		};
		// WP Mail SMTP and other late mailer hooks must not add recipients.
		$mailer_guard = static function ( $mailer ) use ( $u ) {
			$to = $mailer->getToAddresses();
			if ( count( $to ) !== 1 || 0 !== strcasecmp( (string) ( $to[0][0] ?? '' ), $u->user_email )
				|| $mailer->getCcAddresses() || $mailer->getBccAddresses() ) throw new RuntimeException( 'Welcome mail recipient mismatch.' );
		};
		self::$context = array( 'id' => $id, 'password' => $password, 'coupon' => $coupon->get_code() );
		add_filter( 'pre_wp_mail', $guard, PHP_INT_MAX, 2 );
		add_action( 'phpmailer_init', $mailer_guard, PHP_INT_MAX );
		add_action( 'wp_mail_succeeded', $observe );
		try { affwp_notify_on_approval( $id, 'active', 'pending' ); }
		catch ( Throwable $e ) { $accepted = false; }
		finally {
			self::$context = null;
			remove_action( 'wp_mail_succeeded', $observe );
			remove_filter( 'pre_wp_mail', $guard, PHP_INT_MAX );
			remove_action( 'phpmailer_init', $mailer_guard, PHP_INT_MAX );
			update_option( $key, $accepted ? 'mail_accepted' : 'needs_review', false );
		}
		return $accepted ? 'mail_accepted' : 'needs_review';
	}
}
