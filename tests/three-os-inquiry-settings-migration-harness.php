<?php
/** P3/M5 Inquiry OS settings migration regression; no WordPress, DB or mail. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$checks=0;
function verify_three_settings($ok,$why) { global $checks; $checks++; if(!$ok){throw new RuntimeException($why);} }
class YBY_Module_Registry {
    public static function is_enabled($name){return in_array($name,array('inquiry_os','email_os','connector'),true);}
    public static function module($name){return array();}
}
class YBY_Security { public function can_manage_settings(){return true;} }
require dirname(__DIR__).'/admin/class-yby-admin.php';
$visible=YBY_Admin::settings_tabs();
$legacy=YBY_Admin::settings_tabs(true);
verify_three_settings(!isset($visible['inquiry']) && !isset($visible['inquiry-notification']),'no Inquiry duplication in Settings tabs');
verify_three_settings(isset($legacy['inquiry']) && isset($legacy['inquiry-notification']),'legacy Settings POST routes retained');
verify_three_settings(isset($visible['general'],$visible['modules'],$visible['addons'],$visible['wp-api'],$visible['system-status'],$visible['updates']),'other Settings tabs intact');
$root=dirname(__DIR__);
$core=file_get_contents($root.'/inc/class-yby-core.php');
$admin=file_get_contents($root.'/admin/class-yby-admin.php');
$ia=file_get_contents($root.'/admin/class-yby-inquiry-admin.php');
$os=file_get_contents($root.'/admin/class-yby-three-os-admin.php');
$v1=file_get_contents($root.'/admin/views/inquiry-settings.php');
$v2=file_get_contents($root.'/admin/views/inquiry-notification-settings.php');
verify_three_settings(str_contains($os,"'settings' => '询盘设置'") && str_contains($os,"'notification' => '询盘通知'"),'Inquiry owns both nav labels');
verify_three_settings(str_contains($ia,"'settings', 'notification'") && str_contains($ia,'render_inquiry_os_settings'),'both Inquiry pages use real handler');
verify_three_settings(!str_contains($ia,'打开现有询盘通知设置'),'remove Settings bounce link');
verify_three_settings(str_contains($os,'redirect_legacy_settings_get') && str_contains($core,"'redirect_legacy_settings_get', 21"),'legacy redirects registered');
verify_three_settings(str_contains($os,"'GET' !== ( \$_SERVER['REQUEST_METHOD'] ?? '' )"),'POST never redirected');
verify_three_settings(str_contains($os,"'inquiry' => 'settings', 'inquiry-notification' => 'notification'"),'legacy tab map exactly');
verify_three_settings(str_contains($os,'andy_core_settings_manage') && str_contains($os,'andy_core_leads_view'),'redirect capability gated');
verify_three_settings(str_contains($admin,'settings_tabs( true )'),'legacy POST route stays renderable');
verify_three_settings(str_contains($admin,'$this->security->can_manage_settings()'),'old settings privilege remains');
verify_three_settings(str_contains($admin,"check_admin_referer( 'yby_inquiry_settings_save'"),'Inquiry save nonce intact');
verify_three_settings(str_contains($admin,"check_admin_referer( 'yby_inquiry_notification_save'"),'notification save nonce intact');
verify_three_settings(str_contains($admin,"update_option( 'yby_core_inquiry_settings'"),'Inquiry storage not replaced');
verify_three_settings(str_contains($admin,'YBY_Helpers::lead_notification_primary_recipient_option_key()'),'notification storage intact');
verify_three_settings(str_contains($v1,'empty( $this->inquiry_os_embedded )') && str_contains($v2,'empty( $this->inquiry_os_embedded )'),'legacy header hidden only when embedded');
verify_three_settings(str_contains($v1,'yby_inquiry_settings_submit') && str_contains($v2,'yby_inquiry_notification_submit'),'form names preserved');
verify_three_settings(str_contains($v2,'Primary Recipient Email') && str_contains($v2,'WhatsApp Message Template'),'notification form reused');
verify_three_settings(!preg_match('/\b(?:wp_mail|as_schedule_single_action|as_enqueue_async_action|dbDelta)\s*\(/',$os),'no mail/queue/schema changes');
echo 'PASS three-os-inquiry-settings-migration assertions='.$checks."\n";
