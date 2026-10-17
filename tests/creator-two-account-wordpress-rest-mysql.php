<?php
/** Disposable real WordPress REST + MySQL, synthetic AffiliateWP provider only. */
declare(strict_types=1);
if (!defined('ABSPATH') || get_option('siteurl')!=='http://creator-ci.invalid') {
    throw new RuntimeException('Refuse non-disposable WordPress instance');
}
$_SERVER['HTTPS']='on';
$_SERVER['SERVER_PORT']='443';
$checks=0;
function verify($ok,$label){global $checks;if(!$ok)throw new RuntimeException('CREATOR_MYSQL_TEST_FAILED: '.$label);$checks++;echo 'PASS '.$label.PHP_EOL;}
function test_user($suffix){
    $name='creator-ci-'.$suffix;
    verify(!get_user_by('login',$name),'Account not pre-existing');
    $id=wp_insert_user(['user_login'=>$name,'user_email'=>$name.'@example.invalid',
        'user_pass'=>'disposable-only','role'=>'subscriber']);
    verify(!is_wp_error($id) && $id>0,'Created synthetic WordPress user in MySQL');
    return (int)$id;
}
$a=test_user('a');$b=test_user('b');
$aid=52101;$bid=52202;
$record=function($aff,$ref,$amount){return ['affiliate_id'=>$aff,'referral_id'=>$ref,
    'date'=>'2026-01-02 12:00:00','amount'=>$amount,'status'=>'paid','currency'=>'USD'];};
$aa=['affiliate_id'=>$aid,'earnings'=>'10.21','unpaid_earnings'=>'7.51','visits'=>11,
    'referrals'=>[$record($aid,8101,'11.11')], 'payouts'=>[
        ['affiliate_id'=>$aid,'date'=>'2026-01-03 12:00:00','amount'=>'3.13','status'=>'paid','currency'=>'USD']]];
$bb=['affiliate_id'=>$bid,'earnings'=>'91.92','unpaid_earnings'=>'85.62','visits'=>99,
    'referrals'=>[$record($bid,8202,'22.22')], 'payouts'=>[
        ['affiliate_id'=>$bid,'date'=>'2026-01-04 12:00:00','amount'=>'4.24','status'=>'paid','currency'=>'USD']]];
update_user_meta($a,'_creator_synth_fixture',$aa);
update_user_meta($b,'_creator_synth_fixture',$bb);
wp_cache_flush();
global $wpdb;
verify((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key=%s AND user_id IN (%d,%d)",
    '_creator_synth_fixture',$a,$b))===2,'Two separate synthetic fixture rows persisted in MySQL');

define('BYL_CREATOR_REMOTE_API_ENABLED',true);
define('BYL_CREATOR_REMOTE_KEY_ID','synthetic-creator-ci');
define('BYL_CREATOR_REMOTE_SIGN_KEY',str_repeat('Z',64));
define('BYL_CREATOR_REMOTE_BINDINGS',[
    32101=>['wp_user_id'=>$a,'affiliate_id'=>$aid],
    32202=>['wp_user_id'=>$b,'affiliate_id'=>$bid],
]);
define('BYL_CREATOR_CANARY_STAGE_DIAGNOSTICS',false);
$GLOBALS['creator_owner']=[$aid=>$a,$bid=>$b];
$GLOBALS['creator_reads']=[];
$GLOBALS['creator_inject_foreign']=false;
function fixture($id){
    $uid=$GLOBALS['creator_owner'][(int)$id]??0;
    $f=$uid?get_user_meta($uid,'_creator_synth_fixture',true):null;
    return is_array($f) && (int)$f['affiliate_id']===(int)$id ? $f:null;
}
class SyntheticAffiliateDatabase {
    public function get_referrals($args){return $this->rows('referrals',$args);}
    public function get_payouts($args){return $this->rows('payouts',$args);}
    private function rows($kind,$args){
        $id=(int)($args['affiliate_id']??0);$f=fixture($id);
        if(!$f)throw new RuntimeException('Unscoped synthetic provider');
        $GLOBALS['creator_reads'][]=$id;
        $rows=array_slice($f[$kind],(int)($args['offset']??0),(int)($args['number']??13));
        if($kind==='referrals' && $GLOBALS['creator_inject_foreign']){
            $rows[]=['affiliate_id'=>$id===52101?52202:52101,'referral_id'=>99999,'amount'=>'99999'];
        }
        return array_map(static function($x){return (object)$x;},$rows);
    }
    public function count($args){
        if(array_keys($args)!==['affiliate_id'])throw new RuntimeException('Unscoped count');
        $id=(int)$args['affiliate_id'];$f=fixture($id);
        if(!$f)throw new RuntimeException('Unknown affiliate in count');
        $GLOBALS['creator_reads'][]=$id;
        return count($f['referrals']);
    }
}
$GLOBALS['creator_db']=new SyntheticAffiliateDatabase();
function affwp_get_affiliate_id($u){$x=get_user_meta((int)$u,'_creator_synth_fixture',true);return is_array($x)?(int)$x['affiliate_id']:0;}
function affwp_get_affiliate_status($id){return fixture($id)?'active':'inactive';}
function affwp_get_affiliate($id){
    $f=fixture($id);
    return $f?(object)['user_id'=>$GLOBALS['creator_owner'][(int)$id],
        'earnings'=>$f['earnings'],'unpaid_earnings'=>$f['unpaid_earnings'],'visits'=>$f['visits']]:false;
}
function affwp_get_currency(){return 'USD';}
function affiliate_wp(){return (object)['affiliates'=>$GLOBALS['creator_db'],
    'referrals'=>$GLOBALS['creator_db'],'payouts'=>$GLOBALS['creator_db']];}

$file=getenv('GITHUB_WORKSPACE').'/inc/class-yby-creator-readonly-history.php';
if(!is_file($file))throw new RuntimeException('Missing exact PR provider source');
require $file;
add_action('rest_api_init',['YBY_Creator_Readonly_History','register_routes']);
$routes=rest_get_server()->get_routes();
verify(isset($routes['/andy-core/v1/creator/history']),'Registered real WordPress REST route');
verify(get_current_user_id()===0,'No real logged-in WordPress user');
function send_rest($p,$opts=[]){
    $uri='/wp-json/andy-core/v1/creator/history?'.http_build_query($p,'','&',PHP_QUERY_RFC3986).
        ($opts['extra']??'');
    $_SERVER['REQUEST_URI']=$uri;
    $method=$opts['method']??'GET';
    $t=(string)(time()+($opts['delta']??0));$nonce=bin2hex(random_bytes(16));
    $sig=hash_hmac('sha256',"GET\n".$uri."\n".$t."\n".$nonce."\n".BYL_CREATOR_REMOTE_KEY_ID,
        BYL_CREATOR_REMOTE_SIGN_KEY);
    $req=new WP_REST_Request($method,'/andy-core/v1/creator/history');
    $req->set_query_params($p);
    if(isset($opts['body']))$req->set_body_params($opts['body']);
    $headers=['X-BYL-Creator-Key'=>BYL_CREATOR_REMOTE_KEY_ID,
        'X-BYL-Creator-Time'=>$t,'X-BYL-Creator-Nonce'=>$nonce];
    if(!isset($opts['no_sig']))$headers['X-BYL-Creator-Signature']=isset($opts['bad_sig'])?str_repeat('0',64):$sig;
    foreach($headers as $k=>$v)$req->set_header($k,$v);
    return rest_do_request($req);
}
function allowed($r,$aff,$uid,$name){
    verify($r instanceof WP_REST_Response && $r->get_status()===200,$name.' 200 REST');
    $d=$r->get_data()['data']??[];
    verify((int)($d['affiliate_id']??0)===$aff&&(int)($d['wp_user_id']??0)===$uid,
        $name.' account ownership');
    foreach(['referrals','payouts'] as $type)foreach($d[$type]??[] as $item){
        verify((int)($item['affiliate_id']??0)===$aff,$name.' owned '.$type);
    }
    return $d;
}
function denied($r,$name){
    verify($r instanceof WP_REST_Response&&$r->get_status()>=400&&$r->get_status()<500,
        $name.' 4xx fail-closed');
    $d=$r->get_data();
    verify(!isset($d['gate'])&&!isset($d['canonical_sha256'])&&!isset($d['uri_sha256']),
        $name.' no diagnostic leak');
}
$pa=['dev_user_id'=>32101,'affiliate_id'=>$aid,'wp_user_id'=>$a,'ref_page'=>1,'pay_page'=>1];
$pb=['dev_user_id'=>32202,'affiliate_id'=>$bid,'wp_user_id'=>$b,'ref_page'=>1,'pay_page'=>1];
$da=allowed(send_rest($pa),$aid,$a,'Creator A');
$db=allowed(send_rest($pb),$bid,$b,'Creator B');
verify($da['account']['earnings']==='10.21'&&$db['account']['earnings']==='91.92',
    'Separate MySQL-backed earnings');
verify($da['referrals'][0]['referral_id']===8101&&$db['referrals'][0]['referral_id']===8202,
    'No shared referral orders');
verify($da['payouts'][0]['amount']==='3.13'&&$db['payouts'][0]['amount']==='4.24',
    'No shared payout records');
verify(strpos(json_encode($da),'8202')===false && strpos(json_encode($db),'8101')===false,
    'No cross-owner response leakage');
$readCount=count($GLOBALS['creator_reads']);
denied(send_rest(array_replace($pa,['affiliate_id'=>$bid])),'A requests B affiliate');
denied(send_rest(array_replace($pa,['wp_user_id'=>$b])),'A requests B user');
denied(send_rest(array_replace($pb,['affiliate_id'=>$aid])),'B requests A affiliate');
denied(send_rest(array_replace($pb,['wp_user_id'=>$a])),'B requests A user');
denied(send_rest(array_replace($pa,['dev_user_id'=>32999])),'Unapproved Dev user');
verify(count($GLOBALS['creator_reads'])===$readCount,'No provider reads on invalid mappings');
$spoof=allowed(send_rest($pa,['body'=>$pb]),$aid,$a,'Signed A URL with forged B body');
verify($spoof['account']['earnings']==='10.21','Signed URL overrides body parameters');
denied(send_rest($pa,['extra'=>'&affiliate_id='.$bid]),'Even signed duplicate query denied');
denied(send_rest($pa,['no_sig'=>true]),'Missing HMAC denied');
denied(send_rest($pa,['bad_sig'=>true]),'Invalid HMAC denied');
denied(send_rest($pa,['delta'=>-160]),'Expired HMAC denied');
$post=send_rest($pa,['method'=>'POST']);
verify($post->get_status()>=400,'No POST mutation route');
$GLOBALS['creator_inject_foreign']=true;
$foreign=send_rest($pa);
verify($foreign->get_status()===503,'Foreign provider record rejects whole response');
$GLOBALS['creator_inject_foreign']=false;
allowed(send_rest($pa),$aid,$a,'A after negative tests');
allowed(send_rest($pb),$bid,$b,'B after negative tests');
verify(get_user_meta($a,'_creator_synth_fixture',true)['earnings']==='10.21'&&
    get_user_meta($b,'_creator_synth_fixture',true)['earnings']==='91.92',
    'No financial fixture mutation');
verify(!array_diff($GLOBALS['creator_reads'],[$aid,$bid]),'Every provider read scoped');
echo 'CREATOR_WP_MYSQL_REAL_REST_SYNTHETIC_UAT_PASS checks='.$checks.PHP_EOL;
