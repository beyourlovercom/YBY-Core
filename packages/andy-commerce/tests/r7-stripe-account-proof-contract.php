<?php
/**
 * R7 secure account proof negative-path tests.
 * No real Stripe credentials, HTTP calls, payment intent, order or refunds.
 */
define('ABSPATH','/');
$_SERVER['HTTP_HOST']='dev.beyourlover.com';
$GLOBALS['r7_opts']=[
  'woocommerce_currency'=>'USD',
  'woocommerce_stripe_settings'=>[
    'enabled'=>'no','testmode'=>'yes','test_secret_key'=>'sk_test_SYNTHETIC_R7_DO_NOT_USE'
  ],
  'andy_commerce_currency_r13_dev_canary'=>[
    'enabled'=>true,'user_id'=>11,'expires_at'=>time()+600
  ]
];
function home_url($s='/'){return 'https://dev.beyourlover.com'.$s;}
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}
function is_ssl(){return true;}
function is_admin(){return false;}
function current_user_can($cap){return $cap==='manage_woocommerce';}
function get_current_user_id(){return 11;}
function get_option($n,$default=null){return $GLOBALS['r7_opts'][$n]??$default;}
function wp_salt($n){return 'R7_FAKE_SALT_FOR_TESTS';}
class Andy_Commerce_Currency_Runtime_Dev_V1 {
 public static function legacy_conflict():bool{return $GLOBALS['r7_yay_conflict']??false;}
}
require dirname(__DIR__).'/includes/class-andy-commerce-currency-r13-dev-canary.php';
function verifyR7($v,$label){
 if(!$v)throw new RuntimeException('R7_STRIPE_GATE_FAIL '.$label);
 $GLOBALS['r7_count']=($GLOBALS['r7_count']??0)+1;
}
$class='Andy_Commerce_Currency_R13_Dev_Canary';
verifyR7($class::site(),'synthetic dev and USD context');
verifyR7($class::armed(),'only authorized staff user arms short-lived canary');
$state=$class::test_gateway_state();
verifyR7($state['testmode'] && $state['test_key'] && !$state['enabled'],'test key shape alone never enables checkout');
verifyR7(!$class::payment_ready(),'Stripe disabled never payment-ready');
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['enabled']='yes';
$correctProof=[
 'account'=>$class::EXPECTED_ACCOUNT,
 'key_fingerprint'=>$class::test_gateway_state()['key_fingerprint'],
 'verified_at'=>time()-40
];
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
verifyR7($class::payment_ready(),'only exact expected TEST account and matching fresh proof can pass');
foreach(['acct_1R57M7GR9MwFzg43','acct_1SrJdHCxlWhQx8Rb','acct_FAKEOTHER'] as $wrong){
 $GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]['account']=$wrong;
 verifyR7(!$class::payment_ready(),'reject nonmatching account '.$wrong);
}
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]['verified_at']=time()-901;
verifyR7(!$class::payment_ready(),'15-minute proof TTL enforced');
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]['key_fingerprint']='invalid';
verifyR7(!$class::payment_ready(),'rotated key invalidates stale account proof');
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['testmode']='no';
verifyR7(!$class::payment_ready(),'livemode never accepted');
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['testmode']='yes';
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()-1;
verifyR7(!$class::payment_ready(),'expired user session never permitted');
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()+600;
$_SERVER['HTTP_HOST']='www.beyourlover.com';
verifyR7(!$class::payment_ready(),'WWW host never permitted');
$_SERVER['HTTP_HOST']='dev.beyourlover.com';
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['test_secret_key']='sk_live_SYNTHETIC';
verifyR7(!$class::payment_ready(),'non TEST key blocked');

/** Mock only WordPress Store API request payloads; no checkout is performed. */
class WP_REST_Request {
    public function __construct(private string $method,private string $route,private array $params=[]){}
    public function get_method(){return $this->method;}
    public function get_route(){return $this->route;}
    public function get_param($name){return $this->params[$name]??null;}
}
class WP_Error {
    public function __construct(private string $code,private string $message,private array $data=[]){}
    public function get_error_code(){return $this->code;}
    public function get_error_data(){return $this->data;}
}
class Andy_Commerce_Currency_Runtime_C1 {
    public static function test_currency(){return $GLOBALS['r7_currency']??'USD';}
}
class R7_Session {
    public function get($key){return $GLOBALS['r7_session'][$key]??null;}
}
function WC(){return $GLOBALS['r7_woo'];}
$GLOBALS['r7_woo']=(object)['session'=>new R7_Session()];
$GLOBALS['r7_currency']='EUR';
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['test_secret_key']='sk_test_SYNTHETIC_R7_DO_NOT_USE';
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['enabled']='no';
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()+600;
$r=new WP_REST_Request('POST','/wc/store/v1/checkout',['payment_method'=>'stripe']);
$err=$class::checkout_preflight(null,null,$r);
verifyR7($err instanceof WP_Error && $err->get_error_code()==='aicc_stripe_test_not_ready',
    'Blocks foreign TEST checkout rejects disabled gateway before order');
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['enabled']='yes';
$err=$class::checkout_preflight(null,null,new WP_REST_Request(
    'POST','/wc/store/v1/checkout',['payment_method'=>'woocommerce_payments']));
verifyR7($err instanceof WP_Error&&$err->get_error_code()==='aicc_stripe_test_only',
    'Blocks foreign checkout denies other gateways even with TEST proof');
verifyR7($class::checkout_preflight(null,null,$r)===null,
    'only synthetic fresh proof plus stripe TEST passes preflight, no payment');
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]['verified_at']=time()-901;
verifyR7($class::checkout_preflight(null,null,$r)->get_error_code()==='aicc_stripe_test_not_ready',
    'expired proof blocks Store API before provider invocation');
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()-1;
$GLOBALS['r7_session']=['aicc_currency_runtime_selected'=>'CAD'];
verifyR7($class::checkout_preflight(null,null,$r)->get_error_code()==='aicc_r13_dev_session_expired',
    'expired staff session plus retained foreign cart fails closed');
verifyR7($class::gateways(['stripe'=>'TEST_STRIPE','woocommerce_payments'=>'OTHER'])===[],
    'foreign cart after canary expiration exposes zero gateways');
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()+600;
$GLOBALS['r7_currency']='USD';
$GLOBALS['r7_session']=[];
verifyR7($class::checkout_preflight(null,null,$r)===null,
    'USD ordinary checkout not intercepted by foreign preflight');
verifyR7($class::gateways(['stripe'=>'TEST_STRIPE','woocommerce_payments'=>'OTHER'])
  ===['woocommerce_payments'=>'OTHER'],
    'USD original WooPayments remains available, standalone Stripe removed');
verifyR7($class::checkout_preflight(null,null,new WP_REST_Request(
    'GET','/wc/store/v1/checkout'))===null,
    'GET checkout not affected');
verifyR7($class::checkout_preflight(null,null,new WP_REST_Request(
    'POST','/wc/store/v1/cart',['payment_method'=>'stripe']))===null,
    'other Store API routes not affected');

// YayCurrency owns the storefront FX/checkout path under the newer Owner-approved
// architecture. A retained R13 administrator option or Stripe TEST proof must NOT
// reactivate the old self-built currency canary or surface its gateway.
$GLOBALS['r7_yay_conflict']=true;
$GLOBALS['r7_currency']='EUR';
$GLOBALS['r7_opts'][$class::OPTION]['expires_at']=time()+600;
$GLOBALS['r7_opts'][$class::ACCOUNT_PROOF]=$correctProof;
$GLOBALS['r7_opts']['woocommerce_stripe_settings']['enabled']='yes';
$GLOBALS['r7_session']=['aicc_currency_runtime_selected'=>'CAD'];
verifyR7(!$class::armed(),'YayCurrency conflict rejects stored R13 staff authorization');
verifyR7(!$class::payment_ready(),'YayCurrency conflict rejects previous Stripe TEST proof');
$err=$class::checkout_preflight(null,null,$r);
verifyR7($err instanceof WP_Error && $err->get_error_code()==='aicc_r13_dev_session_expired',
    'YayCurrency conflict rejects retained foreign Store API checkout');
verifyR7($class::gateways(['stripe'=>'TEST_STRIPE','woocommerce_payments'=>'OTHER'])===[],
    'YayCurrency conflict never exposes old R13 gateway on retained foreign cart');
$GLOBALS['r7_session']=[];
verifyR7($class::gateways(['stripe'=>'TEST_STRIPE','woocommerce_payments'=>'OTHER'])
  ===['woocommerce_payments'=>'OTHER'],
    'YayCurrency conflict keeps standard USD WooPayments gateway without R13 Stripe');
// Do not claim that these old R13 mocked failures prove actual Yay+Woo payments.
$GLOBALS['r7_yay_conflict']=false;
echo 'R7_YAYCURRENCY_LEGACY_CANARY_NEGATIVE_PASS=5'.PHP_EOL;
echo 'R7_STRIPE_TEST_ACCOUNT_GATE_PASS='.$GLOBALS['r7_count'].PHP_EOL;
