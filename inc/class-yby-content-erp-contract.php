<?php
/**
 * ERP Content publishing contract.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates and applies the bounded ERP -> WordPress content publishing contract.
 */
class YBY_Content_ERP_Contract {
	const VERSION = 'content-publish-v1';

	const MAX_HTML_BYTES = 524288;

	/**
	 * Validate one preview/publish request.
	 *
	 * @param mixed  $body           Decoded request body.
	 * @param string $connection_key Authenticated Connector connection key.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function validate( $body, $connection_key ) {
		$required = array(
			'erp_article_id',
			'layout_snapshot_id',
			'preview_hash',
			'target_site',
			'content_type',
			'title',
			'html',
			'canonical_path',
			'seo',
			'requested_status',
		);

		if ( ! is_array( $body ) || count( $body ) !== count( $required ) || array_diff( $required, array_keys( $body ) ) || array_diff( array_keys( $body ), $required ) ) {
			return self::validation_error();
		}

		foreach ( array( 'erp_article_id', 'layout_snapshot_id' ) as $key ) {
			if ( ! ( is_int( $body[ $key ] ) || ( is_string( $body[ $key ] ) && ctype_digit( $body[ $key ] ) ) ) || (int) $body[ $key ] < 1 ) {
				return self::validation_error();
			}
		}

		if ( ! is_string( $body['preview_hash'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $body['preview_hash'] ) ) {
			return self::validation_error();
		}

		$target_site = strtolower( trim( (string) $body['target_site'] ) );
		if ( '' === $target_site || strlen( $target_site ) > 191 || ! preg_match( '/^[a-z0-9.-]+$/', $target_site ) ) {
			return self::validation_error();
		}

		if ( ! self::target_site_matches( $target_site ) ) {
			return new WP_Error(
				'TARGET_SITE_MISMATCH',
				'The requested target site does not match this WordPress site.',
				array( 'status' => 409, 'retryable' => false )
			);
		}

		$content_type = (string) $body['content_type'];
		if ( ! in_array( $content_type, array( 'blog', 'guide', 'comparison' ), true ) ) {
			return new WP_Error(
				'CONTENT_TYPE_UNSUPPORTED',
				'This Connector endpoint publishes article-like content only.',
				array( 'status' => 400, 'retryable' => false )
			);
		}

		if ( ! is_string( $body['title'] ) ) {
			return self::validation_error();
		}
		$title = trim( wp_strip_all_tags( $body['title'] ) );
		if ( '' === $title || strlen( $title ) > 200 ) {
			return self::validation_error();
		}

		if ( ! is_string( $body['html'] ) || '' === trim( $body['html'] ) || strlen( $body['html'] ) > self::MAX_HTML_BYTES ) {
			return self::validation_error();
		}
		$html = wp_kses_post( $body['html'] );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return self::validation_error();
		}

		$canonical_path = self::canonical_path( $body['canonical_path'] );
		if ( is_wp_error( $canonical_path ) ) {
			return $canonical_path;
		}

		$seo = self::seo( $body['seo'] );
		if ( is_wp_error( $seo ) ) {
			return $seo;
		}

		$requested_status = (string) $body['requested_status'];
		if ( ! in_array( $requested_status, array( 'draft', 'publish' ), true ) ) {
			return self::validation_error();
		}

		$expected_indexing = 'draft' === $requested_status ? 'noindex' : 'index';
		if ( $expected_indexing !== $seo['indexing'] ) {
			return new WP_Error(
				'INDEXING_STATUS_MISMATCH',
				'Draft content must request noindex and published content must request index.',
				array( 'status' => 409, 'retryable' => false )
			);
		}

		return array(
			'connection_key'    => (string) $connection_key,
			'erp_article_id'    => (int) $body['erp_article_id'],
			'layout_snapshot_id'=> (int) $body['layout_snapshot_id'],
			'preview_hash'      => $body['preview_hash'],
			'target_site'       => $target_site,
			'content_type'      => $content_type,
			'post_type'         => 'post',
			'title'             => $title,
			'html'              => $html,
			'canonical_path'    => $canonical_path,
			'seo'               => $seo,
			'requested_status'  => $requested_status,
		);
	}

	/**
	 * Produce a no-write provider preview.
	 *
	 * @param array<string,mixed> $input Validated input.
	 * @return array<string,mixed>
	 */
	public static function preview( $input ) {
		return array(
			'resource_contract_version' => self::VERSION,
			'write_performed'           => false,
			'post_type'                 => 'post',
			'requested_status'          => $input['requested_status'],
			'title'                     => $input['title'],
			'canonical_path'            => $input['canonical_path'],
			'target_site'               => $input['target_site'],
			'content_type'              => $input['content_type'],
			'preview_hash'              => $input['preview_hash'],
			'layout_snapshot_id'        => $input['layout_snapshot_id'],
			'seo'                       => $input['seo'],
		);
	}

	/**
	 * Create or update exactly one bound WordPress post.
	 *
	 * @param array<string,mixed> $input Validated input.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function publish( $input ) {
		if ( ! function_exists( 'wp_insert_post' ) || ! function_exists( 'wp_update_post' ) || ! function_exists( 'get_post' ) || ! function_exists( 'update_post_meta' ) || ! function_exists( 'get_post_meta' ) || ! function_exists( 'delete_post_meta' ) ) {
			return self::provider_failure( 'PROVIDER_UNAVAILABLE', 'WordPress post provider is unavailable.' );
		}

		$existing = self::find_bound_post_ids( $input['connection_key'], $input['erp_article_id'] );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$postarr = array(
			'post_type'    => 'post',
			'post_status'  => $input['requested_status'],
			'post_title'   => $input['title'],
			'post_content' => $input['html'],
		);

		$slug = self::canonical_slug( $input['canonical_path'] );
		if ( '' !== $slug ) {
			$postarr['post_name'] = $slug;
		}

		$created = false;
		if ( 1 === count( $existing ) ) {
			$postarr['ID'] = (int) $existing[0];
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
			$created = true;
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return self::provider_failure( 'PROVIDER_WRITE_FAILED', 'WordPress post write failed.' );
		}

		$post_id = (int) $post_id;
		$meta_result = self::write_meta( $post_id, $input );
		if ( is_wp_error( $meta_result ) ) {
			if ( $created && function_exists( 'wp_delete_post' ) ) {
				wp_delete_post( $post_id, true );
			}
			return $meta_result;
		}

		$post = get_post( $post_id );
		if (
			! is_object( $post )
			|| (int) ( $post->ID ?? 0 ) !== $post_id
			|| 'post' !== (string) ( $post->post_type ?? '' )
			|| $input['requested_status'] !== (string) ( $post->post_status ?? '' )
			|| $input['title'] !== (string) ( $post->post_title ?? '' )
			|| $input['html'] !== (string) ( $post->post_content ?? '' )
		) {
			return self::provider_failure( 'PROVIDER_READBACK_FAILED', 'WordPress post read-back did not match.' );
		}

		$url = function_exists( 'get_permalink' ) ? get_permalink( $post_id ) : '';
		$url = is_string( $url ) ? $url : '';

		return array(
			'resource_contract_version' => self::VERSION,
			'provider'                  => 'wordpress',
			'post_id'                   => $post_id,
			'external_id'               => (string) $post_id,
			'status'                    => (string) $post->post_status,
			'url'                       => $url,
			'created'                   => $created,
			'layout_snapshot_id'        => $input['layout_snapshot_id'],
			'preview_hash'              => $input['preview_hash'],
			'canonical_path_expected'   => $input['canonical_path'],
			'canonical_path_match'      => self::permalink_matches( $url, $input['canonical_path'] ),
			'indexing'                  => (string) get_post_meta( $post_id, '_yby_content_indexing', true ),
		);
	}

	/**
	 * @return array<int,int>|WP_Error
	 */
	private static function find_bound_post_ids( $connection_key, $erp_article_id ) {
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 3,
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_yby_erp_connection_key',
						'value' => (string) $connection_key,
					),
					array(
						'key'   => '_yby_erp_article_id',
						'value' => (string) $erp_article_id,
					),
				),
			)
		);

		if ( ! is_array( $ids ) ) {
			return self::provider_failure( 'PROVIDER_READ_FAILED', 'WordPress post binding lookup failed.' );
		}

		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		$ids = array_values( array_filter( $ids ) );

		if ( count( $ids ) > 1 ) {
			return new WP_Error(
				'CONTENT_BINDING_CONFLICT',
				'Multiple WordPress posts are bound to this ERP article.',
				array( 'status' => 409, 'retryable' => false )
			);
		}

		return $ids;
	}

	/**
	 * @param int                 $post_id WordPress post ID.
	 * @param array<string,mixed> $input   Validated input.
	 * @return true|WP_Error
	 */
	private static function write_meta( $post_id, $input ) {
		$meta = array(
			'_yby_erp_connection_key'       => $input['connection_key'],
			'_yby_erp_article_id'           => (string) $input['erp_article_id'],
			'_yby_erp_layout_snapshot_id'   => (string) $input['layout_snapshot_id'],
			'_yby_erp_preview_hash'         => $input['preview_hash'],
			'_yby_content_contract_version' => self::VERSION,
			'_yby_content_type'             => $input['content_type'],
			'_yby_primary_keyword'          => $input['seo']['primary_keyword'],
			'_yby_search_intent'            => $input['seo']['search_intent'],
			'_yby_content_indexing'         => $input['seo']['indexing'],
		);

		foreach ( $meta as $key => $value ) {
			$expected = null === $value ? '' : (string) $value;
			update_post_meta( $post_id, $key, $expected );
			if ( $expected !== (string) get_post_meta( $post_id, $key, true ) ) {
				return self::provider_failure( 'PROVIDER_SYNC_FAILED', 'WordPress post binding metadata read-back did not match.' );
			}
		}

		if ( 'noindex' === $input['seo']['indexing'] ) {
			update_post_meta( $post_id, 'rank_math_robots', array( 'noindex', 'follow' ) );
			$robots = get_post_meta( $post_id, 'rank_math_robots', true );
			if ( ! is_array( $robots ) || ! in_array( 'noindex', $robots, true ) ) {
				return self::provider_failure( 'PROVIDER_SYNC_FAILED', 'WordPress noindex read-back did not match.' );
			}
		} else {
			delete_post_meta( $post_id, 'rank_math_robots' );
			$robots = get_post_meta( $post_id, 'rank_math_robots', true );
			if ( is_array( $robots ) && in_array( 'noindex', $robots, true ) ) {
				return self::provider_failure( 'PROVIDER_SYNC_FAILED', 'WordPress index read-back still contains a noindex override.' );
			}
		}

		return true;
	}

	/**
	 * @param mixed $value Canonical path input.
	 * @return string|WP_Error
	 */
	private static function canonical_path( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}
		if ( ! is_string( $value ) || strlen( $value ) > 500 || '/' !== substr( $value, 0, 1 ) || false !== strpos( $value, '://' ) || false !== strpos( $value, '?' ) || false !== strpos( $value, '#' ) || preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
			return self::validation_error();
		}

		$path = '/' . ltrim( preg_replace( '#/+#', '/', trim( $value ) ), '/' );
		return '/' === $path ? '' : rtrim( $path, '/' ) . '/';
	}

	/**
	 * @param mixed $value SEO input.
	 * @return array<string,string|null>|WP_Error
	 */
	private static function seo( $value ) {
		$keys = array( 'primary_keyword', 'search_intent', 'indexing' );
		if ( ! is_array( $value ) || count( $value ) !== count( $keys ) || array_diff( $keys, array_keys( $value ) ) || array_diff( array_keys( $value ), $keys ) ) {
			return self::validation_error();
		}

		$result = array();
		foreach ( array( 'primary_keyword' => 191, 'search_intent' => 64 ) as $key => $max ) {
			if ( null === $value[ $key ] || '' === $value[ $key ] ) {
				$result[ $key ] = null;
				continue;
			}
			if ( ! is_string( $value[ $key ] ) ) {
				return self::validation_error();
			}
			$clean = trim( wp_strip_all_tags( $value[ $key ] ) );
			if ( '' === $clean || strlen( $clean ) > $max ) {
				return self::validation_error();
			}
			$result[ $key ] = $clean;
		}

		if ( ! is_string( $value['indexing'] ) || ! in_array( $value['indexing'], array( 'noindex', 'index' ), true ) ) {
			return self::validation_error();
		}
		$result['indexing'] = $value['indexing'];

		return $result;
	}

	private static function target_site_matches( $target_site ) {
		$host = function_exists( 'wp_parse_url' ) ? wp_parse_url( home_url( '/' ), PHP_URL_HOST ) : parse_url( home_url( '/' ), PHP_URL_HOST );
		return is_string( $host ) && strtolower( $host ) === strtolower( (string) $target_site );
	}

	private static function canonical_slug( $path ) {
		if ( '' === $path ) {
			return '';
		}
		$trimmed = trim( (string) $path, '/' );
		$parts   = explode( '/', $trimmed );
		$last    = (string) end( $parts );
		return function_exists( 'sanitize_title' ) ? sanitize_title( $last ) : preg_replace( '/[^a-z0-9-]/', '-', strtolower( $last ) );
	}

	private static function permalink_matches( $url, $canonical_path ) {
		if ( '' === $canonical_path || '' === $url ) {
			return null;
		}
		$path = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url, PHP_URL_PATH ) : parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return false;
		}
		return rtrim( $path, '/' ) . '/' === rtrim( $canonical_path, '/' ) . '/';
	}

	private static function validation_error() {
		return new WP_Error(
			'VALIDATION_FAILED',
			'Content publishing request syntax or values are invalid.',
			array( 'status' => 400, 'retryable' => false )
		);
	}

	private static function provider_failure( $code, $message ) {
		return new WP_Error( $code, $message, array( 'status' => 503, 'retryable' => true ) );
	}
}
