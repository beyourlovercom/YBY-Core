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

	const MANAGEMENT_TABLE = 'yby_lead_management';

	const ACTIVITIES_TABLE = 'yby_lead_activities';

	/**
	 * Install or upgrade plugin database tables.
	 *
	 * @return void
	 */
	public static function install() {
		if ( ! self::needs_install_or_upgrade() ) {
			return;
		}

		self::create_leads_table();
		self::create_management_tables();
		update_option( self::VERSION_OPTION, YBY_DATABASE_VERSION );
	}

	/**
	 * Run an idempotent upgrade check during plugin boot.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( self::needs_install_or_upgrade() ) {
			self::install();
		}
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

	public static function management_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::MANAGEMENT_TABLE;
	}

	public static function activities_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::ACTIVITIES_TABLE;
	}

	public static function management_tables_exist() {
		global $wpdb;
		$management_exists = self::management_table_name() === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::management_table_name() ) );
		$activities_exists = self::activities_table_name() === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::activities_table_name() ) );
		if ( ! $management_exists || ! $activities_exists ) {
			return false;
		}
		$management_indexes = $wpdb->get_col( 'SHOW INDEX FROM ' . self::management_table_name(), 2 );
		$activity_indexes = $wpdb->get_col( 'SHOW INDEX FROM ' . self::activities_table_name(), 2 );
		return in_array( 'lead_id', $management_indexes, true )
			&& in_array( 'status_archived', $management_indexes, true )
			&& in_array( 'owner_archived', $management_indexes, true )
			&& in_array( 'lead_created', $activity_indexes, true );
	}

	/**
	 * Check whether the database schema is missing or outdated.
	 *
	 * @return bool
	 */
	public static function needs_install_or_upgrade() {
		$stored_version = get_option( self::VERSION_OPTION, '' );

		if ( YBY_DATABASE_VERSION !== $stored_version ) {
			return true;
		}

		return ! self::leads_table_exists() || ! self::management_tables_exist();
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
			source_component varchar(100) DEFAULT '',
			source_preset varchar(100) DEFAULT '',
			source_page varchar(255) DEFAULT '',
			form_version varchar(30) DEFAULT '',
			page_profile varchar(100) DEFAULT '',
			custom_fields longtext,
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

	protected static function create_management_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$management = self::management_table_name();
		$activities = self::activities_table_name();
		$management_sql = "CREATE TABLE {$management} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint unsigned NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'new',
			owner_user_id bigint unsigned NOT NULL DEFAULT 0,
			priority varchar(20) NOT NULL DEFAULT 'normal',
			next_follow_up_at datetime NULL,
			last_activity_at datetime NULL,
			archived_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY lead_id (lead_id),
			KEY status_archived (status, archived_at),
			KEY owner_archived (owner_user_id, archived_at),
			KEY created_at (created_at)
		) {$charset_collate};";
		$activities_sql = "CREATE TABLE {$activities} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint unsigned NOT NULL,
			activity_type varchar(40) NOT NULL,
			content text NULL,
			old_value text NULL,
			new_value text NULL,
			created_by bigint unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY lead_created (lead_id, created_at),
			KEY activity_type (activity_type)
		) {$charset_collate};";
		dbDelta( $management_sql );
		dbDelta( $activities_sql );
	}
}
