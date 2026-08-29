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
	const CONTRACT_VERSION = '1';

	public static function defaults() {
		return array(
			'enabled'        => false,
			'connection_key' => '',
			'key_id'         => '',
		);
	}

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
		return 'Ready';
	}

	public static function has_identity( $options = null ) {
		$options = is_array( $options ) ? $options : self::get_options();
		return '' !== self::sanitize_identity( $options['connection_key'] ?? '', 128 ) && '' !== self::sanitize_identity( $options['key_id'] ?? '', 64 );
	}

	public static function status_label( $status ) {
		$labels = array(
			'Connector Disabled' => '已关闭',
			'Configuration Error' => '待配置',
			'Provider Missing' => '缺少组件',
			'Ready' => '正常',
		);
		return $labels[ $status ] ?? $status;
	}

	public static function status_class( $status ) {
		$classes = array( 'Connector Disabled' => 'disabled', 'Configuration Error' => 'error', 'Provider Missing' => 'missing', 'Ready' => 'ready' );
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
