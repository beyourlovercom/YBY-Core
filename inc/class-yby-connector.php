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
		register_rest_route( self::REST_NAMESPACE, '/affiliates/provision', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'dispatch_affiliate_provision' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::REST_NAMESPACE, '/affiliates/(?P<affiliate_id>\\d+)/status', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'dispatch_affiliate_status' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::REST_NAMESPACE, '/coupons/check', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'dispatch_coupon_check' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::REST_NAMESPACE, '/coupons/provision', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'dispatch_coupon_provision' ), 'permission_callback' => '__return_true' ) );
	}

	public static function dispatch_coupon_check( $request ) {
		$auth = self::authenticate( $request ); if ( is_wp_error( $auth ) ) { return self::error_response( $auth, $request ); }
		$body = self::json_params( $request ); $input = self::validate_coupon_check_input( $body );
		if ( is_wp_error( $input ) ) { return self::error_response( $input, $request ); }
		if ( ! self::woocommerce_coupon_ready() ) { return self::error_response( self::provider_unavailable(), $request ); }
		$found = self::find_coupon_by_normalized_code( $input['normalized_code'] );
		$data = $found ? array( 'available' => false, 'coupon_id' => (int) $found->get_id(), 'code' => (string) $found->get_code(), 'linked_affiliate_id' => self::coupon_affiliate_id( $found ), 'normalized_code' => $input['normalized_code'] ) : array( 'available' => true, 'normalized_code' => $input['normalized_code'] );
		$response = array( 'ok' => true, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => self::request_id(), 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'data' => $data );
		return new WP_REST_Response( $response, 200 );
	}

	public static function dispatch_coupon_provision( $request ) {
		return self::dispatch_mutation( $request, 'coupon.provision', '/coupons/provision', 'coupon_provision' );
	}

	public static function dispatch_affiliate_provision( $request ) {
		return self::dispatch_mutation( $request, 'affiliate.provision', '/affiliates/provision', 'provision' );
	}

	public static function dispatch_affiliate_status( $request ) {
		return self::dispatch_mutation( $request, 'affiliate.status', '/affiliates/{affiliate_id}/status', 'status' );
	}

	private static function dispatch_mutation( $request, $action, $endpoint, $operation ) {
		$auth = self::authenticate( $request );
		if ( is_wp_error( $auth ) ) { return self::error_response( $auth, $request ); }
		$request_id = self::request_id();
		$idempotency_key = self::request_header( $request, 'X-YBY-Idempotency-Key' );
		if ( '' === $idempotency_key ) { return self::mutation_failure( 'VALIDATION_FAILED', 'X-YBY-Idempotency-Key is required.', 400, false, $request, $request_id, $endpoint, $idempotency_key ); }
		$body = self::json_params( $request );
		$input = 'provision' === $operation ? self::validate_provision_input( $request, $body ) : ( 'status' === $operation ? self::validate_status_input( $request, $body ) : self::validate_coupon_provision_input( $request, $body ) );
		if ( is_wp_error( $input ) ) { return self::mutation_failure( $input->get_error_code(), $input->get_error_message(), 400, false, $request, $request_id, $endpoint, $idempotency_key ); }
		if ( ( 'coupon_provision' === $operation && ( ! self::woocommerce_coupon_ready() || ! self::affiliate_provider_ready( 'status' ) ) ) || ( 'coupon_provision' !== $operation && ! self::affiliate_provider_ready( $operation ) ) ) { return self::mutation_failure( 'PROVIDER_UNAVAILABLE', 'The required provider is unavailable.', 503, true, $request, $request_id, $endpoint, $idempotency_key, $input ); }
		if ( 'status' === $operation && ! array_key_exists( $input['status'], affwp_get_affiliate_statuses() ) ) { return self::mutation_failure( 'VALIDATION_FAILED', 'Affiliate status is not supported.', 400, false, $request, $request_id, $endpoint, $idempotency_key, $input ); }
		$begin = YBY_Connector_Idempotency::begin( self::request_header( $request, 'X-YBY-Connection-Key' ), $action, $idempotency_key, $input );
		if ( is_wp_error( $begin ) ) { return self::mutation_failure( $begin->get_error_code(), $begin->get_error_message(), self::error_status( $begin ), ! empty( $begin->get_error_data()['retryable'] ), $request, $request_id, $endpoint, $idempotency_key, $input ); }
		if ( 'replay' === $begin['status'] ) { self::audit( $request, $request_id, $endpoint, $idempotency_key, $begin['record']['result'], 'OK', true, false ); return self::mutation_success( $begin['record']['result'], $request, $request_id ); }
		if ( 'failed' === $begin['status'] ) { $failed_code = $begin['record']['failure_code']; return self::mutation_failure( $failed_code, 'The previous mutation failed.', self::failure_replay_status( $failed_code ), false, $request, $request_id, $endpoint, $idempotency_key, $input ); }
		$result = 'provision' === $operation ? self::provision_affiliate( $input ) : ( 'status' === $operation ? self::set_affiliate_status( $input ) : self::provision_coupon( $input ) );
		if ( is_wp_error( $result ) ) {
			$retryable = ! empty( $result->get_error_data()['retryable'] );
			YBY_Connector_Idempotency::fail( $begin['id'], $result->get_error_code(), $retryable );
			return self::mutation_failure( $result->get_error_code(), $result->get_error_message(), self::error_status( $result ), $retryable, $request, $request_id, $endpoint, $idempotency_key, $input, $result );
		}
		if ( ! YBY_Connector_Idempotency::succeed( $begin['id'], $result ) ) { return self::mutation_failure( 'IDEMPOTENCY_UNAVAILABLE', 'Could not persist mutation result.', 503, true, $request, $request_id, $endpoint, $idempotency_key, $input ); }
		self::audit( $request, $request_id, $endpoint, $idempotency_key, $result, 'OK', true, false );
		return self::mutation_success( $result, $request, $request_id );
	}

	private static function provision_affiliate( $input ) {
		$connection = $input['connection_key']; $kol = $input['erp_kol_id'];
		$owner_hash = self::provision_lease_owner();
		$reservation = YBY_Connector_Affiliate_Bindings::reserve( $connection, $kol, $owner_hash );
		if ( is_wp_error( $reservation ) ) { return $reservation; }
		if ( 'ready' === $reservation['status'] ) { return self::read_bound_result( $reservation['row'], false, false ); }
		if ( 'busy' === $reservation['status'] ) { return new WP_Error( 'PROVISION_IN_PROGRESS', 'Affiliate provisioning for this ERP KOL is already processing.', array( 'status' => 409, 'retryable' => true ) ); }
		$lease_failure = function () use ( $connection, $kol, $owner_hash ) {
			YBY_Connector_Affiliate_Bindings::release( $connection, $kol, $owner_hash );
			return new WP_Error( 'PROVISION_IN_PROGRESS', 'Affiliate provisioning reservation was lost; retry safely.', array( 'status' => 503, 'retryable' => true ) );
		};
		$provider_failure = function ( $code, $message ) use ( $connection, $kol, $owner_hash ) { YBY_Connector_Affiliate_Bindings::release( $connection, $kol, $owner_hash ); return self::provider_failure( $code, $message ); };
		if ( ! YBY_Connector_Affiliate_Bindings::renew( $connection, $kol, $owner_hash ) ) { return $lease_failure(); }
		$user = get_user_by( 'email', $input['email'] ); $created_user = false;
		if ( ! $user ) {
			$base = sanitize_user( $input['preferred_username'], true );
			if ( '' === $base ) { return $provider_failure( 'PROVIDER_WRITE_FAILED', 'Username could not be resolved.' ); }
			$username = self::resolve_username( $base, $connection, $kol );
			if ( false === $username ) { return $provider_failure( 'PROVIDER_WRITE_FAILED', 'Username could not be resolved safely.' ); }
			if ( ! YBY_Connector_Affiliate_Bindings::renew( $connection, $kol, $owner_hash ) ) { return $lease_failure(); }
			$password = wp_generate_password( 32, true, true );
			$user_id = wp_insert_user( array( 'user_login' => substr( $username, 0, 60 ), 'user_pass' => $password, 'user_email' => $input['email'], 'display_name' => $input['display_name'], 'role' => 'subscriber' ) );
			unset( $password );
			if ( is_wp_error( $user_id ) ) { return $provider_failure( 'PROVIDER_WRITE_FAILED', 'WordPress user creation failed.' ); }
			$user = get_user_by( 'id', $user_id ); $created_user = true;
		}
		if ( ! $user || empty( $user->ID ) ) { return $provider_failure( 'PROVIDER_READBACK_FAILED', 'WordPress user read-back failed.' ); }
		$affiliate = function_exists( 'affwp_get_affiliate_by' ) ? affwp_get_affiliate_by( 'user_id', (int) $user->ID ) : false;
		$created_affiliate = false;
		if ( is_wp_error( $affiliate ) || ! $affiliate ) {
			if ( ! YBY_Connector_Affiliate_Bindings::renew( $connection, $kol, $owner_hash ) ) { return $lease_failure(); }
			$affiliate_id = affwp_add_affiliate( array( 'user_id' => (int) $user->ID, 'status' => 'active', 'payment_email' => $input['payment_email'] ) );
			if ( ! $affiliate_id ) { return $provider_failure( 'PROVIDER_WRITE_FAILED', 'Affiliate creation failed.' ); }
			$affiliate = affwp_get_affiliate( $affiliate_id ); $created_affiliate = true;
		}
		$affiliate_id = self::affiliate_id_from_object( $affiliate );
		if ( ! $affiliate || null === $affiliate_id || $affiliate_id < 1 ) { return $provider_failure( 'PROVIDER_READBACK_FAILED', 'Affiliate read-back failed.' ); }
		$other = YBY_Connector_Affiliate_Bindings::by_affiliate( $connection, $affiliate_id );
		if ( $other && (int) $other['erp_kol_id'] !== $kol ) { YBY_Connector_Affiliate_Bindings::release( $connection, $kol, $owner_hash ); return new WP_Error( 'AFFILIATE_ID_CONFLICT', 'Affiliate is already bound to another ERP KOL.', array( 'status' => 409, 'retryable' => false ) ); }
		if ( 'active' !== (string) $affiliate->status ) {
			// Only an unbound affiliate may be brought to the ERP-approved active state.
			if ( ! $other && function_exists( 'affwp_set_affiliate_status' ) && ( ! YBY_Connector_Affiliate_Bindings::renew( $connection, $kol, $owner_hash ) || ! affwp_set_affiliate_status( $affiliate_id, 'active' ) ) ) { return $provider_failure( 'PROVIDER_WRITE_FAILED', 'Affiliate status update failed.' ); }
		}
		$affiliate = affwp_get_affiliate( $affiliate_id );
		$expected_status = $other && $affiliate ? (string) $affiliate->status : 'active';
		if ( ! $affiliate || self::affiliate_id_from_object( $affiliate ) !== $affiliate_id || $expected_status !== (string) $affiliate->status ) { return $provider_failure( 'PROVIDER_READBACK_FAILED', 'Affiliate provider read-back did not match.' ); }
		$stored = YBY_Connector_Affiliate_Bindings::finalize( $connection, $kol, $owner_hash, (int) $user->ID, $affiliate_id );
		if ( ! $stored ) { $stored = YBY_Connector_Affiliate_Bindings::by_kol( $connection, $kol ); }
		if ( ! $stored || 'ready' !== (string) ( $stored['state'] ?? '' ) ) { return new WP_Error( 'PROVISION_IN_PROGRESS', 'Affiliate provisioning reservation was taken over; retry safely.', array( 'status' => 503, 'retryable' => true ) ); }
		if ( (int) $stored['affiliate_id'] !== $affiliate_id ) { return new WP_Error( 'AFFILIATE_ID_CONFLICT', 'Affiliate binding could not be established safely.', array( 'status' => 409, 'retryable' => false ) ); }
		return array( 'wp_user_id' => (int) $user->ID, 'affiliate_id' => $affiliate_id, 'affiliate_status' => $expected_status, 'created_user' => $created_user, 'created_affiliate' => $created_affiliate );
	}

	private static function provision_lease_owner() {
		$entropy = function_exists( 'random_bytes' ) ? random_bytes( 32 ) : uniqid( 'yby-', true );
		return hash( 'sha256', (string) $entropy . microtime( true ) );
	}

	private static function read_bound_result( $binding, $created_user, $created_affiliate ) {
		$affiliate = affwp_get_affiliate( (int) $binding['affiliate_id'] );
		$user = get_user_by( 'id', (int) $binding['wp_user_id'] );
		$affiliate_id = self::affiliate_id_from_object( $affiliate );
		if ( ! $affiliate || ! $user || null === $affiliate_id || $affiliate_id !== (int) $binding['affiliate_id'] || (int) $affiliate->user_id !== (int) $binding['wp_user_id'] ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Bound provider identity read-back failed.' ); }
		return array( 'wp_user_id' => (int) $user->ID, 'affiliate_id' => $affiliate_id, 'affiliate_status' => (string) $affiliate->status, 'created_user' => (bool) $created_user, 'created_affiliate' => (bool) $created_affiliate );
	}

	private static function set_affiliate_status( $input ) {
		$id = $input['affiliate_id']; $affiliate = affwp_get_affiliate( $id );
		if ( ! $affiliate ) { return new WP_Error( 'AFFILIATE_NOT_FOUND', 'Affiliate was not found.', array( 'status' => 404, 'retryable' => false ) ); }
		if ( (string) $affiliate->status !== $input['status'] && ( ! function_exists( 'affwp_set_affiliate_status' ) || ! affwp_set_affiliate_status( $id, $input['status'] ) ) ) { return self::provider_failure( 'PROVIDER_WRITE_FAILED', 'Affiliate status update failed.' ); }
		$read = affwp_get_affiliate( $id );
		if ( ! $read || self::affiliate_id_from_object( $read ) !== $id || (string) $read->status !== $input['status'] ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Affiliate status read-back did not match.' ); }
		return array( 'affiliate_id' => $id, 'status' => (string) $read->status );
	}

	private static function provision_coupon( $input ) {
		$connection = $input['connection_key']; $normalized = $input['normalized_code']; $owner = self::provision_lease_owner();
		// Provider truth and exact Affiliate validation precede the durable lease.
		if ( self::find_coupon_by_normalized_code( $input['code'] ) ) { return new WP_Error( 'COUPON_CONFLICT', 'Coupon code already exists.', array( 'status' => 409, 'retryable' => false ) ); }
		$affiliate = affwp_get_affiliate( $input['affiliate_id'] );
		if ( ! $affiliate || self::affiliate_id_from_object( $affiliate ) !== $input['affiliate_id'] ) { return new WP_Error( 'AFFILIATE_NOT_FOUND', 'Affiliate was not found.', array( 'status' => 404, 'retryable' => false ) ); }
		$reservation = YBY_Connector_Coupon_Bindings::reserve( $connection, $normalized, $input['code'], $owner );
		if ( 'ready' === $reservation['status'] ) { return new WP_Error( 'COUPON_CONFLICT', 'Coupon code is already bound.', array( 'status' => 409, 'retryable' => false ) ); }
		if ( 'busy' === $reservation['status'] ) { return new WP_Error( 'PROVISION_IN_PROGRESS', 'Coupon provisioning for this code is already processing.', array( 'status' => 409, 'retryable' => true ) ); }
		$release = function ( $error ) use ( $connection, $normalized, $owner ) { YBY_Connector_Coupon_Bindings::release( $connection, $normalized, $owner ); return $error; };
		// Re-check under the global normalized-code lease to close the check/acquire race.
		if ( self::find_coupon_by_normalized_code( $input['code'] ) ) { return $release( new WP_Error( 'COUPON_CONFLICT', 'Coupon code already exists.', array( 'status' => 409, 'retryable' => false ) ) ); }
		if ( ! YBY_Connector_Coupon_Bindings::renew( $normalized, $owner ) ) { return $release( new WP_Error( 'PROVISION_IN_PROGRESS', 'Coupon provisioning reservation was lost; retry safely.', array( 'status' => 503, 'retryable' => true ) ) ); }
		$coupon = new WC_Coupon(); $coupon->set_code( $input['code'] ); $coupon->set_discount_type( $input['discount_type'] ); $coupon->set_amount( $input['amount'] ); $coupon->set_date_expires( $input['expiry_timestamp'] ); $coupon->update_meta_data( 'affwp_discount_affiliate', (string) $input['affiliate_id'] );
		try { $coupon->save(); } catch ( Exception $e ) { return $release( self::provider_failure( 'PROVIDER_WRITE_FAILED', 'Coupon creation failed.' ) ); }
		if ( ! $coupon->get_id() ) { return $release( self::provider_failure( 'PROVIDER_WRITE_FAILED', 'Coupon creation failed.' ) ); }
		$created_id = (int) $coupon->get_id(); $read = null; $read_error = false;
		try { $read = new WC_Coupon( $created_id ); } catch ( Exception $e ) { $read_error = true; }
		$linked = $read ? self::coupon_affiliate_id( $read ) : null;
		if ( $read_error || ! $read || (string) $read->get_code() !== (string) $input['code'] || self::normalize_coupon_code( $read->get_code() ) !== $normalized || (string) $read->get_discount_type() !== $input['discount_type'] || ! self::decimal_equal( $read->get_amount(), $input['amount'] ) || (int) $linked !== (int) $input['affiliate_id'] || ! self::coupon_expiry_matches( $read, $input['expiry_timestamp'] ) ) {
			return self::compensate_created_coupon( $connection, $normalized, $owner, $created_id, $release );
		}
		if ( ! YBY_Connector_Coupon_Bindings::finalize( $connection, $normalized, $owner, $created_id, $input['affiliate_id'], $input['erp_kol_id'] ) ) {
			$row = YBY_Connector_Coupon_Bindings::by_code( $normalized );
			if ( self::coupon_binding_matches( $row, $created_id, $input['affiliate_id'], $input['erp_kol_id'], $normalized ) ) { return self::coupon_result( $read, $input['affiliate_id'] ); }
			return new WP_Error( 'PROVISION_IN_PROGRESS', 'Coupon reservation state could not be proven safe.', array( 'status' => 503, 'retryable' => true ) );
		}
		$row = YBY_Connector_Coupon_Bindings::by_code( $normalized ); return self::coupon_binding_matches( $row, $created_id, $input['affiliate_id'], $input['erp_kol_id'], $normalized ) ? self::coupon_result( $read, $input['affiliate_id'] ) : new WP_Error( 'PROVISION_IN_PROGRESS', 'Coupon reservation finalization could not be verified.', array( 'status' => 503, 'retryable' => true ) );
	}

	private static function compensate_created_coupon( $connection, $normalized, $owner, $coupon_id, $release ) {
		$row = YBY_Connector_Coupon_Bindings::by_code( $normalized );
		if ( ! self::coupon_reservation_owned( $row, $normalized, $owner ) ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Coupon read-back failed and reservation ownership could not be proven.' ); }
		try { $coupon = new WC_Coupon( (int) $coupon_id ); $coupon->delete( true ); } catch ( Exception $e ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Coupon read-back failed and safe cleanup could not be completed.' ); }
		$remaining = self::find_coupon_by_normalized_code( $normalized );
		if ( $remaining && (int) $remaining->get_id() !== (int) $coupon_id ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Coupon read-back failed and provider state is ambiguous.' ); }
		if ( $remaining ) { return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Coupon cleanup could not be verified.' ); }
		return $release( self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Coupon provider read-back did not match.' ) );
	}

	private static function coupon_reservation_owned( $row, $normalized, $owner ) { return is_array( $row ) && (string) ( $row['normalized_code'] ?? '' ) === (string) $normalized && 'processing' === (string) ( $row['state'] ?? '' ) && (string) ( $row['lease_owner_hash'] ?? '' ) === (string) $owner && ! empty( $row['lease_expires_at'] ) && strtotime( (string) $row['lease_expires_at'] . ' UTC' ) >= time(); }
	private static function coupon_binding_matches( $row, $coupon_id, $affiliate_id, $kol, $normalized ) { if ( ! is_array( $row ) || 'ready' !== (string) ( $row['state'] ?? '' ) || (int) ( $row['coupon_id'] ?? 0 ) !== (int) $coupon_id || (int) ( $row['affiliate_id'] ?? 0 ) !== (int) $affiliate_id || (int) ( $row['erp_kol_id'] ?? 0 ) !== (int) $kol || (string) ( $row['normalized_code'] ?? '' ) !== (string) $normalized ) { return false; } $coupon = new WC_Coupon( (int) $coupon_id ); return (int) $coupon->get_id() === (int) $coupon_id && (int) self::coupon_affiliate_id( $coupon ) === (int) $affiliate_id; }
	private static function coupon_result( $coupon, $affiliate_id ) { return array( 'coupon_id' => (int) $coupon->get_id(), 'code' => (string) $coupon->get_code(), 'normalized_code' => self::normalize_coupon_code( $coupon->get_code() ), 'affiliate_id' => (int) $affiliate_id, 'discount_type' => (string) $coupon->get_discount_type(), 'amount' => (string) $coupon->get_amount(), 'date_expires' => self::provider_date( $coupon->get_date_expires() ) ); }
	private static function coupon_binding_result( $row ) { $coupon = new WC_Coupon( (int) $row['coupon_id'] ); $linked = self::coupon_affiliate_id( $coupon ); return $coupon->get_id() && $linked === (int) $row['affiliate_id'] ? self::coupon_result( $coupon, $linked ) : self::provider_failure( 'PROVIDER_READBACK_FAILED', 'Bound coupon read-back failed.' ); }
	private static function coupon_expiry_matches( $coupon, $timestamp ) { $date = $coupon->get_date_expires(); return null === $timestamp ? ! $date : ( $date instanceof DateTimeInterface && abs( $date->getTimestamp() - $timestamp ) <= 1 ); }
	private static function decimal_equal( $left, $right ) { return self::canonical_decimal( $left ) === self::canonical_decimal( $right ); }
	private static function canonical_decimal( $value ) { $value = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $value, 6, false ) : (string) $value; if ( ! preg_match( '/^\d+(?:\.\d{1,6})?$/', (string) $value ) ) { return false; } list( $whole, $fraction ) = array_pad( explode( '.', (string) $value, 2 ), 2, '' ); $whole = ltrim( $whole, '0' ); $whole = '' === $whole ? '0' : $whole; $fraction = rtrim( $fraction, '0' ); return '' === $fraction ? $whole : $whole . '.' . $fraction; }
	private static function decimal_at_most_100( $value ) { $canonical = self::canonical_decimal( $value ); if ( false === $canonical ) { return false; } list( $whole, $fraction ) = array_pad( explode( '.', $canonical, 2 ), 2, '' ); if ( strlen( $whole ) < 3 ) { return true; } if ( strlen( $whole ) > 3 || '100' !== $whole ) { return false; } return '' === $fraction; }
	private static function coupon_affiliate_id( $coupon ) { $value = function_exists( 'get_post_meta' ) ? get_post_meta( (int) $coupon->get_id(), 'affwp_discount_affiliate', true ) : ''; return is_numeric( $value ) && (int) $value > 0 ? (int) $value : null; }
	private static function normalize_coupon_code( $code ) { $code = trim( (string) $code ); return function_exists( 'wc_strtolower' ) ? wc_strtolower( $code ) : strtolower( $code ); }
	private static function find_coupon_by_normalized_code( $display_code ) { if ( ! function_exists( 'wc_get_coupon_id_by_code' ) ) { return false; } $id = wc_get_coupon_id_by_code( self::normalize_coupon_code( $display_code ) ); return $id ? new WC_Coupon( (int) $id ) : false; }
	private static function woocommerce_coupon_ready() { return self::provider_available( 'woocommerce' ) && class_exists( 'WC_Coupon' ) && function_exists( 'wc_get_coupon_id_by_code' ); }

	private static function affiliate_id_from_object( $affiliate ) {
		if ( ! is_object( $affiliate ) ) { return null; }
		if ( isset( $affiliate->affiliate_id ) ) { return (int) $affiliate->affiliate_id; }
		if ( isset( $affiliate->ID ) ) { return (int) $affiliate->ID; }
		return null;
	}

	private static function affiliate_provider_ready( $operation ) {
		if ( ! self::provider_available( 'affiliatewp' ) || ! function_exists( 'affiliate_wp' ) || ! is_object( affiliate_wp() ) || ! function_exists( 'affwp_get_affiliate' ) || ! function_exists( 'affwp_set_affiliate_status' ) || ! function_exists( 'affwp_get_affiliate_statuses' ) ) { return false; }
		return 'status' === $operation || ( function_exists( 'affwp_get_affiliate_by' ) && function_exists( 'affwp_add_affiliate' ) );
	}

	private static function resolve_username( $base, $connection, $kol ) {
		$seed = substr( hash( 'sha256', $connection . ':' . $kol ), 0, 12 );
		$candidates = array( $base . '-' . $kol, $base . '-' . $seed, $base . '-' . $kol . '-' . substr( $seed, 0, 6 ), $base . '-' . $seed . '-1', $base . '-' . $seed . '-2' );
		foreach ( $candidates as $candidate ) { $candidate = substr( $candidate, 0, 60 ); if ( '' !== $candidate && ! username_exists( $candidate ) ) { return $candidate; } }
		return false;
	}

	private static function failure_replay_status( $code ) {
		if ( 'AFFILIATE_NOT_FOUND' === $code ) { return 404; }
		if ( in_array( $code, array( 'IDEMPOTENCY_CONFLICT', 'AFFILIATE_ID_CONFLICT', 'COUPON_CONFLICT' ), true ) ) { return 409; }
		return in_array( $code, array( 'PROVIDER_UNAVAILABLE', 'PROVIDER_WRITE_FAILED', 'PROVIDER_READBACK_FAILED', 'IDEMPOTENCY_UNAVAILABLE', 'AFFILIATE_BINDING_UNAVAILABLE', 'PROVISION_IN_PROGRESS' ), true ) ? 503 : 400;
	}

	private static function validate_provision_input( $request, $body ) {
		$required = array( 'erp_kol_id', 'email', 'display_name', 'preferred_username', 'requested_affiliate_status', 'payment_email' );
		if ( ! is_array( $body ) || array_diff( $required, array_keys( $body ) ) || array_diff( array_keys( $body ), $required ) || 6 !== count( $body ) || 'active' !== (string) $body['requested_affiliate_status'] ) { return self::validation_error(); }
		foreach ( array( 'email', 'display_name', 'preferred_username', 'requested_affiliate_status', 'payment_email' ) as $field ) { if ( ! isset( $body[ $field ] ) || ! is_scalar( $body[ $field ] ) ) { return self::validation_error(); } }
		$kol = $body['erp_kol_id'];
		if ( ! ( is_int( $kol ) || ( is_string( $kol ) && ctype_digit( $kol ) ) ) || (int) $kol < 1 || (int) $kol > PHP_INT_MAX ) { return self::validation_error(); }
		$email = trim( (string) $body['email'] ); $payment = trim( (string) $body['payment_email'] );
		if ( ! is_email( $email ) || ! is_email( $payment ) ) { return self::validation_error(); }
		$display = trim( wp_strip_all_tags( (string) $body['display_name'] ) ); $username = trim( (string) $body['preferred_username'] );
		if ( '' === $display || strlen( $display ) > 120 || '' === $username || strlen( $username ) > 60 || ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $username ) ) { return self::validation_error(); }
		return array( 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'erp_kol_id' => (int) $kol, 'email' => strtolower( $email ), 'display_name' => $display, 'preferred_username' => $username, 'requested_affiliate_status' => 'active', 'payment_email' => strtolower( $payment ) );
	}

	private static function validate_status_input( $request, $body ) {
		$id = is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'affiliate_id' ) : null;
		if ( ! ( is_int( $id ) || ( is_string( $id ) && ctype_digit( $id ) ) ) || (int) $id < 1 || ! is_array( $body ) || 1 !== count( $body ) || ! array_key_exists( 'status', $body ) || ! is_scalar( $body['status'] ) || '' === (string) $body['status'] ) { return self::validation_error(); }
		return array( 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'affiliate_id' => (int) $id, 'status' => (string) $body['status'] );
	}

	private static function validate_coupon_check_input( $body ) { if ( ! is_array( $body ) || 1 !== count( $body ) || ! array_key_exists( 'code', $body ) || ! is_scalar( $body['code'] ) ) { return self::validation_error(); } $code = trim( (string) $body['code'] ); if ( '' === $code || strlen( $code ) > 255 ) { return self::validation_error(); } return array( 'code' => $code, 'normalized_code' => self::normalize_coupon_code( $code ) ); }

	private static function validate_coupon_provision_input( $request, $body ) {
		$required = array( 'erp_kol_id', 'affiliate_id', 'code', 'discount_type', 'amount', 'expiry' );
		if ( ! is_array( $body ) || array_diff( $required, array_keys( $body ) ) || array_diff( array_keys( $body ), $required ) || 6 !== count( $body ) ) { return self::validation_error(); }
		foreach ( array( 'erp_kol_id', 'affiliate_id' ) as $key ) { if ( ! ( is_int( $body[ $key ] ) || ( is_string( $body[ $key ] ) && ctype_digit( $body[ $key ] ) ) ) || (int) $body[ $key ] < 1 ) { return self::validation_error(); } }
		if ( ! is_scalar( $body['code'] ) ) { return self::validation_error(); }
		$display_code = trim( (string) $body['code'] );
		if ( '' === $display_code || strlen( $display_code ) > 255 || ( function_exists( 'wc_sanitize_coupon_code' ) && wc_sanitize_coupon_code( $display_code ) !== $display_code ) || ! is_scalar( $body['discount_type'] ) || ! in_array( (string) $body['discount_type'], array( 'percent', 'fixed_cart', 'fixed_product' ), true ) || ! is_scalar( $body['amount'] ) || ! preg_match( '/^(?:0|[1-9]\d*)(?:\.\d{1,6})?$/', (string) $body['amount'] ) ) { return self::validation_error(); }
		if ( 'percent' === (string) $body['discount_type'] && ! self::decimal_at_most_100( (string) $body['amount'] ) ) { return self::validation_error(); }
		$timestamp = null; if ( null !== $body['expiry'] ) { if ( ! is_scalar( $body['expiry'] ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/', (string) $body['expiry'], $parts ) ) { return self::validation_error(); } $warning = false; set_error_handler( function () use ( &$warning ) { $warning = true; } ); try { $date = new DateTimeImmutable( (string) $body['expiry'] ); } catch ( Exception $e ) { $date = false; } restore_error_handler(); if ( $warning || ! $date || $date->format( 'Y-m-d\\TH:i:s' ) !== implode( '-', array_slice( $parts, 1, 3 ) ) . 'T' . implode( ':', array_slice( $parts, 4, 3 ) ) ) { return self::validation_error(); } $timestamp = $date->getTimestamp(); }
		return array( 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'erp_kol_id' => (int) $body['erp_kol_id'], 'affiliate_id' => (int) $body['affiliate_id'], 'code' => $display_code, 'normalized_code' => self::normalize_coupon_code( $display_code ), 'discount_type' => (string) $body['discount_type'], 'amount' => (string) $body['amount'], 'expiry_timestamp' => $timestamp );
	}

	private static function validation_error() { return new WP_Error( 'VALIDATION_FAILED', 'Request syntax or values are invalid.', array( 'status' => 400, 'retryable' => false ) ); }
	private static function provider_failure( $code, $message ) { return new WP_Error( $code, $message, array( 'status' => 503, 'retryable' => true ) ); }
	private static function json_params( $request ) {
		if ( is_object( $request ) && method_exists( $request, 'get_json_params' ) ) { $params = $request->get_json_params(); return is_array( $params ) ? $params : array(); }
		$decoded = json_decode( self::request_body( $request ), true ); return is_array( $decoded ) ? $decoded : array();
	}
	private static function error_status( $error ) { $data = $error->get_error_data(); if ( is_array( $data ) && isset( $data['status'] ) ) { return (int) $data['status']; } return in_array( $error->get_error_code(), array( 'IDEMPOTENCY_CONFLICT', 'AFFILIATE_ID_CONFLICT', 'COUPON_CONFLICT' ), true ) ? 409 : 400; }
	private static function mutation_success( $data, $request, $request_id ) { return array( 'ok' => true, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => $request_id, 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'data' => $data ); }
	private static function mutation_failure( $code, $message, $status, $retryable, $request, $request_id, $endpoint, $idempotency_key, $input = array(), $error = null ) {
		self::audit( $request, $request_id, $endpoint, $idempotency_key, $input, $code, false, $retryable );
		return new WP_REST_Response( array( 'ok' => false, 'contract_version' => self::CONTRACT_VERSION, 'request_id' => $request_id, 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'code' => $code, 'message' => $message, 'retryable' => (bool) $retryable ), (int) $status );
	}
	private static function audit( $request, $request_id, $endpoint, $idempotency_key, $result, $code, $success, $retryable ) {
		$targets = array();
		foreach ( array( 'wp_user_id', 'affiliate_id', 'coupon_id' ) as $key ) { if ( isset( $result[ $key ] ) && is_scalar( $result[ $key ] ) ) { $targets[ $key ] = array( (int) $result[ $key ] ); } }
		YBY_Connector_Audit::record( array( 'request_id' => $request_id, 'key_id' => self::request_header( $request, 'X-YBY-Key-Id' ), 'connection_key' => self::request_header( $request, 'X-YBY-Connection-Key' ), 'endpoint_action' => $endpoint, 'idempotency_key_hash' => YBY_Connector_Idempotency::key_hash( $idempotency_key ), 'target_provider_ids' => $targets, 'result_code' => $code, 'success' => $success, 'retryable' => $retryable ) );
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
		$id = self::affiliate_id_from_object( $affiliate );
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
		$implemented = array( '/health', '/snapshot/affiliates', '/snapshot/coupons', '/snapshot/referrals', '/snapshot/payouts', '/snapshot/subscribers', '/affiliates/provision', '/affiliates/{affiliate_id}/status', '/coupons/check', '/coupons/provision' );
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
