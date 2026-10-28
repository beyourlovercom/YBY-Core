<?php
/** Pure deterministic Dev-only R13 eligibility/payment matrix; never networks or writes. */
declare(strict_types=1);
define('ABSPATH','/isolated/ci/');
$GLOBALS['devtest']=[
 'host'=>'dev.beyourlover.com','ssl'=>true,'admin'=>false,
 'cap'=>true,'uid'=>18,'currency'=>'CAD','legacy'=>false,
 'opts'=>['woocommerce_currency'=>'USD']
];
function home_url($url='/'){return 'https://dev.beyourlover.com'.$url;}
function wp_parse_url($url,$part){return parse_url($url,$part);}
function get_option($name,$fallback=null){return $GLOBALS['devtest']['opts'][$name]??$fallback;}
function is_ssl(){return $GLOBALS['devtest']['ssl'];}
function is_admin(){return $GLOBALS['devtest']['admin'];}
function current_user_can($cap){return $GLOBALS['devtest']['cap'];}
function get_current_user_id(){return $GLOBALS['devtest']['uid'];}
function wp_salt($type='auth'){return 'PURE_UNIT_KEY_NOT_WORDPRESS_SECRET';}
class Andy_Commerce_Currency_Runtime_Dev_V1 {
 public static function settings(){return ['enabled'=>$GLOBALS['devtest']['legacy']];}
 public static function legacy_conflict(){return $GLOBALS['devtest']['external_conflict']??false;}
}
class Andy_Commerce_Currency_Runtime_C1 {
 public static function test_currency(){return $GLOBALS['devtest']['currency'];}
}
class WP_REST_Request {
 private string $method;private string $route;private array $params=[];
 public function __construct($method,$route){$this->method=$method;$this->route=$route;}
 public function get_method(){return $this->method;}
 public function get_route(){return $this->route;}
 public function get_param($k){return $this->params[$k]??null;}
 public function set_param($k,$v){$this->params[$k]=$v;}
}
class WP_Error {
 public string $code;public array $data;
 public function __construct($code,$message,$data=[]){$this->code=$code;$this->data=$data;}
}
require dirname(__DIR__).'/includes/class-andy-commerce-currency-r13-dev-canary.php';
$check=static function($ok,$m){if(!$ok)throw new RuntimeException('P6_R13_DEV_GATE '.$m);};
$now=time();
$opts=&$GLOBALS['devtest']['opts'];
$opts[Andy_Commerce_Currency_R13_Dev_Canary::OPTION]=[
 'enabled'=>true,'user_id'=>18,'expires_at'=>$now+500];
$optbase=$opts;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'requires actual HTTP host');
$_SERVER['HTTP_HOST']='dev.beyourlover.com';
$check(Andy_Commerce_Currency_R13_Dev_Canary::armed(),'authorized dev admin canary allowed');
$_SERVER['HTTP_HOST']='www.beyourlover.com';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'WWW denied');
$_SERVER['HTTP_HOST']='dev.beyourlover.com';
$GLOBALS['devtest']['ssl']=false;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'HTTP denied');
$GLOBALS['devtest']['ssl']=true;
$GLOBALS['devtest']['cap']=false;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'guest denied');
$GLOBALS['devtest']['cap']=true;
$GLOBALS['devtest']['uid']=19;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'different manager denied');
$GLOBALS['devtest']['uid']=18;
$GLOBALS['devtest']['admin']=true;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'WP admin pricing unaffected');
$GLOBALS['devtest']['admin']=false;
$opts['woocommerce_currency']='EUR';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'non USD base denied');
$opts['woocommerce_currency']='USD';
$opts[Andy_Commerce_Currency_R13_Dev_Canary::OPTION]['expires_at']=$now-1;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'expired user denied');
$opts[Andy_Commerce_Currency_R13_Dev_Canary::OPTION]['expires_at']=$now+940;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'excessive window denied');
$opts=$optbase;
$GLOBALS['devtest']['legacy']=true;
$check(Andy_Commerce_Currency_R13_Dev_Canary::armed(),'global legacy Dev mode coexists outside administrator canary');
$GLOBALS['devtest']['external_conflict']=true;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::armed(),'external currency plugin overlap denied');
$GLOBALS['devtest']['external_conflict']=false;
$GLOBALS['devtest']['legacy']=false;
$opts['woocommerce_stripe_settings']=[
 'enabled'=>'no','testmode'=>'yes','test_secret_key'=>'sk_test_PURE_FIXTURE_NOT_REAL'];
$fingerprint=Andy_Commerce_Currency_R13_Dev_Canary::test_gateway_state()['key_fingerprint'];
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]=[
 'account'=>Andy_Commerce_Currency_R13_Dev_Canary::EXPECTED_ACCOUNT,
 'key_fingerprint'=>$fingerprint,'verified_at'=>$now];
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'disabled Stripe Gateway denied');
$opts['woocommerce_stripe_settings']['enabled']='yes';
$check(Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'approved TEST account allowed only with all evidence');
$opts['woocommerce_stripe_settings']['testmode']='no';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'LIVE mode denied');
$opts['woocommerce_stripe_settings']['testmode']='yes';
$opts['woocommerce_stripe_settings']['test_secret_key']='sk_live_PURE_FIXTURE_NOT_REAL';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'live secret denied');
$opts['woocommerce_stripe_settings']['test_secret_key']='sk_test_PURE_FIXTURE_NOT_REAL';
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['verified_at']=$now-901;
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'stale provider proof denied');
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['verified_at']=$now;
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['account']='acct_WRONG';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'wrong Stripe TEST account denied');
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['account']=Andy_Commerce_Currency_R13_Dev_Canary::EXPECTED_ACCOUNT;
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['key_fingerprint']='untrusted';
$check(!Andy_Commerce_Currency_R13_Dev_Canary::payment_ready(),'changed key proof denied');
$opts[Andy_Commerce_Currency_R13_Dev_Canary::ACCOUNT_PROOF]['key_fingerprint']=$fingerprint;
$gateway=['stripe'=>new stdClass(),'woocommerce_payments'=>new stdClass()];
$shown=Andy_Commerce_Currency_R13_Dev_Canary::gateways($gateway);
$check(array_keys($shown)===['stripe'],'canary foreign allows only verified Stripe TEST');
$req=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req->set_param('payment_method','woocommerce_payments');
$err=Andy_Commerce_Currency_R13_Dev_Canary::checkout_preflight(null,[],$req);
$check($err instanceof WP_Error && $err->code==='aicc_stripe_test_only'
 && $err->data['status']===422,'unverified alternate gateway rejected before Woo payment');
$req->set_param('payment_method','stripe');
$check(Andy_Commerce_Currency_R13_Dev_Canary::checkout_preflight(null,[],$req)===null,
 'approved TEST only preflight passes');
$opts['woocommerce_stripe_settings']['enabled']='no';
$err=Andy_Commerce_Currency_R13_Dev_Canary::checkout_preflight(null,[],$req);
$check($err instanceof WP_Error && $err->code==='aicc_stripe_test_not_ready'
 && $err->data['status']===503,'unavailable Stripe blocked before Checkout');
$check(Andy_Commerce_Currency_R13_Dev_Canary::gateways($gateway)===[],
 'no fallback to WooPayments');
$GLOBALS['devtest']['currency']='USD';
$check(Andy_Commerce_Currency_R13_Dev_Canary::checkout_preflight(null,[],$req)===null,
 'ordinary USD checkout untouched');
$_SERVER['HTTP_HOST']='www.beyourlover.com';
$check(Andy_Commerce_Currency_R13_Dev_Canary::gateways($gateway)===$gateway,
 'WWW gateway array untouched');
// An expired admin authorization MUST NOT reactivate WooPayments for a cached CAD cart.
class R13_Fake_Session {public string $currency='CAD';public function get($k){return $k==='aicc_currency_runtime_selected'?$this->currency:null;}}
$GLOBALS['r13_wc']=(object)['session'=>new R13_Fake_Session()];
function WC(){return $GLOBALS['r13_wc']??(object)['session'=>null];}
$_SERVER['HTTP_HOST']='dev.beyourlover.com';
$opts[Andy_Commerce_Currency_R13_Dev_Canary::OPTION]=['enabled'=>false];
$GLOBALS['devtest']['currency']='USD';
$check(Andy_Commerce_Currency_R13_Dev_Canary::gateways($gateway)===[],
 'expired CAD session must hide all payment providers');
$expired=Andy_Commerce_Currency_R13_Dev_Canary::checkout_preflight(null,[],$req);
$check($expired instanceof WP_Error&&$expired->code==='aicc_r13_dev_session_expired'
 &&$expired->data['status']===503,'expired CAD cannot submit payment');
$GLOBALS['r13_wc']->session->currency='USD';
$check(Andy_Commerce_Currency_R13_Dev_Canary::gateways($gateway)===$gateway,
 'ordinary USD Dev gateway survives retired canary');
echo 'R13_DEV_CANARY_STRICT_HOST_USER_SESSION_STRIPE_TEST_GATE_PASS assertions=27'.PHP_EOL;
