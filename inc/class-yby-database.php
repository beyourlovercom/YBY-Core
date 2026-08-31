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

	const CONNECTOR_IDEMPOTENCY_TABLE = 'yby_connector_idempotency';

	const CONNECTOR_AUDIT_TABLE = 'yby_connector_audit';

	const CONNECTOR_AFFILIATE_BINDINGS_TABLE = 'yby_connector_affiliate_bindings';

	const CONNECTOR_COUPON_BINDINGS_TABLE = 'yby_connector_coupon_bindings';
	const CONNECTOR_PAYOUT_BINDINGS_TABLE = 'yby_connector_payout_bindings';
	const CONNECTOR_PAYOUT_CLAIMS_TABLE = 'yby_connector_payout_claims';

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
		self::create_connector_tables();
		if ( self::leads_table_exists() && self::management_tables_exist() && self::connector_tables_exist() ) {
			update_option( self::VERSION_OPTION, YBY_DATABASE_VERSION );
		}
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

	public static function connector_idempotency_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::CONNECTOR_IDEMPOTENCY_TABLE;
	}

	public static function connector_audit_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::CONNECTOR_AUDIT_TABLE;
	}

	public static function connector_affiliate_bindings_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::CONNECTOR_AFFILIATE_BINDINGS_TABLE;
	}

	public static function connector_coupon_bindings_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::CONNECTOR_COUPON_BINDINGS_TABLE;
	}

	public static function connector_payout_bindings_table_name() { global $wpdb; return $wpdb->prefix . self::CONNECTOR_PAYOUT_BINDINGS_TABLE; }
	public static function connector_payout_claims_table_name() { global $wpdb; return $wpdb->prefix . self::CONNECTOR_PAYOUT_CLAIMS_TABLE; }

	public static function connector_tables_exist() {
		global $wpdb;
		$idempotency = self::connector_idempotency_table_name();
		$audit       = self::connector_audit_table_name();
		$bindings    = self::connector_affiliate_bindings_table_name();
		$coupons     = self::connector_coupon_bindings_table_name();
		$payouts     = self::connector_payout_bindings_table_name(); $claims = self::connector_payout_claims_table_name();
		if ( $idempotency !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $idempotency ) ) || $audit !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $audit ) ) || $bindings !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bindings ) ) || $coupons !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $coupons ) ) || $payouts !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $payouts ) ) || $claims !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $claims ) ) ) {
			return false;
		}
		$required = array( $idempotency => array( 'mutation_identity', 'state_expires', 'connection_action' ), $audit => array( 'request_id', 'connection_action', 'created_at' ), $bindings => array( 'connection_kol', 'connection_affiliate' ), $coupons => array( 'normalized_code', 'coupon_id', 'state_expires' ) );
		foreach ( $required as $table => $names ) {
			$found = array();
			foreach ( (array) $wpdb->get_results( 'SHOW INDEX FROM ' . $table, ARRAY_A ) as $index ) {
				if ( ! empty( $index['Key_name'] ) ) { $found[ $index['Key_name'] ] = true; }
				if ( in_array( $index['Key_name'] ?? '', array( 'mutation_identity', 'connection_kol', 'connection_affiliate', 'normalized_code', 'coupon_id' ), true ) && '0' !== (string) ( $index['Non_unique'] ?? '1' ) ) { return false; }
			}
			foreach ( $names as $name ) { if ( empty( $found[ $name ] ) ) { return false; } }
		}
		$columns = array();
		foreach ( (array) $wpdb->get_results( 'SHOW COLUMNS FROM ' . $bindings, ARRAY_A ) as $column ) { if ( ! empty( $column['Field'] ) ) { $columns[ $column['Field'] ] = true; } }
		foreach ( array( 'state', 'lease_owner_hash', 'lease_expires_at' ) as $column ) { if ( empty( $columns[ $column ] ) ) { return false; } }
		$payout_columns = array(); foreach ( (array) $wpdb->get_results( 'SHOW COLUMNS FROM ' . $payouts, ARRAY_A ) as $column ) { if ( ! empty( $column['Field'] ) ) { $payout_columns[ $column['Field'] ] = true; } }
		$claim_columns = array(); foreach ( (array) $wpdb->get_results( 'SHOW COLUMNS FROM ' . $claims, ARRAY_A ) as $column ) { if ( ! empty( $column['Field'] ) ) { $claim_columns[ $column['Field'] ] = true; } }
		return isset( $payout_columns['erp_payout_request_id'], $payout_columns['request_fingerprint'], $payout_columns['payout_id'], $payout_columns['lease_owner_hash'], $claim_columns['referral_id'] );
	}

	public static function connector_affiliate_bindings_table_exists() {
		global $wpdb;
		$table = self::connector_affiliate_bindings_table_name();
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { return false; }
		$found = array();
		foreach ( (array) $wpdb->get_results( 'SHOW INDEX FROM ' . $table, ARRAY_A ) as $index ) {
			if ( ! empty( $index['Key_name'] ) ) { $found[ $index['Key_name'] ] = true; }
		}
		if ( ! isset( $found['connection_kol'], $found['connection_affiliate'] ) ) { return false; }
		$columns = array();
		foreach ( (array) $wpdb->get_results( 'SHOW COLUMNS FROM ' . $table, ARRAY_A ) as $column ) { if ( ! empty( $column['Field'] ) ) { $columns[ $column['Field'] ] = true; } }
		return isset( $columns['state'], $columns['lease_owner_hash'], $columns['lease_expires_at'] );
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

		return ! self::leads_table_exists() || ! self::management_tables_exist() || ! self::connector_tables_exist();
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

	protected static function create_connector_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$idempotency = self::connector_idempotency_table_name();
		$audit       = self::connector_audit_table_name();
		$bindings    = self::connector_affiliate_bindings_table_name();
		$coupons     = self::connector_coupon_bindings_table_name();
		$payouts     = self::connector_payout_bindings_table_name(); $claims = self::connector_payout_claims_table_name();
		$idempotency_sql = "CREATE TABLE {$idempotency} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			connection_key varchar(128) NOT NULL,
			action_key varchar(128) NOT NULL,
			idempotency_key_hash char(64) NOT NULL,
			mutation_identity_hash char(64) NOT NULL,
			request_fingerprint char(64) NOT NULL,
			state varchar(20) NOT NULL,
			safe_result text NULL,
			failure_code varchar(80) NOT NULL DEFAULT '',
			retryable tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			expires_at datetime NULL,
			PRIMARY KEY (id),
			UNIQUE KEY mutation_identity (mutation_identity_hash),
			KEY state_expires (state, expires_at),
			KEY connection_action (connection_key, action_key)
		) {$charset_collate};";
		$audit_sql = "CREATE TABLE {$audit} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			request_id varchar(80) NOT NULL,
			key_id varchar(64) NOT NULL DEFAULT '',
			connection_key varchar(128) NOT NULL,
			endpoint_action varchar(160) NOT NULL,
			idempotency_key_hash char(64) NOT NULL DEFAULT '',
			actor varchar(40) NOT NULL DEFAULT 'ERP trusted system',
			target_provider_ids text NULL,
			result_code varchar(80) NOT NULL DEFAULT '',
			success tinyint(1) NOT NULL DEFAULT 0,
			retryable tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY request_id (request_id),
			KEY connection_action (connection_key, endpoint_action),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $idempotency_sql );
		dbDelta( $audit_sql );
		$bindings_sql = "CREATE TABLE {$bindings} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			connection_key varchar(128) NOT NULL,
			erp_kol_id bigint unsigned NOT NULL,
			wp_user_id bigint unsigned NULL,
			affiliate_id bigint unsigned NULL,
			state varchar(20) NOT NULL DEFAULT 'processing',
			lease_owner_hash char(64) NULL,
			lease_expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY connection_kol (connection_key, erp_kol_id),
			UNIQUE KEY connection_affiliate (connection_key, affiliate_id)
		) {$charset_collate};";
		dbDelta( $bindings_sql );
		$coupons_sql = "CREATE TABLE {$coupons} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			connection_key varchar(128) NOT NULL,
			normalized_code varchar(255) NOT NULL,
			exact_code varchar(255) NOT NULL,
			coupon_id bigint unsigned NULL,
			affiliate_id bigint unsigned NULL,
			erp_kol_id bigint unsigned NULL,
			state varchar(20) NOT NULL DEFAULT 'processing',
			lease_owner_hash char(64) NULL,
			lease_expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY normalized_code (normalized_code),
			UNIQUE KEY coupon_id (coupon_id),
			KEY state_expires (state, lease_expires_at)
		) {$charset_collate};";
		dbDelta( $coupons_sql );
		$payouts_sql = "CREATE TABLE {$payouts} (
			id bigint unsigned NOT NULL AUTO_INCREMENT, connection_key varchar(128) NOT NULL, erp_payout_request_id varchar(128) NOT NULL, request_fingerprint char(64) NOT NULL, state varchar(20) NOT NULL DEFAULT 'processing', payout_id bigint unsigned NULL, lease_owner_hash char(64) NULL, lease_expires_at datetime NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id), UNIQUE KEY connection_request (connection_key, erp_payout_request_id), UNIQUE KEY payout_id (payout_id), KEY state_expires (state, lease_expires_at)
		) {$charset_collate};";
		$claims_sql = "CREATE TABLE {$claims} (
			id bigint unsigned NOT NULL AUTO_INCREMENT, referral_id bigint unsigned NOT NULL, connection_key varchar(128) NOT NULL, erp_payout_request_id varchar(128) NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY (id), UNIQUE KEY referral_id (referral_id), KEY payout_request (connection_key, erp_payout_request_id)
		) {$charset_collate};";
		dbDelta( $payouts_sql ); dbDelta( $claims_sql );
	}
}
