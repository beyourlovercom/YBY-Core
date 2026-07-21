<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'YBY_CORE_VERSION' ) ) {
	define( 'YBY_CORE_VERSION', '1.3.0-dev' );
}

if ( ! defined( 'YBY_CORE_PLUGIN_DIR' ) ) {
	define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'YBY_CORE_PLUGIN_URL' ) ) {
	define( 'YBY_CORE_PLUGIN_URL', 'https://plugin.example.test/' );
}

$GLOBALS['yby_option_store']   = array();
$GLOBALS['yby_enqueued']       = array();
$GLOBALS['yby_inline_styles']  = array();
$GLOBALS['yby_inline_scripts'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['yby_option_store'] ) ? $GLOBALS['yby_option_store'][ $key ] : $default;
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

function sanitize_email( $value ) {
	return filter_var( trim( (string) $value ), FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function home_url( $path = '/' ) {
	return 'https://frontend.example.test/' . ltrim( (string) $path, '/' );
}

function wp_parse_url( $value ) {
	return parse_url( (string) $value );
}

function wp_rand( $min = 0, $max = 0 ) {
	static $counter = 0;
	++$counter;

	return $min + ( $counter % max( 1, ( $max - $min + 1 ) ) );
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

function wp_enqueue_style( $handle, $src, $deps = array(), $ver = false ) {
	$GLOBALS['yby_enqueued']['style'][ $handle ] = compact( 'src', 'deps', 'ver' );
}

function wp_enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['yby_enqueued']['script'][ $handle ] = compact( 'src', 'deps', 'ver', 'in_footer' );
}

function wp_add_inline_style( $handle, $data ) {
	$GLOBALS['yby_inline_styles'][ $handle ] = $data;
}

function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	$GLOBALS['yby_inline_scripts'][ $handle ] = array(
		'data'     => $data,
		'position' => $position,
	);
}

class YBY_Helpers {
	public static function option_key() {
		return 'yby_core_options';
	}

	public static function lead_recipient_option_key() {
		return 'yby_lead_recipient_email';
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

class YBY_Project {
	public static function get_current_project() {
		return array( 'projectId' => 'project-1' );
	}
}

class YBY_Page_Profile {
	public static function get_current_profile() {
		return array( 'profileId' => 'page-1' );
	}
}

class YBY_Content {
	public static function get_current_content() {
		return array( 'sections' => array() );
	}
}

class YBY_Project_Template {
	public static function get_current_template() {
		return array( 'templateId' => 'template-1' );
	}
}

class YBY_Tracking {
	public function get_frontend_config() {
		return array( 'events' => array(), 'enabled' => true );
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-config.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-site-profile.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-brand-profile.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-case-id.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-lead-session.php';
require_once dirname( __DIR__ ) . '/public/class-yby-public.php';

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$GLOBALS['yby_option_store'] = array(
	'admin_email' => 'admin@frontend.example.test',
	YBY_Helpers::option_key() => array(
		'site_brand_key'           => 'yby_bottle',
		'site_brand_name'          => 'YBY Bottle',
		'case_id_brand_code'       => 'BOT',
		'website_url'              => 'https://bottle.example.test/',
		'brand_primary_color'      => '#8B5E3C',
		'brand_primary_text_color' => '#FFFFFF',
		'brand_secondary_color'    => '#1F2937',
		'brand_surface_color'      => '#FAFAFA',
		'brand_text_color'         => '#111827',
		'brand_muted_text_color'   => '#6B7280',
		'brand_border_color'       => '#D1D5DB',
		'catalog_url'              => 'https://bottle.example.test/catalog.pdf',
		'youtube_video_id'         => 'Bottle123',
		'support_email'            => 'support@bottle.example.test',
		'crm_webhook_url'          => 'https://crm.example.test/hooks/SECRET-TOKEN-123',
		'default_country'          => '',
		'default_product_interest' => 'glass bottle wholesale',
		'thank_you_url'            => '/thank-you-bottle/',
		'return_page_url'          => '/bottle-products/',
		'whatsapp_number'          => '+8613812345678',
		'whatsapp_message_template'=> "Hello {brand_name}\n\nCase: {case_id}",
	),
);

$public = new YBY_Public( 'yby-core', YBY_CORE_VERSION );
$public->enqueue_assets();

$style  = $GLOBALS['yby_inline_styles']['yby-inquiry-components'];
$script = $GLOBALS['yby_inline_scripts']['yby-core-public']['data'];

harness_assert( false !== strpos( $style, '--yby-inquiry-primary:#8B5E3C' ), 'Inline CSS must include configured primary color.' );
harness_assert( false !== strpos( $style, '--yby-inquiry-secondary:#1F2937' ), 'Inline CSS must include configured secondary color.' );
harness_assert( false === strpos( $style, 'javascript:' ), 'Inline CSS must not include unsafe raw values.' );
harness_assert( false !== strpos( $script, '"siteBrandKey":"yby_bottle"' ), 'Runtime config must expose siteBrandKey.' );
harness_assert( false !== strpos( $script, '"siteBrandName":"YBY Bottle"' ), 'Runtime config must expose siteBrandName.' );
harness_assert( false !== strpos( $script, '"caseIdBrandCode":"BOT"' ), 'Runtime config must expose caseIdBrandCode.' );
harness_assert( false !== strpos( $script, '"websiteUrl":"https:\\/\\/bottle.example.test\\/"' ), 'Runtime config must expose websiteUrl.' );
harness_assert( false !== strpos( $script, '"thankYouUrl":"\\/thank-you-bottle\\/"' ), 'Runtime config must expose thankYouUrl.' );
harness_assert( false !== strpos( $script, '"returnPageUrl":"\\/bottle-products\\/"' ), 'Runtime config must expose returnPageUrl.' );
harness_assert( false !== strpos( $script, '"catalogUrl":"https:\\/\\/bottle.example.test\\/catalog.pdf"' ), 'Runtime config must expose catalogUrl.' );
harness_assert( false !== strpos( $script, '"youtubeVideoId":"Bottle123"' ), 'Runtime config must expose youtubeVideoId.' );
harness_assert( false !== strpos( $script, '"whatsappNumber":"+8613812345678"' ), 'Runtime config must expose whatsappNumber.' );
harness_assert( false !== strpos( $script, '"whatsappMessageTemplate":"Hello {brand_name}\\n\\nCase: {case_id}"' ), 'Runtime config must expose whatsappMessageTemplate.' );
harness_assert( false !== strpos( $script, '"supportEmail":"support@bottle.example.test"' ), 'Runtime config must expose supportEmail.' );
harness_assert( false === strpos( $script, 'crmWebhookUrl' ), 'Runtime config must not expose crmWebhookUrl.' );
harness_assert( false === strpos( $script, 'SECRET-TOKEN-123' ), 'Runtime config must not expose CRM webhook secret material.' );
harness_assert( false === strpos( $script, 'lead_notification_primary_recipient' ), 'Runtime config must not expose recipient option keys.' );
harness_assert( false === strpos( $script, 'smtp' ), 'Runtime config must not expose SMTP settings.' );
harness_assert( false === strpos( $script, 'admin@frontend.example.test' ), 'Runtime config must not expose admin_email fallback.' );

echo "frontend_runtime:PASS\n";
