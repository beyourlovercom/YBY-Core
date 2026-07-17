<?php
/**
 * Database installer.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns YBY Core database tables.
 */
class YBY_Database {

	/**
	 * Lead table suffix.
	 *
	 * @var string
	 */
	const LEADS_TABLE = 'yby_leads';

	/**
	 * Database version option key.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'yby_database_version';

	/**
	 * Install or upgrade plugin database tables.
	 *
	 * @return void
	 */
	public static function install() {
		$stored_version = get_option( self::VERSION_OPTION, '' );

		if ( YBY_DATABASE_VERSION === $stored_version && self::leads_table_exists() ) {
			return;
		}

		self::create_leads_table();
		update_option( self::VERSION_OPTION, YBY_DATABASE_VERSION );
	}

	/**
	 * Return the full leads table name.
	 *
	 * @return string
	 */
	public static function leads_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::LEADS_TABLE;
	}

	/**
	 * Check whether the leads table exists.
	 *
	 * @return bool
	 */
	public static function leads_table_exists() {
		global $wpdb;

		$table_name = self::leads_table_name();

		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}

	/**
	 * Create or upgrade the leads table.
	 *
	 * @return void
	 */
	protected static function create_leads_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::leads_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			case_id varchar(50) NOT NULL,
			brand varchar(50),
			website varchar(255),
			source_url text,
			name varchar(100),
			company varchar(150),
			country varchar(100),
			email varchar(150),
			whatsapp varchar(50),
			buyer_type varchar(50),
			product_interest text,
			quantity varchar(100),
			project_details longtext,
			utm_source varchar(100),
			utm_medium varchar(100),
			utm_campaign varchar(150),
			utm_term varchar(150),
			gclid varchar(255),
			fbclid varchar(255),
			status varchar(50),
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY case_id (case_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
