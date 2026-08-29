<?php
/**
 * BYL ERP WordPress Connector foundation.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	}

	public static function dispatch_health( $request ) {
		$auth = self::authenticate( $request );
		if ( is_wp_error( $auth ) ) { return self::error_response( $auth, $request ); }
		return self::health( $request );
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
		foreach ( $endpoints as &$endpoint ) {
			if ( '/health' === $endpoint['path'] && 'Ready' === $connector_status && self::is_https() ) {
				$endpoint['status'] = 'Ready';
				$endpoint['available'] = true;
				unset( $endpoint['providers'] );
				continue;
			}
			if ( '/health' !== $endpoint['path'] ) {
				$endpoint['status'] = 'Not Available';
				$endpoint['available'] = false;
				unset( $endpoint['providers'] );
				continue;
			}
			$provider_missing = false;
			foreach ( $endpoint['providers'] as $provider ) {
				if ( ! self::provider_available( $provider ) ) {
					$provider_missing = true;
					break;
				}
			}
			if ( $provider_missing ) {
				$endpoint['status'] = 'Provider Missing';
			} elseif ( 'Connector Disabled' === $connector_status ) {
				$endpoint['status'] = 'Connector Disabled';
			} else {
				$endpoint['status'] = 'Configuration Error';
			}
			$endpoint['available'] = false;
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
