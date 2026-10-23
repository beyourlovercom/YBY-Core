<?php
/** Isolated WP stubs validate strict Dev admin draft-only routing. */
declare(strict_types=1);
define('ABSPATH','/test/');
$host='dev.beyourlover.com';
$actions=[];$options=[];
function wp_parse_url($u,$part){return parse_url($u,$part);}
function home_url($path=''){global $host;return 'https://'.$host.'/'.$path;}
function is_admin(){return true;}
function add_action($k,$v){global $actions;$actions[$k]=$v;}
function get_option($name,$fallback=[]){global $options;return $options[$name]??$fallback;}
$nonceActions=[];
function current_user_can($cap){return $cap==='manage_woocommerce';}
function check_admin_referer($action){global $nonceActions;$nonceActions[]=$action;return 1;}
require_once dirname(__DIR__).'/includes/class-andy-commerce-m7-draft-studio.php';
$n=0;
$ok=static function($cond,$label)use(&$n){if(!$cond)throw new RuntimeException("M7 FAIL: ".$label);$n++;};
$ok(Andy_Commerce_M7_Draft_Studio::dev_only(),'dev host authorized for drafts');
Andy_Commerce_M7_Draft_Studio::boot();
$ok(isset($actions['admin_post_andy_commerce_m7_save_draft']),'Dev-only save route registered');
$ok(isset($actions['admin_post_andy_commerce_m7_archive_draft']),'Dev-only archive route registered');
global $host,$options;
$host='www.beyourlover.com';
$ok(!Andy_Commerce_M7_Draft_Studio::dev_only(),'www cannot write drafts');
$host='beyourlover.com';
$ok(!Andy_Commerce_M7_Draft_Studio::dev_only(),'root production domain cannot write');
$host='dev.beyourlover.com';
$options[Andy_Commerce_M7_Draft_Studio::OPTION]=[
 'valid-1'=>['name'=>'Test','type'=>'bogo','active'=>true,'status'=>'published','revision'=>2],
 '<script>'=>['active'=>true],
];
$rows=Andy_Commerce_M7_Draft_Studio::rows();
$ok(count($rows)===1,'invalid key excluded');
$ok($rows['valid-1']['active']===false,'malicious active option always forced inactive');
$ok($rows['valid-1']['status']==='draft','malicious published option forced draft');
$source=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-m7-draft-studio.php');
$entry=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
$ok(str_contains($source,'check_admin_referer')&&str_contains($source,"current_user_can('manage_woocommerce')"),'nonce and capability gate in handler');
$ok(str_contains($source,"'revision'")&&str_contains($source,"'conflict'"),'optimistic revision guard');
$ok(str_contains($source,"'active'=>false")&&!str_contains($source,"'active'=>true"),'persist only inactive drafts');
$ok(!str_contains($entry,'woocommerce_cart_calculate_fees')&&!str_contains($entry,'woocommerce_package_rates'),'no double discount/shipping runtime hooks');
$view=file_get_contents(dirname(__DIR__).'/admin/views/marketing-m7-dev-studio.php');
$ok(str_contains($view,'wp_nonce_field')&&str_contains($view,'esc_attr'),'form CSRF and escaping');
$ok(str_contains($view,'仅模拟：')&&str_contains($view,'实际优惠已执行：'),'UAT clearly identifies simulation');
// Execute the real handler permission/nonce guard in isolation without POST
// or DB writes, then confirm both HTML nonce actions match exactly.
$guard=new ReflectionMethod(Andy_Commerce_M7_Draft_Studio::class,'require_authority');
$guard->invoke(null,'save');
$guard->invoke(null,'archive');
$ok($nonceActions===['andy_commerce_m7_save_draft','andy_commerce_m7_archive_draft'],'save and archive nonce actions match WordPress HTML form');
$ok(str_contains($view,"wp_nonce_field('andy_commerce_m7_save_draft')"),'save HTML nonce action matches handler');
$ok(str_contains($view,"wp_nonce_field('andy_commerce_m7_archive_draft')"),'archive HTML nonce action matches handler');
$ok(substr_count($view,'wp_nonce_field(')===2,'exactly two nonce-protected forms');
echo "M7_DEV_DRAFT_SECURITY_PASS=$n; WWW_WRITE_GATE=CLOSED; CAMPAIGNS=OFF\n";
