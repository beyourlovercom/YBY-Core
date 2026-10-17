<?php
/**
 * Creator signed-readonly multi-account synthetic UAT.
 * Disposable in-memory fixtures, NO WordPress installation, network, real IDs or secrets.
 * Runs the exact provider class shipped in the independent WWW Canary.
 */
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('BYL_CREATOR_REMOTE_API_ENABLED', true);
define('BYL_CREATOR_REMOTE_KEY_ID', 'synthetic-dev-key');
define('BYL_CREATOR_REMOTE_SIGN_KEY', str_repeat('X', 64));
define('BYL_CREATOR_REMOTE_BINDINGS', [
    321 => ['wp_user_id'=>421,'affiliate_id'=>521],
    322 => ['wp_user_id'=>422,'affiliate_id'=>522],
]);
define('BYL_CREATOR_CANARY_STAGE_DIAGNOSTICS', false);

$GLOBALS['syntheticAccounts'] = [
    521 => ['user'=>421,'earnings'=>'10.21','unpaid'=>'7.51','visits'=>11,
            'referrals'=>[(object)['affiliate_id'=>521,'referral_id'=>8101,'date'=>'2026-01-01','amount'=>'11.11','status'=>'unpaid','currency'=>'USD']],
            'payouts'=>[(object)['affiliate_id'=>521,'date'=>'2026-01-01','amount'=>'3.13','status'=>'paid','currency'=>'USD']]],
    522 => ['user'=>422,'earnings'=>'91.92','unpaid'=>'85.62','visits'=>99,
            'referrals'=>[(object)['affiliate_id'=>522,'referral_id'=>8202,'date'=>'2026-02-02','amount'=>'22.22','status'=>'paid','currency'=>'USD']],
            'payouts'=>[(object)['affiliate_id'=>522,'date'=>'2026-02-02','amount'=>'4.24','status'=>'paid','currency'=>'USD']]],
];
$GLOBALS['nonces'] = [];
$GLOBALS['reads'] = [];
$GLOBALS['otherOwnerRow'] = false;

class WP_Error {
    public $code, $message, $data;
    public function __construct($code,$message,$data=[]) {$this->code=$code;$this->message=$message;$this->data=$data;}
}
class WP_REST_Response {
    public $data, $status;
    public function __construct($data,$status=200) {$this->data=$data;$this->status=$status;}
}
class SyntheticRequest {
    public $method='GET', $headers=[], $params=[];
    public function __construct(array $headers, array $params) {$this->headers=$headers;$this->params=$params;}
    public function get_header($k){return $this->headers[$k]??'';}
    public function get_method(){return $this->method;}
    public function get_params(){return $this->params;}
}
class WP_User {
    public $ID;
    public function __construct(int $id){$this->ID=$id;}
}
class SyntheticRecords {
    public function get_referrals($args) {
        return $this->load('referrals', $args);
    }
    public function get_payouts($args) {
        return $this->load('payouts', $args);
    }
    private function load(string $kind, array $args): array {
        $id=(int)$args['affiliate_id'];
        if (!isset($GLOBALS['syntheticAccounts'][$id])) {
            throw new RuntimeException('Unscoped or unknown synthetic affiliate');
        }
        $GLOBALS['reads'][]=['kind'=>$kind,'affiliate'=>$id,'limit'=>$args['number'],'offset'=>$args['offset']];
        $records=$GLOBALS['syntheticAccounts'][$id][$kind];
        $rows=array_slice($records,(int)$args['offset'],(int)$args['number']);
        if ($GLOBALS['otherOwnerRow'] && $kind==='referrals') {
            $rows[]=(object)['affiliate_id'=>$id===521?522:521,'referral_id'=>99999,'amount'=>'999999'];
        }
        return $rows;
    }
    public function count($args) {
        if (count($args)!==1 || !isset($args['affiliate_id'])) {
            throw new RuntimeException('Count cannot be unscoped');
        }
        $id=(int)$args['affiliate_id'];
        if (!isset($GLOBALS['syntheticAccounts'][$id])) {
            throw new RuntimeException('Unexpected synthetic affiliate count');
        }
        $GLOBALS['reads'][]=['kind'=>'count','affiliate'=>$id];
        return count($GLOBALS['syntheticAccounts'][$id]['referrals']);
    }
}
$GLOBALS['syntheticRecords']=new SyntheticRecords();
function is_wp_error($obj){return $obj instanceof WP_Error;}
function is_ssl(){return true;}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function get_transient($k){return $GLOBALS['nonces'][$k]??false;}
function set_transient($k,$v,$ttl){$GLOBALS['nonces'][$k]=$v;}
function get_userdata($uid){
    return in_array((int)$uid,[421,422],true) ? new WP_User((int)$uid) : false;
}
function affwp_get_affiliate_id($uid){
    if ((int)$uid===421) return 521;
    if ((int)$uid===422) return 522;
    return 0;
}
function affwp_get_affiliate_status($id){return in_array((int)$id,[521,522],true)?'active':'inactive';}
function affwp_get_affiliate($id){
    $row=$GLOBALS['syntheticAccounts'][(int)$id]??null;
    return $row ? (object)['user_id'=>$row['user'],'earnings'=>$row['earnings'],
        'unpaid_earnings'=>$row['unpaid'],'visits'=>$row['visits']] : false;
}
function affwp_get_currency(){return 'USD';}
function affiliate_wp(){
    return (object)['affiliates'=>$GLOBALS['syntheticRecords'],
                    'referrals'=>$GLOBALS['syntheticRecords'],
                    'payouts'=>$GLOBALS['syntheticRecords']];
}
function home_url($path='/'){return 'https://synthetic.example.test'.$path;}
function wp_http_validate_url($url){return filter_var($url,FILTER_VALIDATE_URL)?$url:false;}
function affwp_get_affiliate_referral_url($args) {
    return 'https://synthetic.example.test/ref/'.(int)$args['affiliate_id'].'/';
}
function nocache_headers(){}

require dirname(__DIR__).'/inc/class-yby-creator-readonly-history.php';
$checks=0;
function assertion($test,$msg) {
    global $checks;
    if (!$test) throw new RuntimeException('SYNTHETIC_UAT_FAIL '.$msg);
    $checks++;
    echo 'PASS '.$msg.PHP_EOL;
}
function signed_request(int $dev,int $user,int $affiliate,array $change=[]): SyntheticRequest {
    $p=['dev_user_id'=>$dev,'affiliate_id'=>$affiliate,'wp_user_id'=>$user,
        'ref_page'=>1,'pay_page'=>1];
    $p=array_replace($p,$change);
    $_SERVER['REQUEST_URI']='/wp-json/andy-core/v1/creator/history?'.
        http_build_query($p,'','&',PHP_QUERY_RFC3986);
    $ts=(string)time();
    $nonce=bin2hex(random_bytes(16));
    $signature=hash_hmac('sha256', "GET\n".$_SERVER['REQUEST_URI']."\n".
        $ts."\n".$nonce."\n".BYL_CREATOR_REMOTE_KEY_ID,
        BYL_CREATOR_REMOTE_SIGN_KEY);
    return new SyntheticRequest([
        'X-BYL-Creator-Key'=>BYL_CREATOR_REMOTE_KEY_ID,
        'X-BYL-Creator-Time'=>$ts,
        'X-BYL-Creator-Nonce'=>$nonce,
        'X-BYL-Creator-Signature'=>$signature,
    ],$p);
}
function authorize_and_read(SyntheticRequest $req){
    $ok=YBY_Creator_Readonly_History::authorize($req);
    return $ok===true ? YBY_Creator_Readonly_History::handle($req) : $ok;
}
function assert_denied($result,string $msg){
    assertion($result instanceof WP_Error,$msg);
}
$a=signed_request(321,421,521);
$readA=authorize_and_read($a);
assertion($readA instanceof WP_REST_Response && $readA->status===200,'Synthetic A signed GET 200');
assertion($readA->data['data']['account']['earnings']==='10.21' &&
    $readA->data['data']['account']['unpaid_earnings']==='7.51' &&
    $readA->data['data']['account']['visits']===11,'A finances and visits isolated');
assertion(count($readA->data['data']['referrals'])===1 &&
    $readA->data['data']['referrals'][0]['referral_id']===8101 &&
    $readA->data['data']['referrals'][0]['amount']==='11.11','A referral order owned');
assertion(count($readA->data['data']['payouts'])===1 &&
    $readA->data['data']['payouts'][0]['amount']==='3.13','A payout owned');

$b=signed_request(322,422,522);
$readB=authorize_and_read($b);
assertion($readB instanceof WP_REST_Response && $readB->status===200,'Synthetic B signed GET 200');
assertion($readB->data['data']['account']['earnings']==='91.92' &&
    $readB->data['data']['account']['unpaid_earnings']==='85.62' &&
    $readB->data['data']['account']['visits']===99,'B finances and visits isolated');
assertion(count($readB->data['data']['referrals'])===1 &&
    $readB->data['data']['referrals'][0]['referral_id']===8202 &&
    $readB->data['data']['referrals'][0]['amount']==='22.22','B referral order owned');
assertion(count($readB->data['data']['payouts'])===1 &&
    $readB->data['data']['payouts'][0]['amount']==='4.24','B payout owned');

$serializedA=json_encode($readA->data); $serializedB=json_encode($readB->data);
assertion(strpos($serializedA,'8202')===false && strpos($serializedA,'91.92')===false &&
    strpos($serializedA,'4.24')===false,'No B data leakage in A snapshot');
assertion(strpos($serializedB,'8101')===false && strpos($serializedB,'10.21')===false &&
    strpos($serializedB,'3.13')===false,'No A data leakage in B snapshot');
assertion($GLOBALS['reads'] && count(array_filter($GLOBALS['reads'],
    static function($x){return !in_array($x['affiliate'],[521,522],true);}))===0,
    'All provider reads exactly affiliate-scoped');

$before=count($GLOBALS['reads']);
assert_denied(authorize_and_read(signed_request(321,421,522)),'Signed A attempts B affiliate denied');
assert_denied(authorize_and_read(signed_request(321,422,521)),'Signed A attempts B WordPress ID denied');
assert_denied(authorize_and_read(signed_request(322,421,522)),'Signed B attempts A WordPress ID denied');
assert_denied(authorize_and_read(signed_request(322,422,521)),'Signed B attempts A affiliate denied');
assert_denied(authorize_and_read(signed_request(323,421,521)),'Unknown third Dev account denied');
assertion(count($GLOBALS['reads'])===$before,'No DB reads on invalid identity combinations');

$spoof=signed_request(321,421,521);
$spoof->params=['dev_user_id'=>322,'affiliate_id'=>522,'wp_user_id'=>422,
    'ref_page'=>1,'pay_page'=>1];
$scoped=authorize_and_read($spoof);
assertion($scoped instanceof WP_REST_Response &&
    $scoped->data['data']['affiliate_id']===521 &&
    $scoped->data['data']['wp_user_id']===421,
    'Unsigned body overrides cannot redirect A to B');
$replay=authorize_and_read($spoof);
assertion($replay instanceof WP_Error && ($replay->data['status']??0)===409,
    'HMAC replay rejected');

$invalid=signed_request(321,421,521);
$invalid->headers['X-BYL-Creator-Signature']=str_repeat('0',64);
$invalidResult=authorize_and_read($invalid);
assert_denied($invalidResult,'Invalid HMAC denied');
assertion(!array_key_exists('gate',$invalidResult->data) &&
    !array_key_exists('canonical_sha256',$invalidResult->data) &&
    !array_key_exists('uri_sha256',$invalidResult->data),
    'Production-style denial leaks no diagnostics or canonical hashes');
$missingHeader=signed_request(321,421,521);
unset($missingHeader->headers['X-BYL-Creator-Signature']);
assert_denied(authorize_and_read($missingHeader),'Missing HMAC signature rejected');
$expired=signed_request(321,421,521);
$expired->headers['X-BYL-Creator-Time']=(string)(time()-150);
$expired->headers['X-BYL-Creator-Signature']=hash_hmac('sha256',
    "GET\n".$_SERVER['REQUEST_URI']."\n".
    $expired->headers['X-BYL-Creator-Time']."\n".
    $expired->headers['X-BYL-Creator-Nonce']."\n".
    $expired->headers['X-BYL-Creator-Key'], BYL_CREATOR_REMOTE_SIGN_KEY);
assert_denied(authorize_and_read($expired),'Expired but correctly signed HMAC denied');
$duplicate=signed_request(321,421,521);
$_SERVER['REQUEST_URI'].='&affiliate_id=522';
$duplicate->headers['X-BYL-Creator-Signature']=hash_hmac('sha256',
    "GET\n".$_SERVER['REQUEST_URI']."\n".
    $duplicate->headers['X-BYL-Creator-Time']."\n".
    $duplicate->headers['X-BYL-Creator-Nonce']."\n".
    $duplicate->headers['X-BYL-Creator-Key'], BYL_CREATOR_REMOTE_SIGN_KEY);
assert_denied(authorize_and_read($duplicate),'Correctly signed duplicate affiliate query denied');

$denied=signed_request(321,421,521);
$denied->method='POST';
assert_denied(authorize_and_read($denied),'Mutating HTTP POST denied');

$crossRow=signed_request(321,421,521);
$GLOBALS['otherOwnerRow']=true;
assert_denied(authorize_and_read($crossRow),'Foreign provider referral row fails entire response');
$GLOBALS['otherOwnerRow']=false;
assertion(count($GLOBALS['syntheticAccounts'])===2 &&
    $GLOBALS['syntheticAccounts'][521]['earnings']==='10.21' &&
    $GLOBALS['syntheticAccounts'][522]['earnings']==='91.92',
    'Fixtures remain unchanged; no business writes');

echo 'CREATOR_TWO_IDENTITY_SYNTHETIC_UAT_PASS tests='.$checks.PHP_EOL;
