<?php
/**
 * Local inquiry management service.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Lead_Management {

	const STATUSES = array( 'new', 'pending_contact', 'contacted', 'following_up', 'pending_quote', 'quoted', 'won', 'invalid', 'closed' );

	const PRIORITIES = array( 'low', 'normal', 'high', 'urgent' );

	public static function ensure( $lead_id ) {
		global $wpdb;
		$lead_id = absint( $lead_id );
		if ( ! $lead_id || ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . YBY_Database::leads_table_name() . ' WHERE id = %d', $lead_id ) ) ) {
			return 0;
		}
		$table = YBY_Database::management_table_name();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE lead_id = %d", $lead_id ) );
		if ( $existing ) {
			return (int) $existing;
		}
		$now = current_time( 'mysql' );
		$wpdb->insert( $table, array( 'lead_id' => $lead_id, 'status' => 'new', 'owner_user_id' => 0, 'priority' => 'normal', 'created_at' => $now, 'updated_at' => $now ), array( '%d', '%s', '%d', '%s', '%s', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	public static function list_leads( $args ) {
		global $wpdb;
		$leads = YBY_Database::leads_table_name();
		$management = YBY_Database::management_table_name();
		$page = max( 1, absint( $args['page'] ?? 1 ) );
		$per_page = min( 100, max( 30, absint( $args['per_page'] ?? 30 ) ) );
		$offset = ( $page - 1 ) * $per_page;
		$where = array( '1=1' );
		$values = array();
		$search = trim( (string) ( $args['search'] ?? '' ) );
		if ( strlen( $search ) >= 2 ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(l.case_id LIKE %s OR l.name LIKE %s OR l.email LIKE %s OR l.company LIKE %s OR l.whatsapp LIKE %s)';
			array_push( $values, $like, $like, $like, $like, $like );
		}
		if ( in_array( $args['status'] ?? '', self::STATUSES, true ) ) {
			$where[] = 'COALESCE(m.status, "new") = %s';
			$values[] = $args['status'];
		}
		if ( in_array( $args['priority'] ?? '', self::PRIORITIES, true ) ) {
			$where[] = 'COALESCE(m.priority, "normal") = %s';
			$values[] = $args['priority'];
		}
		if ( ! empty( $args['owner_user_id'] ) ) { $where[] = 'COALESCE(m.owner_user_id, 0) = %d'; $values[] = absint( $args['owner_user_id'] ); }
		if ( '' !== (string) ( $args['country'] ?? '' ) ) { $where[] = 'l.country = %s'; $values[] = sanitize_text_field( $args['country'] ); }
		if ( '' !== (string) ( $args['source_preset'] ?? '' ) ) { $where[] = 'l.source_preset = %s'; $values[] = sanitize_key( $args['source_preset'] ); }
		if ( '' !== (string) ( $args['page_profile'] ?? '' ) ) { $where[] = 'l.page_profile = %s'; $values[] = sanitize_key( $args['page_profile'] ); }
		if ( '' !== (string) ( $args['date_from'] ?? '' ) ) { $where[] = 'l.created_at >= %s'; $values[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00'; }
		if ( '' !== (string) ( $args['date_to'] ?? '' ) ) { $where[] = 'l.created_at <= %s'; $values[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59'; }
		if ( 'active' === ( $args['archived'] ?? '' ) ) { $where[] = '(m.archived_at IS NULL OR m.archived_at = "0000-00-00 00:00:00")'; }
		if ( 'archived' === ( $args['archived'] ?? '' ) ) { $where[] = 'm.archived_at IS NOT NULL AND m.archived_at <> "0000-00-00 00:00:00"'; }
		$where_sql = implode( ' AND ', $where );
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$leads} l LEFT JOIN {$management} m ON m.lead_id = l.id WHERE {$where_sql}", $values ) );
		$sql = "SELECT l.id,l.case_id,l.created_at,l.name,l.company,l.country,l.source_preset,l.page_profile,m.status,m.owner_user_id,m.priority,m.next_follow_up_at,m.last_activity_at,m.archived_at FROM {$leads} l LEFT JOIN {$management} m ON m.lead_id = l.id WHERE {$where_sql} ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
		$values[] = $per_page;
		$values[] = $offset;
		return array( 'items' => $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ), 'total' => $count, 'page' => $page, 'per_page' => $per_page );
	}

	public static function get_detail( $lead_id ) {
		global $wpdb;
		$lead_id = absint( $lead_id );
		$leads = YBY_Database::leads_table_name();
		$management = YBY_Database::management_table_name();
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT l.*,m.status,m.owner_user_id,m.priority,m.next_follow_up_at,m.last_activity_at,m.archived_at FROM {$leads} l LEFT JOIN {$management} m ON m.lead_id = l.id WHERE l.id = %d", $lead_id ), ARRAY_A );
		if ( ! $lead ) {
			return null;
		}
		$lead['activities'] = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . YBY_Database::activities_table_name() . ' WHERE lead_id = %d ORDER BY created_at DESC,id DESC LIMIT 50', $lead_id ), ARRAY_A );
		return $lead;
	}

	public static function save( $lead_id, $data, $actor ) {
		global $wpdb;
		$id = self::ensure( $lead_id );
		if ( ! $id ) {
			return false;
		}
		$current = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . YBY_Database::management_table_name() . ' WHERE lead_id = %d', $lead_id ), ARRAY_A );
		if ( ! in_array( $data['status'] ?? $current['status'], self::STATUSES, true ) || ! in_array( $data['priority'] ?? $current['priority'], self::PRIORITIES, true ) ) {
			return false;
		}
		if ( isset( $data['owner_user_id'] ) && absint( $data['owner_user_id'] ) && ! get_user_by( 'id', absint( $data['owner_user_id'] ) ) ) {
			return false;
		}
		$now = current_time( 'mysql' );
		$updates = array();
		$events = array();
		foreach ( array( 'status', 'priority', 'owner_user_id', 'next_follow_up_at' ) as $field ) {
			if ( array_key_exists( $field, $data ) && (string) $data[ $field ] !== (string) $current[ $field ] ) {
				$value = 'owner_user_id' === $field ? absint( $data[ $field ] ) : sanitize_text_field( (string) $data[ $field ] );
				$updates[ $field ] = $value;
				$events[] = array( 'activity_type' => 'next_follow_up_at' === $field ? 'follow_up_scheduled' : $field . '_changed', 'old_value' => (string) $current[ $field ], 'new_value' => (string) $value );
			}
		}
		if ( ! empty( $data['archived'] ) ) {
			$updates['archived_at'] = $now;
			$events[] = array( 'activity_type' => 'archived' );
		}
		if ( ! empty( $data['restore'] ) ) {
			$updates['archived_at'] = null;
			$events[] = array( 'activity_type' => 'restored' );
		}
		if ( ! empty( $data['note'] ) ) {
			$events[] = array( 'activity_type' => 'note', 'content' => sanitize_textarea_field( $data['note'] ) );
		}
		$updates['updated_at'] = $now;
		if ( ! empty( $events ) ) {
			$updates['last_activity_at'] = $now;
		}
		$wpdb->update( YBY_Database::management_table_name(), $updates, array( 'lead_id' => $lead_id ) );
		foreach ( $events as $event ) {
			$wpdb->insert( YBY_Database::activities_table_name(), array_merge( $event, array( 'lead_id' => $lead_id, 'created_by' => absint( $actor ), 'created_at' => $now ) ) );
		}
		return true;
	}
}
