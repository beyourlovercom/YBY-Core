<?php
/**
 * Email OS transport, test-send and health diagnostics.
 *
 * Transport credentials remain owned by the active mail provider.
 * Email OS reads only non-secret health metadata.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Email_Health_Center {

	const LAST_TEST_OPTION = 'yby_email_os_last_test';

	public function transport_status() {
		$status = array(
			'wp_mail_ready' => function_exists( 'wp_mail' ),
			'plugin'        => 'WordPress Core',
			'provider'      => 'Server mail',
			'provider_slug' => 'mail',
			'configured'    => function_exists( 'wp_mail' ),
			'settings_url'  => '',
			'diagnostics'   => array(),
		);

		if ( ! function_exists( 'wp_mail_smtp' ) ) {
			$status['diagnostics'][] = 'No dedicated wp_mail transport plugin detected.';
			return $status;
		}

		$status['plugin']       = defined( 'WPMS_PLUGIN_VER' ) ? 'WP Mail SMTP v' . WPMS_PLUGIN_VER : 'WP Mail SMTP';
		$status['settings_url'] = admin_url( 'admin.php?page=wp-mail-smtp' );

		try {
			$core = wp_mail_smtp();
			$manager = is_object( $core ) && method_exists( $core, 'get_connections_manager' ) ? $core->get_connections_manager() : null;
			$connection = is_object( $manager ) && method_exists( $manager, 'get_mail_connection' ) ? $manager->get_mail_connection() : null;
			if ( is_object( $connection ) ) {
				if ( method_exists( $connection, 'get_title' ) ) {
					$status['provider'] = sanitize_text_field( (string) $connection->get_title() );
				}
				if ( method_exists( $connection, 'get_mailer_slug' ) ) {
					$status['provider_slug'] = sanitize_key( (string) $connection->get_mailer_slug() );
				}
				$mailer = method_exists( $connection, 'get_mailer' ) ? $connection->get_mailer() : null;
				$status['configured'] = is_object( $mailer ) && method_exists( $mailer, 'is_mailer_complete' ) ? (bool) $mailer->is_mailer_complete() : false;
			}
		} catch ( \Throwable $throwable ) {
			$status['configured'] = false;
			$status['diagnostics'][] = 'Transport metadata unavailable.';
		}

		return $status;
	}

	public function native_health( $templates, $store ) {
		$rows = array();
		$published = 0;

		foreach ( (array) $templates as $key => $identity ) {
			if ( ! YBY_Email_Template_Store::is_native_provider( $identity['provider'] ?? '' ) ) {
				continue;
			}
			$snapshot = $store->get_published_snapshot( $identity );
			$is_published = ! empty( $snapshot['payload'] );
			if ( $is_published ) {
				$published++;
			}
			$rows[ $key ] = array(
				'label'          => sanitize_text_field( (string) ( $identity['label'] ?? $key ) ),
				'provider'       => sanitize_key( (string) ( $identity['provider'] ?? '' ) ),
				'published'      => $is_published,
				'version_number' => $is_published ? absint( $snapshot['version_number'] ?? 0 ) : 0,
				'published_at'   => $is_published ? sanitize_text_field( (string) ( $snapshot['published_at'] ?? '' ) ) : '',
			);
		}

		return array(
			'total'     => count( $rows ),
			'published' => $published,
			'rows'      => $rows,
		);
	}

	public function get_last_test() {
		$value = get_option( self::LAST_TEST_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public function send_test( $identity, $recipient, $store, $renderer ) {
		$recipient = sanitize_email( (string) $recipient );
		if ( ! is_email( $recipient ) ) {
			return $this->record_test( false, $identity, 0, 'invalid_recipient', 'Please enter a valid test recipient.' );
		}
		if ( ! is_array( $identity ) || ! YBY_Email_Template_Store::is_native_provider( $identity['provider'] ?? '' ) ) {
			return $this->record_test( false, $identity, 0, 'native_template_required', 'Only WordPress / Andy Core native templates can be tested here.' );
		}

		$snapshot = $store->get_published_snapshot( $identity );
		if ( empty( $snapshot['payload'] ) ) {
			return $this->record_test( false, $identity, 0, 'published_snapshot_required', 'Publish this native template before sending a test.' );
		}

		$rendered = $renderer->render(
			$snapshot['payload'],
			(array) ( $identity['sample_context'] ?? array() ),
			YBY_Email_Design_Settings::get()
		);
		if ( empty( $rendered['valid'] ) ) {
			$diagnostics = implode( ', ', (array) ( $rendered['diagnostics'] ?? array() ) );
			return $this->record_test( false, $identity, (int) $snapshot['version_number'], 'render_validation_failed', $diagnostics );
		}

		$error_message = '';
		$failure_hook = static function ( $error ) use ( &$error_message ) {
			if ( is_wp_error( $error ) ) {
				$error_message = sanitize_text_field( $error->get_error_message() );
			}
		};

		add_action( 'wp_mail_failed', $failure_hook, 10, 1 );
		try {
			$sent = (bool) wp_mail(
				$recipient,
				'[Email OS Test] ' . (string) ( $rendered['subject'] ?? '' ),
				(string) ( $rendered['html'] ?? '' ),
				array( 'Content-Type: text/html; charset=UTF-8', 'X-Andy-Core-Test: Email-OS-P6' )
			);
		} catch ( \Throwable $throwable ) {
			$sent = false;
			$error_message = sanitize_text_field( $throwable->getMessage() );
		} finally {
			remove_action( 'wp_mail_failed', $failure_hook, 10 );
		}

		if ( ! $sent ) {
			return $this->record_test(
				false,
				$identity,
				(int) $snapshot['version_number'],
				'mail_send_failed',
				'' !== $error_message ? $error_message : 'wp_mail returned false.'
			);
		}

		return $this->record_test( true, $identity, (int) $snapshot['version_number'], '', 'Test email accepted by wp_mail transport.' );
	}

	protected function record_test( $success, $identity, $version, $error_code, $message ) {
		$result = array(
			'success'        => (bool) $success,
			'template_key'   => sanitize_text_field( (string) ( $identity['template_key'] ?? '' ) ),
			'template_label' => sanitize_text_field( (string) ( $identity['label'] ?? '' ) ),
			'version_number' => absint( $version ),
			'error_code'     => sanitize_key( (string) $error_code ),
			'message'        => sanitize_text_field( (string) $message ),
			'tested_at'      => current_time( 'mysql' ),
		);

		update_option( self::LAST_TEST_OPTION, $result, false );

		return $result;
	}
}
