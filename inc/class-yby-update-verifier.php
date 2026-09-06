<?php
/**
 * Release evidence and package validation primitives.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Update_Verifier {
	const DATABASE_VERSION = '1.4.0';
	const SIGNING_PUBLIC_KEY_B64 = 'LcPq5x+fNa96aC+cXyC1ZbwRoGXCihF9YSG+iMHtjKU=';

	public static function stable_version( $version ) {
		$version = is_string( $version ) ? trim( $version ) : '';
		return 1 === preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $version ) ? $version : '';
	}

	public static function is_newer( $candidate, $current ) {
		$candidate = self::stable_version( $candidate );
		$current   = self::stable_version( $current );
		return '' !== $candidate && '' !== $current && version_compare( $candidate, $current, '>' );
	}

	public static function release_version( $release ) {
		if ( ! is_array( $release ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
			return '';
		}
		$tag = isset( $release['tag_name'] ) ? trim( (string) $release['tag_name'] ) : '';
		return 1 === preg_match( '/^v([0-9]+\.[0-9]+\.[0-9]+)$/', $tag, $match ) ? self::stable_version( $match[1] ) : '';
	}

	public static function select_asset( $release, $version ) {
		if ( self::release_version( $release ) !== $version || ! isset( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			return false;
		}

		$package_name = 'andy-core-v' . $version . '.zip';
		$required     = array( $package_name, 'SHA256.txt', 'update-metadata.json', 'update-metadata.sig' );
		$selected     = array_fill_keys( $required, false );
		$seen         = array_fill_keys( $required, 0 );

		foreach ( $release['assets'] as $asset ) {
			if ( ! is_array( $asset ) || ! isset( $asset['name'], $asset['url'] ) ) {
				continue;
			}
			$name = (string) $asset['name'];
			if ( ! array_key_exists( $name, $selected ) ) {
				continue;
			}
			$seen[ $name ]++;
			$selected[ $name ] = $asset;
		}

		foreach ( $required as $name ) {
			if ( 1 !== $seen[ $name ] || ! is_array( $selected[ $name ] ) ) {
				return false;
			}
		}

		$digest = isset( $selected[ $package_name ]['digest'] ) ? strtolower( trim( (string) $selected[ $package_name ]['digest'] ) ) : '';
		if ( '' !== $digest && 1 !== preg_match( '/^sha256:[a-f0-9]{64}$/', $digest ) ) {
			return false;
		}

		return array(
			'package'  => $selected[ $package_name ],
			'checksum' => $selected['SHA256.txt'],
			'metadata'  => $selected['update-metadata.json'],
			'signature' => $selected['update-metadata.sig'],
		);
	}

	public static function checksum( $contents, $package_name ) {
		$found = '';
		$lines = preg_split( '/\R/', trim( (string) $contents ) );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( ! preg_match( '/^([a-f0-9]{64})\s+\*?([^\s]+)$/i', $line, $match ) || basename( $match[2] ) !== $package_name || '' !== $found ) {
				return '';
			}
			$found = strtolower( $match[1] );
		}
		return 64 === strlen( $found ) ? $found : '';
	}


	public static function signature( $metadata_contents, $signature_contents, $public_key_b64 = '' ) {
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) { return false; }
		$public_key_b64 = '' !== trim( (string) $public_key_b64 ) ? trim( (string) $public_key_b64 ) : self::SIGNING_PUBLIC_KEY_B64;
		$public_key = base64_decode( $public_key_b64, true );
		$signature  = base64_decode( trim( (string) $signature_contents ), true );
		if ( false === $public_key || false === $signature || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $public_key ) || SODIUM_CRYPTO_SIGN_BYTES !== strlen( $signature ) ) { return false; }
		return sodium_crypto_sign_verify_detached( $signature, (string) $metadata_contents, $public_key );
	}

	public static function metadata( $contents, $version, $package_name, $sha256 ) {
		$data = json_decode( (string) $contents, true );
		if ( ! is_array( $data ) || 1 !== (int) ( $data['schema_version'] ?? 0 ) ) {
			return false;
		}
		$metadata_sha = strtolower( trim( (string) ( $data['sha256'] ?? '' ) ) );
		return self::stable_version( $data['version'] ?? '' ) === $version
			&& self::DATABASE_VERSION === (string) ( $data['database_version'] ?? '' )
			&& $package_name === (string) ( $data['package'] ?? '' )
			&& 1 === preg_match( '/^[a-f0-9]{64}$/', $metadata_sha )
			&& hash_equals( strtolower( $sha256 ), $metadata_sha );
	}

	public static function validate_zip( $zip_path, $version ) {
		if ( ! class_exists( 'ZipArchive' ) || ! is_file( $zip_path ) ) {
			return new WP_Error( 'yby_update_package_invalid', __( 'The update package is not a valid ZIP.', 'yby-core' ) );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new WP_Error( 'yby_update_package_invalid', __( 'The update package could not be opened.', 'yby-core' ) );
		}

		$required  = array(
			'yby-core/yby-core.php',
			'yby-core/inc/class-yby-core.php',
			'yby-core/inc/class-yby-loader.php',
			'yby-core/admin/class-yby-admin.php',
		);
		$forbidden = array( '.git', '.github', 'tests', 'docs', 'releases', 'scripts' );
		$found     = array();

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( false === $name || '' === $name || false !== strpos( $name, "\0" ) || false !== strpos( $name, '\\' ) || 0 === strpos( $name, '/' ) ) {
				$zip->close();
				return new WP_Error( 'yby_update_package_invalid', __( 'The update package contains unsafe paths.', 'yby-core' ) );
			}
			$trimmed  = rtrim( $name, '/' );
			$segments = explode( '/', $trimmed );
			if ( empty( $segments ) || 'yby-core' !== $segments[0] ) {
				$zip->close();
				return new WP_Error( 'yby_update_package_invalid', __( 'The update package has an unexpected root directory.', 'yby-core' ) );
			}
			foreach ( $segments as $segment ) {
				if ( '' === $segment || '.' === $segment || '..' === $segment ) {
					$zip->close();
					return new WP_Error( 'yby_update_package_invalid', __( 'The update package contains unsafe paths.', 'yby-core' ) );
				}
			}
			foreach ( array_slice( $segments, 1 ) as $segment ) {
				if ( in_array( $segment, $forbidden, true ) ) {
					$zip->close();
					return new WP_Error( 'yby_update_package_invalid', __( 'The update package contains development-only paths.', 'yby-core' ) );
				}
			}
			if ( isset( $found[ $name ] ) ) {
				$zip->close();
				return new WP_Error( 'yby_update_package_invalid', __( 'The update package contains duplicate paths.', 'yby-core' ) );
			}
			$found[ $name ] = true;

			$opsys = 0;
			$attr  = 0;
			if ( method_exists( $zip, 'getExternalAttributesIndex' ) && $zip->getExternalAttributesIndex( $i, $opsys, $attr ) ) {
				$mode = ( $attr >> 16 ) & 0170000;
				if ( 0120000 === $mode ) {
					$zip->close();
					return new WP_Error( 'yby_update_package_invalid', __( 'The update package contains symbolic links.', 'yby-core' ) );
				}
			}
		}

		foreach ( $required as $file ) {
			if ( empty( $found[ $file ] ) ) {
				$zip->close();
				return new WP_Error( 'yby_update_package_incomplete', __( 'The update package is incomplete.', 'yby-core' ) );
			}
		}

		$main = $zip->getFromName( 'yby-core/yby-core.php' );
		$zip->close();
		if ( ! is_string( $main )
			|| ! preg_match( '/^\s*\*\s*Version:\s*' . preg_quote( $version, '/' ) . '\s*$/mi', $main )
			|| false === strpos( $main, "define( 'YBY_CORE_VERSION', '{$version}' );" )
			|| false === strpos( $main, "define( 'YBY_DATABASE_VERSION', '" . self::DATABASE_VERSION . "' );" ) ) {
			return new WP_Error( 'yby_update_package_invalid', __( 'The update package does not contain the expected Andy Core bootstrap.', 'yby-core' ) );
		}
		return true;
	}
}
