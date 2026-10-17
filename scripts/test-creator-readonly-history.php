<?php
/** Creator History API isolated auth/owner-scoping regression test. No WordPress DB. */
declare(strict_types=1);
define('ABSPATH', __DIR__.'/');
define('BYL_CREATOR_REMOTE_API_ENABLED', true);
define('BYL_CREATOR_REMOTE_KEY_ID', 'creator-dev-v1');
define('BYL_CREATOR_REMOTE_SIGN_KEY', str_repeat('K', 64));
define('BYL_CREATOR_REMOTE_BINDINGS', [21=>['wp_user_id'=>55,'affiliate_id'=>99]]);
define('BYL_CREATOR_CANARY_STAGE_DIAGNOSTICS', true);
class WP_Error {
    public $code, $message, $data;
    function __construct($code,$message,$data=[]) {$this->code=$code;$this->message=$message;$this->data=$data;}
}
function is_wp_error($x) {return $x instanceof WP_Error;}
class WP_REST_Response {
    public $data,$status;
    function __construct($data,$status=200) {$this->data=$data;$this->status=$status;}
}
class MockRequest {
    public $headers,$params,$method='GET';
    function __construct($params=[],$headers=[]) {$this->params=$params;$this->headers=$headers;}
    function get_header($key) {return $this->headers[$key]??'';}
    function get_method() {return $this->method;}
    function get_params() {return $this->params;}
}
class WP_User {
    public $ID;
    function __construct($id){$this->ID=$id;}
}
class MockAffiliateDB {
    public $badOwner=false;
    // Live AffiliateWP: payouts live under affiliates->payouts and the DB has
    // no get_affiliate() method. This mock intentionally enforces that shape.
    public $payouts;
    function __construct() { $this->payouts=$this; }
    function count($args){
        if (count($args)!==1 || (int)($args['affiliate_id']??0)!==99) {
            throw new RuntimeException('Count query must be affiliate-scoped');
        }
        return 20;
    }
    function get_referrals($args){
        if ((int)$args['affiliate_id']!==99 || $args['number']!==13) throw new RuntimeException('Referral query not scoped');
        return [(object)['affiliate_id'=>$this->badOwner?100:99,'referral_id'=>5,'date'=>'2026-10-15','amount'=>'20.10','status'=>'paid','currency'=>'USD']];
    }
    function get_payouts($args) {
        if ((int)$args['affiliate_id']!==99 || $args['number']!==5) throw new RuntimeException('Payout query not scoped');
        return [(object)['affiliate_id'=>99,'date'=>'2026-10-01','amount'=>'10','status'=>'paid','currency'=>'USD']];
    }
}
$GLOBALS['mocks']=new MockAffiliateDB();
$GLOBALS['transients']=[];
function get_userdata($id){return (int)$id===55 ? new WP_User(55):false;}
function affwp_get_affiliate_id($user_id){return (int)$user_id===55 ? 99:0;}
function affwp_get_affiliate_status($aff_id){return (int)$aff_id===99?'active':'inactive';}
function affwp_get_affiliate($aff_id){return (int)$aff_id===99 ? (object)['user_id'=>55,'earnings'=>'500.50','unpaid_earnings'=>'40.25','visits'=>55,'referrals'=>3] : false;}
function affwp_get_currency(){return 'USD';}
function affiliate_wp(){return (object)['affiliates'=>$GLOBALS['mocks'],'referrals'=>$GLOBALS['mocks']];}
function get_transient($k){return $GLOBALS['transients'][$k]??false;}
function set_transient($k,$v,$ttl){$GLOBALS['transients'][$k]=$v;}
function is_ssl(){return true;}
function wp_parse_url($url,$key=-1){return parse_url($url,$key);}
function home_url($u='/'){return 'https://www.beyourlover.com'.$u;}
function wp_http_validate_url($url){return filter_var($url,FILTER_VALIDATE_URL)?$url:false;}
function nocache_headers(){}
function affwp_get_affiliate_referral_url($arr){return 'https://www.beyourlover.com/?ref=99';}
require dirname(__DIR__).'/inc/class-yby-creator-readonly-history.php';
function check($state,$label){if(!$state){throw new RuntimeException('FAIL '.$label);}echo 'PASS '.$label."\n";}
$parameters=['dev_user_id'=>21,'affiliate_id'=>99,'wp_user_id'=>55,'ref_page'=>1,'pay_page'=>1];
function request_for($p,$override=[]) {
    $query=http_build_query($p,'','&',PHP_QUERY_RFC3986);
    $_SERVER['REQUEST_URI']='/wp-json/andy-core/v1/creator/history?'.$query;
    $ts=(string)time();
    $nonce=bin2hex(random_bytes(16));
    $sig=hash_hmac('sha256',"GET\n".$_SERVER['REQUEST_URI']."\n".$ts."\n".$nonce."\n".BYL_CREATOR_REMOTE_KEY_ID,BYL_CREATOR_REMOTE_SIGN_KEY);
    return new MockRequest($p,array_merge([
      'X-BYL-Creator-Key'=>BYL_CREATOR_REMOTE_KEY_ID,
      'X-BYL-Creator-Time'=>$ts,
      'X-BYL-Creator-Nonce'=>$nonce,
      'X-BYL-Creator-Signature'=>$sig,
    ],$override));
}
$r=request_for($parameters);
check(YBY_Creator_Readonly_History::authorize($r)===true,'HMAC GET authenticated');
$res=YBY_Creator_Readonly_History::handle($r);
check($res instanceof WP_REST_Response && $res->status===200,'owner verified and snapshot accepted');
check($res->data['data']['affiliate_id']===99 && $res->data['data']['wp_user_id']===55,'both provider identity keys preserved');
check(count($res->data['data']['referrals'])===1 && count($res->data['data']['payouts'])===1,'own referrals/payouts only');
check($res->data['data']['account']['referrals']===20,'Total Referrals counts all owned statuses, not paid-only account counter');
check($res->data['data']['account']['earnings']==='500.50','Paid Earnings remains native account field');
check(is_wp_error(YBY_Creator_Readonly_History::authorize($r)),'HMAC nonce replay denied');
$bad=request_for($parameters,['X-BYL-Creator-Signature'=>str_repeat('0',64)]);
check(is_wp_error(YBY_Creator_Readonly_History::authorize($bad)),'invalid HMAC denied');
check(YBY_Creator_Readonly_History::authorize(request_for($parameters,['X-BYL-Creator-Signature'=>str_repeat('0',64)]))->data['gate']==='signature','auth diagnostic reports only stage, not private values');
check((bool) preg_match('/^[a-f0-9]{64}$/D', YBY_Creator_Readonly_History::authorize(request_for($parameters,['X-BYL-Creator-Signature'=>str_repeat('0',64)]))->data['canonical_sha256'] ?? ''),'auth diagnostic emits only canonical message hash');
$invalid=$parameters; $invalid['affiliate_id']=100;
$foreign=request_for($invalid);
check(YBY_Creator_Readonly_History::authorize($foreign)===true,'foreign identity request signed but untrusted');
check(is_wp_error(YBY_Creator_Readonly_History::handle($foreign)),'foreign affiliate bound to same user is denied');
$invalid=$parameters; $invalid['dev_user_id']=22;
$not_allowlisted=request_for($invalid);
check(YBY_Creator_Readonly_History::authorize($not_allowlisted)===true,'unknown Dev account signature accepted but no mapping privilege');
check(is_wp_error(YBY_Creator_Readonly_History::handle($not_allowlisted)),'WWW independent allowlist rejects other Dev account');
$invalid=$parameters; $invalid['wp_user_id']=56;
$other=request_for($invalid);
check(YBY_Creator_Readonly_History::authorize($other)===true,'alternate WordPress ID request signed but untrusted');
check(is_wp_error(YBY_Creator_Readonly_History::handle($other)),'wrong WordPress owner denied');
$valid=request_for($parameters);
$GLOBALS['mocks']->badOwner=true;
check(is_wp_error(YBY_Creator_Readonly_History::handle($valid)),'cross-affiliate provider row rejected');
$GLOBALS['mocks']->badOwner=false;
$invalid=$parameters; $invalid['ref_page']=1001;
$paging=request_for($invalid);
check(is_wp_error(YBY_Creator_Readonly_History::authorize($paging)),'out-of-range cursor denied');
$valid=request_for($parameters);
$valid->method='POST';
check(is_wp_error(YBY_Creator_Readonly_History::authorize($valid)),'POST method refused');
echo "CORE_CREATOR_READONLY_SECURITY_PASS\n";
