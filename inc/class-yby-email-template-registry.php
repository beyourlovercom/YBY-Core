<?php
/**
 * Email Template OS runtime registry.
 *
 * Source-only foundation for Andy Core v1.5.8. No hooks are registered here.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers email template identities from runtime providers.
 */
class YBY_Email_Template_Registry {

	/**
	 * Return every currently discoverable template identity.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function discover() {
		$items = array();

		foreach ( $this->discover_woocommerce() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		foreach ( $this->wordpress_templates() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		foreach ( $this->andy_core_templates() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Discover WooCommerce mail identities and their current editor ownership.
	 * Email OS is governance-only for WooCommerce; no runtime hooks are registered.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function discover_woocommerce() {
		if ( ! function_exists( 'WC' ) || ! WC() || ! is_object( WC()->mailer() ) ) {
			return array();
		}

		$emails = WC()->mailer()->get_emails();
		if ( ! is_array( $emails ) ) {
			return array();
		}

		$result = array();
		foreach ( $emails as $email ) {
			if ( ! is_object( $email ) ) {
				continue;
			}

			$id = isset( $email->id ) ? sanitize_key( (string) $email->id ) : '';
			if ( '' === $id ) {
				continue;
			}

			$is_customer = method_exists( $email, 'is_customer_email' ) ? (bool) $email->is_customer_email() : false;
			$recipient = $is_customer ? 'Customer' : ( method_exists( $email, 'get_recipient' ) ? (string) $email->get_recipient() : '' );
			$viwec_id = $this->find_viwec_template_id( $id );
			$woo_url = admin_url( 'admin.php?page=wc-settings&tab=email&section=' . rawurlencode( strtolower( $id ) ) );
			$editor_url = $viwec_id ? admin_url( 'post.php?post=' . $viwec_id . '&action=edit' ) : $woo_url;

			$result[] = $this->normalize_item( array(
				'template_key' => 'woocommerce:' . $id,
				'provider' => 'woocommerce',
				'source_id' => $id,
				'source_class' => get_class( $email ),
				'audience' => $is_customer ? 'customer' : 'admin',
				'label' => isset( $email->title ) ? (string) $email->title : $id,
				'description' => isset( $email->description ) ? (string) $email->description : '',
				'runtime_available' => true,
				'runtime_enabled' => method_exists( $email, 'is_enabled' ) ? (bool) $email->is_enabled() : true,
				'is_manual' => method_exists( $email, 'is_manual' ) ? (bool) $email->is_manual() : false,
				'recipient' => $recipient,

				'content_type' => method_exists( $email, 'get_content_type' ) ? (string) $email->get_content_type() : '',
				'current_editor' => $viwec_id ? 'villatheme' : 'woocommerce',
				'editor_label' => $viwec_id ? 'VillaTheme Customizer' : 'WooCommerce 原生',
				'editor_url' => $editor_url,
				'settings_url' => $woo_url,
				'preview_test_url' => $editor_url,
				'legacy_template_ref' => $viwec_id ? (string) $viwec_id : '',
				'capabilities' => array( 'subject', 'preheader', 'body', 'cta', 'dynamic_sections' ),
			) );
		}

		return $result;
	}

	/**
	 * Resolve the active VillaTheme template for one Woo email id.
	 *
	 * @param string $email_id Woo email id.
	 * @return int
	 */
	protected function find_viwec_template_id( $email_id ) {
		if ( ! post_type_exists( 'viwec_template' ) ) {
			return 0;
		}

		$posts = get_posts( array(
			'numberposts' => 1,
			'post_type' => 'viwec_template',
			'post_status' => 'publish',
			'meta_key' => 'viwec_settings_type',
			'meta_value' => sanitize_key( $email_id ),
			'fields' => 'ids',
		) );

		return empty( $posts ) ? 0 : absint( $posts[0] );
	}

	/**
	 * WordPress system-mail identities supported by V1 adapters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function wordpress_templates() {
		return array(
			$this->normalize_item( array(
				'template_key'      => 'wordpress:new_user',
				'provider'          => 'wordpress',
				'source_id'         => 'new_user',
				'source_class'      => '',
				'audience'          => 'customer',
				'label'             => 'New User / Account',
				'description'       => 'WordPress new-user notification adapter.',
				'runtime_available' => true,
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'preheader', 'body', 'cta' ),
				'variables'         => array( 'site_name', 'user_login', 'user_email', 'set_password_url', 'login_url' ),
				'sample_context'    => array( 'site_name' => 'Example Site', 'user_login' => 'alex', 'user_email' => 'alex@example.test', 'set_password_url' => 'https://example.test/set-password', 'login_url' => 'https://example.test/login' ),
				'default_payload'   => array( 'subject' => '[{site_name}] Your account', 'preheader' => 'Your account is ready', 'heading' => 'Welcome, {user_login}', 'intro_copy' => 'Your account for {site_name} has been created.', 'primary_cta' => array( 'label' => 'Set your password', 'url' => '{set_password_url}' ), 'secondary_copy' => 'Username: {user_login}', 'additional_content' => 'If you did not expect this email, you can ignore it.' ),
			) ),
			$this->normalize_item( array(
				'template_key'      => 'wordpress:reset_password',
				'provider'          => 'wordpress',
				'source_id'         => 'reset_password',
				'source_class'      => '',
				'audience'          => 'customer',
				'label'             => 'Reset Password',
				'description'       => 'WordPress password-reset notification adapter.',
				'runtime_available' => true,
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'preheader', 'body', 'cta' ),
				'variables'         => array( 'site_name', 'user_login', 'reset_url' ),
				'sample_context'    => array( 'site_name' => 'Example Site', 'user_login' => 'alex', 'reset_url' => 'https://example.test/reset-password' ),
				'default_payload'   => array( 'subject' => '[{site_name}] Password reset', 'preheader' => 'Reset your password', 'heading' => 'Reset your password', 'intro_copy' => 'A password reset was requested for {user_login}.', 'primary_cta' => array( 'label' => 'Reset password', 'url' => '{reset_url}' ), 'additional_content' => 'If you did not request a password reset, you can ignore this email.' ),
			) ),
		);
	}

	/**
	 * Andy Core business-mail identities supported by V1.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function andy_core_templates() {
		return array(
			$this->normalize_item( array(
				'template_key'      => 'andy_core:inquiry_internal_notification',
				'provider'          => 'andy_core',
				'source_id'         => 'inquiry_internal_notification',
				'source_class'      => 'YBY_Email_Notification_Provider',
				'audience'          => 'admin',
				'label'             => 'Inquiry Internal Notification',
				'description'       => 'Internal notification generated when a website inquiry is received.',
				'runtime_available' => class_exists( 'YBY_Email_Notification_Provider' ),
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'preheader', 'body', 'cta', 'dynamic_sections' ),
				'variables'         => array( 'inquiry.case_id', 'customer_name', 'company', 'customer_email', 'product_interest', 'quantity', 'admin_inquiry_url' ),
				'sample_context'    => array( 'inquiry' => array( 'case_id' => 'YBY-TEST-001' ), 'customer_name' => 'Alex Buyer', 'company' => 'Example Trading', 'customer_email' => 'alex@example.test', 'product_interest' => 'Irrigation Kit', 'quantity' => '500', 'admin_inquiry_url' => 'https://example.test/wp-admin/admin.php?page=andy-core-leads' ),
				'default_payload'   => array( 'subject' => '[New Inquiry] {inquiry.case_id} · {customer_name}', 'preheader' => 'New website inquiry from {company}', 'heading' => 'New website inquiry', 'intro_copy' => 'A new inquiry was submitted by {customer_name} from {company}.', 'dynamic_sections' => array( array( 'label' => 'Case ID', 'value' => '{inquiry.case_id}' ), array( 'label' => 'Email', 'value' => '{customer_email}' ), array( 'label' => 'Product', 'value' => '{product_interest}' ), array( 'label' => 'Quantity', 'value' => '{quantity}' ) ), 'primary_cta' => array( 'label' => 'Open inquiry', 'url' => '{admin_inquiry_url}' ) ),
			) ),
		);
	}

	/**
	 * Normalize one registry row to a stable contract.
	 *
	 * @param array<string, mixed> $item Raw item.
	 * @return array<string, mixed>
	 */
	protected function normalize_item( $item ) {
		$item = is_array( $item ) ? $item : array();

		return array(
			'template_key'        => sanitize_key( str_replace( ':', '_', (string) ( $item['template_key'] ?? '' ) ) ) ? (string) ( $item['template_key'] ?? '' ) : '',
			'provider'            => sanitize_key( (string) ( $item['provider'] ?? '' ) ),
			'source_id'           => sanitize_key( (string) ( $item['source_id'] ?? '' ) ),
			'source_class'        => sanitize_text_field( (string) ( $item['source_class'] ?? '' ) ),
			'audience'            => in_array( (string) ( $item['audience'] ?? '' ), array( 'customer', 'admin', 'system' ), true ) ? (string) $item['audience'] : 'system',
			'label'               => sanitize_text_field( (string) ( $item['label'] ?? '' ) ),
			'description'         => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
			'runtime_available'   => ! empty( $item['runtime_available'] ),
			'runtime_enabled'     => ! empty( $item['runtime_enabled'] ),
			'is_manual'           => ! empty( $item['is_manual'] ),
			'recipient'           => sanitize_text_field( (string) ( $item['recipient'] ?? '' ) ),
			'content_type'        => sanitize_text_field( (string) ( $item['content_type'] ?? '' ) ),
			'current_editor'      => sanitize_key( (string) ( $item['current_editor'] ?? '' ) ),
			'editor_label'        => sanitize_text_field( (string) ( $item['editor_label'] ?? '' ) ),
			'editor_url'          => esc_url_raw( (string) ( $item['editor_url'] ?? '' ) ),
			'settings_url'        => esc_url_raw( (string) ( $item['settings_url'] ?? '' ) ),
			'preview_test_url'    => esc_url_raw( (string) ( $item['preview_test_url'] ?? '' ) ),
			'legacy_template_ref' => sanitize_text_field( (string) ( $item['legacy_template_ref'] ?? '' ) ),
			'capabilities'        => array_values( array_unique( array_map( 'sanitize_key', (array) ( $item['capabilities'] ?? array() ) ) ) ),
			'variables'           => array_values( array_map( 'sanitize_text_field', (array) ( $item['variables'] ?? array() ) ) ),
			'sample_context'      => is_array( $item['sample_context'] ?? null ) ? $item['sample_context'] : array(),
			'default_payload'     => is_array( $item['default_payload'] ?? null ) ? $item['default_payload'] : array(),
		);
	}
}
