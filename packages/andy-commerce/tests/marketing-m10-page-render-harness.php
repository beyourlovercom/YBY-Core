<?php
/** WordPress-isolated M10 server HTML tests; all drafts read-only fixtures. */
declare(strict_types=1);
define('ABSPATH','/test/');
define('ANDY_COMMERCE_PLUGIN_DIR',dirname(__DIR__).'/');
$GLOBALS['m10_role']=true;
$GLOBALS['m10_dev']=true;
$GLOBALS['m10_rows']=[];
function current_user_can($cap){return $GLOBALS['m10_role']&&$cap==='manage_woocommerce';}
function admin_url($path=''){return 'https://dev.beyourlover.com/wp-admin/'.$path;}
function home_url($path=''){return 'https://dev.beyourlover.com/'.$path;}
function add_query_arg($args,$url){return $url.(str_contains($url,'?')?'&':'?').http_build_query($args);}
function sanitize_key($s){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$s));}
function wp_unslash($s){return $s;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_attr($s);}
function esc_textarea($s){return esc_html($s);}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function selected($x,$y,$echo=true){$v=(string)$x===(string)$y?' selected="selected"':'';if($echo)echo $v;return $v;}
function wp_nonce_field($action){echo '<input type="hidden" name="_wpnonce" value="'.esc_attr($action).'">';}
function wp_die($message,$title='',$args=[]){throw new RuntimeException('DENIED: '.$message);}
final class Andy_Commerce_M7_Draft_Studio {
 public static function dev_only(){return $GLOBALS['m10_dev'];}
 public static function rows(){return $GLOBALS['m10_rows'];}
}
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-marketing-v2-rule-fields.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
$path=dirname(__DIR__).'/admin/views/marketing-m10-workbench.php';
$n=0;
$ok=static function(bool $pass,string $name)use(&$n){if(!$pass)throw new RuntimeException('M10 PAGE FAIL: '.$name);++$n;};
$render=static function(array $query=[])use($path):string {
  $_GET=array_merge(['page'=>'andy-commerce','tab'=>'marketing-promotions'],$query);
  ob_start();try{include $path;return ob_get_clean();}
  catch(Throwable $e){ob_end_clean();throw $e;}
};
$empty=$render();
$ok(str_contains($empty,'活动列表'),'empty list title');
$ok(str_contains($empty,'暂无活动草稿'),'no fabricated WordPress draft');
$ok(str_contains($empty,'tab=marketing-create'),'real create route');
$ok(!str_contains($empty,'已启用活动</span><strong>1'),'all inactive');
$GLOBALS['m10_rows']=[
 'draft-one'=>['id'=>'draft-one','revision'=>3,'name'=>'已存在的满额活动',
   'type'=>'spend_save','currency'=>'USD','countries'=>['US'],
   'rule'=>['threshold_minor'=>16900,'discount_minor'=>2000],
   'priority'=>100,'active'=>false,'status'=>'draft'],
];
$list=$render();
$ok(str_contains($list,'已存在的满额活动'),'list reads M7 rows');
$ok(str_contains($list,'copy_id=draft-one'),'copy uses canonical route');
$ok(str_contains($list,'draft_id=draft-one'),'edit uses canonical route');
$ok(str_contains($list,'andy_commerce_m7_archive_draft'),'old archive action');
$ok(str_contains($list,'draft_revision'),'revision guard present');
$ok(str_contains($list,'andy_commerce_m7_archive_draft'),'archive nonce form present');
$new=$render(['tab'=>'marketing-create','template'=>'gift']);
$ok(str_contains($new,'新建活动'),'new form visible');
$ok(str_contains($new,'andy_commerce_m7_save_draft'),'original save POST+nonce');
$ok(str_contains($new,'draft_rule_fields[free_gift][threshold]'),'gift threshold form present');
$ok(str_contains($new,'draft_rule_fields[free_gift][product_id]'),'gift product form present');
$ok(str_contains($new,'andy-commerce-v2-rule-editor'),'typed visual editor reused');
$ok(str_contains($new,'id="ac-m10-live-threshold"'),'live preview wired');
$ok(str_contains($new,'name="draft_id" value=""'),'new has blank ID');
$edit=$render(['tab'=>'marketing-create','draft_id'=>'draft-one']);
$ok(str_contains($edit,'name="draft_id" value="draft-one"'),'edit has existing draft id');
$ok(str_contains($edit,'name="draft_revision" value="3"'),'edit protects revision');
$copied=$render(['tab'=>'marketing-create','copy_id'=>'draft-one']);
$ok(str_contains($copied,'name="draft_id" value=""'),'copy creates new ID');
$ok(str_contains($copied,'已存在的满额活动（副本）'),'copy changes name');
$templates=$render(['tab'=>'marketing-templates']);
foreach(['满额赠品','满额立减','百分比折扣','买赠活动','组合优惠','阶梯优惠'] as $type){
 $ok(str_contains($templates,$type),'six existing templates: '.$type);
}
$sim=$render(['tab'=>'marketing-simulate']);
$ok(str_contains($sim,'营销 OS · 美元多规则工作台'),'simulator embeds M9');
$ok(str_contains($sim,'marketing-simulate'),'simulation GET retains third-level screen');
$ok(str_contains($sim,'未修改 Woo 运费'),'no real shipping changes');
$logs=$render(['tab'=>'marketing-logs']);
$ok(str_contains($logs,'尚未建立真实订单级活动执行日志'),'no invented transaction events');
$GLOBALS['m10_role']=false;
try {$render(['tab'=>'marketing-create']);$ok(false,'unauthorized should die');}
catch(RuntimeException $e){$ok(str_contains($e->getMessage(),'DENIED'),'capability denies');}
$GLOBALS['m10_role']=true;$GLOBALS['m10_dev']=false;
try {$render(['tab'=>'marketing-templates']);$ok(false,'non Dev should die');}
catch(RuntimeException $e){$ok(str_contains($e->getMessage(),'DENIED'),'non Dev denied');}
echo "PASS MARKETING_M10_WP_RENDER={$n}; CRUD_M7_NONCE=REUSED; NO_LIVE_EXECUTION\n";
