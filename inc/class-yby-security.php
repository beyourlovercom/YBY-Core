<?php
/**
 * Security helpers.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin security helpers.
 */
class YBY_Security {
	public static function inquiry_settings() {
		$raw = wp_parse_args( get_option( 'yby_core_inquiry_settings', array() ), array( 'default_status' => 'new', 'default_priority' => 'normal', 'default_owner_user_id' => 0, 'leads_per_page' => 30, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft' ) );
		return array(
			'default_status' => in_array( $raw['default_status'], array( 'new', 'pending_contact', 'contacted', 'following_up', 'pending_quote', 'quoted', 'won', 'invalid', 'closed' ), true ) ? $raw['default_status'] : 'new',
			'default_priority' => in_array( $raw['default_priority'], array( 'low', 'normal', 'high', 'urgent' ), true ) ? $raw['default_priority'] : 'normal',
			'default_owner_user_id' => absint( $raw['default_owner_user_id'] ),
			'leads_per_page' => in_array( absint( $raw['leads_per_page'] ), array( 30, 50, 100 ), true ) ? absint( $raw['leads_per_page'] ) : 30,
			'editors_can_manage' => ! empty( $raw['editors_can_manage'] ),
			'assignment_enabled' => ! empty( $raw['assignment_enabled'] ),
			'archive_behavior' => 'soft' === $raw['archive_behavior'] ? 'soft' : 'soft',
		);
	}

	public static function can_view_leads() { return current_user_can( 'andy_core_leads_view' ); }
	public static function can_manage_leads() {
		if ( ! current_user_can( 'andy_core_leads_manage' ) ) { return false; }
		$user = wp_get_current_user();
		return ! in_array( 'editor', (array) $user->roles, true ) || ! empty( self::inquiry_settings()['editors_can_manage'] );
	}
	public static function can_assign_leads() { return self::inquiry_settings()['assignment_enabled'] && current_user_can( 'andy_core_leads_assign' ); }
	public static function can_archive_leads() { return current_user_can( 'andy_core_leads_archive' ) && 'soft' === self::inquiry_settings()['archive_behavior']; }

	/**
	 * Verify admin capability.
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return current_user_can( 'andy_core_settings_manage' );
	}
}
