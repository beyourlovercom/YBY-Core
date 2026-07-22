<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['yby_option_store'] = array();
$GLOBALS['yby_notifications'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['yby_option_store'] ) ? $GLOBALS['yby_option_store'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['yby_option_store'][ $key ] = $value;

	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t]+/', ' ', $value );
	$value = preg_replace( '/\s+/', ' ', $value );

	return trim( (string) $value );
}

function sanitize_textarea_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = str_replace( "\r", '', $value );

	return trim( $value );
}

function sanitize_email( $value ) {
	return filter_var( trim( (string) $value ), FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function sanitize_key( $value ) {
	$value = strtolower( (string) $value );

	return preg_replace( '/[^a-z0-9_\-]/', '', $value );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function home_url( $path = '/' ) {
	return 'https://example.test/' . ltrim( (string) $path, '/' );
}

function apply_filters( $hook, $value ) {
	return $value;
}

function __( $text ) {
	return $text;
}

function current_time() {
	return '2026-07-21 00:00:00';
}

function wp_rand( $min = 0, $max = 0 ) {
	static $counter = 0;
	++$counter;

	return $min + ( $counter % max( 1, ( $max - $min + 1 ) ) );
}

function wp_generate_password( $length = 12, $special_chars = false, $extra_special_chars = false ) {
	return str_repeat( 'A', (int) $length );
}

function do_action( $hook ) {
	$args = func_get_args();
	array_shift( $args );
	$GLOBALS['yby_actions'][] = array(
		'hook' => $hook,
		'args' => $args,
	);
}

function wp_json_encode( $value, $flags = 0 ) {
	return json_encode( $value, $flags );
}

class YBY_Helpers {
	public static function option_key() {
		return 'yby_core_options';
	}
}

class Harness_WPDB {
	public $insert_id = 0;
	public $rows = array();

	public function insert( $table, $record, $format = array() ) {
		$this->insert_id = count( $this->rows ) + 1;
		$record['id']    = $this->insert_id;
		$this->rows[]    = array(
			'table'  => $table,
			'record' => $record,
		);

		return true;
	}

	public function prepare( $query ) {
		$args = func_get_args();
		array_shift( $args );

		foreach ( $args as $arg ) {
			$query = preg_replace( '/%s/', "'" . addslashes( (string) $arg ) . "'", $query, 1 );
		}

		return $query;
	}

	public function get_var( $query ) {
		if ( preg_match( "/WHERE case_id = '([^']+)'/i", (string) $query, $matches ) ) {
			$case_id = stripslashes( $matches[1] );

			foreach ( $this->rows as $row ) {
				if ( isset( $row['record']['case_id'] ) && $row['record']['case_id'] === $case_id ) {
					return $row['record']['id'];
				}
			}

			return null;
		}

		return null;
	}
}

class YBY_Database {
	public static function needs_install_or_upgrade() {
		return false;
	}

	public static function install() {
	}

	public static function leads_table_name() {
		return 'wp_yby_leads';
	}
}

class YBY_Inquiry_Lead_Mapper {
	public function normalize_source_metadata( $payload ) {
		return array(
			'source_component' => isset( $payload['source_component'] ) ? sanitize_text_field( $payload['source_component'] ) : '',
			'source_preset'    => isset( $payload['source_preset'] ) ? sanitize_text_field( $payload['source_preset'] ) : '',
			'source_page'      => isset( $payload['source_page'] ) ? sanitize_text_field( $payload['source_page'] ) : '',
			'form_version'     => isset( $payload['form_version'] ) ? sanitize_text_field( $payload['form_version'] ) : '',
		);
	}

	public function sanitize_custom_fields( $fields, $preset = '' ) {
		return is_array( $fields ) ? $fields : array();
	}

	public function build_custom_fields_json( $fields ) {
		return json_encode( is_array( $fields ) ? $fields : array() );
	}
}

class YBY_Notification_Manager {
	public function sendLeadNotification( $lead ) {
		$GLOBALS['yby_notifications'][] = $lead;

		return array(
			'mail_sent'       => true,
			'mail_error_code' => '',
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

$GLOBALS['wpdb'] = new Harness_WPDB();

require_once dirname( __DIR__ ) . '/inc/class-yby-config.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-site-profile.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-case-id.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-lead-service.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-lead-rest-controller.php';

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function harness_reset_state( $options = array() ) {
	global $wpdb;

	$GLOBALS['yby_option_store']  = array(
		YBY_Helpers::option_key() => is_array( $options ) ? $options : array(),
	);
	$GLOBALS['yby_notifications'] = array();
	$GLOBALS['yby_actions']       = array();
	$wpdb                         = new Harness_WPDB();
	$GLOBALS['wpdb']              = $wpdb;
}

function harness_base_request( $overrides = array() ) {
	return new WP_REST_Request(
		array_merge(
			array(
				'name'             => 'Harness Customer',
				'email'            => 'customer@example.test',
				'country'          => 'Kenya',
				'source_component' => 'inquiry_modal',
				'source_preset'    => 'irrigation_quick_inquiry',
				'source_page'      => 'Harness Page',
				'form_version'     => '1.0',
				'page'             => 'Harness Page',
				'source_url'       => 'https://landing.example.test/inquiry',
				'fields'           => array(
					'farm_size' => '5 acres',
				),
			),
			$overrides
		)
	);
}

$tests = array();

$tests['profile_neutral_fallback'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => '!!!',
			'site_brand_name'    => '',
			'case_id_brand_code' => 'invalid code!',
			'website_url'        => '/relative-only',
		)
	);

	$profile = YBY_Site_Profile::get_profile();

	harness_assert( 'yby_core' === $profile['site_brand_key'], 'Neutral fallback must use yby_core brand key.' );
	harness_assert( 'YBY' === $profile['site_brand_name'], 'Neutral fallback must use YBY brand name.' );
	harness_assert( 'CORE' === $profile['case_id_brand_code'], 'Neutral fallback must use CORE case ID code.' );
	harness_assert( 'https://example.test/' === $profile['website_url'], 'Neutral fallback website must use home_url fallback.' );
};

$tests['profile_irrigation_case_id'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => 'yby_irrigation',
			'site_brand_name'    => 'YBY Irrigation',
			'case_id_brand_code' => 'IRR',
			'website_url'        => 'https://ybyirrigation.com/',
		)
	);

	$lead = YBY_Lead_Service::create(
		array(
			'brand'            => YBY_Site_Profile::get_brand_key(),
			'website'          => YBY_Site_Profile::get_website_url(),
			'source_url'       => 'https://landing.example.test/inquiry',
			'name'             => 'Harness Customer',
			'company'          => '',
			'country'          => 'Kenya',
			'email'            => 'customer@example.test',
			'whatsapp'         => '',
			'buyer_type'       => '',
			'product_interest' => '',
			'quantity'         => '',
			'project_details'  => '',
			'source_component' => 'inquiry_modal',
			'source_preset'    => 'irrigation_quick_inquiry',
			'source_page'      => 'Harness Page',
			'form_version'     => '1.0',
			'custom_fields'    => array(),
			'utm_source'       => '',
			'utm_medium'       => '',
			'utm_campaign'     => '',
			'utm_term'         => '',
			'gclid'            => '',
			'fbclid'           => '',
		)
	);

	$stored = $GLOBALS['wpdb']->rows[0]['record'];

	harness_assert( 0 === strpos( $lead['case_id'], 'YBY-IRR-' ), 'Irrigation profile must generate IRR case IDs.' );
	harness_assert( 'yby_irrigation' === $stored['brand'], 'Irrigation profile must store yby_irrigation brand.' );
};

$tests['profile_bottle_case_id'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => 'yby_bottle',
			'site_brand_name'    => 'YBY Bottle',
			'case_id_brand_code' => 'BOT',
			'website_url'        => 'https://example-bottle.test/',
		)
	);

	$controller = new YBY_Lead_REST_Controller();
	$response   = $controller->submit_lead( harness_base_request() );
	$data       = $response->get_data();
	$stored     = $GLOBALS['wpdb']->rows[0]['record'];

	harness_assert( 200 === $response->get_status(), 'Bottle profile request must succeed.' );
	harness_assert( 0 === strpos( $data['data']['case_id'], 'YBY-BOT-' ), 'Bottle profile must generate BOT case IDs.' );
	harness_assert( 'yby_bottle' === $stored['brand'], 'Bottle profile must store yby_bottle brand.' );
};

$tests['spoofing_and_rest_identity'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => 'yby_irrigation',
			'site_brand_name'    => 'YBY Irrigation',
			'case_id_brand_code' => 'IRR',
			'website_url'        => 'https://ybyirrigation.com/',
		)
	);

	$controller = new YBY_Lead_REST_Controller();
	$response   = $controller->submit_lead(
		harness_base_request(
			array(
				'brand'   => 'fake_brand',
				'website' => 'https://evil.example/',
				'case_id' => 'YBY-EVIL-20260721-ABC234',
			)
		)
	);
	$data       = $response->get_data();
	$stored     = $GLOBALS['wpdb']->rows[0]['record'];

	harness_assert( 'yby_irrigation' === $stored['brand'], 'Spoofed brand must not be stored.' );
	harness_assert( 'https://ybyirrigation.com/' === $stored['website'], 'Spoofed website must not be stored.' );
	harness_assert( 'YBY-EVIL-20260721-ABC234' !== $stored['case_id'], 'Spoofed case ID must not be stored.' );
	harness_assert( 0 === strpos( $stored['case_id'], 'YBY-IRR-' ), 'Server profile must control generated case ID.' );
	harness_assert( $stored['case_id'] === $data['data']['case_id'], 'REST response case ID must match stored case ID.' );
	harness_assert( true === $data['success'], 'REST success response shape must remain successful.' );
	harness_assert( 'Lead received' === $data['message'], 'REST success message must remain unchanged.' );
	harness_assert( isset( $data['data']['lead_id'] ) && is_int( $data['data']['lead_id'] ), 'REST response must include integer lead_id.' );
	harness_assert( 'received' === $data['data']['status'], 'REST response status must remain received.' );
};

$tests['invalid_code_falls_back_to_core'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => 'trusted-site',
			'site_brand_name'    => 'Trusted Site',
			'case_id_brand_code' => 'too-long-code!!!',
			'website_url'        => 'https://trusted.example/',
		)
	);

	harness_assert( 'CORE' === YBY_Site_Profile::get_case_id_code(), 'Invalid case ID code must fall back to CORE.' );
	harness_assert( 0 === strpos( ( new YBY_Case_ID() )->generate(), 'YBY-CORE-' ), 'Generated case ID must use CORE fallback.' );
};

$tests['case_id_compatibility'] = static function () {
	harness_reset_state();
	$case_engine = new YBY_Case_ID();

	harness_assert( $case_engine->validate( 'YBY-CORE-20260721-ABC234' ), 'CORE case IDs must remain valid.' );
	harness_assert( $case_engine->validate( 'YBY-IRR-20260721-ABC234' ), 'IRR case IDs must remain valid.' );
	harness_assert( $case_engine->validate( 'YBY-BOT-20260721-ABC234' ), 'BOT case IDs must remain valid.' );
};

$results = array();

foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
