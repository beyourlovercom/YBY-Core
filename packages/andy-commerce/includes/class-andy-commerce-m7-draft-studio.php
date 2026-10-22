<?php
/**
 * M7/M8 drafts-only marketing workbench. All campaigns are inactive, no
 * public pricing hooks, no shipping settings or Woo order writes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_M7_Draft_Studio {
    const OPTION = 'andy_commerce_m7_inactive_drafts_v1';
    const TYPES = array('bogo','bundles','spend_save','tiered','free_gift');

    public static function dev_only() {
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        // A misconfigured production wp_get_environment_type() must never
        // enable editing on WWW. Require an explicit dev.* hostname.
        return 0 === strpos($host,'dev.');
    }

    public static function boot() {
        if (!is_admin()) return;
        add_action('admin_post_andy_commerce_m7_save_draft',array(__CLASS__,'save'));
        add_action('admin_post_andy_commerce_m7_archive_draft',array(__CLASS__,'archive'));
    }

    public static function rows() {
        $rows=get_option(self::OPTION,array());
        if (!is_array($rows)) return array();
        $clean=array();
        foreach(array_slice($rows,0,50,true) as $id=>$row) {
            if (!is_string($id)||!is_array($row))continue;
            if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id))continue;
            $row['id']=$id;$row['active']=false;
            $row['status']='draft';
            $clean[$id]=$row;
        }
        return $clean;
    }

    private static function require_authority($action) {
        if (!self::dev_only()||!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Draft management is Dev administrator only.','andy-commerce'),'',array('response'=>403));
        }
        // The HTML forms use wp_nonce_field('andy_commerce_m7_<action>_draft').
        // Match that EXACT action on save/edit and archive; a mismatch causes
        // WordPress to show an incorrect “link expired” error on fresh forms.
        check_admin_referer('andy_commerce_m7_'.$action.'_draft');
    }

    private static function go($result) {
        wp_safe_redirect(add_query_arg(array(
            'page'=>'andy-commerce', 'tab'=>'marketing-promotions',
            'm7_result'=>sanitize_key($result),
        ),admin_url('admin.php')));
        exit;
    }

    public static function save() {
        self::require_authority('save');
        if (strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST') {
            wp_die('POST required','',array('response'=>405));
        }
        $id = isset($_POST['draft_id']) ? sanitize_key(wp_unslash($_POST['draft_id'])) : '';
        $revision = isset($_POST['draft_revision']) ? absint($_POST['draft_revision']) : 0;
        $name = sanitize_text_field(wp_unslash($_POST['draft_name']??''));
        $type = sanitize_key(wp_unslash($_POST['draft_type']??''));
        $currency = strtoupper(sanitize_key(wp_unslash($_POST['draft_currency']??'USD')));
        $countries_input = sanitize_text_field(wp_unslash($_POST['draft_countries']??'US'));
        $priority = isset($_POST['draft_priority']) ? intval(wp_unslash($_POST['draft_priority'])) : 0;
        $rawRule=wp_unslash($_POST['draft_rule_json']??'{}');
        if (!is_string($rawRule)||strlen($rawRule)>3000) self::go('invalid');
        $rule=json_decode($rawRule,true);
        $countries=array_values(array_unique(array_filter(array_map('trim',explode(',',$countries_input)))));
        $countries=array_map('strtoupper',$countries);
        if (!$name||strlen($name)>120||!in_array($type,self::TYPES,true)
            ||!preg_match('/^[A-Z]{3}$/D',$currency)||!is_array($rule)
            ||count($rule)>20||count($countries)<1||count($countries)>30
            ||$priority<0||$priority>1000) self::go('invalid');
        foreach ($countries as $country) {
            if (!preg_match('/^[A-Z]{2}$/D',$country))self::go('invalid');
        }
        $rows=self::rows();
        if ($id==='') {
            if (count($rows)>=50) self::go('limit');
            $id='draft-'.str_replace('-','',wp_generate_uuid4());
            $revision=0;
        } elseif (!isset($rows[$id])||($rows[$id]['revision']??-1)!==$revision) {
            self::go('conflict');
        }
        $rows[$id]=array(
            'id'=>$id,'revision'=>$revision+1,'name'=>$name,
            'type'=>$type,'currency'=>$currency,'countries'=>$countries,
            'priority'=>$priority,'rule'=>$rule,'status'=>'draft',
            'active'=>false,'stack_with_coupon'=>false,
            'edited_utc'=>gmdate('c'),
        );
        update_option(self::OPTION,$rows,false);
        self::go('saved');
    }

    public static function archive() {
        self::require_authority('archive');
        if (strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST') {
            wp_die('POST required','',array('response'=>405));
        }
        $id=sanitize_key(wp_unslash($_POST['draft_id']??''));
        $revision=absint($_POST['draft_revision']??0);
        $rows=self::rows();
        if (!isset($rows[$id])||$rows[$id]['revision']!==$revision) self::go('conflict');
        // Archive = remove from *inactive draft workbench* only.
        // No published campaign exists and no Woo coupon or price is changed.
        unset($rows[$id]);
        update_option(self::OPTION,$rows,false);
        self::go('archived');
    }
}
