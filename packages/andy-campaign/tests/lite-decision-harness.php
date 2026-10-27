<?php
/**
 * Standalone contract harness; no external WordPress database or network calls.
 * Run: php packages/andy-campaign/tests/lite-decision-harness.php
 */
declare(strict_types=1);
define( 'ABSPATH', '/' );
$GLOBALS['ac_options'] = array();
$GLOBALS['ac_actions'] = array();
$GLOBALS['ac_boots'] = array();
function get_option($k,$default=false) { return $GLOBALS['ac_options'][$k] ?? $default; }
function update_option($k,$v,$autoload=false) { $GLOBALS['ac_options'][$k]=$v;return true; }
function add_action($hook,$callback,$priority=10,$accepted_args=1) { $GLOBALS['ac_actions'][$hook][]=$callback; }
function do_action($hook,...$args) { foreach($GLOBALS['ac_actions'][$hook]??array() as $callback) { $callback(...$args); } }
function is_admin() { return false; }
function assert_true($value,$message) { if(!$value) { throw new RuntimeException($message); } }

require_once dirname(__DIR__) . '/includes/class-andy-campaign-lite.php';

add_action('andy_campaign_register', static function($host) {
    foreach (array(
        'demo-alpha' => array('cart-discount'),
        'demo-beta' => array('gift'),
        'demo-gamma' => array('cart-discount'),
    ) as $id=>$resources) {
        assert_true($host->register(array('id'=>$id,'name'=>$id,'version'=>'1.0.0',
            'resources'=>$resources,'boot'=>static function($pack) { $GLOBALS['ac_boots'][]=$pack; })),
            'valid pack registration: '.$id);
    }
    assert_true($host->register(array('id'=>'demo-delta','name'=>'Not ready','version'=>'1.0.0',
        'resources'=>array('shipping'),'ready'=>static function() { return false; },
        'boot'=>static function($pack) { $GLOBALS['ac_boots'][]=$pack; })),'register readiness pack');
    assert_true(!$host->register(array('id'=>'../../bad','name'=>'Bad','version'=>'1.0.0',
        'resources'=>array('gift'),'boot'=>static function(){})), 'reject path-like IDs');
    assert_true(!$host->register(array('id'=>'bad-input','name'=>'Bad','version'=>'1.0.0',
        'resources'=>array('remote-code'),'boot'=>static function(){})), 'reject unsupported resource');
},10,1);

$host=Andy_Campaign_Lite::boot();
assert_true(count($host->packs())===4,'four packs registered');
assert_true($host->decisions(1000)['demo-alpha']==='master_off','default OFF');
$GLOBALS['ac_options'][Andy_Campaign_Lite::MASTER]='yes';
$GLOBALS['ac_options'][Andy_Campaign_Lite::SETTINGS]=array(
    'demo-alpha'=>array('enabled'=>true,'start'=>900,'end'=>1200),
    'demo-beta'=>array('enabled'=>true,'start'=>900,'end'=>1200),
    'demo-delta'=>array('enabled'=>true,'start'=>900,'end'=>1200),
    'demo-gamma'=>array('enabled'=>true,'start'=>900,'end'=>1200),
);
$states=$host->decisions(1000);
assert_true($states['demo-alpha']==='eligible','alpha active');
assert_true($states['demo-beta']==='eligible','beta independently active');
assert_true($states['demo-gamma']==='resource_conflict','overlap fail closed');
assert_true($states['demo-delta']==='pack_not_ready','pack readiness fail closed');
assert_true($host->decisions(800)['demo-alpha']==='not_started','start schedule');
assert_true($host->decisions(1200)['demo-alpha']==='expired','end exclusive');
$GLOBALS['ac_options']['andy_commerce_marketing_dev_uat_enabled']='yes';
assert_true($host->decisions(1000)['demo-alpha']==='legacy_engine_active','legacy excludes parallel execution');
unset($GLOBALS['ac_options']['andy_commerce_marketing_dev_uat_enabled']);
$GLOBALS['ac_options'][Andy_Campaign_Lite::MASTER]='no';
$host->launch();
assert_true(count($GLOBALS['ac_boots'])===0,'OFF invokes no pack boot');
echo "ANDY_CAMPAIGN_LITE_DECISIONS_PASS=11\n";
