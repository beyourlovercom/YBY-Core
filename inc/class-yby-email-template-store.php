<?php
/**
 * Email OS native-template persistence.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_Template_Store {

	public static function is_native_provider( $provider ) {
		return in_array( (string) $provider, array( 'wordpress', 'andy_core' ), true );
	}

	public function get_state( $identity ) {
		if ( ! is_array( $identity ) || ! self::is_native_provider( $identity['provider'] ?? '' ) ) {
			return array();
		}

		$row = $this->find_row( (string) $identity['template_key'] );
		$payload = $row ? json_decode( (string) $row['working_payload'], true ) : array();
		$payload = wp_parse_args( is_array( $payload ) ? $payload : array(), (array) ( $identity['default_payload'] ?? array() ) );
		$version = $row && ! empty( $row['published_version_id'] ) ? $this->find_version( (int) $row['published_version_id'] ) : array();

		return array(
			'id'                => $row ? (int) $row['id'] : 0,
			'status'            => $row ? (string) $row['status'] : YBY_Email_Template_Schema::STATUS_DRAFT,
			'payload'           => $this->sanitize_payload( $payload ),
			'published_version' => $version ? (int) $version['version_number'] : 0,
			'published_hash'    => $version ? (string) $version['content_hash_sha256'] : '',
			'published_at'      => $version ? (string) $version['published_at'] : '',
		);
	}

	public function save_draft( $identity, $payload ) {
		if ( ! $this->valid_identity( $identity ) ) {
			return array( 'success' => false, 'error' => 'invalid_native_identity' );
		}

		$payload = $this->sanitize_payload( $payload );
		$row = $this->find_row( (string) $identity['template_key'] );
		$now = current_time( 'mysql' );
		$data = array(
			'provider'        => (string) $identity['provider'],
			'label'           => sanitize_text_field( (string) $identity['label'] ),
			'working_payload' => wp_json_encode( $payload ),
			'updated_at'      => $now,
		);

		global $wpdb;
		$table = YBY_Database::email_templates_table_name();
		if ( $row ) {
			if ( YBY_Email_Template_Schema::STATUS_PUBLISHED !== (string) $row['status'] ) {
				$data['status'] = YBY_Email_Template_Schema::STATUS_DRAFT;
			}
			$ok = false !== $wpdb->update( $table, $data, array( 'id' => (int) $row['id'] ) );
			$template_id = (int) $row['id'];
		} else {
			$data['template_key'] = (string) $identity['template_key'];
			$data['status'] = YBY_Email_Template_Schema::STATUS_DRAFT;
			$data['created_at'] = $now;
			$ok = false !== $wpdb->insert( $table, $data );
			$template_id = $ok ? (int) $wpdb->insert_id : 0;
		}

		return array( 'success' => $ok, 'template_id' => $template_id, 'payload' => $payload );
	}

	public function publish( $identity, $payload, $renderer, $published_by = 0 ) {
		$saved = $this->save_draft( $identity, $payload );
		if ( empty( $saved['success'] ) ) {
			return $saved;
		}

		$context = is_array( $identity['sample_context'] ?? null ) ? $identity['sample_context'] : array();
		$rendered = $renderer->render( $saved['payload'], $context, YBY_Email_Design_Settings::get() );
		if ( empty( $rendered['valid'] ) ) {
			return array( 'success' => false, 'error' => 'render_validation_failed', 'diagnostics' => $rendered['diagnostics'] );
		}

		global $wpdb;
		$template_id = (int) $saved['template_id'];
		$versions = YBY_Database::email_template_versions_table_name();
		$templates = YBY_Database::email_templates_table_name();
		$version_number = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(version_number),0) FROM {$versions} WHERE template_id = %d", $template_id ) );
		$snapshot = wp_json_encode( $saved['payload'] );
		$now = current_time( 'mysql' );
		$inserted = $wpdb->insert( $versions, array(
			'template_id'        => $template_id,
			'version_number'     => $version_number,
			'content_hash_sha256'=> hash( 'sha256', $snapshot ),
			'payload_snapshot'   => $snapshot,
			'published_by'       => absint( $published_by ),
			'published_at'       => $now,
		) );
		if ( false === $inserted ) {
			return array( 'success' => false, 'error' => 'version_insert_failed' );
		}

		$version_id = (int) $wpdb->insert_id;
		$updated = $wpdb->update( $templates, array(
			'status'               => YBY_Email_Template_Schema::STATUS_PUBLISHED,
			'published_version_id' => $version_id,
			'updated_at'           => $now,
		), array( 'id' => $template_id ) );

		return array(
			'success' => false !== $updated,
			'version_id' => $version_id,
			'version_number' => $version_number,
			'content_hash_sha256' => hash( 'sha256', $snapshot ),
		);
	}

	protected function valid_identity( $identity ) {
		return is_array( $identity )
			&& self::is_native_provider( $identity['provider'] ?? '' )
			&& '' !== (string) ( $identity['template_key'] ?? '' )
			&& '' !== (string) ( $identity['label'] ?? '' );
	}

	protected function find_row( $template_key ) {
		global $wpdb;
		$table = YBY_Database::email_templates_table_name();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE template_key = %s LIMIT 1", $template_key ), ARRAY_A );
		return is_array( $row ) ? $row : array();
	}

	protected function find_version( $version_id ) {
		global $wpdb;
		$table = YBY_Database::email_template_versions_table_name();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $version_id ), ARRAY_A );
		return is_array( $row ) ? $row : array();
	}

	public function sanitize_payload( $payload ) {
		$payload = wp_parse_args( is_array( $payload ) ? $payload : array(), YBY_Email_Template_Schema::payload_defaults() );
		$clean = YBY_Email_Template_Schema::payload_defaults();
		foreach ( array( 'subject', 'preheader', 'heading' ) as $key ) {
			$clean[ $key ] = sanitize_text_field( (string) $payload[ $key ] );
		}
		foreach ( array( 'intro_copy', 'secondary_copy', 'additional_content' ) as $key ) {
			$clean[ $key ] = sanitize_textarea_field( (string) $payload[ $key ] );
		}
		$cta = is_array( $payload['primary_cta'] ) ? $payload['primary_cta'] : array();
		$clean['primary_cta'] = array(
			'label' => sanitize_text_field( (string) ( $cta['label'] ?? '' ) ),
			'url'   => sanitize_text_field( (string) ( $cta['url'] ?? '' ) ),
		);
		$clean['dynamic_sections'] = array();
		foreach ( (array) $payload['dynamic_sections'] as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$clean['dynamic_sections'][] = array(
				'label' => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'value' => sanitize_text_field( (string) ( $row['value'] ?? '' ) ),
			);
		}
		$clean['enabled_blocks'] = array_values( array_unique( array_map( 'sanitize_key', (array) $payload['enabled_blocks'] ) ) );
		$clean['template_specific_settings'] = array();
		foreach ( (array) $payload['template_specific_settings'] as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$clean['template_specific_settings'][ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
			}
		}
		return $clean;
	}
}
