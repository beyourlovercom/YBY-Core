<?php
/**
 * Read-only consent adapter contract for Analytics Control Layer V1.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Analytics_Consent_Adapter {
	const MODE = 'respect_existing';
	const SOURCE_FILTER = 'andy_analytics_consent_source';
	const STATE_FILTER = 'andy_analytics_consent_state';

	public static function defaults() {
		return array(
			'analytics_storage' => 'unknown',
			'ad_storage' => 'unknown',
			'ad_user_data' => 'unknown',
			'ad_personalization' => 'unknown',
		);
	}

	public static function normalize_state( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$state = self::defaults();
		foreach ( array_keys( $state ) as $key ) {
			$value = isset( $raw[ $key ] ) ? sanitize_key( (string) $raw[ $key ] ) : 'unknown';
			$state[ $key ] = in_array( $value, array( 'granted', 'denied', 'unknown' ), true ) ? $value : 'unknown';
		}
		return $state;
	}

	public static function state() {
		$raw = self::defaults();
		if ( function_exists( 'apply_filters' ) ) {
			$raw = apply_filters( self::STATE_FILTER, $raw );
		}
		return self::normalize_state( $raw );
	}

	public static function source() {
		$source = 'existing_runtime';
		if ( function_exists( 'apply_filters' ) ) {
			$source = apply_filters( self::SOURCE_FILTER, $source );
		}
		$source = sanitize_key( (string) $source );
		return '' !== $source ? $source : 'existing_runtime';
	}

	public static function runtime_config() {
		return array(
			'mode' => self::MODE,
			'source' => self::source(),
			'state' => self::state(),
			'owns_consent_state' => false,
			'writes_google_consent' => false,
			'mutates_cmp' => false,
		);
	}
}
