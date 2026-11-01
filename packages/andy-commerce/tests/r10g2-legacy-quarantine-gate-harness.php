<?php
/**
 * R10-G2: mocked no-site-write contract. php ... [denied|authorized]
 * Authorization is process-local. Never writes WordPress options.
 */
declare(strict_types=1);
if(!defined('ABSPATH'))define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
$mode=$argv[1]??'denied';
if(!in_array($mode,['denied','authorized','sandbox'],true))exit(3);
if($mode==='authorized'){
 define('ANDY_COMMERCE_R10G2_OWNER_LEGACY_AUTH','OWNER_APPROVED_R10G2_DEV_LEGACY');
}
if($mode==='sandbox'){
 define('AICC_CURRENCY_RUNTIME_SANDBOX',true);
 define('DB_NAME','commerce_p5_isolated');
 define('WP_HTTP_BLOCK_EXTERNAL',true);
 define('DISABLE_WP_CRON',true);
}
$GLOBALS['g2_opts']=[
 'siteurl'=>'https://dev.beyourlover.com',
 'andy_commerce_r10g2_legacy_shipping_enabled_v1'=>'no',
 'andy_commerce_r10g2_legacy_currency_enabled_v1'=>'no',
 'andy_commerce_r10g2_legacy_marketing_enabled_v1'=>'no',
];
$GLOBALS['g2_env']='staging';
function get_option(string $name,$default=null){return $GLOBALS['g2_opts'][$name]??$default;}
function wp_get_environment_type():string {return $GLOBALS['g2_env'];}
class Andy_Commerce_Observation_Mode {
 public static bool $isObserving=false;
 public static function is_observing():bool{return self::$isObserving;}
}
require dirname(__DIR__).'/includes/class-andy-commerce-r10g2-legacy-runtime-quarantine.php';
$failed=[];$total=0;
function trial(string $case,string $module,bool $expect):void{
 global $failed,$total;
 $actual=Andy_Commerce_R10G2_Legacy_Runtime_Quarantine::allowed($module);
 $total++;
 echo 'R10G2_'.strtoupper($case).'='.($actual===$expect?'PASS':'FAIL')."\n";
 if($actual!==$expect)$failed[]=$case;
}
trial('shipping_default_off','shipping',false);
trial('currency_default_off','currency',false);
trial('marketing_default_off','marketing',false);
trial('unknown_module','offspec',false);
foreach(['shipping','currency','marketing'] as $m){
 $key='andy_commerce_r10g2_legacy_'.$m.'_enabled_v1';
 $GLOBALS['g2_opts'][$key]='yes';
 trial($m.'_wordpres_option_alone',$m,$mode==='authorized');
 $GLOBALS['g2_opts'][$key]='no';
}
$GLOBALS['g2_opts']['andy_commerce_r10g2_legacy_shipping_enabled_v1']='yes';
$GLOBALS['g2_opts']['andy_commerce_r10g2_legacy_currency_enabled_v1']='yes';
$GLOBALS['g2_opts']['andy_commerce_r10g2_legacy_marketing_enabled_v1']='yes';
trial('shipping_full_keys','shipping',$mode==='authorized');
trial('currency_full_keys','currency',$mode==='authorized');
trial('marketing_full_keys','marketing',$mode==='authorized');
Andy_Commerce_Observation_Mode::$isObserving=true;
trial('global_observe_shipping','shipping',false);
trial('global_observe_currency','currency',false);
trial('global_observe_marketing','marketing',false);
Andy_Commerce_Observation_Mode::$isObserving=false;
$GLOBALS['g2_opts']['siteurl']='https://www.beyourlover.com';
trial('www_never_shipping','shipping',false);
trial('www_never_currency','currency',false);
trial('www_never_marketing','marketing',false);
$GLOBALS['g2_opts']['siteurl']='https://dev.beyourlover.com';
$GLOBALS['g2_env']='production';
trial('production_environment_shipping','shipping',false);
trial('production_environment_currency','currency',false);
trial('production_environment_marketing','marketing',false);
$GLOBALS['g2_env']='staging';
$GLOBALS['g2_opts']['siteurl']='https://dev.beyourlover.com.evil.example';
trial('lookalike_host_rejected','shipping',false);
$GLOBALS['g2_opts']['siteurl']='https://dev.beyourlover.com';
$GLOBALS['g2_opts']['andy_commerce_r10g2_legacy_shipping_enabled_v1']='true';
trial('not_exact_yes','shipping',false);
if($mode==='sandbox'){
 $GLOBALS['g2_opts']['siteurl']='http://commerce-p5.invalid';
 $GLOBALS['g2_env']='local';
 $GLOBALS['g2_opts']['andy_commerce_r10g2_legacy_currency_enabled_v1']='no';
 trial('exact_isolated_currency_runtime_allowed','currency',true);
 trial('isolated_shipping_still_denied','shipping',false);
 trial('isolated_marketing_still_denied','marketing',false);
 $GLOBALS['g2_env']='production';
 trial('isolated_wrong_environment_denied','currency',false);
 $GLOBALS['g2_env']='local';
 $GLOBALS['g2_opts']['siteurl']='https://dev.beyourlover.com';
 trial('isolated_wrong_host_denied','currency',false);
}
echo 'R10G2_GATE_MODE='.strtoupper($mode)."\n";
echo 'R10G2_GATE_TOTAL='.($total-count($failed)).'/'.$total."\n";
exit($failed?1:0);
