<?php
/**
 * Code-only updater backup and restore.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Update_Backup {
	const OPTION = 'yby_core_updater_last_backup';
	const RETAIN = 3;

	public static function root() {
		if ( defined( 'YBY_CORE_UPDATE_BACKUP_DIR' ) && is_string( YBY_CORE_UPDATE_BACKUP_DIR ) && '' !== trim( YBY_CORE_UPDATE_BACKUP_DIR ) ) {
			return untrailingslashit( YBY_CORE_UPDATE_BACKUP_DIR );
		}
		return trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'andy-core-updater-backups';
	}

	public static function create( $plugin_dir, $version ) {
		$plugin_dir = untrailingslashit( $plugin_dir );
		if ( ! is_dir( $plugin_dir ) || is_link( $plugin_dir ) || ! YBY_Update_Verifier::stable_version( $version ) ) {
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because the installed plugin path is not safe to back up.', 'yby-core' ) );
		}

		$root = self::root();
		$normalized_root = strtolower( str_replace( '\\', '/', $root ) );
		$public_root = strtolower( rtrim( str_replace( '\\', '/', untrailingslashit( ABSPATH ) ), '/' ) );
		if ( preg_match( '!/(?:uploads)(?:/|$)!', $normalized_root ) || $normalized_root === $public_root || 0 === strpos( $normalized_root . '/', $public_root . '/' ) ) {
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because its backup directory is inside the public WordPress root.', 'yby-core' ) );
		}
		if ( ! wp_mkdir_p( $root ) || is_link( $root ) ) {
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because its backup directory could not be created safely.', 'yby-core' ) );
		}
		$root_real = realpath( $root );
		if ( ! $root_real ) {
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because its backup directory could not be resolved.', 'yby-core' ) );
		}

		$target = trailingslashit( $root_real ) . 'yby-core-' . sanitize_file_name( $version ) . '-' . gmdate( 'YmdHis' ) . '-' . wp_generate_password( 8, false, false );
		if ( ! wp_mkdir_p( $target ) || ! self::is_direct_backup_child( $target ) || ! self::copy_tree( $plugin_dir, $target ) ) {
			self::remove_tree( $target );
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because its code backup could not be completed.', 'yby-core' ) );
		}

		$record = array(
			'path'             => realpath( $target ),
			'version'          => self::read_version( $target ),
			'database_version' => YBY_Update_Verifier::DATABASE_VERSION,
			'created_at'       => gmdate( 'c' ),
		);
		if ( $record['version'] !== $version || ! self::health( $target, $version ) ) {
			self::remove_tree( $target );
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core refused the update because the backup failed validation.', 'yby-core' ) );
		}
		if ( ! update_option( self::OPTION, $record, false ) ) {
			self::remove_tree( $target );
			return new WP_Error( 'yby_update_backup_failed', __( 'Andy Core could not record its code backup.', 'yby-core' ) );
		}
		self::prune();
		return $record;
	}

	public static function latest() {
		$record = get_option( self::OPTION, array() );
		return self::valid_record( $record ) ? $record : false;
	}

	public static function restore_latest( $plugin_dir ) {
		$record = self::latest();
		if ( ! $record ) {
			return new WP_Error( 'yby_update_rollback_unavailable', __( 'No compatible Andy Core updater backup is available.', 'yby-core' ) );
		}
		return self::restore_record( $plugin_dir, $record );
	}

	public static function restore_record( $plugin_dir, $record ) {
		if ( ! self::valid_record( $record ) ) {
			return new WP_Error( 'yby_update_rollback_unavailable', __( 'The requested Andy Core updater backup is not valid.', 'yby-core' ) );
		}
		$plugin_dir = untrailingslashit( $plugin_dir );
		if ( is_link( $plugin_dir ) ) {
			return new WP_Error( 'yby_update_rollback_failed', __( 'Andy Core refused rollback because the plugin path is a symbolic link.', 'yby-core' ) );
		}

		$parent = dirname( $plugin_dir );
		$failed = $parent . '/yby-core-failed-' . gmdate( 'YmdHis' ) . '-' . wp_generate_password( 6, false, false );
		$moved  = false;
		if ( is_dir( $plugin_dir ) ) {
			if ( ! rename( $plugin_dir, $failed ) ) {
				return new WP_Error( 'yby_update_rollback_failed', __( 'Andy Core could not move the failed code out of the way.', 'yby-core' ) );
			}
			$moved = true;
		}

		if ( ! wp_mkdir_p( $plugin_dir ) || ! self::copy_tree( $record['path'], $plugin_dir ) || ! self::health( $plugin_dir, $record['version'] ) ) {
			if ( is_dir( $plugin_dir ) ) {
				self::remove_tree( $plugin_dir );
			}
			if ( $moved && is_dir( $failed ) ) {
				rename( $failed, $plugin_dir );
			}
			return new WP_Error( 'yby_update_rollback_failed', __( 'Andy Core could not restore its previous code safely.', 'yby-core' ) );
		}

		if ( $moved && is_dir( $failed ) ) {
			self::remove_tree( $failed );
		}
		return true;
	}

	public static function health( $plugin_dir, $version = '' ) {
		$plugin_dir = untrailingslashit( $plugin_dir );
		if ( is_link( $plugin_dir ) ) {
			return false;
		}
		$main   = $plugin_dir . '/yby-core.php';
		$source = is_file( $main ) && ! is_link( $main ) ? file_get_contents( $main ) : false;
		if ( false === $source || ! preg_match( '/^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$/mi', $source, $match ) ) {
			return false;
		}
		$actual = $match[1];
		if ( $version && $actual !== $version ) {
			return false;
		}
		return false !== strpos( $source, "define( 'YBY_CORE_VERSION', '{$actual}' );" )
			&& false !== strpos( $source, "define( 'YBY_DATABASE_VERSION', '" . YBY_Update_Verifier::DATABASE_VERSION . "' );" )
			&& is_file( $plugin_dir . '/inc/class-yby-core.php' )
			&& is_file( $plugin_dir . '/inc/class-yby-loader.php' )
			&& is_file( $plugin_dir . '/inc/class-yby-github-release-client.php' )
			&& is_file( $plugin_dir . '/inc/class-yby-update-verifier.php' )
			&& is_file( $plugin_dir . '/inc/class-yby-update-backup.php' )
			&& is_file( $plugin_dir . '/inc/class-yby-updater.php' );
	}

	private static function valid_record( $record ) {
		if ( ! is_array( $record ) || empty( $record['path'] ) || empty( $record['version'] ) ) {
			return false;
		}
		if ( YBY_Update_Verifier::DATABASE_VERSION !== (string) ( $record['database_version'] ?? '' ) ) {
			return false;
		}
		if ( ! self::is_direct_backup_child( $record['path'] ) || ! is_dir( $record['path'] ) || is_link( $record['path'] ) ) {
			return false;
		}
		$version = YBY_Update_Verifier::stable_version( $record['version'] );
		return '' !== $version && self::read_version( $record['path'] ) === $version && self::health( $record['path'], $version );
	}

	private static function is_direct_backup_child( $path ) {
		$root = realpath( self::root() );
		$path = realpath( $path );
		if ( ! $root || ! $path || is_link( $path ) || realpath( dirname( $path ) ) !== $root ) {
			return false;
		}
		return 1 === preg_match( '/^yby-core-[0-9]+\.[0-9]+\.[0-9]+-[0-9]{14}-[A-Za-z0-9]+$/', basename( $path ) );
	}

	private static function read_version( $dir ) {
		$main = untrailingslashit( $dir ) . '/yby-core.php';
		if ( is_link( $main ) ) {
			return '';
		}
		$source = @file_get_contents( $main );
		return $source && preg_match( '/^\s*\*\s*Version:\s*([^\r\n]+)$/mi', $source, $match ) ? trim( $match[1] ) : '';
	}

	private static function copy_tree( $from, $to ) {
		$from = untrailingslashit( $from );
		$to   = untrailingslashit( $to );
		if ( ! is_dir( $from ) || is_link( $from ) || ! wp_mkdir_p( $to ) || is_link( $to ) ) {
			return false;
		}
		$items = scandir( $from );
		if ( false === $items ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$source      = $from . '/' . $item;
			$destination = $to . '/' . $item;
			if ( is_link( $source ) ) {
				return false;
			}
			if ( is_dir( $source ) ) {
				if ( ! self::copy_tree( $source, $destination ) ) {
					return false;
				}
			} elseif ( ! is_file( $source ) || false === copy( $source, $destination ) ) {
				return false;
			}
		}
		return true;
	}

	private static function remove_tree( $dir ) {
		if ( ! is_dir( $dir ) || is_link( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( false === $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_link( $path ) || is_file( $path ) ) {
				@unlink( $path );
			} elseif ( is_dir( $path ) ) {
				self::remove_tree( $path );
			}
		}
		@rmdir( $dir );
	}

	private static function prune() {
		$items = glob( trailingslashit( self::root() ) . 'yby-core-*', GLOB_ONLYDIR );
		if ( ! is_array( $items ) ) {
			return;
		}
		$items = array_values( array_filter( $items, array( __CLASS__, 'is_direct_backup_child' ) ) );
		rsort( $items, SORT_STRING );
		foreach ( array_slice( $items, self::RETAIN ) as $item ) {
			self::remove_tree( $item );
		}
	}
}
