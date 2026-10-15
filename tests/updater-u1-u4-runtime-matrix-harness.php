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
echo "PASS U1-U4 runtime policy matrix assertions={$assertions} no_network=1 no_writes=1\n";
