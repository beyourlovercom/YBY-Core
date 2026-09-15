<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }

class YBY_Email_Template_Store {
	public static function is_native_provider( $provider ) {
		return in_array( (string) $provider, array( 'wordpress', 'andy_core' ), true );
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-email-erp-contract.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$hash = str_repeat( 'a', 64 );
$identity = array(
	'template_key' => 'wordpress:reset_password',
	'provider' => 'wordpress',
	'label' => 'Reset Password',
	'runtime_enabled' => true,
);
$snapshot = array(
	'version_number' => 3,
	'content_hash' => $hash,
	'published_at' => '2026-09-15 17:00:00',
	'payload' => array( 'subject' => 'SECRET CONTENT' ),
	'working_payload' => array( 'subject' => 'DRAFT' ),
	'api_key' => 'never-export-this',
);

$record = YBY_Email_ERP_Contract::project_native_published( $identity, $snapshot );
$assert( YBY_Email_ERP_Contract::VERSION === 'email-os-published-v1', 'contract version' );
$assert( true === YBY_Email_ERP_Contract::is_read_only(), 'contract must be read-only' );
$assert( array_keys( $record ) === YBY_Email_ERP_Contract::fields(), 'record fields must match frozen order exactly' );
$assert( 'published' === $record['status'], 'status must be published' );
$assert( 3 === $record['published_version'], 'published version projection' );
$assert( $hash === $record['content_hash_sha256'], 'hash projection' );

foreach ( YBY_Email_ERP_Contract::forbidden_fields() as $field ) {
	$assert( ! array_key_exists( $field, $record ), 'forbidden field leaked: ' . $field );
}

$woo = $identity;
$woo['template_key'] = 'woocommerce:new_order';
$woo['provider'] = 'woocommerce';
$assert( array() === YBY_Email_ERP_Contract::project_native_published( $woo, $snapshot ), 'Woo must be excluded' );

$bad = $snapshot;
$bad['content_hash'] = 'bad';
$assert( array() === YBY_Email_ERP_Contract::project_native_published( $identity, $bad ), 'invalid hash must fail closed' );

$missing = $snapshot;
unset( $missing['published_at'] );
$assert( array() === YBY_Email_ERP_Contract::project_native_published( $identity, $missing ), 'incomplete published metadata must fail closed' );

$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-email-erp-contract.php' );
foreach ( array( 'register_rest_route', 'wp_mail(', 'wp_update_post', 'update_option(', '$wpdb->insert', '$wpdb->update' ) as $mutation ) {
	$assert( false === strpos( $source, $mutation ), 'P8A contract class must not expose route/send/write behavior: ' . $mutation );
}

$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$assert( false !== strpos( $core, "class-yby-email-erp-contract.php" ), 'contract bootstrap missing' );

$connector = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector.php' );
$assert( false !== strpos( $connector, "'/snapshot/email-templates'" ) && false === strpos( $connector, "'/snapshot/email-templates', array( 'methods' => 'POST'" ), 'P8B must expose the frozen resource as GET-only' );

$doc = file_get_contents( dirname( __DIR__ ) . '/docs/v1.5.8/ANDY-CORE-EMAIL-OS-P8A-ERP-READONLY-CONTRACT.md' );
foreach ( array(
	'GET /wp-json/andy-core/v1/erp/snapshot/email-templates',
	'email-os-published-v1',
	'ERP V1 receives no draft or template-content payload',
	'POST / PUT / PATCH / DELETE template routes',
	'WooCommerce is excluded from this Published Snapshot resource',
) as $needle ) {
	$assert( false !== strpos( $doc, $needle ), 'frozen contract doc missing: ' . $needle );
}

echo "PASS email-os-erp-contract-harness\n";
