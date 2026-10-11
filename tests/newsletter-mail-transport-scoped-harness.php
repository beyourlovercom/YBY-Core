<?php
/**
 * No-network / no-WordPress scoped SMTP transport harness.
 * Each check uses synthetic example.test mail and a fake PHPMailer object.
 */
define( 'ABSPATH', __DIR__ );
define( 'YBY_MAIL_TRANSPORT_ENABLED', true );
define( 'YBY_MAIL_TRANSPORT_DRIVER', 'smtp' );
$GLOBALS['mt_hooks'] = array();
$GLOBALS['mt_attempts'] = 0;
$GLOBALS['mt_sender_calls'] = 0;
$GLOBALS['mt_last_mailer'] = null;
$GLOBALS['mt_assertions'] = 0;
function mt_assert( $bool, $label ) {
    $GLOBALS['mt_assertions']++;
    if ( ! $bool ) { throw new RuntimeException( 'MAIL_TRANSPORT_TEST_FAIL: ' . $label ); }
}
function add_action( $hook, $fn, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['mt_hooks'][ $hook ][ $priority ][] = $fn;
}
function remove_action( $hook, $fn, $priority = 10 ) {
    foreach ( $GLOBALS['mt_hooks'][ $hook ][ $priority ] ?? array() as $i => $callback ) {
        if ( $callback === $fn ) { unset( $GLOBALS['mt_hooks'][ $hook ][ $priority ][ $i ] ); }
    }
}
function add_filter( $hook, $fn, $priority = 10, $accepted_args = 1 ) {
    add_action( $hook, $fn, $priority, $accepted_args );
}
function remove_filter( $hook, $fn, $priority = 10 ) {
    remove_action( $hook, $fn, $priority );
}
function apply_filters( $name, $value ) {
    $arr = $GLOBALS['mt_hooks'][ $name ] ?? array();
    ksort( $arr );
    foreach ( $arr as $list ) { foreach ( $list as $cb ) { $value = $cb( $value ); } }
    return $value;
}
function wp_mail( $to, $subject, $message ) {
    $GLOBALS['mt_attempts']++;
    // Stub emulates Dev Safety's veto, including a non-null false result.
    if ( false === apply_filters( 'pre_wp_mail', null ) ) { return false; }
    $mailer = new class {
        public $is_smtp = false;
        public $Host = ''; public $Port = 0; public $SMTPSecure = '';
        public $SMTPAuth = false; public $Username = ''; public $Password = '';
        public $SMTPAutoTLS = false; public $SMTPKeepAlive = null; public $Timeout = 0;
        public $SMTPOptions = array();
        public function isSMTP() { $this->is_smtp = true; }
    };
    $hooks = $GLOBALS['mt_hooks']['phpmailer_init'] ?? array();
    ksort( $hooks );
    foreach ( $hooks as $list ) { foreach ( $list as $cb ) { $cb( $mailer ); } }
    $GLOBALS['mt_last_mailer'] = $mailer;
    $GLOBALS['mt_sender_calls']++;
    return true;
}
require dirname( __DIR__ ) . '/inc/class-yby-mail-transport.php';

$cfg = array(
    'enabled' => true, 'driver' => 'smtp',
    'host' => 'smtp.example.test', 'port' => '587', 'security' => 'tls',
    'user' => 'synthetic@example.test', 'password' => 'CI_ONLY_NEVER_REAL',
    'from' => 'newsletter@example.test', 'from_name' => 'BeYourLover Newsletter',
);
foreach ( array(
    'YBY_MAIL_SMTP_HOST' => $cfg['host'], 'YBY_MAIL_SMTP_PORT' => $cfg['port'],
    'YBY_MAIL_SMTP_SECURITY' => $cfg['security'], 'YBY_MAIL_SMTP_USER' => $cfg['user'],
    'YBY_MAIL_SMTP_PASSWORD' => $cfg['password'], 'YBY_MAIL_FROM_EMAIL' => $cfg['from'],
    'YBY_MAIL_FROM_NAME' => $cfg['from_name'],
) as $key => $value ) { putenv( $key . '=' . $value ); }

mt_assert( YBY_Mail_Transport::readiness()['ready'] === true, 'Complete server-only SMTP readiness' );
mt_assert( YBY_Mail_Transport::readiness()['reason'] === 'configured_not_delivery_verified', 'Do not claim inbox receipt' );
mt_assert( ! str_contains( json_encode( YBY_Mail_Transport::readiness() ), $cfg['password'] ), 'No password in status' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'enabled' => false ) ) ) === null, 'Default disabled rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'driver' => 'wp_mail' ) ) ) === null, 'No transport fallback' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'security' => 'none' ) ) ) === null, 'Plaintext SMTP rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'port' => '25' ) ) ) === null, 'Port 25 rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'host' => "bad\r\nhost" ) ) ) === null, 'CRLF hostname rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'host' => 'a..b.com' ) ) ) === null, 'Ambiguous hostname rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'from' => 'invalid' ) ) ) === null, 'Invalid From denied' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'from_name' => "Name\nBcc:fake" ) ) ) === null, 'Header injection rejected' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'port' => '465', 'security' => 'ssl' ) ) ) !== null, 'Explicit implicit TLS/465 accepted' );
mt_assert( YBY_Mail_Transport::validated_config( array_merge( $cfg, array( 'port' => '465', 'security' => 'tls' ) ) ) === null, 'TLS/port mismatch rejected' );
mt_assert( YBY_Mail_Transport::send_newsletter_confirmation( 'notmail', 'Confirm your newsletter subscription', 'text' ) === false, 'Invalid To denied' );
mt_assert( YBY_Mail_Transport::send_newsletter_confirmation( 'a@example.test', 'Customer order receipt', 'text' ) === false, 'Woo/order email rejected' );
mt_assert( $GLOBALS['mt_attempts'] === 0, 'No mail attempted for invalid request' );
add_filter( 'pre_wp_mail', static function () { return false; }, 99 );
mt_assert( YBY_Mail_Transport::send_newsletter_confirmation( 'a@example.test', 'Confirm your newsletter subscription', 'text' ) === false,
    'Shared Dev Safety veto remains authoritative' );
mt_assert( $GLOBALS['mt_attempts'] === 1 && $GLOBALS['mt_sender_calls'] === 0,
    'Blocked email cannot instantiate SMTP' );
mt_assert( empty( $GLOBALS['mt_hooks']['phpmailer_init'][99999] ), 'SMTP handler removed after veto' );
unset( $GLOBALS['mt_hooks']['pre_wp_mail'] );
mt_assert( YBY_Mail_Transport::send_newsletter_confirmation( 'a@example.test', 'Confirm your newsletter subscription', 'text' ),
    'Synthetic stub accepts correctly scoped confirmation' );
$mailer = $GLOBALS['mt_last_mailer'];
mt_assert( $mailer->is_smtp && $mailer->Host === $cfg['host'] && $mailer->Port === 587 &&
    $mailer->SMTPSecure === 'tls' && $mailer->SMTPAuth && $mailer->SMTPAutoTLS, 'Real TLS config assigned only to scoped synthetic mailer' );
mt_assert( $mailer->SMTPOptions['ssl']['verify_peer'] === true &&
    $mailer->SMTPOptions['ssl']['verify_peer_name'] === true &&
    $mailer->SMTPOptions['ssl']['allow_self_signed'] === false, 'TLS identity verification enforced' );
mt_assert( empty( $GLOBALS['mt_hooks']['phpmailer_init'][99999] ) &&
    empty( $GLOBALS['mt_hooks']['wp_mail_from'][99999] ) &&
    empty( $GLOBALS['mt_hooks']['wp_mail_from_name'][99999] ), 'All transient hooks removed after successful stub send' );
$mailerBefore = $GLOBALS['mt_last_mailer'];
mt_assert( wp_mail( 'woo@example.test', 'Other mail', 'stub' ) && $GLOBALS['mt_last_mailer']->is_smtp === false,
    'Ordinary Woo/WordPress mail uses no Andy Core SMTP overrides' );
putenv( 'YBY_MAIL_SMTP_PASSWORD' );
mt_assert( ! YBY_Mail_Transport::readiness()['ready'] &&
    ! YBY_Mail_Transport::send_newsletter_confirmation( 'a@example.test', 'Confirm your newsletter subscription', 'text' ),
    'Missing host-only secret fails closed before any mail attempt' );
mt_assert( $GLOBALS['mt_attempts'] === 3, 'No extra wp_mail attempt after bad config' );
echo 'NEWSLETTER_MAIL_TRANSPORT_SCOPED_NO_NETWORK_PASS '
    . $GLOBALS['mt_assertions'] . " cases; 0 SMTP connections; 0 real mail\n";
