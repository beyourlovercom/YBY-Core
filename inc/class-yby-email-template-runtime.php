<?php
/**
 * Email OS native runtime adapters.
 *
 * Published snapshots are the only runtime source. Drafts never affect outbound mail.
 * WooCommerce is intentionally excluded and remains a governance bridge.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_Template_Runtime {

	protected $registry;
	protected $store;
	protected $renderer;

	public function __construct( $registry = null, $store = null, $renderer = null ) {
		$this->registry = $registry ?: new YBY_Email_Template_Registry();
		$this->store = $store ?: new YBY_Email_Template_Store();
		$this->renderer = $renderer ?: new YBY_Email_Template_Renderer();
	}

	public function render_published( $template_key, $context ) {
		$items = $this->registry->discover();
		$identity = isset( $items[ $template_key ] ) ? $items[ $template_key ] : array();
		if ( ! $identity || ! YBY_Email_Template_Store::is_native_provider( $identity['provider'] ?? '' ) ) {
			return array( 'active' => false, 'reason' => 'unsupported_template' );
		}

		$snapshot = $this->store->get_published_snapshot( $identity );
		if ( empty( $snapshot['payload'] ) ) {
			return array( 'active' => false, 'reason' => 'not_published' );
		}

		$rendered = $this->renderer->render( $snapshot['payload'], is_array( $context ) ? $context : array(), YBY_Email_Design_Settings::get() );
		if ( empty( $rendered['valid'] ) ) {
			return array( 'active' => false, 'reason' => 'render_invalid', 'diagnostics' => $rendered['diagnostics'] ?? array() );
		}

		$rendered['active'] = true;
		$rendered['version_number'] = (int) $snapshot['version_number'];
		$rendered['content_hash'] = (string) $snapshot['content_hash'];
		return $rendered;
	}

	public function filter_new_user_notification_email( $email, $user, $blogname ) {
		if ( ! is_array( $email ) || ! is_object( $user ) ) {
			return $email;
		}

		$set_password_url = $this->extract_first_url( (string) ( $email['message'] ?? '' ) );
		if ( '' === $set_password_url ) {
			return $email;
		}

		$rendered = $this->render_published( 'wordpress:new_user', array(
			'site_name' => (string) $blogname,
			'user_login' => (string) ( $user->user_login ?? '' ),
			'user_email' => (string) ( $user->user_email ?? '' ),
			'set_password_url' => $set_password_url,
			'login_url' => function_exists( 'wp_login_url' ) ? wp_login_url() : '',
		) );
		if ( empty( $rendered['active'] ) ) {
			return $email;
		}

		$email['subject'] = str_replace( '%', '%%', (string) $rendered['subject'] );
		$email['message'] = (string) $rendered['html'];
		$email['headers'] = $this->html_headers( $email['headers'] ?? array() );
		return $email;
	}

	public function filter_reset_password_notification_email( $email, $key, $user_login, $user_data ) {
		if ( ! is_array( $email ) ) {
			return $email;
		}

		$reset_url = function_exists( 'network_site_url' )
			? network_site_url( 'wp-login.php?login=' . rawurlencode( (string) $user_login ) . '&key=' . rawurlencode( (string) $key ) . '&action=rp', 'login' )
			: '';
		$rendered = $this->render_published( 'wordpress:reset_password', array(
			'site_name' => $this->site_name(),
			'user_login' => (string) $user_login,
			'reset_url' => $reset_url,
		) );
		if ( empty( $rendered['active'] ) ) {
			return $email;
		}

		$email['subject'] = (string) $rendered['subject'];
		$email['message'] = (string) $rendered['html'];
		$email['headers'] = $this->html_headers( $email['headers'] ?? array() );
		return $email;
	}

	public function render_inquiry_notification( $lead ) {
		$lead = is_array( $lead ) ? $lead : array();
		$case_id = sanitize_text_field( (string) ( $lead['case_id'] ?? '' ) );
		return $this->render_published( 'andy_core:inquiry_internal_notification', array(
			'inquiry' => array( 'case_id' => $case_id ),
			'customer_name' => sanitize_text_field( (string) ( $lead['name'] ?? '' ) ),
			'company' => sanitize_text_field( (string) ( $lead['company'] ?? '' ) ),
			'customer_email' => sanitize_email( (string) ( $lead['email'] ?? '' ) ),
			'product_interest' => sanitize_text_field( (string) ( $lead['product_interest'] ?? '' ) ),
			'quantity' => sanitize_text_field( (string) ( $lead['quantity'] ?? '' ) ),
			'admin_inquiry_url' => function_exists( 'admin_url' ) ? admin_url( 'admin.php?page=andy-core-leads' ) : '',
		) );
	}

	protected function extract_first_url( $message ) {
		if ( preg_match( '~https?://[^\\s<>]+~i', (string) $message, $matches ) ) {
			return esc_url_raw( rtrim( $matches[0], ".,;" ) );
		}
		return '';
	}

	protected function html_headers( $headers ) {
		$headers = is_array( $headers ) ? $headers : preg_split( '/\\r?\\n/', (string) $headers );
		$headers = array_values( array_filter( array_map( 'trim', (array) $headers ) ) );
		foreach ( $headers as $header ) {
			if ( 0 === stripos( $header, 'Content-Type:' ) ) {
				return $headers;
			}
		}
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		return $headers;
	}

	protected function site_name() {
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_network' ) ) {
			$network = get_network();
			if ( is_object( $network ) && isset( $network->site_name ) ) {
				return (string) $network->site_name;
			}
		}
		return function_exists( 'get_option' ) ? wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES ) : '';
	}
}
