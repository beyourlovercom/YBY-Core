<?php
/**
 * Lead service.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates central lead records.
 */
class YBY_Lead_Service {

	/**
	 * Create a lead in the central lead table.
	 *
	 * @param array<string, string> $data Sanitized lead data.
	 * @return array<string, mixed>
	 */
	public static function create( $data ) {
		global $wpdb;

		if ( ! YBY_Database::leads_table_exists() ) {
			YBY_Database::install();
		}

		$case_id = self::resolve_case_id( $data );
		$record  = self::build_record( $data, $case_id );

		$inserted = $wpdb->insert(
			YBY_Database::leads_table_name(),
			$record,
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $inserted ) {
			return array(
				'success' => false,
				'message' => 'Lead could not be saved',
			);
		}

		return array(
			'success' => true,
			'lead_id' => (int) $wpdb->insert_id,
			'case_id' => $case_id,
			'status'  => $record['status'],
		);
	}

	/**
	 * Build a database record.
	 *
	 * @param array<string, string> $data Sanitized lead data.
	 * @param string                $case_id Case ID.
	 * @return array<string, string>
	 */
	protected static function build_record( $data, $case_id ) {
		return array(
			'case_id'          => $case_id,
			'brand'            => $data['brand'],
			'website'          => $data['website'],
			'source_url'       => $data['source_url'],
			'name'             => $data['name'],
			'company'          => $data['company'],
			'country'          => $data['country'],
			'email'            => $data['email'],
			'whatsapp'         => $data['whatsapp'],
			'buyer_type'       => $data['buyer_type'],
			'product_interest' => $data['product_interest'],
			'quantity'         => $data['quantity'],
			'project_details'  => $data['project_details'],
			'utm_source'       => $data['utm_source'],
			'utm_medium'       => $data['utm_medium'],
			'utm_campaign'     => $data['utm_campaign'],
			'utm_term'         => $data['utm_term'],
			'gclid'            => $data['gclid'],
			'fbclid'           => $data['fbclid'],
			'status'           => 'received',
			'created_at'       => current_time( 'mysql' ),
		);
	}

	/**
	 * Resolve or generate a unique case ID.
	 *
	 * @param array<string, string> $data Sanitized lead data.
	 * @return string
	 */
	protected static function resolve_case_id( $data ) {
		$case_engine = new YBY_Case_ID();
		$case_id     = $case_engine->normalize( isset( $data['case_id'] ) ? $data['case_id'] : '' );

		if ( $case_engine->validate( $case_id ) && self::is_case_id_unique( $case_id ) ) {
			return $case_id;
		}

		return self::generate_unique_case_id( $data );
	}

	/**
	 * Generate a unique temporary case ID.
	 *
	 * @param array<string, string> $data Sanitized lead data.
	 * @return string
	 */
	protected static function generate_unique_case_id( $data ) {
		$brand = self::brand_code( isset( $data['brand'] ) ? $data['brand'] : '' );

		for ( $attempt = 0; $attempt < 10; $attempt++ ) {
			$case_id = 'YBY-' . $brand . '-' . gmdate( 'Ymd' ) . '-' . self::random_code( 6 );

			if ( self::is_case_id_unique( $case_id ) ) {
				return $case_id;
			}
		}

		return 'YBY-' . $brand . '-' . gmdate( 'Ymd' ) . '-' . strtoupper( wp_generate_password( 10, false, false ) );
	}

	/**
	 * Check whether a Case ID is unused.
	 *
	 * @param string $case_id Case ID.
	 * @return bool
	 */
	protected static function is_case_id_unique( $case_id ) {
		global $wpdb;

		$table_name = YBY_Database::leads_table_name();

		return null === $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE case_id = %s LIMIT 1", $case_id ) );
	}

	/**
	 * Build the case ID brand segment.
	 *
	 * @param string $brand Brand value.
	 * @return string
	 */
	protected static function brand_code( $brand ) {
		$brand = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $brand ) );

		if ( false !== strpos( $brand, 'IRR' ) || false !== strpos( $brand, 'IRRIGATION' ) ) {
			return 'IRR';
		}

		return substr( $brand ?: 'CORE', 0, 8 );
	}

	/**
	 * Generate a readable random code.
	 *
	 * @param int $length Code length.
	 * @return string
	 */
	protected static function random_code( $length ) {
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$max_index  = strlen( $characters ) - 1;
		$output     = '';

		for ( $index = 0; $index < $length; $index++ ) {
			$output .= $characters[ wp_rand( 0, $max_index ) ];
		}

		return $output;
	}
}
