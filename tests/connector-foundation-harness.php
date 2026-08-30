<?php
/** Focused M1 Connector foundation contract and behavior harness. */
define( 'ABSPATH', __DIR__ );
$options = array();
$autoload = array();
function get_option( $key, $default = false ) { global $options; return array_key_exists( $key, $options ) ? $options[ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload_flag = true ) { global $options, $autoload; $options[ $key ] = $value; $autoload[ $key ] = $autoload_flag; return true; }
function update_option( $key, $value, $autoload_flag = null ) { global $options, $autoload; $options[ $key ] = $value; if ( null !== $autoload_flag ) { $autoload[ $key ] = $autoload_flag; } return true; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function get_bloginfo( $show = '' ) { return '7.1'; }
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';
function connector_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$admin = file_get_contents( $root . '/admin/class-yby-connector-admin.php' );
$settings_admin = file_get_contents( $root . '/admin/class-yby-admin.php' );
$view = file_get_contents( $root . '/admin/views/connector-page.php' );
connector_assert( false !== strpos( $core, "inc/class-yby-connector.php" ) && false !== strpos( $core, "admin/class-yby-connector-admin.php" ), 'Connector classes must be loaded by core.' );
connector_assert( false === strpos( $admin, 'add_submenu_page' ), 'WP-API must not register a standalone submenu.' );
connector_assert( false !== strpos( $settings_admin, "'wp-api'" ) && false !== strpos( $settings_admin, 'YBY_Connector_Admin' ), 'WP-API must render as a Settings tab.' );
connector_assert( false !== strpos( $admin, "current_user_can( 'manage_options' )" ) && false !== strpos( $admin, 'check_admin_referer' ), 'Admin access and nonce gates must remain present.' );
connector_assert( false !== strpos( $view, '<h1>WP-API</h1>' ) && false !== strpos( $view, 'ERP 接口设置 · Andy Core v1.5.3' ), 'Connector header must show the approved identity.' );
connector_assert( false !== strpos( $view, '测试连接' ) && false !== strpos( $view, '保存设置' ), 'Connector actions must be visible.' );
connector_assert( false !== strpos( $view, 'M5 已启用接口安全验证、5 个只读快照与 2 个 Affiliate 写入接口；Coupon/Payout 3 个 POST 接口尚未开放。' ) && false !== strpos( $view, 'M5 已开放 /health、5 个只读快照接口与 2 个 Affiliate 写入接口；Coupon/Payout 3 个 POST 接口尚未开放。' ) && false === strpos( $view, 'M3.1' ), 'M5 WP-API copy must describe the five snapshots, two Affiliate writes, and three unopened Coupon/Payout POST routes truthfully.' );
connector_assert( false !== strpos( $view, '协议版本' ) && false !== strpos( $view, 'readonly' ), 'Read-only foundation identity fields must be rendered.' );
connector_assert( false !== strpos( $view, '安全密钥' ) && false !== strpos( $view, 'yby_connector_secret_nonce' ), 'M2 must provide a gated secret lifecycle action.' );
connector_assert( 'yby_core_connector_options' === YBY_Connector::OPTION, 'Connector must use a dedicated option namespace.' );
connector_assert( 'andy-core/v1/erp' === YBY_Connector::REST_NAMESPACE, 'Connector REST namespace must match the canonical v1.5.3 contract.' );
connector_assert( '未启用' === YBY_Connector::status_label( 'Connector Disabled' ) && '配置错误' === YBY_Connector::status_label( 'Configuration Error' ) && '未安装' === YBY_Connector::status_label( 'Provider Missing' ) && '正常' === YBY_Connector::status_label( 'Ready' ), 'Chinese status labels must match the frozen mapping.' );
connector_assert( '未开放' === YBY_Connector::status_label( 'Not Available' ) && 'neutral' === YBY_Connector::status_class( 'Not Available' ), 'Not Available must use the neutral status presentation.' );
connector_assert( false === strpos( $view, 'Version not available' ), 'ERP Connector must not show a meaningless unavailable-version line.' );
connector_assert( 'Connector Disabled' === YBY_Connector::status(), 'Disabled status must be truthful by default.' );
connector_assert( 'Configuration Error' === YBY_Connector::status( array( 'enabled' => true, 'connection_key' => '', 'key_id' => '' ) ), 'Enabled incomplete identity must be Configuration Error.' );
connector_assert( 'Configuration Error' === YBY_Connector::status( array( 'enabled' => true, 'connection_key' => 'connection-key-123', 'key_id' => 'primary' ) ), 'Identity without a configured secret must remain a Configuration Error.' );
connector_assert( 'beyourlover.com' === YBY_Connector::sanitize( array( 'connection_key' => 'beyourlover.com' ) )['connection_key'], 'Canonical domain-style Connection Key must be accepted.' );
connector_assert( '' === YBY_Connector::sanitize( array( 'connection_key' => 'bad key', 'key_id' => 'bad space' ) )['connection_key'], 'Connection Key must reject whitespace.' );
connector_assert( '' === YBY_Connector::sanitize( array( 'connection_key' => '<b>beyourlover.com</b>' ) )['connection_key'], 'Connection Key must reject HTML.' );
connector_assert( 'a.com' === YBY_Connector::sanitize( array( 'connection_key' => 'a.com' ) )['connection_key'], 'Connection Key must accept a valid short domain-style value.' );
connector_assert( 'primary_key' === YBY_Connector::sanitize( array( 'connection_key' => 'connection-key-123', 'key_id' => 'primary_key' ) )['key_id'], 'Key ID must preserve safe values.' );
$options['unrelated_setting'] = 'preserved';
YBY_Connector::save( array( 'enabled' => true, 'connection_key' => 'beyourlover.com', 'key_id' => 'primary_key' ) );
connector_assert( 'preserved' === $options['unrelated_setting'], 'Connector save must not overwrite unrelated options.' );
connector_assert( false === $autoload[ YBY_Connector::OPTION ], 'Connector option must not autoload.' );
connector_assert( 'beyourlover.com' === $options[ YBY_Connector::OPTION ]['connection_key'], 'Connector save must preserve canonical Connection Key.' );
unset( $options[ YBY_Connector::OPTION ] );
connector_assert( 'Provider Missing' === YBY_Connector::provider_statuses()['woocommerce']['status'] && 'Provider Missing' === YBY_Connector::provider_statuses()['affiliatewp']['status'], 'Absent providers must be detected gracefully.' );
$endpoints = YBY_Connector::endpoint_statuses();
connector_assert( 11 === count( $endpoints ), 'M3.1 must expose exactly eleven contract endpoint rows.' );
$expected_routes = array(
	'GET /health', 'GET /snapshot/affiliates', 'GET /snapshot/coupons', 'GET /snapshot/referrals', 'GET /snapshot/payouts', 'GET /snapshot/subscribers',
	'POST /affiliates/provision', 'POST /affiliates/{affiliate_id}/status', 'POST /coupons/check', 'POST /coupons/provision', 'POST /payouts/complete',
);
$actual_routes = array();
foreach ( $endpoints as $endpoint ) { $actual_routes[] = $endpoint['method'] . ' ' . $endpoint['path']; }
sort( $expected_routes ); sort( $actual_routes );
connector_assert( $expected_routes === $actual_routes, 'Endpoint table must contain exactly the eleven approved routes and no others.' );
connector_assert( 'Connector Disabled' === $endpoints['health']['status'], 'Disabled connector health must report Connector Disabled.' );
connector_assert( 'Provider Missing' === $endpoints['affiliate_snapshot']['status'], 'Implemented Affiliate snapshot must report Provider Missing when AffiliateWP is absent.' );
connector_assert( false === $endpoints['health']['available'] && false === $endpoints['affiliate_snapshot']['available'], 'Disabled health and provider-missing snapshot must remain unavailable.' );
foreach ( array( 'affiliate_snapshot', 'coupon_snapshot', 'referral_snapshot', 'payout_snapshot' ) as $name ) { connector_assert( 'Provider Missing' === $endpoints[ $name ]['status'], 'Implemented snapshot rows must report Provider Missing when providers are absent.' ); } foreach ( array( 'coupon_check', 'coupon_provision', 'payout_complete' ) as $name ) { connector_assert( 'Not Available' === $endpoints[ $name ]['status'], 'Later POST rows must stay Not Available.' ); }
define( 'WC_VERSION', '9.9.9' );
define( 'AFFILIATEWP_VERSION', '2.0.0' );
$providers = YBY_Connector::provider_statuses();
connector_assert( 'Ready' === $providers['woocommerce']['status'] && '9.9.9' === $providers['woocommerce']['version'], 'WooCommerce provider-present detection must expose its real version.' );
connector_assert( 'Ready' === $providers['affiliatewp']['status'] && '2.0.0' === $providers['affiliatewp']['version'], 'AffiliateWP provider-present detection must expose its real version.' );
$options['yby_core_connector_options'] = array( 'enabled' => false, 'connection_key' => 'connection-key-123', 'key_id' => 'primary' );
$endpoints = YBY_Connector::endpoint_statuses();
foreach ( $endpoints as $name => $endpoint ) { $implemented = in_array( $name, array( 'health','affiliate_snapshot','coupon_snapshot','referral_snapshot','payout_snapshot','subscriber_snapshot','affiliate_provision','affiliate_status' ), true ); connector_assert( false === $endpoint['available'] && ( $implemented ? 'Connector Disabled' : 'Not Available' ) === $endpoint['status'], 'Disabled connector must disable implemented routes while later POST rows remain Not Available.' ); }
$options['yby_core_connector_options'] = array( 'enabled' => true, 'connection_key' => 'connection-key-123', 'key_id' => 'primary' );
$endpoints = YBY_Connector::endpoint_statuses();
foreach ( $endpoints as $name => $endpoint ) { $implemented = in_array( $name, array( 'health','affiliate_snapshot','coupon_snapshot','referral_snapshot','payout_snapshot','subscriber_snapshot','affiliate_provision','affiliate_status' ), true ); connector_assert( false === $endpoint['available'] && ( $implemented ? 'Configuration Error' : 'Not Available' ) === $endpoint['status'], 'Incomplete connector must gate implemented routes while later POST rows remain Not Available.' ); }
connector_assert( 'Connector Disabled' === YBY_Connector::status( array( 'enabled' => false, 'connection_key' => 'connection-key-123', 'key_id' => 'primary' ) ), 'Disabled connector state must take precedence over complete identity.' );
connector_assert( 'Configuration Error' === YBY_Connector::status( array( 'enabled' => true, 'connection_key' => 'connection-key-123', 'key_id' => '' ) ), 'Enabled incomplete identity must remain Configuration Error.' );
connector_assert( false !== strpos( $admin, "check_admin_referer( 'yby_connector_save', 'yby_connector_nonce' )" ) && false !== strpos( $admin, "check_admin_referer( 'yby_connector_self_check', 'yby_connector_check_nonce' )" ), 'Save and self-check must use distinct nonce actions.' );
connector_assert( false !== strpos( $admin, 'generate_secret' ) && false !== strpos( $view, '安全密钥' ), 'M2 must provide secret generation without rendering stored values.' );
echo "Connector foundation harness passed.\n";
