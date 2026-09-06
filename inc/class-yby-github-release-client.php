<?php
/**
 * Secure allowlisted GitHub Releases client for Andy Core updates.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_GitHub_Release_Client {
	const API_HOST   = 'api.github.com';
	const ASSET_HOST = 'release-assets.githubusercontent.com';
	const OWNER      = 'beyourlovercom';
	const REPOSITORY = 'YBY-Core';

	/** @var string[] */
	private $asset_hosts = array(
		self::ASSET_HOST,
		'objects.githubusercontent.com',
	);

	public function token() {
		$token = defined( 'YBY_CORE_GITHUB_TOKEN' ) ? YBY_CORE_GITHUB_TOKEN : '';
		if ( ! is_string( $token ) || '' === trim( $token ) ) {
			$token = function_exists( 'apply_filters' ) ? apply_filters( 'yby_core_github_token', '' ) : '';
		}
		$token = is_string( $token ) ? trim( $token ) : '';
		return '' !== $token && strlen( $token ) <= 512 && ! preg_match( '/\s/', $token ) ? $token : '';
	}

	public function has_auth() {
		return '' !== $this->token();
	}

	public function latest_release( $force = false ) {
		if ( ! $this->has_auth() ) {
			return new WP_Error( 'yby_update_auth_missing', __( 'Andy Core updates are unavailable because GitHub authentication is not configured.', 'yby-core' ) );
		}
		$key = 'yby_core_github_latest_release';
		if ( ! $force ) {
			$cached = get_site_transient( $key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$url      = 'https://' . self::API_HOST . '/repos/' . self::OWNER . '/' . self::REPOSITORY . '/releases/latest';
		$response = $this->request_json( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		set_site_transient( $key, $response, 10 * MINUTE_IN_SECONDS );
		return $response;
	}

	public function request_json( $url ) {
		if ( ! $this->has_auth() ) {
			return new WP_Error( 'yby_update_auth_missing', __( 'Andy Core updates are unavailable because GitHub authentication is not configured.', 'yby-core' ) );
		}
		if ( ! $this->is_api_url( $url ) ) {
			return new WP_Error( 'yby_update_host_denied', __( 'The update API endpoint is not trusted.', 'yby-core' ) );
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 0,
				'headers'     => $this->api_headers( false ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'yby_update_api_failed', __( 'The GitHub update service could not be reached.', 'yby-core' ) );
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'yby_update_api_failed', __( 'The GitHub update service rejected the request.', 'yby-core' ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) ? $data : new WP_Error( 'yby_update_invalid_response', __( 'GitHub returned invalid update metadata.', 'yby-core' ) );
	}

	public function download_asset( $asset_url, $destination = '' ) {
		if ( ! $this->has_auth() || ! $this->is_api_asset_url( $asset_url ) ) {
			return new WP_Error( 'yby_update_download_denied', __( 'The update download endpoint is not trusted.', 'yby-core' ) );
		}

		$response = wp_safe_remote_get(
			$asset_url,
			array(
				'timeout'     => 45,
				'redirection' => 0,
				'headers'     => $this->api_headers( true ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'yby_update_download_failed', __( 'GitHub did not provide the update asset.', 'yby-core' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( ! $this->is_asset_redirect_url( $location ) ) {
				return new WP_Error( 'yby_update_redirect_denied', __( 'The update download redirect was not trusted.', 'yby-core' ) );
			}
			// Deliberately omit Authorization after leaving api.github.com.
			$response = wp_safe_remote_get(
				$location,
				array(
					'timeout'     => 90,
					'redirection' => 0,
					'headers'     => array(
						'Accept'     => 'application/octet-stream',
						'User-Agent' => 'Andy-Core/' . YBY_CORE_VERSION,
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'yby_update_download_failed', __( 'GitHub did not provide the update asset.', 'yby-core' ) );
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( $code >= 300 && $code < 400 ) {
				return new WP_Error( 'yby_update_redirect_denied', __( 'The update asset used an unexpected additional redirect.', 'yby-core' ) );
			}
		}

		if ( 200 !== $code ) {
			return new WP_Error( 'yby_update_download_failed', __( 'GitHub did not provide the update asset.', 'yby-core' ) );
		}

		$destination = $destination ? $destination : wp_tempnam( 'andy-core-update' );
		if ( ! $destination || false === file_put_contents( $destination, wp_remote_retrieve_body( $response ), LOCK_EX ) ) {
			return new WP_Error( 'yby_update_temp_failed', __( 'The update asset could not be stored safely.', 'yby-core' ) );
		}
		return $destination;
	}

	private function api_headers( $download ) {
		return array(
			'Accept'        => $download ? 'application/octet-stream' : 'application/vnd.github+json',
			'Authorization' => 'Bearer ' . $this->token(),
			'User-Agent'    => 'Andy-Core/' . YBY_CORE_VERSION,
			'X-GitHub-Api-Version' => '2022-11-28',
		);
	}

	private function strict_https_parts( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
			return false;
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ) {
			return false;
		}
		if ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) {
			return false;
		}
		return $parts;
	}

	public function is_api_url( $url ) {
		$parts = $this->strict_https_parts( $url );
		if ( ! $parts || self::API_HOST !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
			return false;
		}
		$path = (string) ( $parts['path'] ?? '' );
		$base = '/repos/' . self::OWNER . '/' . self::REPOSITORY . '/';
		return 0 === strpos( $path, $base ) && empty( $parts['query'] );
	}

	public function is_api_asset_url( $url ) {
		if ( ! $this->is_api_url( $url ) ) {
			return false;
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return 1 === preg_match( '!^/repos/' . preg_quote( self::OWNER, '!' ) . '/' . preg_quote( self::REPOSITORY, '!' ) . '/releases/assets/[0-9]+$!', $path );
	}

	public function is_asset_redirect_url( $url ) {
		$parts = $this->strict_https_parts( $url );
		if ( ! $parts ) {
			return false;
		}
		return in_array( strtolower( (string) ( $parts['host'] ?? '' ) ), $this->asset_hosts, true );
	}
}
