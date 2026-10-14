<?php
/** M6/M7 offline security and read-only contracts; no WordPress or MySQL needed. */
define( 'ABSPATH', __DIR__ );
function esc_html__( $v, $d = '' ) { return $v; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function number_format_i18n( $n ) { return number_format( $n ); }
function wp_die( $v ) { throw new RuntimeException( 'DENIED' ); }
function current_user_can( $cap ) { return ! empty( $GLOBALS['permissions'][ $cap ] ); }
function get_current_user_id() { return 77; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
class YBY_Three_OS_Admin {
    const NEWSLETTER_VIEW = 'andy_core_newsletter_view';
    const MAIL_VIEW = 'andy_core_mail_view';
    public static function newsletter_tabs() { return array( 'subscribers'=>'x', 'consent'=>'x', 'popup_subscribe'=>'x', 'shortcode_subscribe'=>'x', 'popup_lottery'=>'x', 'campaigns'=>'x', 'approvals'=>'x', 'statistics'=>'x' ); }
    public static function email_tabs() { return array( 'overview'=>'x', 'queue'=>'x', 'failures'=>'x', 'logs'=>'x', 'email_templates'=>'x', 'transport'=>'x', 'health'=>'x' ); }
    public static function newsletter_visible_tabs() {
        return array_intersect_key( self::newsletter_tabs(), array_flip( array( 'subscribers', 'consent', 'popup_subscribe', 'shortcode_subscribe' ) ) );
    }
    public static function email_visible_tabs() {
        return array_intersect_key( self::email_tabs(), array_flip( array( 'overview', 'email_templates', 'transport', 'health' ) ) );
    }
}
class YBY_Module_Registry { public static $enabled=true; public static function is_enabled($n){return self::$enabled;} }
class YBY_Database {
    public static $exists=true;
    public static function leads_table_exists(){return self::$exists;}
    public static function management_tables_exist(){return self::$exists;}
    public static function leads_table_name(){return 'wp_yby_leads';}
    public static function management_table_name(){return 'wp_yby_lead_management';}
}
class YBY_Security {
    public static $view=true;
    public static $sales=false;
    public static function can_view_leads(){return self::$view;}
    public static function is_salesperson(){return self::$sales;}
}
class YBY_Lead_Management { const STATUSES=array('new','pending_contact','contacted','following_up','pending_quote','quoted','won','invalid','closed'); }
class YBY_Newsletter_Store {
    public static $exists=true;
    public static function exists(){return self::$exists;}
    public static function table_name(){return 'wp_yby_newsletter_subscriptions';}
}
class YBY_Email_Health_Center {
    public function transport_status(){return array('plugin'=>'WP Mail SMTP','provider'=>'Other SMTP','configured'=>true,'api_key'=>'DO_NOT_SHOW','settings_url'=>'https://bad.test?pass=secret');}
}
class FakeWPDB {
    public $last_error='';
    public $sqls=array();
    public function prepare($sql,$arg){ return str_replace('%d',intval($arg),$sql); }
    public function get_results($sql,$output){$this->sqls[]=$sql; if(strpos($sql,'newsletter_subscriptions')!==false)return array(array('status'=>'pending','n'=>'2'),array('status'=>'subscribed','n'=>'4'),array('status'=>'bad','n'=>'999'));return array(array('state'=>'new','n'=>'3'),array('state'=>'won','n'=>'1'),array('state'=>'unexpected','n'=>'999'));}
}
$wpdb=new FakeWPDB();
define('ARRAY_A','ARRAY_A');
$checks=0;
function check_m7($pass,$why){global $checks;$checks++;if(!$pass)throw new RuntimeException('M7_FAIL:'.$why);}
require dirname(__DIR__).'/admin/class-yby-three-os-readonly.php';
$GLOBALS['permissions']=array('andy_core_newsletter_view'=>true,'andy_core_mail_view'=>true,'manage_options'=>true);
check_m7(count(YBY_Three_OS_Admin::newsletter_visible_tabs())===4,'newsletter 4 useful tabs');
check_m7(!isset(YBY_Three_OS_Admin::newsletter_visible_tabs()['campaigns']),'hide marketing draft');
check_m7(!isset(YBY_Three_OS_Admin::newsletter_visible_tabs()['popup_lottery']),'hide unavailable lottery');
check_m7(count(YBY_Three_OS_Admin::email_visible_tabs())===4,'email 4 actual pages');
foreach(array('queue','failures','logs') as $tab){check_m7(!isset(YBY_Three_OS_Admin::email_visible_tabs()[$tab]),'hide inactive '.$tab);}
$r=YBY_Three_OS_Readonly::inquiry_status_counts();
check_m7($r['total']===4,'only recognized lead statuses');
check_m7($r['counts']['won']===1&&$r['counts']['new']===3,'real status counts');
check_m7($r['scoped']===false,'admin all scope');
check_m7(strpos(end($wpdb->sqls),'owner_user_id')===false,'admin no owner filter');
YBY_Security::$sales=true;$GLOBALS['permissions']['manage_options']=false;
$r=YBY_Three_OS_Readonly::inquiry_status_counts();
check_m7($r['scoped']===true,'salesperson must be scoped');
check_m7(strpos(end($wpdb->sqls),'m.owner_user_id=77')!==false,'SQL enforces own record scope');
YBY_Security::$view=false;
check_m7(YBY_Three_OS_Readonly::inquiry_status_counts()===null,'unauthorized leads denied');
try{YBY_Three_OS_Readonly::render_inquiry_stats();check_m7(false,'denial missing');}catch(RuntimeException $e){check_m7($e->getMessage()==='DENIED','inquiry renderer fail closed');}
YBY_Security::$view=true;YBY_Database::$exists=false;
check_m7(YBY_Three_OS_Readonly::inquiry_status_counts()===null,'absent leads table not zero');
YBY_Database::$exists=true;
$r=YBY_Three_OS_Readonly::newsletter_status_counts();
check_m7($r['total']===6,'subscription valid statuses only');
check_m7($r['counts']['pending']===2&&$r['counts']['subscribed']===4,'status groups');
check_m7(!str_contains(end($wpdb->sqls),'email'),'no email selected in consent count');
$GLOBALS['permissions']['andy_core_newsletter_view']=false;
check_m7(YBY_Three_OS_Readonly::newsletter_status_counts()===null,'newsletter cap required');
$GLOBALS['permissions']['andy_core_newsletter_view']=true;
YBY_Newsletter_Store::$exists=false;
check_m7(YBY_Three_OS_Readonly::newsletter_status_counts()===null,'missing newsletter table not zero');
YBY_Newsletter_Store::$exists=true;YBY_Module_Registry::$enabled=false;
check_m7(YBY_Three_OS_Readonly::newsletter_status_counts()===null,'disabled newsletter unavailable');
YBY_Module_Registry::$enabled=true;
$h=YBY_Three_OS_Readonly::email_health();
check_m7($h['transport_plugin']==='WP Mail SMTP','real provider plugin');
check_m7($h['provider']==='Other SMTP','real provider name');
check_m7($h['configured']===true,'config label');
check_m7($h['mail_v2_queue']===false&&$h['mail_v2_worker']===false&&$h['mail_v2_outbound']===false,'no fictitious live worker');
check_m7(!isset($h['api_key'])&&!isset($h['settings_url']),'never expose keys and links');
$GLOBALS['permissions']['andy_core_mail_view']=false;
check_m7(YBY_Three_OS_Readonly::email_health()===null,'Email role required');
$GLOBALS['permissions']['andy_core_mail_view']=true;
ob_start();YBY_Three_OS_Readonly::render_newsletter_consent();$html=ob_get_clean();
check_m7(str_contains($html,'已订阅')&&str_contains($html,'待确认'),'consent dashboard real labels');
check_m7(!str_contains($html,'DO_NOT_SHOW')&&!str_contains($html,'unsubscribe_hash'),'privacy without secrets');
ob_start();YBY_Three_OS_Readonly::render_email_summary('health');$html=ob_get_clean();
check_m7(str_contains($html,'未启用'),'health warns disabled');
check_m7(!str_contains($html,'DO_NOT_SHOW')&&!str_contains($html,'pass=secret'),'health no credentials');
$source=file_get_contents(dirname(__DIR__).'/admin/class-yby-three-os-readonly.php');
check_m7(!preg_match('/\b(?:wp_mail|wp_schedule_event|as_enqueue_async_action|dbDelta|insert|update_option|delete_option)\s*\(/i',$source),'no send/DB mutations');
echo 'PASS three-os-m6-m7-readonly assertions='.$checks."\n";
