<?php
/**
 * Site-level feature module registry.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Module_Registry {
    const OPTION_KEY = 'yby_core_enabled_modules_v1';
    const ADOPTION_OPTION = 'yby_core_module_adoptions_v1';
    const VERSION = '2';

    protected static $registered = array();

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

    protected static function core_modules() {
        return array(
            'inquiry_os' => array(
                'name' => 'Inquiry OS',
                'description' => '询盘、弹窗、短代码与 Lead Runtime。',
                'default_enabled' => true,
                'status' => 'ready',
                'settings' => array( 'page' => 'yby-core', 'tab' => 'inquiry' ),
            ),
            'email_os' => array(
                'name' => 'Email OS',
                'description' => '邮件模板治理、Native 发布、测试与健康中心。',
                'default_enabled' => true,
                'status' => 'ready',
                'settings' => array( 'page' => 'yby-core-popups', 'tab' => 'email_templates' ),
            ),
            'project_studio' => array(
                'name' => 'Project Studio',
                'description' => '项目、Landing Page 与站点项目内容管理。',
                'default_enabled' => true,
                'status' => 'ready',
                'settings' => array( 'page' => 'yby-os' ),
            ),
            'social_login' => array(
                'name' => 'Social Login',
                'description' => 'Google 等第三方登录 Runtime 与管理。',
                'default_enabled' => true,
                'status' => 'ready',
                'settings' => array( 'page' => 'yby-social-login' ),
            ),
            'connector' => array(
                'name' => 'Connector / WP-API',
                'description' => 'ERP 与外部系统的签名 Connector API。',
                'default_enabled' => true,
                'status' => 'ready',
                'settings' => array( 'page' => 'yby-core', 'tab' => 'wp-api' ),
            ),
            'docs_os' => array(
                'name' => 'Andy Docs',
                'description' => 'FAQ、Tutorial、Docs、搜索、TOC 与 Schema。',
                'default_enabled' => false,
                'status' => 'ready',
                'capability' => 'andy_core_settings_manage',
                'admin_parent' => 'yby-content',
                'settings' => array( 'page' => 'yby-docs-os' ),
            ),
            'landing_pages' => array(
                'name' => 'Landing Pages',
                'description' => '广告与 Campaign 独立落地页，公开路径固定为 /lp/{slug}。',
                'version' => '1.0.0',
                'schema_version' => '1',
                'default_enabled' => true,
                'status' => 'ready',
                'capability' => 'edit_pages',
                'admin_parent' => 'yby-content',
                'settings' => array( 'url' => 'edit.php?post_type=yby_landing_page' ),
            ),
        );
    }

    protected static function normalize_module( $id, $module ) {
        $module = is_array( $module ) ? $module : array();
        $name = isset( $module['name'] ) ? $module['name'] : ( isset( $module['label'] ) ? $module['label'] : $id );
        $default = array_key_exists( 'default_enabled', $module )
            ? (bool) $module['default_enabled']
            : ! empty( $module['default'] );

        return array(
            'id' => sanitize_key( $id ),
            'name' => (string) $name,
            'label' => (string) $name,
            'description' => isset( $module['description'] ) ? (string) $module['description'] : '',
            'version' => isset( $module['version'] ) ? (string) $module['version'] : '',
            'schema_version' => isset( $module['schema_version'] ) ? (string) $module['schema_version'] : '',
            'default_enabled' => $default,
            'default' => $default,
            'status' => isset( $module['status'] ) ? sanitize_key( $module['status'] ) : 'ready',
            'capability' => isset( $module['capability'] ) ? (string) $module['capability'] : '',
            'admin_parent' => isset( $module['admin_parent'] ) ? sanitize_key( $module['admin_parent'] ) : '',
            'bootstrap_class' => isset( $module['bootstrap_class'] ) ? (string) $module['bootstrap_class'] : '',
            'boot' => isset( $module['boot'] ) && is_callable( $module['boot'] ) ? $module['boot'] : null,
            'admin_menu' => isset( $module['admin_menu'] ) && is_callable( $module['admin_menu'] ) ? $module['admin_menu'] : null,
            'admin_menu_priority' => isset( $module['admin_menu_priority'] ) ? (int) $module['admin_menu_priority'] : 20,
            'settings' => isset( $module['settings'] ) && is_array( $module['settings'] ) ? $module['settings'] : array(),
            'settings_register' => isset( $module['settings_register'] ) && is_callable( $module['settings_register'] ) ? $module['settings_register'] : null,
            'settings_priority' => isset( $module['settings_priority'] ) ? (int) $module['settings_priority'] : 10,
            'assets' => isset( $module['assets'] ) && is_array( $module['assets'] ) ? $module['assets'] : array( 'admin' => array(), 'frontend' => array() ),
            'dependencies' => isset( $module['dependencies'] ) && is_array( $module['dependencies'] ) ? array_values( array_unique( array_map( 'sanitize_key', $module['dependencies'] ) ) ) : array(),
        );
    }

    public static function register( $id, $metadata ) {
        $id = sanitize_key( $id );
        if ( '' === $id || ! is_array( $metadata ) || isset( self::core_modules()[ $id ] ) || isset( self::$registered[ $id ] ) ) {
            return false;
        }
        $module = self::normalize_module( $id, $metadata );
        if ( '' === trim( $module['name'] ) ) {
            return false;
        }
        self::$registered[ $id ] = $module;
        return true;
    }

    public static function modules() {
        $modules = array();
        foreach ( self::core_modules() as $id => $module ) {
            $modules[ $id ] = self::normalize_module( $id, $module );
        }
        foreach ( self::$registered as $id => $module ) {
            $modules[ $id ] = $module;
        }
        return $modules;
    }

    public static function module( $id ) {
        $modules = self::modules();
        $id = sanitize_key( $id );
        return isset( $modules[ $id ] ) ? $modules[ $id ] : array();
    }

    public static function registered_modules() {
        return self::$registered;
    }

    public static function dependencies_met( $id ) {
        $module = self::module( $id );
        if ( empty( $module ) ) { return false; }
        $foundation = array_keys( self::foundation() );
        foreach ( $module['dependencies'] as $dependency ) {
            if ( in_array( $dependency, $foundation, true ) ) { continue; }
            if ( $dependency === $module['id'] || empty( self::module( $dependency ) ) || ! self::is_enabled( $dependency ) ) { return false; }
        }
        return true;
    }

    public static function defaults() {
        $enabled = array();
        foreach ( self::modules() as $id => $module ) {
            if ( ! empty( $module['default_enabled'] ) && 'planned' !== $module['status'] ) { $enabled[] = $id; }
        }
        return $enabled;
    }

    public static function enabled_modules() {
        $stored = get_option( self::OPTION_KEY, null );
        return is_array( $stored ) ? self::sanitize_enabled_modules( $stored ) : self::defaults();
    }

    public static function sanitize_enabled_modules( $raw ) {
        $modules = self::modules();
        $known = array_keys( $modules );
        $raw = is_array( $raw ) ? $raw : array();
        $clean = array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw ), static function ( $id ) use ( $known ) {
            return in_array( $id, $known, true );
        } ) ) );

        return array_values( array_filter( $clean, static function ( $id ) use ( $modules ) {
            return 'planned' !== $modules[ $id ]['status'];
        } ) );
    }

    public static function save( $raw ) {
        return update_option( self::OPTION_KEY, self::sanitize_enabled_modules( $raw ), false );
    }

    public static function adopt_default_modules_once( $adoption_id, $module_ids ) {
        $adoption_id = sanitize_key( $adoption_id );
        if ( '' === $adoption_id ) { return false; }

        $done = get_option( self::ADOPTION_OPTION, array() );
        $done = is_array( $done ) ? array_values( array_unique( array_map( 'sanitize_key', $done ) ) ) : array();
        if ( in_array( $adoption_id, $done, true ) ) { return false; }

        $stored = get_option( self::OPTION_KEY, null );
        if ( is_array( $stored ) ) {
            $next = self::sanitize_enabled_modules( $stored );
            foreach ( (array) $module_ids as $module_id ) {
                $module_id = sanitize_key( $module_id );
                $module = self::module( $module_id );
                if ( empty( $module ) || empty( $module['default_enabled'] ) || 'planned' === $module['status'] ) { continue; }
                if ( ! in_array( $module_id, $next, true ) ) { $next[] = $module_id; }
            }
            if ( $next !== self::sanitize_enabled_modules( $stored ) ) {
                update_option( self::OPTION_KEY, $next, false );
            }
        }

        $done[] = $adoption_id;
        update_option( self::ADOPTION_OPTION, array_values( array_unique( $done ) ), false );
        return true;
    }

    public static function is_enabled( $id ) {
        return in_array( sanitize_key( $id ), self::enabled_modules(), true );
    }

    public static function settings_url( $id ) {
        $module = self::module( $id );
        $target = isset( $module['settings'] ) ? $module['settings'] : array();
        if ( ! empty( $target['url'] ) ) { return admin_url( ltrim( (string) $target['url'], '/' ) ); }
        if ( empty( $target['page'] ) ) { return ''; }
        $args = array( 'page' => $target['page'] );
        if ( ! empty( $target['tab'] ) ) { $args['tab'] = $target['tab']; }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }
}
