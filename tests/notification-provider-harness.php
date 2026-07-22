<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'YBY_CORE_VERSION' ) ) {
	define( 'YBY_CORE_VERSION', '1.3.0-dev' );
}

$GLOBALS['yby_option_store']       = array();
$GLOBALS['yby_actions']            = array();
$GLOBALS['yby_do_actions']         = array();
$GLOBALS['yby_wp_mail_calls']      = array();
$GLOBALS['yby_current_test']       = '';
$GLOBALS['yby_subject_render_log'] = array();

function add_action( $hook, $callback ) {
	$GLOBALS['yby_actions'][ $hook ][] = $callback;
}

function remove_action( $hook, $callback ) {
	if ( empty( $GLOBALS['yby_actions'][ $hook ] ) ) {
		return;
	}

	foreach ( $GLOBALS['yby_actions'][ $hook ] as $index => $registered ) {
		if ( $registered === $callback ) {
			unset( $GLOBALS['yby_actions'][ $hook ][ $index ] );
		}
	}
}

function do_action( $hook ) {
	$args = func_get_args();
	array_shift( $args );

	$GLOBALS['yby_do_actions'][] = array(
		'hook' => $hook,
		'args' => $args,
	);
}

function get_option( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['yby_option_store'] ) ? $GLOBALS['yby_option_store'][ $key ] : $default;
}

function sanitize_text_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t]+/', ' ', $value );
	$value = preg_replace( '/\s+/', ' ', $value );

	return trim( (string) $value );
}

function sanitize_key( $value ) {
	$value = strtolower( (string) $value );

	return preg_replace( '/[^a-z0-9_\-]/', '', $value );
}

function sanitize_textarea_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = str_replace( "\r", '', $value );

	return trim( $value );
}

function sanitize_email( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/[\r\n]/', $value ) ) {
		return '';
	}

	return filter_var( $value, FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function esc_url( $value ) {
	return trim( (string) $value );
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function home_url( $path = '/' ) {
	return 'https://example.test' . ltrim( (string) $path, '/' );
}

function wp_mail( $to, $subject, $message, $headers ) {
	$phpmailer = new Harness_PHPMailer();

	if ( ! empty( $GLOBALS['yby_actions']['phpmailer_init'] ) ) {
		foreach ( $GLOBALS['yby_actions']['phpmailer_init'] as $callback ) {
			call_user_func( $callback, $phpmailer );
		}
	}

	$GLOBALS['yby_wp_mail_calls'][] = array(
		'to'       => $to,
		'subject'  => $subject,
		'message'  => $message,
		'headers'  => $headers,
		'alt_body' => $phpmailer->AltBody,
		'test'     => $GLOBALS['yby_current_test'],
		'stored'   => YBY_Lead_Service::$stored_before_notification,
	);

	return empty( $GLOBALS['yby_wp_mail_return'] ) ? false : (bool) $GLOBALS['yby_wp_mail_return'];
}

function __( $text ) {
	return $text;
}

class Harness_PHPMailer {
	public $AltBody = '';
}

class YBY_Helpers {
	public static function option_key() {
		return 'yby_core_options';
	}

	public static function lead_notification_primary_recipient_option_key() {
		return 'yby_lead_notification_primary_recipient_email';
	}

	public static function lead_notification_cc_recipient_option_key() {
		return 'yby_lead_notification_cc_recipient_emails';
	}

	public static function lead_notification_bcc_recipient_option_key() {
		return 'yby_lead_notification_bcc_recipient_emails';
	}

	public static function lead_notification_reply_to_policy_option_key() {
		return 'yby_lead_notification_reply_to_policy';
	}
}

class YBY_Config {
	public static function get_lead_notification_primary_recipient_email() {
		return 'sales@example.test';
	}

	public static function get_lead_notification_cc_recipient_emails() {
		return 'sales@example.test, cc@example.test, invalid, CC@example.test';
	}

	public static function get_lead_notification_bcc_recipient_emails() {
		return 'bcc@example.test, cc@example.test, invalid, BCC@example.test';
	}

	public static function get_lead_notification_reply_to_policy() {
		return 'auto';
	}
}

class YBY_Brand_Profile {
	public static function get_subject_template() {
		return '[New Inquiry] {country} | {product_interest} | {case_id}';
	}

	public static function get_brand_name() {
		return 'YBY Bottle';
	}

	public static function get_website_url() {
		return 'https://bottle.example.test/';
	}

	public static function get_phone() {
		return '+86 123456789';
	}

	public static function get_whatsapp() {
		return '+86123456789';
	}

	public static function get_support_email() {
		return 'support@example.test';
	}

	public static function get_footer_copyright() {
		return '© 2026 YBY Bottle. All rights reserved.';
	}

	public static function get_logo_url() {
		return '';
	}

	public static function get_reverse_logo_url() {
		return '';
	}

	public static function get_inquiry_email_title() {
		return 'Bottle Website Inquiry';
	}

	public static function get_primary_color() {
		return '#123456';
	}

	public static function get_primary_text_color() {
		return '#FFFFFF';
	}

	public static function get_secondary_color() {
		return '#654321';
	}

	public static function get_surface_color() {
		return '#FAFAFA';
	}

	public static function get_text_color() {
		return '#101010';
	}

	public static function get_muted_text_color() {
		return '#707070';
	}

	public static function get_border_color() {
		return '#CCCCCC';
	}
}

class YBY_Site_Profile {
	public static function get_brand_key() {
		return 'yby_bottle';
	}

	public static function get_brand_name() {
		return 'YBY Bottle';
	}

	public static function get_case_id_code() {
		return 'BOT';
	}

	public static function get_website_url() {
		return 'https://bottle.example.test/';
	}
}

class YBY_Email_Subject_Renderer {
	public function render( $template, $lead ) {
		$replacements = array(
			'{country}'   => isset( $lead['country'] ) ? (string) $lead['country'] : '',
			'{farm_size}' => isset( $lead['farm_size'] ) ? (string) $lead['farm_size'] : '',
			'{crop}'      => isset( $lead['crop'] ) ? (string) $lead['crop'] : '',
			'{case_id}'   => isset( $lead['case_id'] ) ? (string) $lead['case_id'] : '',
		);

		$output = strtr( (string) $template, $replacements );
		$GLOBALS['yby_subject_render_log'][] = $output;

		return $output;
	}
}

class YBY_Inquiry_Lead_Mapper {
	public function get_core_field_ids() {
		return array(
			'name',
			'company',
			'country',
			'email',
			'whatsapp',
			'buyer_type',
			'product_interest',
			'quantity',
			'project_details',
		);
	}

	public function decode_custom_fields( $json ) {
		$decoded = json_decode( (string) $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	public function build_custom_fields_json( $fields ) {
		return json_encode( $fields );
	}

	public function normalize_source_metadata( $payload ) {
		return array(
			'source_component' => isset( $payload['source_component'] ) ? sanitize_text_field( $payload['source_component'] ) : '',
			'source_preset'    => isset( $payload['source_preset'] ) ? sanitize_text_field( $payload['source_preset'] ) : '',
			'source_page'      => isset( $payload['source_page'] ) ? sanitize_text_field( $payload['source_page'] ) : '',
			'form_version'     => isset( $payload['form_version'] ) ? sanitize_text_field( $payload['form_version'] ) : '',
		);
	}

	public function sanitize_custom_fields( $fields ) {
		return is_array( $fields ) ? $fields : array();
	}
}

class YBY_Inquiry_Field_Manager {
	public function get_field( $field_id ) {
		$labels = array(
			'custom_note' => array( 'label' => 'Custom Note' ),
		);

		return isset( $labels[ $field_id ] ) ? $labels[ $field_id ] : array( 'label' => ucwords( str_replace( '_', ' ', $field_id ) ) );
	}
}

class YBY_Lead_Service {
	public static $stored_before_notification = false;

	public static $lead_id = 91;

	public static $case_id = 'YBY-CORE-20260720-HARNESS1';

	public static function create( $lead ) {
		self::$stored_before_notification = true;

		return array(
			'success' => true,
			'lead_id' => self::$lead_id,
			'case_id' => self::$case_id,
			'status'  => 'new',
		);
	}
}

class WP_REST_Server {
	const CREATABLE = 'CREATABLE';
}

class WP_REST_Request {
	protected $params = array();

	public function __construct( $params = array() ) {
		$this->params = is_array( $params ) ? $params : array();
	}

	public function get_param( $key ) {
		return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
	}
}

class WP_REST_Response {
	protected $data;
	protected $status;

	public function __construct( $data, $status ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function get_data() {
		return $this->data;
	}

	public function get_status() {
		return $this->status;
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-email-notification-provider.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-notification-manager.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-lead-rest-controller.php';

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function harness_lead() {
	return array(
		'case_id'              => 'YBY-CORE-20260720-HARNESS1',
		'name'                 => 'Harness Customer',
		'company'              => 'Harness Co',
		'country'              => 'Kenya',
		'email'                => 'customer@example.test',
		'contact_email'        => 'customer@example.test',
		'whatsapp'             => '+1234567890',
		'contact_whatsapp_url' => 'https://wa.me/1234567890',
		'product_interest'     => 'Drip irrigation',
		'quantity'             => '100 units',
		'project_details'      => "Line 1\nLine 2",
		'source_component'     => 'inquiry_modal',
		'source_preset'        => 'irrigation_quick_inquiry',
		'source_page'          => 'https://example.test/inquiry',
		'form_version'         => '1.0',
		'landing_page_url'     => 'https://example.test/inquiry',
		'utm_source'           => 'google',
		'utm_medium'           => 'cpc',
		'utm_campaign'         => 'summer',
		'utm_term'             => 'drip',
		'gclid'                => 'gclid-123',
		'fbclid'               => 'fbclid-123',
		'farm_size'            => '5 acres',
		'crop'                 => 'Tomato',
		'custom_fields'        => array(
			'custom_note' => '<script>alert(1)</script>Need & review',
		),
	);
}

$provider = new YBY_Email_Notification_Provider();
$tests    = array();

$tests['headers_no_hardcoded_sender'] = static function () use ( $provider ) {
	$headers = $provider->build_headers( harness_lead() );
	$joined  = implode( "\n", $headers );

	harness_assert( false === stripos( $joined, 'From:' ), 'Headers must not contain a From header.' );
	harness_assert( false === stripos( $joined, 'no-reply@ybyirrigation.com' ), 'Headers must not contain the hardcoded no-reply sender.' );
};

$tests['headers_content_type_once'] = static function () use ( $provider ) {
	$headers = $provider->build_headers( harness_lead() );
	$count   = 0;

	foreach ( $headers as $header ) {
		if ( 'Content-Type: text/html; charset=UTF-8' === $header ) {
			++$count;
		}
	}

	harness_assert( 1 === $count, 'Headers must contain exactly one HTML content type.' );
};

$tests['headers_reply_to_and_injection'] = static function () use ( $provider ) {
	$valid_headers   = $provider->build_headers( harness_lead() );
	$reply_to_values = array_values(
		array_filter(
			$valid_headers,
			static function ( $header ) {
				return 0 === stripos( $header, 'Reply-To:' );
			}
		)
	);

	harness_assert( 1 === count( $reply_to_values ), 'Valid auto policy must add exactly one Reply-To header.' );
	harness_assert( 'Reply-To: customer@example.test' === $reply_to_values[0], 'Reply-To header must contain the sanitized customer email.' );

	$malicious_lead                  = harness_lead();
	$malicious_lead['contact_email'] = "customer@example.test\r\nBcc:evil@example.test";
	$malicious_headers               = $provider->build_headers( $malicious_lead );

	foreach ( $malicious_headers as $header ) {
		harness_assert( false === stripos( $header, 'evil@example.test' ), 'Reply-To header must reject injection payloads.' );
	}
};

$tests['headers_cc_bcc_dedupe'] = static function () use ( $provider ) {
	$headers = $provider->build_headers( harness_lead() );
	$cc      = array_values(
		array_filter(
			$headers,
			static function ( $header ) {
				return 0 === stripos( $header, 'Cc:' );
			}
		)
	);
	$bcc     = array_values(
		array_filter(
			$headers,
			static function ( $header ) {
				return 0 === stripos( $header, 'Bcc:' );
			}
		)
	);

	$cc_normalized  = array_map( 'strtolower', $cc );
	$bcc_normalized = array_map( 'strtolower', $bcc );

	harness_assert( array( 'cc: cc@example.test' ) === $cc_normalized, 'CC list must keep one valid deduped address.' );
	harness_assert( array( 'bcc: bcc@example.test' ) === $bcc_normalized, 'BCC list must keep one valid deduped address.' );
};

$tests['bodies_case_id_and_escaping'] = static function () use ( $provider ) {
	$html  = $provider->build_html( harness_lead() );
	$plain = $provider->build_plain_text( harness_lead() );

	harness_assert( false !== strpos( $html, 'YBY-CORE-20260720-HARNESS1' ), 'HTML body must include the Case ID.' );
	harness_assert( false !== strpos( $plain, 'YBY-CORE-20260720-HARNESS1' ), 'Plain text body must include the Case ID.' );
	harness_assert( false !== strpos( $html, 'alert(1)Need &amp; review' ), 'HTML body must escape custom field content.' );
	harness_assert( false !== strpos( $plain, 'alert(1)Need & review' ), 'Plain text body must remain readable after sanitization.' );
	harness_assert( false !== strpos( $html, 'Bottle Website Inquiry' ), 'HTML body must include the configured inquiry email title.' );
	harness_assert( false !== strpos( $plain, 'Bottle Website Inquiry' ), 'Plain text body must include the configured inquiry email title.' );
	harness_assert( false !== strpos( $html, '#123456' ), 'HTML body must use configured primary brand color.' );
	harness_assert( false !== strpos( $html, '#654321' ), 'HTML body must use configured secondary brand color.' );
	harness_assert( false !== strpos( $html, 'YBY Bottle' ), 'HTML body must use configured brand identity.' );
	harness_assert( false !== strpos( $html, 'https://bottle.example.test/' ), 'HTML body must use configured brand website.' );
	harness_assert( false === strpos( $html, '#00754A' ), 'HTML body must not contain deprecated hardcoded green colors.' );
	harness_assert( false === strpos( $html, '#17211B' ), 'HTML body must not contain deprecated hardcoded dark colors.' );
};

$tests['notification_ordering_nonfatal'] = static function () {
	$GLOBALS['yby_current_test']   = 'notification_ordering_nonfatal';
	$GLOBALS['yby_wp_mail_return'] = false;
	$GLOBALS['yby_wp_mail_calls']  = array();
	$GLOBALS['yby_do_actions']     = array();
	YBY_Lead_Service::$stored_before_notification = false;

	$request = new WP_REST_Request(
		array(
			'name'             => 'Harness Customer',
			'email'            => 'customer@example.test',
			'country'          => 'Kenya',
			'project_details'  => 'Stored before notification',
			'source_component' => 'inquiry_modal',
			'source_preset'    => 'irrigation_quick_inquiry',
			'source_page'      => 'https://example.test/inquiry',
			'form_version'     => '1.0',
			'fields'           => array(
				'custom_note' => 'Extra detail',
			),
		)
	);

	$controller = new YBY_Lead_REST_Controller();
	$response   = $controller->submit_lead( $request );
	$data       = $response->get_data();

	harness_assert( 200 === $response->get_status(), 'Notification failure must remain non-fatal to the REST response.' );
	harness_assert( ! empty( $data['success'] ), 'REST response must still report success.' );
	harness_assert( 1 === count( $GLOBALS['yby_wp_mail_calls'] ), 'Exactly one notification send attempt must occur per lead.' );
	harness_assert( true === $GLOBALS['yby_wp_mail_calls'][0]['stored'], 'Lead storage must happen before notification send.' );

	$notification_actions = array_values(
		array_filter(
			$GLOBALS['yby_do_actions'],
			static function ( $entry ) {
				return 'yby_lead_notification_result' === $entry['hook'];
			}
		)
	);

	harness_assert( 1 === count( $notification_actions ), 'Exactly one notification result action must fire per lead.' );
	harness_assert( false === $notification_actions[0]['args'][0]['mail_sent'], 'Failure result must be surfaced through the action payload.' );
};

$results = array();

foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
