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
 public static function legacy_conflict():bool{return false;}
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
echo 'R7_STRIPE_TEST_ACCOUNT_GATE_PASS='.$GLOBALS['r7_count'].PHP_EOL;
