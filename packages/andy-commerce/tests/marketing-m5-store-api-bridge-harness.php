<?php
/** M5 mocked Woo Store API registration contract: no DB, Woo or HTTP. */
declare(strict_types=1);
define('ABSPATH','/synthetic');
define('ARRAY_A','array_a');
define('WP_ENVIRONMENT_TYPE','staging');
define('ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED',true);
$GLOBALS['host']='www.beyourlover.com';
$GLOBALS['actions']=[];
$GLOBALS['extensions']=[];
function home_url():string {return 'https://'.$GLOBALS['host'].'/';}
function did_action(string $name):int {return 0;}
function add_action(string $name,$callback,int $priority=10):void {$GLOBALS['actions'][]=[$name,$callback,$priority];}
function woocommerce_store_api_register_endpoint_data(array $args):bool {$GLOBALS['extensions'][]=$args;return true;}
final class Andy_Commerce_Shipping_Promotion_Policy{
  public const ELIGIBILITY_BASIS='pre_discount_merchandise_subtotal';
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-progress-projection.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-store-api-readonly-extension.php';
$count=0;
$assert=static function(bool $cond,string $case)use(&$count):void{
 $count++;if(!$cond)throw new RuntimeException('M5 STORE FAIL '.$case);
};
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::enabled(),'production host fails closed');
Andy_Commerce_M5_Store_API_Readonly_Extension::boot();
$assert(count($GLOBALS['actions'])===0,'WWW registers zero hooks');
$GLOBALS['host']='dev.beyourlover.com';
$assert(Andy_Commerce_M5_Store_API_Readonly_Extension::enabled(),'Dev explicit gate');
Andy_Commerce_M5_Store_API_Readonly_Extension::boot();
$assert(count($GLOBALS['actions'])===1
    && $GLOBALS['actions'][0][0]==='woocommerce_blocks_loaded','single registration hook');
Andy_Commerce_M5_Store_API_Readonly_Extension::register();
$assert(count($GLOBALS['extensions'])===1,'one read-only extension');
$registration=$GLOBALS['extensions'][0];
$assert($registration['endpoint']==='cart'
    && $registration['namespace']==='andy_commerce_marketing_v1','canonical Woo cart namespace');
$data=call_user_func($registration['data_callback']);
$assert(is_array($data) && $data['show_progress']===false
    && $data['state']==='hidden','hidden for legacy BYL49');
$assert($data['reason']==='legacy_policy_migration_pending','explicit migration block');
$assert($data['gift_enabled']===false && $data['claim_free_shipping']===false,'gifts and free shipping not awarded');
$schema=call_user_func($registration['schema_callback']);
$assert(array_key_exists('reason',$schema) && $schema['show_progress']['type']==='boolean','schema matches API payload');
$assert(!array_key_exists('update_callback',$registration),'no mutation endpoint');
$GLOBALS['host']='www.beyourlover.com';
$GLOBALS['extensions']=[];
Andy_Commerce_M5_Store_API_Readonly_Extension::register();
$assert(count($GLOBALS['extensions'])===0,'WWW cannot register');
$bootstrap=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
$assert(str_contains($bootstrap,"'dev.beyourlover.com'")
    && str_contains($bootstrap,'M5_Store_API_Readonly_Extension::boot()'),'bootstrap Dev gate present');
echo "M5_STORE_API_READONLY_BRIDGE_PASS={$count}; LIVE_DISCOUNTS=OFF; ORDER_WRITES=0\n";
