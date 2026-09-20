<?php
/**
 * Site-level feature module registry.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Module_Registry {
    const OPTION_KEY = 'yby_core_enabled_modules_v1';
    const VERSION = '1';

    public static function foundation() {
        return array(
            'core_runtime' => 'Core Runtime',
            'security' => 'Security Foundation',
            'database' => 'Database Migration',
            'updater' => 'Signed Updater',
            'site_identity' => 'Site Identity',
            'module_registry' => 'Module Registry',
        );
    }

    public static function modules() {
        return array(
            'inquiry_os' => array('label'=>'Inquiry OS','description'=>'询盘、弹窗、短代码与 Lead Runtime。','default'=>true,'status'=>'ready','settings'=>array('page'=>'yby-core','tab'=>'inquiry'),'dependencies'=>array()),
            'email_os' => array('label'=>'Email OS','description'=>'邮件模板治理、Native 发布、测试与健康中心。','default'=>true,'status'=>'ready','settings'=>array('page'=>'yby-core-popups','tab'=>'email_templates'),'dependencies'=>array()),
            'project_studio' => array('label'=>'Project Studio','description'=>'项目、Landing Page 与站点项目内容管理。','default'=>true,'status'=>'ready','settings'=>array('page'=>'yby-os'),'dependencies'=>array()),
            'social_login' => array('label'=>'Social Login','description'=>'Google 等第三方登录 Runtime 与管理。','default'=>true,'status'=>'ready','settings'=>array('page'=>'yby-social-login'),'dependencies'=>array()),
            'connector' => array('label'=>'Connector / WP-API','description'=>'ERP 与外部系统的签名 Connector API。','default'=>true,'status'=>'ready','settings'=>array('page'=>'yby-core','tab'=>'wp-api'),'dependencies'=>array()),
            'docs_os' => array('label'=>'Andy Docs','description'=>'FAQ、Tutorial、Docs、搜索、TOC 与 Schema。','default'=>false,'status'=>'ready','settings'=>array('page'=>'yby-docs-os'),'dependencies'=>array()),
        );
    }

    public static function defaults() {
        $enabled = array();
        foreach ( self::modules() as $id => $module ) { if ( ! empty( $module['default'] ) ) { $enabled[] = $id; } }
        return $enabled;
    }

    public static function enabled_modules() {
        $stored = get_option( self::OPTION_KEY, null );
        return is_array( $stored ) ? self::sanitize_enabled_modules( $stored ) : self::defaults();
    }

    public static function sanitize_enabled_modules( $raw ) {
        $known = array_keys( self::modules() );
        $raw = is_array( $raw ) ? $raw : array();
        $clean = array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw ), static function ( $id ) use ( $known ) { return in_array( $id, $known, true ); } ) ) );
        return array_values( array_filter( $clean, static function ( $id ) { $module = self::modules()[ $id ]; return 'planned' !== $module['status']; } ) );
    }

    public static function save( $raw ) { return update_option( self::OPTION_KEY, self::sanitize_enabled_modules( $raw ), false ); }
    public static function is_enabled( $id ) { return in_array( sanitize_key( $id ), self::enabled_modules(), true ); }

    public static function settings_url( $id ) {
        $module = self::modules()[ sanitize_key( $id ) ] ?? array();
        $target = $module['settings'] ?? array();
        if ( empty( $target['page'] ) ) { return ''; }
        $args = array( 'page' => $target['page'] );
        if ( ! empty( $target['tab'] ) ) { $args['tab'] = $target['tab']; }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }
}
