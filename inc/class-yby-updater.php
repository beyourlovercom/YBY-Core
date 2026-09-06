<?php
/**
 * WordPress updater integration and guarded orchestration.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Updater {
	const CHECK_NONCE    = 'yby_core_check_updates';
	const ROLLBACK_NONCE = 'yby_core_rollback';
	const PENDING_KEY    = 'yby_core_updater_pending';

	private $plugin_file;
	private $version;
	private $client;

	public function __construct( $plugin_file, $version ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
		$this->client      = new YBY_GitHub_Release_Client();
	}

	public function register_hooks( $loader ) {
		$loader->add_filter( 'pre_set_site_transient_update_plugins', $this, 'filter_updates', 10, 1 );
		$loader->add_filter( 'plugins_api', $this, 'filter_plugin_information', 10, 3 );
		$loader->add_filter( 'upgrader_pre_download', $this, 'secure_download', 10, 4 );
		$loader->add_filter( 'upgrader_pre_install', $this, 'prepare_backup', 10, 2 );
		$loader->add_action( 'upgrader_process_complete', $this, 'validate_install', 10, 2 );
		$loader->add_action( 'admin_init', $this, 'handle_admin_action', 10, 0 );
		$loader->add_action( 'admin_notices', $this, 'render_notice', 10, 0 );
	}

	private function can_manage() {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			return false;
		}
		$security = new YBY_Security();
		return $security->can_manage_settings();
	}

	public function filter_updates( $transient ) {
		if ( ! is_object( $transient ) || ! $this->client->has_auth() ) {
			return $transient;
		}
		$release = $this->validated_release( false );
		if ( is_wp_error( $release ) || ! YBY_Update_Verifier::is_newer( $release['version'], $this->version ) ) {
			return $transient;
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$plugin = plugin_basename( $this->plugin_file );
		$transient->response[ $plugin ] = (object) array(
			'slug'        => 'yby-core',
			'plugin'      => $plugin,
			'new_version' => $release['version'],
			'url'         => $release['release_url'],
			'package'     => $release['package_url'],
			'tested'      => get_bloginfo( 'version' ),
			'icons'       => array(),
		);
		return $transient;
	}

	public function filter_plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || 'yby-core' !== (string) ( $args->slug ?? '' ) || ! $this->client->has_auth() ) {
			return $result;
		}
		$release = $this->validated_release( false );
		if ( is_wp_error( $release ) ) {
			return $result;
		}
		$info              = new stdClass();
		$info->name        = 'Andy Core';
		$info->slug        = 'yby-core';
		$info->version     = $release['version'];
		$info->author      = '<a href="https://ybyglobal.com/">YBY Global</a>';
		$info->homepage    = 'https://ybyglobal.com/';
		$info->download_link = $release['package_url'];
		$info->sections    = array( 'description' => esc_html__( 'Secure Andy Core release update.', 'yby-core' ) );
		return $info;
	}

	public function secure_download( $reply, $package, $upgrader, $hook_extra = array() ) {
		if ( ! is_string( $package ) || ! $this->client->is_api_asset_url( $package ) ) {
			return $reply;
		}
		if ( is_array( $hook_extra ) && ! empty( $hook_extra['plugin'] ) && plugin_basename( $this->plugin_file ) !== (string) $hook_extra['plugin'] ) {
			return $reply;
		}

		$release = $this->validated_release( true );
		if ( is_wp_error( $release ) ) {
			return $release;
		}
		if ( $package !== $release['package_url'] ) {
			return new WP_Error( 'yby_update_package_rejected', __( 'Andy Core rejected an unverified package endpoint.', 'yby-core' ) );
		}
		if ( ! YBY_Update_Verifier::is_newer( $release['version'], $this->version ) ) {
			return new WP_Error( 'yby_update_package_rejected', __( 'Andy Core rejected the release package.', 'yby-core' ) );
		}

		$zip = $this->client->download_asset( $package );
		if ( is_wp_error( $zip ) ) {
			return $zip;
		}
		$actual = strtolower( (string) hash_file( 'sha256', $zip ) );
		if ( ! hash_equals( $release['sha256'], $actual ) ) {
			@unlink( $zip );
			return new WP_Error( 'yby_update_checksum_mismatch', __( 'Andy Core rejected the release because its SHA-256 does not match.', 'yby-core' ) );
		}
		$valid = YBY_Update_Verifier::validate_zip( $zip, $release['version'] );
		if ( is_wp_error( $valid ) ) {
			@unlink( $zip );
			return $valid;
		}

		if ( ! set_site_transient(
			self::PENDING_KEY,
			array( 'target_version' => $release['version'] ),
			60 * MINUTE_IN_SECONDS
		) ) {
			@unlink( $zip );
			return new WP_Error( 'yby_update_pending_store_failed', __( 'Andy Core refused the update because verified pending state could not be stored.', 'yby-core' ) );
		}
		return $zip;
	}

	public function prepare_backup( $response, $hook_extra ) {
		if ( is_wp_error( $response ) || ! $this->is_this_plugin_hook( $hook_extra ) ) {
			return $response;
		}
		$pending = get_site_transient( self::PENDING_KEY );
		if ( ! is_array( $pending ) || ! YBY_Update_Verifier::is_newer( $pending['target_version'] ?? '', $this->version ) ) {
			return new WP_Error( 'yby_update_pending_missing', __( 'Andy Core refused the update because verified pending update state is missing.', 'yby-core' ) );
		}

		$backup = YBY_Update_Backup::create( dirname( $this->plugin_file ), $this->version );
		if ( is_wp_error( $backup ) ) {
			delete_site_transient( self::PENDING_KEY );
			return $backup;
		}
		$pending = array( 'target_version' => $pending['target_version'], 'backup_reference' => $backup['path'] );
		if ( ! set_site_transient( self::PENDING_KEY, $pending, 60 * MINUTE_IN_SECONDS ) ) {
			delete_site_transient( self::PENDING_KEY );
			return new WP_Error( 'yby_update_pending_store_failed', __( 'Andy Core refused the update because rollback state could not be stored.', 'yby-core' ) );
		}
		return $response;
	}

	public function validate_install( $upgrader, $hook_extra ) {
		if ( ! $this->is_this_plugin_hook( $hook_extra ) ) {
			return;
		}
		$pending = get_site_transient( self::PENDING_KEY );
		if ( ! is_array( $pending ) || empty( $pending['target_version'] ) || empty( $pending['backup_reference'] ) ) {
			$restored = YBY_Update_Backup::restore_latest( dirname( $this->plugin_file ) );
			$this->notice(
				'error',
				is_wp_error( $restored )
					? __( 'Andy Core lost verified update state after installation and automatic rollback was unavailable. Owner intervention is required.', 'yby-core' )
					: __( 'Andy Core lost verified update state after installation. The previous compatible code backup was restored fail-closed.', 'yby-core' )
			);
			return;
		}

		$target  = YBY_Update_Verifier::stable_version( $pending['target_version'] );
		$user_id = get_current_user_id();
		if ( '' === $target || ! YBY_Update_Backup::health( dirname( $this->plugin_file ), $target ) ) {
			$backup = YBY_Update_Backup::latest();
			$restored = is_array( $backup ) && (string) $backup['path'] === (string) $pending['backup_reference'] ? YBY_Update_Backup::restore_record( dirname( $this->plugin_file ), $backup ) : new WP_Error( 'yby_update_rollback_unavailable', __( 'The exact compatible Andy Core backup could not be located.', 'yby-core' ) );
			delete_site_transient( self::PENDING_KEY );
			$this->notice(
				'error',
				is_wp_error( $restored )
					? __( 'Andy Core health validation failed and automatic rollback also failed. Owner intervention is required.', 'yby-core' )
					: __( 'Andy Core health validation failed. The previous code backup was restored.', 'yby-core' ),
				$user_id
			);
			return;
		}

		delete_site_transient( self::PENDING_KEY );
		$this->notice( 'success', sprintf( __( 'Andy Core %s was installed and passed local health validation.', 'yby-core' ), $target ), $user_id );
	}

	public function handle_admin_action() {
		if ( ! is_admin() || ! $this->can_manage() ) {
			return;
		}

		if ( isset( $_POST['yby_core_check_updates'] ) ) {
			check_admin_referer( self::CHECK_NONCE, 'yby_core_check_updates_nonce' );
			$release = $this->validated_release( true );
			if ( is_wp_error( $release ) ) {
				$this->notice( 'error', $release->get_error_message() );
			} elseif ( YBY_Update_Verifier::is_newer( $release['version'], $this->version ) ) {
				delete_site_transient( 'update_plugins' );
				wp_update_plugins();
				$this->notice( 'success', sprintf( __( 'Andy Core %s is available in Plugins.', 'yby-core' ), $release['version'] ) );
			} else {
				$this->notice( 'success', __( 'Andy Core is up to date.', 'yby-core' ) );
			}
			$this->redirect();
		}

		if ( isset( $_POST['yby_core_rollback'] ) ) {
			check_admin_referer( self::ROLLBACK_NONCE, 'yby_core_rollback_nonce' );
			$restored = YBY_Update_Backup::restore_latest( dirname( $this->plugin_file ) );
			$this->notice(
				is_wp_error( $restored ) ? 'error' : 'success',
				is_wp_error( $restored ) ? $restored->get_error_message() : __( 'The latest compatible Andy Core code backup was restored.', 'yby-core' )
			);
			$this->redirect();
		}
	}

	public function render_notice() {
		if ( ! $this->can_manage() ) {
			return;
		}
		$key    = 'yby_core_updater_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
	}

	public function render_tab() {
		if ( ! $this->can_manage() ) {
			return;
		}
		$release = $this->validated_release( false );
		echo '<div class="wrap"><h1>' . esc_html__( 'Andy Core', 'yby-core' ) . '</h1>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( array( 'general' => '常规', 'inquiry' => '询盘', 'inquiry-notification' => '询盘通知', 'wp-api' => 'WP-API', 'system-status' => '系统状态', 'updates' => '更新' ) as $key => $label ) {
			echo '<a class="nav-tab ' . ( 'updates' === $key ? 'nav-tab-active' : '' ) . '" href="' . esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</h2><h2>' . esc_html__( 'Secure Updates', 'yby-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'Andy Core checks the private GitHub stable release channel. The GitHub token is server-side only and is never stored, rendered, or logged by Andy Core.', 'yby-core' ) . '</p>';
		echo '<p>' . esc_html( sprintf( __( 'Current version: %s', 'yby-core' ), $this->version ) ) . '</p>';
		if ( is_wp_error( $release ) ) {
			echo '<p><strong>' . esc_html( $release->get_error_message() ) . '</strong></p>';
		} elseif ( YBY_Update_Verifier::is_newer( $release['version'], $this->version ) ) {
			echo '<p>' . esc_html( sprintf( __( 'Verified update available: %s', 'yby-core' ), $release['version'] ) ) . '</p>';
		} else {
			echo '<p>' . esc_html( sprintf( __( 'Andy Core is up to date. Latest verified stable release: %s', 'yby-core' ), $release['version'] ) ) . '</p>';
		}
		echo '<form method="post">';
		wp_nonce_field( self::CHECK_NONCE, 'yby_core_check_updates_nonce' );
		echo '<p><button class="button button-primary" type="submit" name="yby_core_check_updates" value="1">' . esc_html__( 'Check for Updates', 'yby-core' ) . '</button></p></form>';
		echo '<hr><p>' . esc_html__( 'Rollback is code-only and restores the latest compatible updater backup. Database state is never rolled back by this tool.', 'yby-core' ) . '</p>';
		echo '<form method="post">';
		wp_nonce_field( self::ROLLBACK_NONCE, 'yby_core_rollback_nonce' );
		echo '<p><button class="button" type="submit" name="yby_core_rollback" value="1">' . esc_html__( 'Rollback to Latest Backup', 'yby-core' ) . '</button></p></form></div>';
	}

	private function validated_release( $force ) {
		$raw = $this->client->latest_release( $force );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}
		$version = YBY_Update_Verifier::release_version( $raw );
		if ( ! $version ) {
			return new WP_Error( 'yby_update_release_invalid', __( 'GitHub did not return a valid stable Andy Core release.', 'yby-core' ) );
		}
		$assets = YBY_Update_Verifier::select_asset( $raw, $version );
		if ( ! $assets ) {
			return new WP_Error( 'yby_update_assets_missing', __( 'The Andy Core release is missing unique required update assets or has invalid digest metadata.', 'yby-core' ) );
		}
		foreach ( array( 'package', 'checksum', 'metadata', 'signature' ) as $kind ) {
			if ( empty( $assets[ $kind ]['url'] ) || ! $this->client->is_api_asset_url( (string) $assets[ $kind ]['url'] ) ) {
				return new WP_Error( 'yby_update_assets_invalid', __( 'The Andy Core release contains an untrusted asset endpoint.', 'yby-core' ) );
			}
		}

		$package_name  = 'andy-core-v' . $version . '.zip';
		$checksum_file = $this->client->download_asset( (string) $assets['checksum']['url'] );
		if ( is_wp_error( $checksum_file ) ) {
			return $checksum_file;
		}
		$checksum_text = file_get_contents( $checksum_file );
		@unlink( $checksum_file );
		$sha256 = YBY_Update_Verifier::checksum( $checksum_text, $package_name );
		if ( ! $sha256 ) {
			return new WP_Error( 'yby_update_checksum_invalid', __( 'The Andy Core release checksum evidence is malformed.', 'yby-core' ) );
		}

		$digest = isset( $assets['package']['digest'] ) ? strtolower( trim( (string) $assets['package']['digest'] ) ) : '';
		if ( '' !== $digest && ! hash_equals( $sha256, substr( $digest, 7 ) ) ) {
			return new WP_Error( 'yby_update_checksum_evidence_mismatch', __( 'The Andy Core release checksum evidence does not agree.', 'yby-core' ) );
		}

		$metadata_file = $this->client->download_asset( (string) $assets['metadata']['url'] );
		if ( is_wp_error( $metadata_file ) ) {
			return $metadata_file;
		}
		$metadata_text = file_get_contents( $metadata_file );
		@unlink( $metadata_file );

		$signature_file = $this->client->download_asset( (string) $assets['signature']['url'] );
		if ( is_wp_error( $signature_file ) ) { return $signature_file; }
		$signature_text = file_get_contents( $signature_file );
		@unlink( $signature_file );
		if ( ! YBY_Update_Verifier::signature( $metadata_text, $signature_text ) ) {
			return new WP_Error( 'yby_update_signature_invalid', __( 'The Andy Core release signature is invalid or cannot be verified.', 'yby-core' ) );
		}
		if ( ! YBY_Update_Verifier::metadata( $metadata_text, $version, $package_name, $sha256 ) ) {
			return new WP_Error( 'yby_update_metadata_invalid', __( 'The Andy Core release metadata is incompatible with this updater.', 'yby-core' ) );
		}

		return array(
			'version'     => $version,
			'package_url' => (string) $assets['package']['url'],
			'release_url' => isset( $raw['html_url'] ) && is_string( $raw['html_url'] ) ? esc_url_raw( $raw['html_url'] ) : 'https://github.com/' . YBY_GitHub_Release_Client::OWNER . '/' . YBY_GitHub_Release_Client::REPOSITORY,
			'sha256'      => $sha256,
		);
	}

	private function is_this_plugin_hook( $hook_extra ) {
		if ( ! is_array( $hook_extra ) ) { return false; }
		$plugin = plugin_basename( $this->plugin_file );
		if ( $plugin === (string) ( $hook_extra['plugin'] ?? '' ) ) { return true; }
		return isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) && in_array( $plugin, $hook_extra['plugins'], true );
	}

	private function notice( $type, $message, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( ! $user_id ) {
			return;
		}
		set_transient(
			'yby_core_updater_notice_' . $user_id,
			array(
				'type'    => in_array( $type, array( 'success', 'warning', 'error', 'info' ), true ) ? $type : 'info',
				'message' => wp_strip_all_tags( (string) $message ),
			),
			120
		);
	}

	private function redirect() {
		wp_safe_redirect( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => 'updates' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
