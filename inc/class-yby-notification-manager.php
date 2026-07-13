<?php
/**
 * Notification manager.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dispatch lead notifications to configured providers.
 */
class YBY_Notification_Manager {

	/**
	 * Registered providers.
	 *
	 * @var array<string, object>
	 */
	protected $providers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->providers['email'] = new YBY_Email_Notification_Provider();
	}

	/**
	 * Register a provider.
	 *
	 * @param string $name Provider name.
	 * @param object $provider Provider instance.
	 * @return void
	 */
	public function registerProvider( $name, $provider ) {
		if ( ! is_string( $name ) || '' === trim( $name ) || ! is_object( $provider ) ) {
			return;
		}

		$this->providers[ sanitize_key( $name ) ] = $provider;
	}

	/**
	 * Dispatch a lead notification.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @param string                $provider_name Provider name.
	 * @return array<string, mixed>
	 */
	public function dispatch( $lead, $provider_name = 'email' ) {
		$key = sanitize_key( $provider_name );

		if ( ! isset( $this->providers[ $key ] ) ) {
			return array(
				'mail_sent'      => false,
				'mail_error_code' => 'provider_not_found',
			);
		}

		$provider = $this->providers[ $key ];

		if ( method_exists( $provider, 'sendLeadNotification' ) ) {
			return $provider->sendLeadNotification( $lead );
		}

		return array(
			'mail_sent'      => false,
			'mail_error_code' => 'provider_unsupported',
		);
	}

	/**
	 * Send the lead notification using the default provider.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return array<string, mixed>
	 */
	public function sendLeadNotification( $lead ) {
		return $this->dispatch( $lead, 'email' );
	}
}
