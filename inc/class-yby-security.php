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
		$raw = wp_parse_args( get_option( 'yby_core_inquiry_settings', array() ), array( 'default_status' => 'new', 'default_priority' => 'normal', 'default_owner_user_id' => 0, 'leads_per_page' => 30, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => get_option( 'yby_core_inquiry_salespeople', array() ) ) );
		$default_owner = absint( $raw['default_owner_user_id'] );
		if ( ! self::is_assignable_owner( $default_owner, $raw['salespeople'] ) ) { $default_owner = 0; }
		return array(
			'default_status' => in_array( $raw['default_status'], array( 'new', 'pending_contact', 'contacted', 'following_up', 'pending_quote', 'quoted', 'won', 'invalid', 'closed' ), true ) ? $raw['default_status'] : 'new',
			'default_priority' => in_array( $raw['default_priority'], array( 'low', 'normal', 'high', 'urgent' ), true ) ? $raw['default_priority'] : 'normal',
			'default_owner_user_id' => $default_owner,
			'leads_per_page' => in_array( absint( $raw['leads_per_page'] ), array( 30, 50, 100 ), true ) ? absint( $raw['leads_per_page'] ) : 30,
			'editors_can_manage' => ! empty( $raw['editors_can_manage'] ),
			'assignment_enabled' => ! empty( $raw['assignment_enabled'] ),
			'archive_behavior' => 'soft' === $raw['archive_behavior'] ? 'soft' : 'soft',
			'salespeople' => self::active_owner_ids( $raw['salespeople'] ),
		);
	}

	public static function active_owner_ids( $ids = null ) {
		if ( null === $ids ) {
			$settings = get_option( 'yby_core_inquiry_settings', array() );
			$ids = is_array( $settings ) && array_key_exists( 'salespeople', $settings ) ? $settings['salespeople'] : get_option( 'yby_core_inquiry_salespeople', array() );
		}
		$ids = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
		return array_values( array_filter( $ids, static function ( $id ) { return self::is_salesperson( $id ); } ) );
	}

	public static function salesperson_ids( $ids = null ) { return self::active_owner_ids( $ids ); }

	public static function salesperson_user_ids() {
		return array_map( 'absint', get_users( array( 'role' => 'salesperson', 'fields' => 'ID' ) ) );
	}

	public static function owner_ids() { return array_merge( array( 0 ), self::active_owner_ids() ); }

	public static function is_active_owner( $owner_id, $ids = null ) { return in_array( absint( $owner_id ), self::active_owner_ids( $ids ), true ); }

	public static function is_assignable_owner( $owner_id, $ids = null ) { return 0 === absint( $owner_id ) || self::is_active_owner( $owner_id, $ids ); }

	public static function is_valid_owner( $owner_id, $ids = null ) { return self::is_assignable_owner( $owner_id, $ids ); }

	public static function is_existing_owner_valid_for_record( $requested_owner_id, $current_owner_id, $ids = null ) {
		$requested_owner_id = absint( $requested_owner_id );
		$current_owner_id = absint( $current_owner_id );
		return $requested_owner_id === $current_owner_id || self::is_assignable_owner( $requested_owner_id, $ids );
	}

	public static function is_salesperson( $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( ! $user_id ) { return false; }
		$user = get_user_by( 'id', $user_id );
		return $user instanceof WP_User && in_array( 'salesperson', (array) $user->roles, true );
	}

	public static function owner_display_name( $owner_id, $mark_inactive = false ) {
		$owner_id = absint( $owner_id );
		if ( 0 === $owner_id ) { return __( 'Unassigned', 'yby-core' ); }
		$user = get_user_by( 'id', $owner_id );
		$label = $user instanceof WP_User ? $user->display_name : __( 'Unknown owner', 'yby-core' );
		return $mark_inactive ? sprintf( __( '%s (Inactive)', 'yby-core' ), $label ) : $label;
	}

	public static function owner_dropdown_options( $current_owner_id = 0 ) {
		$current_owner_id = absint( $current_owner_id );
		$options = array( array( 'id' => 0, 'label' => self::owner_display_name( 0 ), 'inactive' => false ) );
		$active_owner_ids = self::active_owner_ids();
		foreach ( $active_owner_ids as $owner_id ) {
			$options[] = array( 'id' => $owner_id, 'label' => self::owner_display_name( $owner_id ), 'inactive' => false );
		}
		if ( $current_owner_id && ! in_array( $current_owner_id, $active_owner_ids, true ) ) {
			$options[] = array( 'id' => $current_owner_id, 'label' => self::owner_display_name( $current_owner_id, true ), 'inactive' => true );
		}
		return $options;
	}

	public static function can_view_leads() { return current_user_can( 'andy_core_leads_view' ) || self::is_salesperson(); }

	public static function is_restricted_salesperson() { return self::is_salesperson() && ! current_user_can( 'manage_options' ); }

	public static function can_view_lead( $lead_id ) {
		if ( ! self::can_view_leads() || ! self::is_restricted_salesperson() ) { return self::can_view_leads(); }
		global $wpdb;
		$owner_id = $wpdb->get_var( $wpdb->prepare( 'SELECT owner_user_id FROM ' . YBY_Database::management_table_name() . ' WHERE lead_id = %d', absint( $lead_id ) ) );
		return absint( $owner_id ) === get_current_user_id();
	}
	public static function can_manage_leads() {
		if ( self::is_restricted_salesperson() ) { return false; }
		if ( ! current_user_can( 'andy_core_leads_manage' ) ) { return false; }
		$user = wp_get_current_user();
		return ! in_array( 'editor', (array) $user->roles, true ) || ! empty( self::inquiry_settings()['editors_can_manage'] );
	}
	public static function can_assign_leads() { return ! self::is_restricted_salesperson() && self::inquiry_settings()['assignment_enabled'] && current_user_can( 'andy_core_leads_assign' ); }
	public static function can_archive_leads() { return ! self::is_restricted_salesperson() && current_user_can( 'andy_core_leads_archive' ) && 'soft' === self::inquiry_settings()['archive_behavior']; }

	/**
	 * Verify admin capability.
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return ! self::is_restricted_salesperson() && current_user_can( 'andy_core_settings_manage' );
	}
}
