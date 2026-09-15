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
		$native_notice = array();

		if ( isset( $_POST['yby_email_template_action'] ) ) {
			$native_notice = $this->handle_native_action( $templates, $store, $renderer );
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

		include YBY_CORE_PLUGIN_DIR . 'admin/views/email-template-settings.php';
	}

	protected function native_provider_for_section( $section ) {
		if ( self::SECTION_WORDPRESS === $section ) { return 'wordpress'; }
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
