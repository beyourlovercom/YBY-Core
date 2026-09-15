<?php
/**
 * Email OS -> ERP published-template contract.
 *
 * P8A freezes the read-only projection only. P8B exposes it through the
 * existing authenticated Connector namespace.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_ERP_Contract {
	const VERSION = 'email-os-published-v1';

	public static function fields() {
		return array(
			'template_key',
			'provider',
			'label',
			'status',
			'runtime_enabled',
			'published_version',
			'content_hash_sha256',
			'published_at',
		);
	}

	public static function project_native_published( $identity, $snapshot ) {
		if ( ! is_array( $identity ) || ! is_array( $snapshot ) ) {
			return array();
		}

		$provider = sanitize_key( (string) ( $identity['provider'] ?? '' ) );
		if ( ! YBY_Email_Template_Store::is_native_provider( $provider ) ) {
			return array();
		}

		$template_key = (string) ( $identity['template_key'] ?? '' );
		$label = sanitize_text_field( (string) ( $identity['label'] ?? '' ) );
		$version = absint( $snapshot['version_number'] ?? 0 );
		$hash = strtolower( (string) ( $snapshot['content_hash'] ?? '' ) );
		$published_at = sanitize_text_field( (string) ( $snapshot['published_at'] ?? '' ) );

		if ( '' === $template_key || '' === $label || $version < 1 || ! preg_match( '/^[a-f0-9]{64}$/', $hash ) || '' === $published_at ) {
			return array();
		}

		return array(
			'template_key' => $template_key,
			'provider' => $provider,
			'label' => $label,
			'status' => 'published',
			'runtime_enabled' => ! empty( $identity['runtime_enabled'] ),
			'published_version' => $version,
			'content_hash_sha256' => $hash,
			'published_at' => $published_at,
		);
	}

	public static function is_read_only() {
		return true;
	}

	public static function forbidden_fields() {
		return array(
			'payload', 'working_payload', 'payload_snapshot',
			'default_payload', 'sample_context', 'variables',
			'subject', 'preheader', 'heading', 'intro_copy',
			'dynamic_sections', 'primary_cta', 'secondary_copy',
			'additional_content', 'enabled_blocks',
			'template_specific_settings', 'owner_note',
			'recipient', 'editor_url', 'preview_test_url',
			'smtp', 'api_key', 'oauth_secret', 'transport_secret',
		);
	}
}
