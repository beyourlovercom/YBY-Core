<?php
/**
 * Read-only WordPress subscriber snapshot adapter.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class YBY_Subscriber_Snapshot {
	const CONSENT_SOURCE = 'wordpress_elementor_signup';
	const PROVIDER = 'wordpress';

	public static function snapshot( $query ) {
		global $wpdb;
		if ( ! self::database_available( $wpdb ) ) {
			return self::unavailable();
		}
		$submissions = $wpdb->prefix . 'e_submissions';
		$values = $wpdb->prefix . 'e_submissions_values';
		if ( ! self::schema_available( $wpdb, $submissions, array( 'id', 'form_name', 'created_at_gmt' ) ) ) {
			return self::unavailable();
		}
		if ( ! self::schema_available( $wpdb, $values, array( 'submission_id', 'key', 'value' ) ) ) {
			return self::unavailable();
		}

		$where = array(
			"LOWER(TRIM(s.form_name)) IN ('singup','signup')",
			"LOWER(TRIM(v.`key`)) = 'email'",
			"TRIM(v.value) <> ''",
		);
		$params = array();
		if ( null !== $query['cursor_key'] ) {
			$where[] = 'LOWER(TRIM(v.value)) > %s';
			$params[] = $query['cursor_key'];
		}

		$having = '';
		if ( null !== $query['updated_after'] ) {
			$after = YBY_Connector::timestamp( $query['updated_after'] );
			if ( false === $after ) {
				return self::validation_error( 'updated_after must be ISO-8601.' );
			}
			$having = ' HAVING latest_signup_gmt > %s';
			$params[] = gmdate( 'Y-m-d H:i:s', $after );
		}

		$sql = "SELECT LOWER(TRIM(v.value)) AS email_normalized, "
			. "MIN(s.created_at_gmt) AS first_signup_gmt, "
			. "MAX(s.created_at_gmt) AS latest_signup_gmt "
			. "FROM `{$submissions}` s "
			. "INNER JOIN `{$values}` v ON v.submission_id = s.id "
			. 'WHERE ' . implode( ' AND ', $where )
			. ' GROUP BY email_normalized'
			. $having
			. ' ORDER BY email_normalized ASC LIMIT %d';
		$params[] = (int) $query['limit'] + 1;
		$prepared = call_user_func_array(
			array( $wpdb, 'prepare' ),
			array_merge( array( $sql ), $params )
		);
		$rows = $wpdb->get_results( $prepared );
		if ( ! is_array( $rows ) || ! empty( $wpdb->last_error ) ) {
			return self::unavailable();
		}

		$options = YBY_Connector::get_options();
		$items = array();
		foreach ( array_slice( $rows, 0, $query['limit'] ) as $row ) {
			$email = strtolower( trim( (string) ( $row->email_normalized ?? '' ) ) );
			$first = self::gmt_iso8601( $row->first_signup_gmt ?? null );
			$latest = self::gmt_iso8601( $row->latest_signup_gmt ?? null );
			if ( '' === $email || null === $first || null === $latest ) {
				return self::unavailable();
			}
			$items[] = array(
				'email' => $email,
				'status' => 'subscribed',
				'consent_source' => self::CONSENT_SOURCE,
				'subscribed_at' => $first,
				'status_updated_at' => $latest,
				'source_site' => (string) ( $options['connection_key'] ?? '' ),
				'provider' => self::PROVIDER,
			);
		}

		$has_more = count( $rows ) > $query['limit'];
		$next_cursor = null;
		if ( $has_more && $items ) {
			$last = end( $items );
			$next_cursor = self::cursor( $last['email'] );
		}

		return array(
			'items' => $items,
			'next_cursor' => $next_cursor,
		);
	}

	private static function database_available( $wpdb ) {
		return is_object( $wpdb )
			&& isset( $wpdb->prefix )
			&& is_string( $wpdb->prefix )
			&& method_exists( $wpdb, 'get_col' )
			&& method_exists( $wpdb, 'get_results' )
			&& method_exists( $wpdb, 'prepare' );
	}

	private static function schema_available( $wpdb, $table, $required ) {
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
			return false;
		}
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );
		return is_array( $columns )
			&& empty( $wpdb->last_error )
			&& 0 === count( array_diff( $required, $columns ) );
	}

	private static function gmt_iso8601( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$date = DateTime::createFromFormat(
			'Y-m-d H:i:s',
			$value,
			new DateTimeZone( 'UTC' )
		);
		$errors = DateTime::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			return null;
		}
		return $date->format( DateTime::ATOM );
	}

	private static function cursor( $email ) {
		$payload = array( 'resource' => 'subscribers', 'email' => $email );
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload ) : json_encode( $payload );
		return rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	}

	private static function validation_error( $message ) {
		return new WP_Error(
			'VALIDATION_FAILED',
			$message,
			array( 'status' => 400, 'retryable' => false )
		);
	}

	private static function unavailable() {
		return new WP_Error(
			'PROVIDER_UNAVAILABLE',
			'The required WordPress subscriber tables are unavailable.',
			array( 'status' => 503, 'retryable' => true )
		);
	}
}
