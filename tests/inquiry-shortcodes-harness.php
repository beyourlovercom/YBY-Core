<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['shortcode_tags'] = array();
$GLOBALS['post']           = null;

function add_shortcode( $tag, $callback ) {
	$GLOBALS['shortcode_tags'][ $tag ] = $callback;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	unset( $shortcode );
	return array_merge( $pairs, is_array( $atts ) ? $atts : array() );
}

function sanitize_key( $value ) {
	$value = strtolower( (string) $value );
	return preg_replace( '/[^a-z0-9_\-]/', '', $value );
}

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $value ) ) );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function sanitize_html_class( $value ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
}

function esc_html( $value ) {
	return (string) $value;
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

function get_the_ID() {
	return isset( $GLOBALS['post']->ID ) ? (int) $GLOBALS['post']->ID : 0;
}

function get_shortcode_regex( $tagnames = null ) {
	$tagregexp = is_array( $tagnames ) ? join( '|', array_map( 'preg_quote', $tagnames ) ) : '[^<>&/\[\]\x00-\x20=]+';
	return '\[(\[?)(' . $tagregexp . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)\[\/\2\])?)(\]?)';
}

function shortcode_parse_atts( $text ) {
	$atts = array();

	if ( preg_match_all( '/([\w-]+)\s*=\s*"([^"]*)"/', (string) $text, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$atts[ $match[1] ] = $match[2];
		}
	}

	return $atts;
}

function do_shortcode( $content ) {
	return preg_replace_callback(
		'/' . get_shortcode_regex( array_keys( $GLOBALS['shortcode_tags'] ) ) . '/',
		static function ( $matches ) {
			if ( '[' === $matches[1] ) {
				return substr( $matches[0], 1 );
			}

			$tag      = $matches[2];
			$attr     = shortcode_parse_atts( $matches[3] );
			$callback = $GLOBALS['shortcode_tags'][ $tag ];

			return call_user_func( $callback, $attr, isset( $matches[5] ) ? $matches[5] : null, $tag );
		},
		$content
	);
}

$harness_plugin_dir = getenv( 'YBY_HARNESS_PLUGIN_DIR' );
$harness_plugin_dir = is_string( $harness_plugin_dir ) && '' !== $harness_plugin_dir ? rtrim( $harness_plugin_dir, '/\\' ) : dirname( __DIR__ );

require_once $harness_plugin_dir . '/inc/class-yby-inquiry-shortcodes.php';

class Harness_Inquiry_Manager {
	public function get_preset_manager() {
		return new Harness_Preset_Manager();
	}

	public function get_field_manager() {
		return new Harness_Field_Manager();
	}
}

class Harness_Preset_Manager {
	public function get_preset( $preset_id ) {
		$presets = array(
			'irrigation_quick_inquiry' => array(
				'id'               => 'irrigation_quick_inquiry',
				'label'            => 'Irrigation Quick Inquiry',
				'enabled'          => true,
				'version'          => '1.0',
				'fields'           => array( 'name', 'email', 'whatsapp', 'country', 'farm_size', 'message' ),
				'validation'       => array( 'contact_requirement' => 'email_or_whatsapp' ),
				'source_component' => 'inquiry_modal',
			),
			'bottle_wholesale_inquiry' => array(
				'id'               => 'bottle_wholesale_inquiry',
				'label'            => 'Bottle Wholesale Inquiry',
				'enabled'          => true,
				'version'          => '1.0',
				'fields'           => array( 'name', 'company', 'email', 'whatsapp', 'country', 'product_interest', 'quantity', 'customization', 'message' ),
				'validation'       => array( 'contact_requirement' => 'email_or_whatsapp' ),
				'source_component' => 'inquiry_modal',
			),
			'disabled_inquiry' => array(
				'id'               => 'disabled_inquiry',
				'label'            => 'Disabled',
				'enabled'          => false,
				'version'          => '1.0',
				'fields'           => array( 'name' ),
				'validation'       => array(),
				'source_component' => 'inquiry_modal',
			),
		);

		return isset( $presets[ $preset_id ] ) ? $presets[ $preset_id ] : null;
	}
}

class Harness_Field_Manager {
	public function get_fields() {
		return array(
			'name'             => array( 'id' => 'name', 'type' => 'text', 'enabled' => true ),
			'company'          => array( 'id' => 'company', 'type' => 'text', 'enabled' => true ),
			'email'            => array( 'id' => 'email', 'type' => 'email', 'enabled' => true ),
			'whatsapp'         => array( 'id' => 'whatsapp', 'type' => 'tel', 'enabled' => true ),
			'country'          => array( 'id' => 'country', 'type' => 'text', 'enabled' => true ),
			'farm_size'        => array( 'id' => 'farm_size', 'type' => 'text', 'enabled' => true ),
			'product_interest' => array( 'id' => 'product_interest', 'type' => 'text', 'enabled' => true ),
			'quantity'         => array( 'id' => 'quantity', 'type' => 'text', 'enabled' => true ),
			'customization'    => array( 'id' => 'customization', 'type' => 'textarea', 'enabled' => true ),
			'message'          => array( 'id' => 'message', 'type' => 'textarea', 'enabled' => true ),
		);
	}
}

class Harness_Renderer {
	public function render_modal( $preset, $fields, $attributes = array() ) {
		if ( ! is_array( $preset ) || empty( $preset['id'] ) || empty( $fields ) ) {
			return '';
		}

		return '<div id="' . $attributes['id'] . '" class="yby-inquiry-modal" data-yby-inquiry-modal data-yby-preset="' . $preset['id'] . '"></div>';
	}
}

function harness_property_value( $class, $name ) {
	$reflection = new ReflectionProperty( $class, $name );
	$reflection->setAccessible( true );
	return $reflection->getValue();
}

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function harness_state() {
	return array(
		'modal_ids' => array_keys( harness_property_value( 'YBY_Inquiry_Shortcodes', 'deferred_modals' ) ),
		'cache'     => harness_property_value( 'YBY_Inquiry_Shortcodes', 'content_render_cache' ),
	);
}

$shortcodes = new YBY_Inquiry_Shortcodes( new Harness_Inquiry_Manager(), new Harness_Renderer() );
$shortcodes->register();

$tests = array();

$tests['repeated_content'] = static function () use ( $shortcodes ) {
	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 632 );

	$content = '[yby_inquiry_modal id="irrigation-inquiry-test" preset="irrigation_quick_inquiry" title="Irrigation Inquiry Test" submit_label="Submit Irrigation Inquiry"]'
		. "\n"
		. '[yby_inquiry_modal id="bottle-inquiry-test" preset="bottle_wholesale_inquiry" title="Bottle Inquiry Test" submit_label="Submit Bottle Inquiry"]';

	$first  = $shortcodes->capture_modal_shortcodes_in_content( $content );
	$second = $shortcodes->capture_modal_shortcodes_in_content( $content );
	$state  = harness_state();

	harness_assert( $first === $second, 'Repeated content should return cached transformed output.' );
	harness_assert( 2 === count( $state['modal_ids'] ), 'Repeated content should defer exactly two modals.' );
	harness_assert( ! in_array( 'irrigation-inquiry-test-2', $state['modal_ids'], true ), 'Repeated content must not create irrigation -2.' );
	harness_assert( ! in_array( 'bottle-inquiry-test-2', $state['modal_ids'], true ), 'Repeated content must not create bottle -2.' );
};

$tests['intentional_duplicate'] = static function () use ( $shortcodes ) {
	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 700 );

	$content = '[yby_inquiry_modal id="irrigation-inquiry-test" preset="irrigation_quick_inquiry"]'
		. "\n"
		. '[yby_inquiry_modal id="irrigation-inquiry-test" preset="irrigation_quick_inquiry"]';

	$shortcodes->capture_modal_shortcodes_in_content( $content );
	$state = harness_state();

	harness_assert( in_array( 'irrigation-inquiry-test', $state['modal_ids'], true ), 'First duplicate should keep base ID.' );
	harness_assert( in_array( 'irrigation-inquiry-test-2', $state['modal_ids'], true ), 'Second duplicate should receive -2.' );
	harness_assert( 2 === count( $state['modal_ids'] ), 'Intentional duplicate should produce two modals.' );
};

$tests['different_presets'] = static function () use ( $shortcodes ) {
	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 701 );

	$content = '[yby_inquiry_modal id="irrigation-inquiry-test" preset="irrigation_quick_inquiry"]'
		. "\n"
		. '[yby_inquiry_modal id="bottle-inquiry-test" preset="bottle_wholesale_inquiry"]';

	$shortcodes->capture_modal_shortcodes_in_content( $content );
	$state = harness_state();

	harness_assert( $state['modal_ids'] === array( 'irrigation-inquiry-test', 'bottle-inquiry-test' ), 'Different presets should render one modal each.' );
};

$tests['request_isolation'] = static function () use ( $shortcodes ) {
	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 702 );

	$content = '[yby_inquiry_modal id="irrigation-inquiry-test" preset="irrigation_quick_inquiry"]';

	$shortcodes->capture_modal_shortcodes_in_content( $content );
	$first = harness_state();

	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 703 );

	$shortcodes->capture_modal_shortcodes_in_content( $content );
	$second = harness_state();

	harness_assert( 1 === count( $first['cache'] ), 'First request should populate one cache entry.' );
	harness_assert( 1 === count( $second['cache'] ), 'Second request should populate one fresh cache entry.' );
	harness_assert( $second['modal_ids'] === array( 'irrigation-inquiry-test' ), 'Request reset should clear modal suffix state.' );
};

$tests['invalid_preset'] = static function () use ( $shortcodes ) {
	YBY_Inquiry_Shortcodes::reset_request_state();
	$GLOBALS['post'] = (object) array( 'ID' => 704 );

	$content = '[yby_inquiry_modal id="invalid-test" preset="missing_preset"]';
	$result  = $shortcodes->capture_modal_shortcodes_in_content( $content );
	$state   = harness_state();

	harness_assert( '' === trim( $result ), 'Invalid preset should produce no modal output.' );
	harness_assert( 0 === count( $state['modal_ids'] ), 'Invalid preset should defer no modals.' );
};

$results = array();

foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
