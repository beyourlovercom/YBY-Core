<?php
/**
 * Email OS template administration.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_Template_Admin {

	const SECTION_OVERVIEW = 'overview';
	const SECTION_BRAND = 'brand';
	const SECTION_WOOCOMMERCE = 'woocommerce';
	const SECTION_WORDPRESS = 'wordpress';
	const SECTION_ANDY_CORE = 'andy-core';
	const SECTION_RELEASE = 'release-test';
	const OWNER_NOTES_OPTION = 'yby_email_template_owner_notes';

	public static function sections() {
		return array(
			self::SECTION_OVERVIEW    => '概览',
			self::SECTION_BRAND       => '品牌样式',
			self::SECTION_WOOCOMMERCE => 'WooCommerce',
			self::SECTION_WORDPRESS   => 'WordPress',
			self::SECTION_ANDY_CORE   => 'Andy Core',
			self::SECTION_RELEASE     => '发布与测试',
		);
	}

	public function current_section() {
		$section = isset( $_GET['email_section'] ) ? sanitize_key( wp_unslash( $_GET['email_section'] ) ) : self::SECTION_OVERVIEW;
		return array_key_exists( $section, self::sections() ) ? $section : self::SECTION_OVERVIEW;
	}

	public function render() {
		$registry = new YBY_Email_Template_Registry();
		$templates = $registry->discover();
		$design = YBY_Email_Design_Settings::get();
		$section = $this->current_section();
		$sections = self::sections();
		$store = new YBY_Email_Template_Store();
		$renderer = new YBY_Email_Template_Renderer();
		$health_center = new YBY_Email_Health_Center();
		$native_notice = array();
		$test_notice = array();
		$registry_notice = array();

		if ( isset( $_POST['yby_email_registry_note_action'] ) ) {
			$registry_notice = $this->handle_registry_note_action( $templates );
		}

		if ( isset( $_POST['yby_email_template_action'] ) ) {
			$native_notice = $this->handle_native_action( $templates, $store, $renderer );
		}

		if ( isset( $_POST['yby_email_test_action'] ) ) {
			$test_notice = $this->handle_test_action( $templates, $store, $renderer, $health_center );
		}

		$native_provider = $this->native_provider_for_section( $section );
		$native_templates = array();
		foreach ( $templates as $key => $identity ) {
			if ( $native_provider && $native_provider === ( $identity['provider'] ?? '' ) ) {
				$native_templates[ $key ] = $identity;
			}
		}
		$selected_key = isset( $_REQUEST['template_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template_key'] ) ) : '';
		if ( ! isset( $native_templates[ $selected_key ] ) ) {
			$selected_key = $native_templates ? (string) array_key_first( $native_templates ) : '';
		}

		$selected_identity = $selected_key && isset( $native_templates[ $selected_key ] ) ? $native_templates[ $selected_key ] : array();
		$selected_state = $selected_identity ? $store->get_state( $selected_identity ) : array();
		$preview = array();
		if ( $selected_identity && ! empty( $selected_state['payload'] ) ) {
			$preview = $renderer->render(
				$selected_state['payload'],
				(array) ( $selected_identity['sample_context'] ?? array() ),
				$design
			);
		}

		$owner_notes = $this->owner_notes();
		$transport_health = $health_center->transport_status();
		$native_health = $health_center->native_health( $templates, $store );
		$last_test = $health_center->get_last_test();
		$current_user = wp_get_current_user();
		$test_recipient_default = is_object( $current_user ) ? sanitize_email( (string) $current_user->user_email ) : '';

		include YBY_CORE_PLUGIN_DIR . 'admin/views/email-template-settings.php';
	}

	public function owner_notes() {
		$notes = get_option( self::OWNER_NOTES_OPTION, array() );
		return is_array( $notes ) ? $notes : array();
	}

	public function template_editor_url( $identity, $base_url ) {
		$provider = (string) ( $identity['provider'] ?? '' );
		if ( 'woocommerce' === $provider ) {
			return (string) ( $identity['editor_url'] ?: ( $identity['settings_url'] ?? '' ) );
		}
		$section = 'wordpress' === $provider ? self::SECTION_WORDPRESS : ( 'andy_core' === $provider ? self::SECTION_ANDY_CORE : '' );
		if ( '' === $section ) {
			return '';
		}
		return add_query_arg( array( 'email_section' => $section, 'template_key' => (string) ( $identity['template_key'] ?? '' ) ), $base_url );
	}

	protected function handle_registry_note_action( $templates ) {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			return array( 'type' => 'error', 'message' => 'Permission denied.' );
		}
		check_admin_referer( 'yby_email_registry_note', 'yby_email_registry_note_nonce' );
		$template_key = isset( $_POST['registry_template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['registry_template_key'] ) ) : '';
		if ( ! isset( $templates[ $template_key ] ) ) {
			return array( 'type' => 'error', 'message' => 'Invalid template.' );
		}
		$note = isset( $_POST['registry_note'] ) ? sanitize_text_field( wp_unslash( $_POST['registry_note'] ) ) : '';
		$notes = $this->owner_notes();
		if ( '' === $note ) {
			unset( $notes[ $template_key ] );
		} else {
			$notes[ $template_key ] = $note;
		}
		update_option( self::OWNER_NOTES_OPTION, $notes, false );
		return array( 'type' => 'success', 'message' => '备注已保存。' );
	}


	protected function handle_test_action( $templates, $store, $renderer, $health_center ) {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			return array( 'success' => false, 'message' => 'Permission denied.', 'error_code' => 'permission_denied' );
		}
		check_admin_referer( 'yby_email_test_send', 'yby_email_test_nonce' );
		$template_key = isset( $_POST['test_template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['test_template_key'] ) ) : '';
		$recipient = isset( $_POST['test_recipient'] ) ? sanitize_email( wp_unslash( $_POST['test_recipient'] ) ) : '';
		$identity = isset( $templates[ $template_key ] ) ? $templates[ $template_key ] : array();
		return $health_center->send_test( $identity, $recipient, $store, $renderer );
	}

	protected function native_provider_for_section( $section ) {		if ( self::SECTION_WORDPRESS === $section ) { return 'wordpress'; }
		if ( self::SECTION_ANDY_CORE === $section ) { return 'andy_core'; }
		return '';
	}

	protected function handle_native_action( $templates, $store, $renderer ) {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			return array( 'type' => 'error', 'message' => 'Permission denied.' );
		}

		check_admin_referer( 'yby_email_template_edit', 'yby_email_template_nonce' );
		$template_key = isset( $_POST['template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['template_key'] ) ) : '';
		$identity = isset( $templates[ $template_key ] ) ? $templates[ $template_key ] : array();
		if ( ! $identity || ! YBY_Email_Template_Store::is_native_provider( $identity['provider'] ?? '' ) ) {
			return array( 'type' => 'error', 'message' => 'Invalid native template.' );
		}

		$payload = isset( $_POST['payload'] ) && is_array( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
		$current_state = $store->get_state( $identity );
		$payload = wp_parse_args( $payload, (array) ( $current_state['payload'] ?? ( $identity['default_payload'] ?? array() ) ) );
		$action = sanitize_key( wp_unslash( $_POST['yby_email_template_action'] ) );
		if ( 'publish' === $action ) {
			$result = $store->publish( $identity, $payload, $renderer, get_current_user_id() );
			if ( empty( $result['success'] ) ) {
				$details = ! empty( $result['diagnostics'] ) ? ' ' . implode( ', ', (array) $result['diagnostics'] ) : '';
				return array( 'type' => 'error', 'message' => 'Publish failed.' . $details );
			}
			return array( 'type' => 'success', 'message' => 'Published as immutable version v' . absint( $result['version_number'] ) . '.' );
		}

		$result = $store->save_draft( $identity, $payload );
		if ( empty( $result['success'] ) ) {
			return array( 'type' => 'error', 'message' => 'Draft save failed.' );
		}

		return array( 'type' => 'success', 'message' => 'Draft saved. Published snapshot, if any, was not changed.' );
	}
}
