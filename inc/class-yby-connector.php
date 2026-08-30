<?php
/**
 * BYL ERP WordPress Connector foundation.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-yby-subscriber-snapshot.php';

/**
 * Connector foundation settings and truthful environment detection.
 */
class YBY_Connector {
	const OPTION = 'yby_core_connector_options';
	const SECRET_OPTION = 'yby_core_connector_secret';
	const CONTRACT_VERSION = '1';
	const TIMESTAMP_TOLERANCE = 300;
	const NONCE_TTL = 600;
	const RATE_LIMIT_WINDOW = 60;
	const RATE_LIMIT_MAX_REQUESTS = 60;
	const SNAPSHOT_MAX_LIMIT = 100;
	const REST_NAMESPACE = 'andy-core/v1/erp';

	public static function defaults() {
		return array(
			'enabled'        => false,
			'connection_key' => '',
			'key_id'         => '',
		);
	}

	/**
	 * Canonical signing string: METHOD + "\n" + exact PATH_WITH_QUERY + "\n" +
	 * TIMESTAMP + "\n" + NONCE + "\n" + CONNECTION_KEY + "\n" +
	 * IDEMPOTENCY_KEY + "\n" + SHA256(RAW_BODY).
	 */
	public static function canonical_string( $method, $path_with_query, $timestamp, $nonce, $connection_key, $idempotency_key, $body = '' ) {
		return strtoupper( (string) $method ) . "\n" . (string) $path_with_query . "\n" . (string) $timestamp . "\n" . (string) $nonce . "\n" . (string) $connection_key . "\n" . (string) $idempotency_key . "\n" . hash( 'sha256', (string) $body );
	}

	public static function sign( $method, $path_with_query, $timestamp, $nonce, $connection_key, $idempotency_key, $body, $secret ) {
		return hash_hmac( 'sha256', self::canonical_string( $method, $path_with_query, $timestamp, $nonce, $connection_key, $idempotency_key, $body ), (string) $secret );
	}

	public static function secret_configured() { return '' !== self::get_secret(); }

	/** Generate/rotate and return the secret only to the immediate admin response. */
	public static function generate_secret() {
		$secret = function_exists( 'wp_generate_password' ) ? wp_generate_password( 64, true, true ) : bin2hex( random_bytes( 32 ) );
		return self::store_secret( $secret ) ? $secret : false;
	}

	private static function get_secret() {
		$stored = get_option( self::SECRET_OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['ciphertext'] ) || empty( $stored['iv'] ) ) { return ''; }
		return self::decrypt_secret( $stored['ciphertext'], $stored['iv'] );
	}

	private static function store_secret( $secret ) {
		$encrypted = self::encrypt_secret( $secret );
		if ( false === $encrypted ) { return false; }
		$stored = array( 'ciphertext' => $encrypted['ciphertext'], 'iv' => $encrypted['iv'], 'mac' => $encrypted['mac'], 'version' => 1 );
		return false === get_option( self::SECRET_OPTION, false ) ? add_option( self::SECRET_OPTION, $stored, '', false ) : update_option( self::SECRET_OPTION, $stored, false );
	}

	private static function encryption_key() {
		$salts = array();
		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $constant ) {
			if ( ! defined( $constant ) || '' === constant( $constant ) ) { return ''; }
			$salts[] = constant( $constant );
		}
		return hash( 'sha256', implode( '|', $salts ), true );
	}

	private static function encrypt_secret( $secret ) {
		$key = self::encryption_key();
		if ( ! function_exists( 'openssl_encrypt' ) || '' === $key ) { return false; }
		$iv = function_exists( 'random_bytes' ) ? random_bytes( 16 ) : openssl_random_pseudo_bytes( 16 );
		$ciphertext = openssl_encrypt( (string) $secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $ciphertext ) { return false; }
		$ciphertext = base64_encode( $ciphertext );
		$encoded_iv = base64_encode( $iv );
		return array( 'ciphertext' => $ciphertext, 'iv' => $encoded_iv, 'mac' => hash_hmac( 'sha256', $encoded_iv . '|' . $ciphertext, $key ) );
	}

	private static function decrypt_secret( $ciphertext, $iv ) {
		$key = self::encryption_key();
		if ( ! function_exists( 'openssl_decrypt' ) || '' === $key ) { return ''; }
		$stored = get_option( self::SECRET_OPTION, array() );
		if ( ! is_string( $ciphertext ) || ! is_string( $iv ) || ! is_array( $stored ) || ! is_string( $stored['mac'] ?? null ) || '' === $stored['mac'] || ! hash_equals( $stored['mac'], hash_hmac( 'sha256', $iv . '|' . $ciphertext, $key ) ) ) { return ''; }
		$decoded_ciphertext = base64_decode( (string) $ciphertext, true );
		$decoded_iv = base64_decode( (string) $iv, true );
		if ( false === $decoded_ciphertext || '' === $decoded_ciphertext || false === $decoded_iv || 16 !== strlen( $decoded_iv ) ) { return ''; }
		$plain = openssl_decrypt( $decoded_ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $decoded_iv );
		return false === $plain ? '' : (string) $plain;
	}

	public static function authenticate( $request ) {
		if ( ! self::is_https() ) { return self::auth_error( 'AUTH_INVALID', 'HTTPS is required.', 403, false ); }
		$options = self::get_options();
		$secret = self::get_secret();
		$headers = array();
		foreach ( array( 'connection_key' => 'X-YBY-Connection-Key', 'key_id' => 'X-YBY-Key-Id', 'timestamp' => 'X-YBY-Timestamp', 'nonce' => 'X-YBY-Nonce', 'idempotency_key' => 'X-YBY-Idempotency-Key', 'signature' => 'X-YBY-Signature' ) as $key => $header ) { $headers[ $key ] = self::request_header( $request, $header ); }
		$headers['signature_version'] = self::request_header_exact( $request, 'X-YBY-Signature-Version' );
		if ( 'v1' !== $headers['signature_version'] ) { return self::auth_error( 'CONTRACT_VERSION_UNSUPPORTED', 'Signature version must be v1.', 400, false ); }
		if ( empty( $options['enabled'] ) || ! self::has_identity( $options ) || '' === $secret || $headers['connection_key'] !== $options['connection_key'] || $headers['key_id'] !== $options['key_id'] ) { return self::auth_error( 'AUTH_INVALID', 'Request identity is invalid.', 401, false ); }
		if ( ! ctype_digit( $headers['timestamp'] ) || abs( time() - (int) $headers['timestamp'] ) > self::TIMESTAMP_TOLERANCE ) { return self::auth_error( 'AUTH_INVALID', 'Request timestamp is stale or invalid.', 401, false ); }
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $headers['nonce'] ) || '' === $headers['signature'] ) { return self::auth_error( 'AUTH_INVALID', 'Request authentication fields are invalid.', 401, false ); }
		$expected = self::sign( self::request_method( $request ), self::request_path_with_query( $request ), $headers['timestamp'], $headers['nonce'], $headers['connection_key'], $headers['idempotency_key'], self::request_body( $request ), $secret );
		if ( ! hash_equals( $expected, $headers['signature'] ) ) { return self::auth_error( 'AUTH_INVALID', 'Request signature is invalid.', 401, false ); }
		$nonce_key = 'yby_conn_nonce_' . hash( 'sha256', $headers['key_id'] . ':' . $headers['connection_key'] . ':' . $headers['nonce'] );
		if ( get_transient( $nonce_key ) ) { return self::auth_error( 'REPLAY_DETECTED', 'Request nonce has already been used.', 409, false ); }
		$rate_key = 'yby_conn_rate_' . hash( 'sha256', $headers['key_id'] . ':' . $headers['connection_key'] );
		$count = (int) get_transient( $rate_key );
		if ( $count >= self::RATE_LIMIT_MAX_REQUESTS ) { return self::auth_error( 'RATE_LIMITED', 'Rate limit exceeded.', 429, true ); }
		set_transient( $nonce_key, 1, self::NONCE_TTL );
		set_transient( $rate_key, $count + 1, self::RATE_LIMIT_WINDOW );
		return true;
	}

	private static function auth_error( $code, $message, $status, $retryable ) {
		return new WP_Error( $code, $message, array( 'status' => (int) $status, 'retryable' => (bool) $retryable ) );
	}

	public static function is_https() { return function_exists( 'is_ssl' ) && is_ssl(); }

	public static function register_routes() {
		register_rest_route( self::REST_NAMESPACE, '/health', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'dispatch_health' ), 'permission_callback' => '__return_true' ) );
		foreach ( array( 'affiliates', 'coupons', 'referrals', 'payouts', 'subscribers' ) as $resource ) {
			register_rest_route( self::REST_NAMESPACE, '/snapshot/' . $resource, array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'dispatch_snapshot' ), 'permission_callback' => '__return_true' ) );
		}
	}

	public static function dispatch_health( $request ) {
		$auth = self::authenticate( $request );
		if ( is_wp_error( $auth ) ) { return self::error_response( $auth, $request ); }
		return self::health( $request );
	}

	/** Dispatch one of the read-only Connector snapshots after HMAC auth. */
	public static function dispatch_snapshot( $request ) {
		$auth = self::authenticate( $request );
		if ( is_wp_error( $auth ) ) { return self::error_response( $auth, $request ); }
		$route = is_object( $request ) && method_exists( $request, 'get_route' ) ? (string) $request->get_route() : '';
		$resource = basename( trim( $route, '/' ) );
		if ( ! in_array( $resource, array( 'affiliates', 'coupons', 'referrals', 'payouts', 'subscribers' ), true ) ) {
			return self::error_response( new WP_Error( 'VALIDATION_FAILED', 'Snapshot resource is not supported.', array( 'status' => 400, 'retryable' => false ) ), $request );
		}
		$result = self::snapshot( $resource, $request );
		if ( is_wp_error( $result ) ) { return self::error_response( $result, $request ); }
		return array( 'ok' => true, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => self::request_id(), 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'data' => $result );
	}

	/** Read-only provider snapshot entry point. */
	public static function snapshot( $resource, $request = null ) {
		$query = self::snapshot_query( $request, $resource );
		if ( is_wp_error( $query ) ) { return $query; }
		if ( 'affiliates' === $resource || 'referrals' === $resource || 'payouts' === $resource ) {
			if ( ! self::provider_available( 'affiliatewp' ) || ! function_exists( 'affiliate_wp' ) ) { return self::provider_unavailable(); }
			return self::affiliatewp_snapshot( $resource, $query );
		}
		if ( 'coupons' === $resource ) {
			if ( ! self::provider_available( 'woocommerce' ) || ! function_exists( 'get_posts' ) || ! class_exists( 'WC_Coupon' ) ) { return self::provider_unavailable(); }
			return self::coupon_snapshot( $query );
		}
		if ( 'subscribers' === $resource ) { return YBY_Subscriber_Snapshot::snapshot( $query ); }
		return new WP_Error( 'VALIDATION_FAILED', 'Snapshot resource is not supported.', array( 'status' => 400, 'retryable' => false ) );
	}

	private static function provider_unavailable() {
		return new WP_Error( 'PROVIDER_UNAVAILABLE', 'The required provider is unavailable.', array( 'status' => 503, 'retryable' => true ) );
	}

	private static function snapshot_query( $request, $resource ) {
		$get = function ( $key ) use ( $request ) {
			if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) { return $request->get_param( $key ); }
			return isset( $_GET[ $key ] ) ? ( function_exists( 'wp_unslash' ) ? wp_unslash( $_GET[ $key ] ) : $_GET[ $key ] ) : null;
		};
		$limit = $get( 'limit' );
		if ( null === $limit || '' === $limit ) { $limit = 50; }
		if ( ! is_scalar( $limit ) || ! ctype_digit( (string) $limit ) || (int) $limit < 1 || (int) $limit > self::SNAPSHOT_MAX_LIMIT ) {
			return new WP_Error( 'VALIDATION_FAILED', 'limit must be a positive bounded integer.', array( 'status' => 400, 'retryable' => false ) );
		}
		$cursor = $get( 'cursor' );
		$offset = 0; $cursor_key = null;
		if ( null !== $cursor && '' !== $cursor ) {
			if ( ! is_scalar( $cursor ) ) { return new WP_Error( 'VALIDATION_FAILED', 'cursor is invalid.', array( 'status' => 400, 'retryable' => false ) ); }
			$decoded = base64_decode( strtr( (string) $cursor, '-_', '+/' ), true );
			$state = false === $decoded ? null : json_decode( $decoded, true );
			if ( 'subscribers' === $resource ) {
				if ( ! is_array( $state ) || 'subscribers' !== ( $state['resource'] ?? '' ) || ! isset( $state['email'] ) || ! is_string( $state['email'] ) || '' === $state['email'] || $state['email'] !== strtolower( trim( $state['email'] ) ) ) { return new WP_Error( 'VALIDATION_FAILED', 'cursor is invalid.', array( 'status' => 400, 'retryable' => false ) ); }
				$cursor_key = $state['email'];
			} elseif ( ! is_array( $state ) || ! isset( $state['resource'], $state['offset'] ) || $state['resource'] !== $resource || ! ctype_digit( (string) $state['offset'] ) ) { return new WP_Error( 'VALIDATION_FAILED', 'cursor is invalid.', array( 'status' => 400, 'retryable' => false ) ); }
			else { $offset = (int) $state['offset']; }
		}
		$updated_after = $get( 'updated_after' );
		if ( null !== $updated_after && '' !== $updated_after ) {
			if ( ! is_scalar( $updated_after ) || false === self::iso_timestamp( (string) $updated_after ) ) { return new WP_Error( 'VALIDATION_FAILED', 'updated_after must be ISO-8601.', array( 'status' => 400, 'retryable' => false ) ); }
			$updated_after = (string) $updated_after;
		} else { $updated_after = null; }
		return array( 'resource' => $resource, 'limit' => (int) $limit, 'offset' => $offset, 'cursor_key' => $cursor_key, 'updated_after' => $updated_after );
	}

	private static function iso_timestamp( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) { return false; }
		try { $date = new DateTime( $value ); } catch ( Exception $e ) { return false; }
		return $date instanceof DateTime ? $date->getTimestamp() : false;
	}

	public static function timestamp( $value ) { return self::iso_timestamp( $value ); }

	private static function snapshot_cursor( $resource, $offset ) {
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( array( 'resource' => $resource, 'offset' => (int) $offset ) ) : json_encode( array( 'resource' => $resource, 'offset' => (int) $offset ) );
		return rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	}

	private static function snapshot_result( $items, $query, $has_more ) {
		return array( 'items' => array_slice( $items, 0, $query['limit'] ), 'next_cursor' => $has_more ? self::snapshot_cursor( $query['resource'], $query['offset'] + $query['limit'] ) : null );
	}

	private static function affiliatewp_snapshot( $resource, $query ) {
		$provider = affiliate_wp();
		if ( ! is_object( $provider ) || ! isset( $provider->affiliates ) ) { return self::provider_unavailable(); }
		$collection = 'affiliates' === $resource ? $provider->affiliates : ( 'referrals' === $resource ? $provider->referrals : ( isset( $provider->affiliates->payouts ) ? $provider->affiliates->payouts : null ) );
		$method = 'affiliates' === $resource ? 'get_affiliates' : ( 'referrals' === $resource ? 'get_referrals' : 'get_payouts' );
		if ( ! is_object( $collection ) || ! method_exists( $collection, $method ) ) { return self::provider_unavailable(); }
		$args = array( 'number' => $query['limit'] + 1, 'offset' => $query['offset'], 'order' => 'ASC', 'orderby' => 'affiliates' === $resource ? 'affiliate_id' : ( 'referrals' === $resource ? 'referral_id' : 'payout_id' ) );
		if ( $query['updated_after'] ) { $after = gmdate( 'Y-m-d H:i:s', self::iso_timestamp( $query['updated_after'] ) + 1 ); if ( 'affiliates' === $resource ) { $args['date_registered'] = array( 'start' => $after ); } else { $args['date'] = array( 'start' => $after ); } }
		$objects = $collection->{$method}( $args );
		if ( ! is_array( $objects ) ) { return self::provider_unavailable(); }
		$items = array();
		foreach ( $objects as $object ) { $items[] = 'affiliates' === $resource ? self::affiliate_item( $object ) : ( 'referrals' === $resource ? self::referral_item( $object ) : self::payout_item( $object ) ); }
		return self::snapshot_result( $items, $query, count( $objects ) > $query['limit'] );
	}

	private static function affiliate_item( $affiliate ) {
		$id = isset( $affiliate->ID ) ? $affiliate->ID : ( $affiliate->affiliate_id ?? null );
		$user_id = isset( $affiliate->user_id ) ? $affiliate->user_id : 0;
		$user = $user_id && function_exists( 'get_userdata' ) ? get_userdata( $user_id ) : false;
		return array( 'affiliate_id' => $id, 'user_id' => $user_id, 'username' => $user ? (string) $user->user_login : null, 'display_name' => $user ? (string) $user->display_name : (string) ( $affiliate->name ?? '' ), 'email' => $user ? (string) $user->user_email : null, 'status' => (string) ( $affiliate->status ?? '' ), 'rate' => function_exists( 'affwp_get_affiliate_rate' ) ? affwp_get_affiliate_rate( $affiliate ) : null, 'rate_type' => function_exists( 'affwp_get_affiliate_rate_type' ) ? affwp_get_affiliate_rate_type( $affiliate ) : null, 'payment_email' => function_exists( 'affwp_get_affiliate_payment_email' ) ? affwp_get_affiliate_payment_email( $affiliate ) : null, 'registered_at' => self::provider_date( $affiliate->date_registered ?? null ), 'provider_modified_at' => self::provider_date( $affiliate->date_modified ?? null ) );
	}

	private static function referral_item( $referral ) {
		return array( 'referral_id' => $referral->ID ?? ( $referral->referral_id ?? null ), 'affiliate_id' => $referral->affiliate_id ?? null, 'context' => (string) ( $referral->context ?? '' ), 'reference' => (string) ( $referral->reference ?? '' ), 'amount' => (string) ( $referral->amount ?? '' ), 'currency' => (string) ( $referral->currency ?? '' ), 'status' => (string) ( $referral->status ?? '' ), 'description' => isset( $referral->description ) ? (string) $referral->description : null, 'referred_at' => self::provider_date( $referral->date ?? null ), 'payout_id' => $referral->payout_id ?? null );
	}

	private static function payout_item( $payout ) {
		$referral_ids = array();
		if ( function_exists( 'affwp_get_payout_referrals' ) ) { foreach ( (array) affwp_get_payout_referrals( $payout ) as $referral ) { $referral_ids[] = $referral->ID ?? ( $referral->referral_id ?? null ); } }
		$currency = isset( $payout->currency ) ? (string) $payout->currency : ( function_exists( 'affwp_get_currency' ) ? (string) affwp_get_currency() : '' );
		return array( 'payout_id' => $payout->ID ?? ( $payout->payout_id ?? null ), 'affiliate_id' => $payout->affiliate_id ?? null, 'referral_ids' => $referral_ids, 'amount' => (string) ( $payout->amount ?? '' ), 'currency' => $currency, 'payout_method' => (string) ( $payout->payout_method ?? '' ), 'status' => (string) ( $payout->status ?? '' ), 'date' => self::provider_date( $payout->date ?? null ) );
	}

	private static function coupon_snapshot( $query ) {
		$args = array( 'post_type' => 'shop_coupon', 'post_status' => 'any', 'posts_per_page' => $query['limit'] + 1, 'offset' => $query['offset'], 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true );
		if ( $query['updated_after'] ) { $args['date_query'] = array( array( 'column' => 'post_modified_gmt', 'after' => gmdate( 'Y-m-d H:i:s', self::iso_timestamp( $query['updated_after'] ) ), 'inclusive' => false ) ); }
		$ids = get_posts( $args );
		if ( ! is_array( $ids ) ) { return self::provider_unavailable(); }
		$items = array();
		foreach ( $ids as $id ) { $coupon = new WC_Coupon( $id ); $code = (string) $coupon->get_code(); $linked = null; if ( function_exists( 'affwp_get_coupon' ) ) { $affiliate_coupon = affwp_get_coupon( $code ); $linked = $affiliate_coupon && isset( $affiliate_coupon->affiliate_id ) ? (int) $affiliate_coupon->affiliate_id : null; } if ( null === $linked && self::provider_available( 'affiliatewp' ) && function_exists( 'get_post_meta' ) ) { $legacy_link = get_post_meta( (int) $id, 'affwp_discount_affiliate', true ); $linked = is_numeric( $legacy_link ) && (int) $legacy_link > 0 ? (int) $legacy_link : null; } $modified = method_exists( $coupon, 'get_date_modified' ) ? $coupon->get_date_modified() : null; $items[] = array( 'coupon_id' => $coupon->get_id(), 'code' => $code, 'normalized_code' => strtolower( trim( $code ) ), 'discount_type' => (string) $coupon->get_discount_type(), 'amount' => (string) $coupon->get_amount(), 'status' => (string) $coupon->get_status(), 'date_expires' => self::provider_date( $coupon->get_date_expires() ), 'linked_affiliate_id' => $linked, 'provider_modified_at' => self::provider_date( $modified ) ); }
		return self::snapshot_result( $items, $query, count( $ids ) > $query['limit'] );
	}

	private static function provider_date( $value ) {
		if ( $value instanceof DateTimeInterface ) { return $value->format( DateTime::ATOM ); }
		if ( is_string( $value ) && '' !== $value && false !== strtotime( $value ) ) { return gmdate( 'c', strtotime( $value ) ); }
		return null;
	}

	public static function health( $request = null ) {
		$options = self::get_options();
		$providers = self::provider_statuses();
		$connection_key = self::request_header( $request, 'X-YBY-Connection-Key' );
		if ( '' === $connection_key ) { $connection_key = $options['connection_key']; }
		$operational = 'Ready' === self::status( $options ) && self::is_https();
		return array( 'ok' => true, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => self::request_id(), 'connection_key' => $connection_key, 'data' => array( 'site_url' => self::site_url(), 'andy_core_version' => defined( 'YBY_CORE_VERSION' ) ? YBY_CORE_VERSION : '1.5.3', 'connector_contract_version' => self::CONTRACT_VERSION, 'wp_version' => (string) get_bloginfo( 'version' ), 'woocommerce_active' => 'Ready' === $providers['woocommerce']['status'], 'woocommerce_version' => $providers['woocommerce']['version'] ? $providers['woocommerce']['version'] : null, 'affiliatewp_active' => 'Ready' === $providers['affiliatewp']['status'], 'affiliatewp_version' => $providers['affiliatewp']['version'] ? $providers['affiliatewp']['version'] : null, 'server_time' => gmdate( 'c' ), 'writable' => $operational, 'operational' => $operational ) );
	}

	private static function error_response( $error, $request ) {
		$data = method_exists( $error, 'get_error_data' ) ? $error->get_error_data() : array();
		$data = is_array( $data ) ? $data : array();
		$code = method_exists( $error, 'get_error_code' ) ? $error->get_error_code() : ( $error->code ?? 'AUTH_INVALID' );
		$message = method_exists( $error, 'get_error_message' ) ? $error->get_error_message() : 'Request rejected.';
		$payload = array( 'ok' => false, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => self::request_id(), 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'code' => $code, 'message' => $message, 'retryable' => ! empty( $data['retryable'] ) );
		return new WP_REST_Response( $payload, isset( $data['status'] ) ? (int) $data['status'] : 400 );
	}

	private static function request_id() {
		if ( function_exists( 'wp_generate_uuid4' ) ) { return 'req_' . str_replace( '-', '', wp_generate_uuid4() ); }
		try { return 'req_' . bin2hex( random_bytes( 12 ) ); } catch ( Exception $e ) { return 'req_' . uniqid(); }
	}

	private static function request_header( $request, $name ) { return is_object( $request ) && method_exists( $request, 'get_header' ) ? trim( (string) $request->get_header( $name ) ) : ''; }
	private static function request_header_exact( $request, $name ) { return is_object( $request ) && method_exists( $request, 'get_header' ) ? (string) $request->get_header( $name ) : ''; }
	private static function request_method( $request ) { return is_object( $request ) && method_exists( $request, 'get_method' ) ? $request->get_method() : 'GET'; }
	private static function request_path_with_query( $request ) { return isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) && '' !== $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : ( is_object( $request ) && method_exists( $request, 'get_route' ) ? $request->get_route() : '/wp-json/' . self::REST_NAMESPACE . '/health' ); }
	private static function request_body( $request ) { return is_object( $request ) && method_exists( $request, 'get_body' ) ? $request->get_body() : ''; }

	public static function get_options() {
		$stored = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		return array(
			'enabled'         => self::sanitize_checkbox( $raw['enabled'] ?? false ),
			'connection_key'  => self::sanitize_identity( $raw['connection_key'] ?? '', 128 ),
			'key_id'          => self::sanitize_identity( $raw['key_id'] ?? '', 64 ),
		);
	}

	public static function save( $raw ) {
		$options = self::sanitize( $raw );
		if ( false === get_option( self::OPTION, false ) ) {
			return add_option( self::OPTION, $options, '', false );
		}
		return update_option( self::OPTION, $options, false );
	}

	public static function site_url() {
		return home_url( '/' );
	}

	public static function status( $options = null ) {
		$options = is_array( $options ) ? self::sanitize( $options ) : self::get_options();
		if ( empty( $options['enabled'] ) ) {
			return 'Connector Disabled';
		}
		if ( ! self::has_identity( $options ) ) {
			return 'Configuration Error';
		}
		if ( ! self::secret_configured() ) {
			return 'Configuration Error';
		}
		return 'Ready';
	}

	public static function has_identity( $options = null ) {
		$options = is_array( $options ) ? $options : self::get_options();
		return '' !== self::sanitize_identity( $options['connection_key'] ?? '', 128 ) && '' !== self::sanitize_identity( $options['key_id'] ?? '', 64 );
	}

	public static function status_label( $status ) {
		$labels = array(
			'Connector Disabled' => '未启用',
			'Configuration Error' => '配置错误',
			'Provider Missing' => '未安装',
			'Ready' => '正常',
		);
		$labels['Not Available'] = '未开放';
		return $labels[ $status ] ?? $status;
	}

	public static function status_class( $status ) {
		$classes = array( 'Connector Disabled' => 'disabled', 'Configuration Error' => 'error', 'Provider Missing' => 'missing', 'Ready' => 'ready', 'Not Available' => 'neutral' );
		return $classes[ $status ] ?? 'neutral';
	}

	public static function provider_statuses() {
		return array(
			'wordpress'    => array( 'name' => 'WordPress', 'status' => 'Ready', 'version' => (string) get_bloginfo( 'version' ) ),
			'woocommerce'  => self::woocommerce_status(),
			'affiliatewp'  => self::affiliatewp_status(),
		);
	}

	public static function endpoint_statuses() {
		$connector_status = self::status();
		$endpoints = array(
			'subscriber_snapshot' => array( 'label' => '订阅用户快照', 'method' => 'GET', 'path' => '/snapshot/subscribers', 'providers' => array( 'wordpress' ) ),
			'health'              => array( 'label' => '运行状态', 'method' => 'GET', 'path' => '/health', 'providers' => array() ),
			'affiliate_snapshot'  => array( 'label' => '分销商快照', 'method' => 'GET', 'path' => '/snapshot/affiliates', 'providers' => array( 'affiliatewp' ) ),
			'coupon_snapshot'     => array( 'label' => '优惠券快照', 'method' => 'GET', 'path' => '/snapshot/coupons', 'providers' => array( 'woocommerce' ) ),
			'referral_snapshot'   => array( 'label' => '推荐记录快照', 'method' => 'GET', 'path' => '/snapshot/referrals', 'providers' => array( 'affiliatewp' ) ),
			'payout_snapshot'     => array( 'label' => '结算快照', 'method' => 'GET', 'path' => '/snapshot/payouts', 'providers' => array( 'affiliatewp' ) ),
			'affiliate_provision' => array( 'label' => '创建分销商', 'method' => 'POST', 'path' => '/affiliates/provision', 'providers' => array( 'affiliatewp' ) ),
			'affiliate_status'    => array( 'label' => '更新分销商状态', 'method' => 'POST', 'path' => '/affiliates/{affiliate_id}/status', 'providers' => array( 'affiliatewp' ) ),
			'coupon_check'        => array( 'label' => '检查优惠券', 'method' => 'POST', 'path' => '/coupons/check', 'providers' => array( 'woocommerce' ) ),
			'coupon_provision'    => array( 'label' => '创建优惠券', 'method' => 'POST', 'path' => '/coupons/provision', 'providers' => array( 'woocommerce', 'affiliatewp' ) ),
			'payout_complete'     => array( 'label' => '完成结算', 'method' => 'POST', 'path' => '/payouts/complete', 'providers' => array( 'affiliatewp' ) ),
		);
		$implemented = array( '/health', '/snapshot/affiliates', '/snapshot/coupons', '/snapshot/referrals', '/snapshot/payouts', '/snapshot/subscribers' );
		foreach ( $endpoints as &$endpoint ) {
			if ( ! in_array( $endpoint['path'], $implemented, true ) ) {
				$endpoint['status'] = 'Not Available'; $endpoint['available'] = false; unset( $endpoint['providers'] ); continue;
			}
			$provider_missing = false;
			foreach ( $endpoint['providers'] as $provider ) { if ( ! self::provider_available( $provider ) ) { $provider_missing = true; break; } }
			if ( $provider_missing ) { $endpoint['status'] = 'Provider Missing'; $endpoint['available'] = false; }
			elseif ( 'Ready' === $connector_status && self::is_https() ) { $endpoint['status'] = 'Ready'; $endpoint['available'] = true; }
			elseif ( 'Connector Disabled' === $connector_status ) { $endpoint['status'] = 'Connector Disabled'; $endpoint['available'] = false; }
			else { $endpoint['status'] = 'Configuration Error'; $endpoint['available'] = false; }
			unset( $endpoint['providers'] );
		}
		unset( $endpoint );
		return $endpoints;
	}

	public static function provider_available( $provider ) {
		$statuses = self::provider_statuses();
		return isset( $statuses[ $provider ] ) && 'Ready' === $statuses[ $provider ]['status'];
	}

	private static function woocommerce_status() {
		$available = class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' );
		return array( 'name' => 'WooCommerce', 'status' => $available ? 'Ready' : 'Provider Missing', 'version' => $available && defined( 'WC_VERSION' ) ? (string) WC_VERSION : '' );
	}

	private static function affiliatewp_status() {
		$available = defined( 'AFFILIATEWP_VERSION' ) || class_exists( 'AffiliateWP' ) || function_exists( 'affiliate_wp' );
		return array( 'name' => 'AffiliateWP', 'status' => $available ? 'Ready' : 'Provider Missing', 'version' => defined( 'AFFILIATEWP_VERSION' ) ? (string) AFFILIATEWP_VERSION : '' );
	}

	private static function sanitize_checkbox( $value ) {
		return in_array( $value, array( true, 1, '1', 'on' ), true );
	}

	private static function sanitize_identity( $value, $max_length ) {
		if ( is_array( $value ) || ! is_scalar( $value ) ) {
			return '';
		}
		$value = (string) $value;
		if ( '' === $value || strlen( $value ) > (int) $max_length ) {
			return '';
		}
		return preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$/', $value ) ? $value : '';
	}
}
