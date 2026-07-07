<?php
/**
 * CRM webhook placeholder.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Future CRM transport placeholder.
 */
class YBY_Webhook {

	/**
	 * Send lead payload to future CRM webhook.
	 *
	 * MVP intentionally does not send real data unless explicitly enabled and configured.
	 *
	 * @param array<string, mixed> $payload Lead payload.
	 * @return bool|\WP_Error
	 */
	public function send_lead( $payload ) {
		unset( $payload );

		if ( ! YBY_Config::is_crm_webhook_enabled() ) {
			return new WP_Error( 'yby_webhook_disabled', __( 'CRM webhook is disabled in YBY Core.', 'yby-core' ) );
		}

		if ( '' === YBY_Config::get_crm_webhook_url() ) {
			return new WP_Error( 'yby_webhook_missing_url', __( 'CRM webhook URL is missing.', 'yby-core' ) );
		}

		/*
		 * Future implementation note:
		 * A later phase may send a sanitized payload to the configured CRM endpoint.
		 * MVP does not perform external requests.
		 */
		return false;
	}
}
