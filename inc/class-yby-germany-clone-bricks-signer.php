<?php
/**
 * Authorized native Bricks signer for new Germany draft content.
 *
 * Uses Bricks' own signature generator and integrity verifier, never wp_hash
 * directly and never transfers Tanzania signatures to the new draft.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Germany_Clone_Bricks_Signer {
	/**
	 * Sign in-memory transformed Code elements using installed Bricks itself.
	 *
	 * @param array $elements Fresh transformed, unsigned Bricks elements.
	 * @return array|WP_Error Signed elements or a closed failure.
	 */
	public static function sign( $elements ) {
		if ( ! class_exists( '\Bricks\Admin' ) ||
			! class_exists( '\Bricks\Helpers' ) ||
			! class_exists( '\Bricks\Capabilities' ) ||
			! method_exists( '\Bricks\Admin', 'process_elements_for_signature' ) ) {
			return new WP_Error( 'clone_bricks_native_missing', 'Authorized native Bricks signer is unavailable.' );
		}
		if ( ( defined( 'BRICKS_LOCK_CODE_SIGNATURES' ) && BRICKS_LOCK_CODE_SIGNATURES ) ||
			! current_user_can( 'manage_options' ) ||
			! \Bricks\Helpers::code_execution_enabled() ||
			! \Bricks\Capabilities::current_user_can_execute_code() ) {
			return new WP_Error( 'clone_bricks_signer_denied', 'Code signatures locked or current editor lacks native Bricks signing permission.' );
		}
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) { continue; }
			$name = $element['name'] ?? '';
			if ( ! in_array( $name, array( 'code', 'svg' ), true ) ) { continue; }
			if ( ! empty( $element['settings']['signature'] ) ) {
				return new WP_Error( 'clone_old_signature', 'Input must not carry any previous Code signature.' );
			}
		}
		// This native Bricks method contains its own manage_options and
		// can_execute_code gates and attributes signer user/time.
		$signed = \Bricks\Admin::process_elements_for_signature( $elements, false, false );
		if ( ! is_array( $signed ) || count( $signed ) !== count( $elements ) ) {
			return new WP_Error( 'clone_signer_result', 'Bricks did not return a valid signed element array.' );
		}
		$count = 0;
		foreach ( $signed as $element ) {
			if ( ! is_array( $element ) || ! in_array( $element['name'] ?? '', array( 'code', 'svg' ), true ) ) {
				continue;
			}
			$settings = $element['settings'] ?? array();
			$code = $settings['code'] ?? '';
			if ( ! is_string( $code ) || '' === $code ) { continue; }
			if ( empty( $settings['signature'] ) ||
				! \Bricks\Helpers::verify_code_signature( $settings['signature'], $code ) ||
				(int) ( $settings['user_id'] ?? 0 ) !== get_current_user_id() ) {
				return new WP_Error( 'clone_signature_invalid', 'One or more Code elements failed native Bricks signature verification.' );
			}
			++$count;
		}
		if ( 0 === $count ) {
			return new WP_Error( 'clone_no_code', 'Expected source Code elements are absent; manual review required.' );
		}
		return array( 'elements' => $signed, 'signatures_verified' => $count );
	}
}
