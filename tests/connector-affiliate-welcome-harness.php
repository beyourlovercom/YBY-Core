<?php
/** Native AffiliateWP welcome: isolated provider/WordPress hook contract, no network or real mail. */
define('ABSPATH', __DIR__);
require_once dirname(__DIR__).'/inc/class-yby-connector-affiliate-welcome.php';
function ok($v,$label){if(!$v){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS $label\n";}
function esc_html($v){return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function is_wp_error($v){return false;}
function get_option($k,$d=false){return $GLOBALS['opts'][$k]??$d;}
function add_option($k,$v,$x='',$a=false){if(isset($GLOBALS['opts'][$k]))return false;$GLOBALS['opts'][$k]=$v;return true;}
function update_option($k,$v,$a=false){$GLOBALS['opts'][$k]=$v;return true;}
function wp_cache_delete(...$args){}
function get_user_by($field,$id){return $id===70?(object)['ID'=>70,'user_email'=>'creator@example.test','user_pass'=>password_hash('Alpha<&!7',PASSWORD_BCRYPT)]:false;}
function wp_check_password($p,$hash,$id){return password_verify($p,$hash);}
function get_user_meta(...$args){return $GLOBALS['optout'];}
function affwp_get_affiliate($id){return $id===7?(object)['affiliate_id'=>7,'user_id'=>70,'status'=>'active']:false;}
function affwp_get_affiliate_email($id){return $GLOBALS['email'];}
function affwp_email_notification_enabled($type){return $GLOBALS['notification'];}
function affiliate_wp(){return (object)['settings'=>new class {function get($k,$d=''){return $GLOBALS['settings'][$k]??$d;}}];}
function get_posts($query){return $GLOBALS['posts'];}
function get_post_status($id){return $GLOBALS['coupon_status'];}
function get_post_meta($id,$key,$single){return $GLOBALS['coupon_affiliate'];}
function add_filter($k,$f,$p=10,$n=1){$GLOBALS['hooks'][$k][]=$f;}
function add_action($k,$f,$p=10,$n=1){add_filter($k,$f,$p,$n);}
function remove_filter($k,$f,$p=10){$GLOBALS['hooks'][$k]=array_values(array_filter($GLOBALS['hooks'][$k]??[],static function($x)use($f){return $x!==$f;}));}
function remove_action($k,$f,$p=10){remove_filter($k,$f,$p);}
function apply_filters($k,$v,...$args){foreach($GLOBALS['hooks'][$k]??[] as $f)$v=$f($v,...$args);return $v;}
function do_action($k,...$args){foreach($GLOBALS['hooks'][$k]??[] as $f)$f(...$args);}
class Affiliate_WP_Emails {}
class WC_Coupon {
	private $id;function __construct($id){$this->id=(int)$id;}
	function get_id(){return $this->id===9?9:0;}
	function get_code(){return 'Creator20';}
	function get_discount_type(){return 'percent';}
	function get_amount(){return $GLOBALS['amount'];}
	function get_date_expires(){return $GLOBALS['expiry'];}
}
class YBY_Connector_Affiliate_Bindings {static function by_kol($c,$k){return $GLOBALS['binding'];}}
class YBY_Connector_Coupon_Bindings {static function by_code($c){return $GLOBALS['cb'];}}
class Welcome_DB {
	public $options='wp_options';
	function prepare($q,...$a){return $a;}
	function query($a){return add_option($a[0],$a[1])?1:0;}
}
function affwp_notify_on_approval($id,$status,$old){
	if(!apply_filters('affwp_notify_on_approval',true))return;
	$GLOBALS['native_calls']++;
	$msg=$GLOBALS['settings']['accepted_email'];
	foreach(apply_filters('affwp_email_tags',[]) as $tag)$msg=str_replace('{'.$tag['tag'].'}',call_user_func($tag['function'],$id,null,$tag['tag']),$msg);
	$mail=['to'=>$GLOBALS['send_to'],'subject'=>'Welcome','message'=>$msg,'headers'=>$GLOBALS['headers']];
	if(null!==apply_filters('pre_wp_mail',$GLOBALS['prior_veto'],$mail))return;
	$GLOBALS['attempts'][]=$mail;
	do_action('phpmailer_init', new class {
		function getToAddresses(){return [[$GLOBALS['send_to'],'']];}
		function getCcAddresses(){return $GLOBALS['late_cc'];}
		function getBccAddresses(){return [];}
	});
	if($GLOBALS['mail_ok'])do_action('wp_mail_succeeded',$mail);
}
$wpdb=new Welcome_DB();
function fixture(){
	$GLOBALS['opts']=[];$GLOBALS['hooks']=[];$GLOBALS['native_calls']=0;$GLOBALS['attempts']=[];
	$GLOBALS['binding']=['state'=>'ready','affiliate_id'=>7,'wp_user_id'=>70];
	$GLOBALS['cb']=['state'=>'ready','connection_key'=>'site-a','erp_kol_id'=>11,'affiliate_id'=>7,'coupon_id'=>9];
	$GLOBALS['coupon_status']='publish';$GLOBALS['coupon_affiliate']=7;$GLOBALS['amount']=20;$GLOBALS['expiry']=null;$GLOBALS['posts']=[9];
	$GLOBALS['notification']=true;$GLOBALS['optout']=false;$GLOBALS['email']='creator@example.test';$GLOBALS['send_to']=$GLOBALS['email'];
	$GLOBALS['settings']=['accepted_email'=>'<p>{initial_password}</p><p>{coupon_code}</p>','accepted_subject'=>'Welcome'];
	$GLOBALS['prior_veto']=null;$GLOBALS['mail_ok']=true;$GLOBALS['headers']=[];$GLOBALS['late_cc']=[];
	add_filter('affwp_email_tags',['YBY_Connector_Affiliate_Welcome','tags']);
}
$input=['connection_key'=>'site-a','erp_kol_id'=>11,'normalized_code'=>'creator20'];
$affiliate=['created_user'=>true,'created_affiliate'=>true,'affiliate_id'=>7,'wp_user_id'=>70,'affiliate_status'=>'active'];
$coupon=['affiliate_id'=>7,'coupon_id'=>9];
$pw='Alpha<&!7';
ok(YBY_Connector_Affiliate_Welcome::tags([])===[],'default-off adds no tags');
define('YBY_ERP_NATIVE_WELCOME_ENABLED',true);
fixture();
ok(YBY_Connector_Affiliate_Welcome::coupon_tag(7)==='Creator20','unique actual code preview');
ok(strpos(YBY_Connector_Affiliate_Welcome::password_tag(7),$pw)===false,'preview never exposes password');
$GLOBALS['posts']=[9,10];ok(YBY_Connector_Affiliate_Welcome::coupon_tag(7)==='','ambiguous preview fails closed');
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='needs_review'&&!$GLOBALS['attempts'],'no welcome for historical accounts');
ok(!YBY_Connector_Affiliate_Welcome::enroll($input,array_merge($affiliate,['created_user'=>false])),'historical account cannot enroll');
ok(YBY_Connector_Affiliate_Welcome::enroll($input,$affiliate)&&!$GLOBALS['native_calls'],'new account enrollment never sends early');
foreach ([
	['wrong connection',['connection_key'=>'site-b'],null,null],
	['wrong KOL',['erp_kol_id'=>12],null,null],
	['wrong coupon binding',[],'cb',['state'=>'ready','connection_key'=>'site-a','erp_kol_id'=>11,'affiliate_id'=>99,'coupon_id'=>9]],
	['wrong coupon owner',[],'coupon_affiliate',99],
	['wrong discount',[],'amount',15],
	['expired coupon',[],'expiry',new DateTimeImmutable('-1 day')],
	['unpublished coupon',[],'coupon_status','draft'],
	['disabled notification',[],'notification',false],
	['opted-out user',[],'optout',true],
	['mismatched email',[],'email','wrong@example.test'],
	['missing body tags',[],'settings',['accepted_email'=>'{coupon_code}','accepted_subject'=>'Welcome']],
	['password in subject',[],'settings',['accepted_email'=>'{initial_password} {coupon_code}','accepted_subject'=>'{initial_password}']],
] as [$label,$over,$var,$val]){
	fixture();YBY_Connector_Affiliate_Welcome::enroll($input,$affiliate);
	if($var)$GLOBALS[$var]=$val;
	ok(YBY_Connector_Affiliate_Welcome::complete(array_merge($input,$over),$coupon,$pw)==='needs_review'&&!$GLOBALS['native_calls'],$label.' blocked before send');
}
fixture();YBY_Connector_Affiliate_Welcome::enroll($input,$affiliate);
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,null)==='needs_review'&&!$GLOBALS['native_calls'],'missing transient password blocked');
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,'WrongPass9')==='needs_review'&&!$GLOBALS['native_calls'],'incorrect password blocked');
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='mail_accepted','native approval accepted after exact coupon verification');
ok(count($GLOBALS['attempts'])===1&&$GLOBALS['attempts'][0]['message']==='<p>Alpha&lt;&amp;!7</p><p>Creator20</p>','native tags show escaped exact password and coupon');
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='mail_accepted'&&count($GLOBALS['attempts'])===1,'replay does not send twice');
ok(strpos(serialize($GLOBALS['opts']),$pw)===false&&empty($GLOBALS['hooks']['pre_wp_mail'])&&empty($GLOBALS['hooks']['wp_mail_succeeded']),'no stored password and scoped hooks removed');
ok(strpos(YBY_Connector_Affiliate_Welcome::password_tag(7),$pw)===false,'password cleared after send');
fixture();YBY_Connector_Affiliate_Welcome::enroll($input,$affiliate);
$GLOBALS['opts']['yby_erp_welcome_pending_7']['created_at']=time()-901;
ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='needs_review'&&!$GLOBALS['native_calls'],'expired onboarding context blocked');
foreach(['wrong-recipient','cc-header','late-cc','veto','transport-failure'] as $mode){
	fixture();YBY_Connector_Affiliate_Welcome::enroll($input,$affiliate);
	if($mode==='wrong-recipient')$GLOBALS['send_to']='other@example.test';
	if($mode==='cc-header')$GLOBALS['headers']=['Cc: other@example.test'];
	if($mode==='late-cc')$GLOBALS['late_cc']=[['other@example.test','']];
	if($mode==='veto')$GLOBALS['prior_veto']=false;
	if($mode==='transport-failure')$GLOBALS['mail_ok']=false;
	ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='needs_review',$mode.' fails closed');
	$count=count($GLOBALS['attempts']);
	ok(YBY_Connector_Affiliate_Welcome::complete($input,$coupon,$pw)==='needs_review'&&count($GLOBALS['attempts'])===$count&&$GLOBALS['native_calls']===1,$mode.' no automatic retry');
}
echo "Native AffiliateWP welcome isolated harness passed.\n";
