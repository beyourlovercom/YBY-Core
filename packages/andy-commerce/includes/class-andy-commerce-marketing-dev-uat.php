<?php
/**
 * Marketing Dev UAT: fail-closed preview administration, never production.
 * Does not create Woo orders, change product prices, or publish real offers.
 */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
require_once __DIR__.'/class-andy-commerce-marketing-p1-sandbox-contract.php';
require_once __DIR__.'/class-andy-commerce-m15-cap-ledger.php';

final class Andy_Commerce_Marketing_Dev_UAT {
    private const STATUS='andy_commerce_marketing_dev_uat_enabled';
    private const DRAFT=Andy_Commerce_Marketing_P1_Sandbox_Contract::APPROVAL_OPTION;
    private const SWITCH=Andy_Commerce_Marketing_P1_Sandbox_Contract::SWITCH_OPTION;

    public static function site_gate(): bool {
        // Rendering the read-only Dev admin panel and installing inert M15
        // schema do not require runtime authorization. Runtime enablement
        // remains guarded by the wp-config constant in the P1 contract.
        return function_exists('home_url')
            && rtrim(home_url(),'/')==='https://dev.beyourlover.com'
            && function_exists('wp_get_environment_type')
            && in_array(wp_get_environment_type(),['staging','development'],true)
            // The inert admin/DDL panel must not disappear when a Currency
            // admin visitor has selected EUR/GBP. Promo runtime separately
            // enforces native USD in the P1 approval contract.
            && function_exists('is_ssl') && is_ssl();
    }

    public static function boot(): void {
        if(!is_admin())return;
        add_action('admin_post_andy_commerce_marketing_dev_uat', [self::class,'post']);
    }

    private static function authority(): void {
        if(!self::site_gate()||!current_user_can('manage_woocommerce')){
            wp_die('Marketing Dev UAT is not authorized on this site','', ['response'=>403]);
        }
        check_admin_referer('andy_commerce_marketing_dev_uat');
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST')
            wp_die('POST required','',['response'=>405]);
    }

    public static function tables_exist(): bool {
        global $wpdb;
        $ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
        // Exact current prefix, not a table-name substring false-positive.
        foreach(['aicc_m15_campaign_caps','aicc_m15_campaign_claims'] as $suffix){
            $table=$wpdb->prefix.$suffix;
            if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)
                return false;
        }
        return true;
    }

    public static function post(): void {
        self::authority();
        $mode=sanitize_key(wp_unslash($_POST['mode']??''));
        // Emergency OFF works without altering drafts or dropping any tables.
        if($mode==='disable'){
            update_option(self::SWITCH,'no',false);
            update_option(self::STATUS,'no',false);
        } elseif($mode==='schema'){
            // Non-destructive, additive, explicit admin/nonce opt-in only.
            global $wpdb;
            $ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
            foreach($ledger->schema_sql() as $statement){
                if($wpdb->query($statement)===false)
                    wp_die('M15 schema creation failed','',['response'=>500]);
            }
            if(!self::tables_exist())
                wp_die('M15 schema verification failed','',['response'=>500]);
        } elseif($mode==='arm'){
            if(!Andy_Commerce_Marketing_P1_Sandbox_Contract::legacy_runtime_allowed())
                wp_die('Andy Campaign must be OFF before arming legacy Marketing V1','',['response'=>409]);
            if(!defined('AICC_MARKETING_DEV_UAT') || AICC_MARKETING_DEV_UAT!==true)
                wp_die('Runtime UAT is disabled by server configuration','',['response'=>403]);
            if(!self::tables_exist())
                wp_die('Create and verify M15 cap tables first','',['response'=>422]);
            $id=sanitize_key(wp_unslash($_POST['draft_id']??''));
            $rows=Andy_Commerce_M7_Draft_Studio::rows();
            $draft=$rows[$id]??null;
            if(!is_array($draft)||($draft['lifecycle']??'')!=='scheduled')
                wp_die('Choose a scheduled OFF draft','',['response'=>422]);
            $raw=sanitize_text_field(wp_unslash($_POST['uat_product_ids']??''));
            if(!preg_match('/^[0-9, ]{1,220}$/D',$raw))
                wp_die('Explicit UAT product ID whitelist required','',['response'=>422]);
            $ids=array_values(array_unique(array_map('intval',explode(',',str_replace(' ','',$raw)))));
            if(!$ids||count($ids)>20||in_array(0,$ids,true))
                wp_die('Invalid UAT product whitelist','',['response'=>422]);
            $flags=[];
            foreach($ids as $productId){
                $product=wc_get_product($productId);
                if(!($product instanceof WC_Product)
                    || $product->get_catalog_visibility()!=='hidden'
                    || get_post_meta($productId,'_aicc_marketing_uat_fixture',true)!=='yes')
                    wp_die('Every UAT product must be hidden and explicitly fixture-tagged',
                        '', ['response'=>422]);
                $gift=($draft['type']==='free_gift'
                    &&(int)($draft['rule']['product_id']??0)===$productId);
                $flags[(string)$productId]=[
                    'eligible_for_promotion'=>!$gift,
                    'special_shipping'=>false,
                    'free_gift'=>$gift,
                ];
            }
            if($draft['type']==='free_gift'
                &&!isset($flags[(string)($draft['rule']['product_id']??0)]))
                wp_die('Gift product must be included in explicit UAT whitelist',
                    '',['response'=>422]);
            $uses=absint($_POST['max_uses']??0);
            if($uses<1||$uses>5)wp_die('Dev UAT maximum 1–5 uses','',['response'=>422]);
            // Stop the old campaign BEFORE changing approval/sku flags.
            update_option(self::SWITCH,'no',false);
            update_option(self::STATUS,'no',false);
            try {
                $snapshot=Andy_Commerce_Marketing_P1_Sandbox_Contract::prepare($draft);
            } catch (Throwable $e) {
                // Invalid draft or owner/currency context is a user-facing
                // validation failure, never a WordPress critical error.
                wp_die('Test activity approval rejected; verify the scheduled OFF draft, USD store base and Woo admin permissions.',
                    '', ['response'=>422]);
            }
            update_option(self::DRAFT,$snapshot,false);
            update_option('andy_commerce_marketing_p2_line_flags',$flags,false);
            update_option('andy_commerce_marketing_p3_sku_enabled','yes',false);
            update_option('andy_commerce_marketing_p4_caps',[
                $id=>['revision'=>$draft['revision'],'max_uses'=>$uses],
            ],false);
            // All six Dev UAT types now have a guarded M15 reservation path.
            update_option('andy_commerce_marketing_p4_order_cap_enabled',
                'yes',false);
            update_option(self::SWITCH,'yes',false);
            update_option(self::STATUS,'yes',false);
        } elseif($mode==='preview'){
            // Preview cannot switch on Woo promotion hooks. Save an approved
            // snapshot only after verifying exact draft/version and whitelist.
            $id=sanitize_key(wp_unslash($_POST['draft_id']??''));
            $rows=Andy_Commerce_M7_Draft_Studio::rows();
            $draft=$rows[$id]??null;
            if(!is_array($draft)||($draft['lifecycle']??'')!=='scheduled'){
                wp_die('Choose a valid scheduled OFF draft','',['response'=>422]);
            }
            // Force OFF first. Pure fingerprinted snapshot remains test-only.
            update_option(self::SWITCH,'no',false);
            update_option(self::STATUS,'no',false);
            // prepare() uses strict isolated local OR *already approved* Dev
            // session. Avoid a circular privilege/grant by NOT activating here.
            $snapshot=[
                'draft_id'=>$id,'revision'=>(int)($draft['revision']??0),
                'mode'=>'REVIEW_ONLY_NOT_RUNTIME','recorded_at'=>gmdate('c'),
            ];
            update_option('andy_commerce_marketing_dev_preview_request',$snapshot,false);
        }else{
            wp_die('Unsupported Marketing Dev UAT command','',['response'=>400]);
        }
        wp_safe_redirect(add_query_arg([
            'page'=>'andy-commerce','tab'=>'marketing-promotions',
            'marketing_dev_result'=>$mode,
        ],admin_url('admin.php')));
        exit;
    }

    public static function render_panel(): void {
        if(!self::site_gate() || !current_user_can('manage_woocommerce'))return;
        $table='NOT_CREATED';
        try{$table=self::tables_exist()?'SCHEMA_PRESENT':'NOT_CREATED';}catch(Throwable $e){}
        echo '<div class="ac-card"><h2>Marketing OS — 受控 Dev UAT</h2>';
        echo '<p>默认 OFF。仅用于 Dev 审核；不触发生产优惠、赠品或真实支付。</p>';
        $serverUat=defined('AICC_MARKETING_DEV_UAT')&&AICC_MARKETING_DEV_UAT===true;
        echo '<p>运行时服务器授权：<strong>'.
            esc_html($serverUat?'AVAILABLE':'NOT_CONFIGURED — 管理与建表可用，营销执行禁用').
            '</strong></p>';
        echo '<p>M15 名额表：<strong>'.esc_html($table).'</strong>；自动运行状态：<strong>'.
            esc_html(get_option(self::STATUS,'no')==='yes'?'ON':'OFF').'</strong></p>';
        foreach([['schema','初始化 M15 名额表'],['disable','立即关闭营销运行']] as $a){
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-right:12px">';
            echo '<input type="hidden" name="action" value="andy_commerce_marketing_dev_uat">';
            echo '<input type="hidden" name="mode" value="'.esc_attr($a[0]).'">';
            wp_nonce_field('andy_commerce_marketing_dev_uat');
            echo '<button type="submit" class="button">'.esc_html($a[1]).'</button></form>';
        }
        if($table==='SCHEMA_PRESENT' && $serverUat){
            $rows=Andy_Commerce_M7_Draft_Studio::rows();
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            echo '<input type="hidden" name="action" value="andy_commerce_marketing_dev_uat">';
            echo '<input type="hidden" name="mode" value="arm">';
            wp_nonce_field('andy_commerce_marketing_dev_uat');
            echo '<p><label>仅选择已排期的 OFF 草稿：<select name="draft_id" required>';
            foreach($rows as $row){
                if(($row['lifecycle']??'')!=='scheduled')continue;
                echo '<option value="'.esc_attr($row['id']).'">'.esc_html($row['name']).'</option>';
            }
            echo '</select></label></p>';
            echo '<p><label>仅填已标记 _aicc_marketing_uat_fixture=yes 且隐藏目录的测试产品 ID（逗号分隔）<input type="text" name="uat_product_ids" required></label></p>';
            echo '<p><label>测试名额上限（1–5）<input type="number" name="max_uses" min="1" max="5" value="2" required></label></p>';
            echo '<p><button type="submit" class="button button-secondary">仅本管理员会话受控启用 Dev 测试活动</button></p>';
            echo '</form>';
        }
        echo '<p>仅登录的 Woo 管理员可受控测试；访客和真实订单不参与，退款不会返还名额。</p></div>';
    }
}
