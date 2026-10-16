<?php
/** Isolated executable U1-U4 updater channel policy matrix; zero network/WordPress writes. */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['u_channel'] = 'disabled';
$GLOBALS['u_auto'] = false;
$GLOBALS['u_host'] = 'www.beyourlover.com';
function get_option( $key, $default = false ) {
    return $key === 'yby_core_update_channel' ? $GLOBALS['u_channel'] : ($key === 'yby_core_automatic_updates' ? $GLOBALS['u_auto'] : $default);
}
function home_url() { return 'https://' . $GLOBALS['u_host'] . '/'; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function plugin_basename( $file ) { return 'yby-core/yby-core.php'; }
class YBY_GitHub_Release_Client {}
// Isolated cache stubs for same-version update-offer and post-install cleanup.
$GLOBALS['u_verified_release'] = false;
$GLOBALS['u_pending'] = false;
$GLOBALS['u_deleted_keys'] = array();
function get_site_transient( $key ) {
    if ('yby_core_verified_release_canary' === $key) return $GLOBALS['u_verified_release'];
    if ('yby_core_updater_pending' === $key) return $GLOBALS['u_pending'];
    return false;
}
function delete_site_transient( $key ) {
    $GLOBALS['u_deleted_keys'][] = $key;
    if ('yby_core_updater_pending' === $key) $GLOBALS['u_pending'] = false;
    return true;
}
function is_wp_error( $item ) { return $item instanceof WP_Error; }
class WP_Error { public function __construct() {} }
class YBY_Update_Verifier {
    public static function is_newer( $candidate, $current ) {
        return version_compare( $candidate, $current, '>' );
    }
    public static function stable_version( $version ) { return (string) $version; }
}
class YBY_Update_Backup {
    public static function health( $path, $version ) { return $version === '2.2.5'; }
}
function get_current_user_id() { return 0; }
function get_bloginfo( $key ) { return '6.7'; }
require dirname( __DIR__ ) . '/inc/class-yby-updater.php';
$updater = new YBY_Updater('/test/plugins/yby-core/yby-core.php', '2.2.0');
$reflect = new ReflectionMethod(YBY_Updater::class, 'allowed_release');
$assertions = 0;
foreach (array(
  array('www.beyourlover.com','stable',true,false),
  array('www.beyourlover.com','canary',true,false),
  array('www.beyourlover.com','disabled',true,false),
  array('dev.ybybottle.com','stable',true,false),
  array('dev.ybybottle.com','canary',true,true),
  array('dev.beyourlover.com','canary',false,true),
  array('dev.beyourlover.com','canary',true,true),
  array('ybybottle.com','disabled',true,false),
  array('ybybottle.com','canary',true,false),
  array('ybybottle.com','stable',true,true),
  array('ybyirrigation.com','stable',false,true),
  array('unexpected.example','stable',true,false),
) as $case) {
  [$GLOBALS['u_host'],$GLOBALS['u_channel'],$GLOBALS['u_auto'],$expect] = $case;
  $actual = $reflect->invoke($updater);
  if ($actual !== $expect) throw new RuntimeException('Fail-closed release eligibility: '.implode('/',$case));
  ++$assertions;
  $update = (object)['plugin'=>'yby-core/yby-core.php'];
  $should_auto = $expect && $GLOBALS['u_auto'];
  if ($updater->automatic_updates(false, $update) !== $should_auto) throw new RuntimeException('Unexpected auto-update policy');
  ++$assertions;
  $offer = (object)['response'=>['yby-core/yby-core.php'=>(object)['new_version'=>'999.0.0']]];
  if (!$expect) {
      $out = $updater->filter_updates($offer);
      if(isset($out->response['yby-core/yby-core.php'])) throw new RuntimeException('Stale excluded update not removed');
      ++$assertions;
  }
}
// Regression: after 2.2.4 installs, a cached 2.2.4 response is STALE,
// and the signed downloader must not be offered an equal-version ZIP again.
$GLOBALS['u_host']='dev.beyourlover.com';
$GLOBALS['u_channel']='canary';
$latest = new YBY_Updater('/test/plugins/yby-core/yby-core.php','2.2.4');
$GLOBALS['u_verified_release']=array('version'=>'2.2.4','sha256'=>str_repeat('a',64),
  'release_url'=>'https://example.invalid/release',
  'package_url'=>'https://example.invalid/package');
$old_offer=(object)array('response'=>array(
  'yby-core/yby-core.php'=>(object)array('new_version'=>'2.2.4'),
  'another-plugin/example.php'=>(object)array('new_version'=>'5.0.0')
));
$out=$latest->filter_updates($old_offer);
if (isset($out->response['yby-core/yby-core.php']))
    throw new RuntimeException('Same-version Andy Core stale update offer remains');
if (!isset($out->response['another-plugin/example.php']))
    throw new RuntimeException('Unrelated plugin update offer was removed');
$assertions+=2;

// A verified higher version remains discoverable and preserves other offers.
$GLOBALS['u_verified_release']['version']='2.2.5';
$new_offer=$latest->filter_updates((object)array('response'=>array(
  'another-plugin/example.php'=>(object)array('new_version'=>'5.0.0')
)));
if (($new_offer->response['yby-core/yby-core.php']->new_version??null)!=='2.2.5')
    throw new RuntimeException('Genuine newer signed release was not offered');
if (!isset($new_offer->response['another-plugin/example.php']))
    throw new RuntimeException('Updating Andy Core suppressed unrelated plugin');
$assertions+=2;

// On confirmed successful install, WordPress's previous plugin update list
// must be invalidated while preserving the exact backup validation gate.
$GLOBALS['u_pending']=array('target_version'=>'2.2.5','backup_reference'=>'/tmp/verified-backup');
$GLOBALS['u_deleted_keys']=array();
$latest->validate_install(null,array('plugin'=>'yby-core/yby-core.php'));
if ($GLOBALS['u_deleted_keys']!==array('yby_core_updater_pending','update_plugins'))
    throw new RuntimeException('Successful install did not clear stale update_plugins transient');
$assertions++;

// On an older release, even a previously cached newer-looking offer must go.
$GLOBALS['u_verified_release']['version']='2.2.3';
$expired=$latest->filter_updates((object)array('response'=>array(
    'yby-core/yby-core.php'=>(object)array('new_version'=>'2.2.4')
)));
if (isset($expired->response['yby-core/yby-core.php']))
    throw new RuntimeException('Older-than-installed release retained a stale offer');
$assertions++;
echo "PASS U1-U4 runtime policy matrix assertions={$assertions} no_network=1 no_writes=1\n";
