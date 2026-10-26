<?php
/**
 * Safe, opt-in policy for administrator-facing notification emails.
 * Customer communications, inquiry delivery and SMTP transport are not changed.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_Admin_Notifications {
    const OPTION = 'yby_core_mail_admin_notifications';
    const ACTION = 'yby_core_mail_admin_notifications_save';

    public static function defaults() {
        return array(
            'registration_admin' => true,
            'newsletter_admin' => true,
            'newsletter_form_ids' => '',
            'newsletter_admin_recipients' => '',
        );
    }

    public static function settings() {
        $stored = get_option( self::OPTION, array() );
        return array_merge( self::defaults(), is_array( $stored ) ? array_intersect_key( $stored, self::defaults() ) : array() );
    }

    public static function sanitize( $input ) {
        $input = is_array( $input ) ? $input : array();
        $ids = preg_split( '/[\s,;]+/', (string) ( $input['newsletter_form_ids'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY );
        $ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );
        $emails = preg_split( '/[\s,;]+/', strtolower( (string) ( $input['newsletter_admin_recipients'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
        $emails = array_unique( array_filter( array_map( 'sanitize_email', $emails ), 'is_email' ) );
        return array(
            'registration_admin' => ! empty( $input['registration_admin'] ),
            'newsletter_admin' => ! empty( $input['newsletter_admin'] ),
            'newsletter_form_ids' => implode( ',', $ids ),
            'newsletter_admin_recipients' => implode( ',', $emails ),
        );
    }

    public static function register() {
        add_filter( 'wp_send_new_user_notification_to_admin', array( __CLASS__, 'filter_registration_admin' ), 10, 2 );
        add_filter( 'wpforms_process_before_form_data', array( __CLASS__, 'filter_wpforms_form_data' ), 99, 2 );
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'save' ) );
    }

    public static function filter_registration_admin( $send, $user = null ) {
        return self::settings()['registration_admin'] ? $send : false;
    }

    /**
     * Remove only explicit administrator recipients in opted-in WPForms forms.
     * Do not disable the form, alter stored entries or suppress customer emails.
     */
    public static function filter_wpforms_form_data( $form_data, $entry = array() ) {
        if ( ! is_array( $form_data ) ) { return $form_data; }
        $settings = self::settings();
        if ( $settings['newsletter_admin'] ) { return $form_data; }
        $form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
        $allowed = array_map( 'absint', explode( ',', $settings['newsletter_form_ids'] ) );
        if ( ! $form_id || ! in_array( $form_id, $allowed, true ) ||
             empty( $form_data['settings']['notifications'] ) ||
             ! is_array( $form_data['settings']['notifications'] ) ) { return $form_data; }

        $admins = array_filter( array_merge(
            array( strtolower( (string) get_option( 'admin_email', '' ) ) ),
            explode( ',', $settings['newsletter_admin_recipients'] )
        ) );
        foreach ( $form_data['settings']['notifications'] as $id => $notification ) {
            if ( ! is_array( $notification ) || ! isset( $notification['email'] ) ||
                 ! is_string( $notification['email'] ) ) { continue; }
            $recipients = preg_split( '/[\s,;]+/', trim( $notification['email'] ), -1, PREG_SPLIT_NO_EMPTY );
            $remaining = array();
            foreach ( $recipients as $recipient ) {
                $normalized = strtolower( trim( $recipient ) );
                if ( '{admin_email}' === $normalized || in_array( $normalized, $admins, true ) ) { continue; }
                $remaining[] = $recipient;
            }
            if ( count( $remaining ) === count( $recipients ) ) { continue; }
            if ( ! $remaining ) {
                unset( $form_data['settings']['notifications'][ $id ] );
            } else {
                $form_data['settings']['notifications'][ $id ]['email'] = implode( ', ', $remaining );
            }
        }
        return $form_data;
    }

    /**
     * Optional legacy provider. The native Newsletter does not depend on WPForms.
     *
     * @return bool
     */
    public static function wpforms_available() {
        return function_exists( 'wpforms' ) || class_exists( 'WPForms', false );
    }

    /**
     * Read-only native Newsletter readiness, not an email sending action.
     *
     * @return array<string,bool>
     */
    public static function native_newsletter_status() {
        $available = class_exists( 'YBY_Newsletter_REST_Controller', false );
        return array(
            'enabled' => $available && YBY_Newsletter_REST_Controller::enabled(),
            'outbound' => $available && YBY_Newsletter_REST_Controller::outbound_enabled(),
            'ready' => $available && YBY_Newsletter_REST_Controller::subscribe_ready(),
            'administrator_notification' => false,
        );
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ||
             ! current_user_can( 'andy_core_settings_manage' ) ||
             ! current_user_can( 'andy_core_mail_view' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( self::ACTION );
        $input = isset( $_POST['mail_admin'] ) && is_array( $_POST['mail_admin'] )
            ? wp_unslash( $_POST['mail_admin'] ) : array();
        // A native-only site's form must not silently wipe hidden legacy settings.
        if ( ! self::wpforms_available() ) {
            $previous = self::settings();
            foreach ( array( 'newsletter_admin', 'newsletter_form_ids', 'newsletter_admin_recipients' ) as $key ) {
                $input[ $key ] = $previous[ $key ];
            }
        }
        update_option( self::OPTION, self::sanitize( $input ), false );
        wp_safe_redirect( admin_url( 'admin.php?page=andy-core-email&tab=transport&notification_saved=1' ) );
        exit;
    }

    public static function render() {
        $settings = self::settings();
        $editable = current_user_can( 'manage_options' ) &&
            current_user_can( 'andy_core_settings_manage' ) &&
            current_user_can( 'andy_core_mail_view' );
        echo '<div class="card" style="max-width:1080px;margin:20px 0;padding:22px">';
        echo '<h2>管理员邮件通知</h2>';
        echo '<p class="description">仅控制管理员接收的非关键提醒。客户邮件、正式询盘、订单异常及 SMTP 路由均不受此设置影响；默认保持原行为。</p>';
        if ( isset( $_GET['notification_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['notification_saved'] ) ) ) {
            echo '<div class="notice notice-success inline"><p>管理员通知规则已保存。</p></div>';
        }
        if ( $editable ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
            wp_nonce_field( self::ACTION );
        } else {
            echo '<p><em>当前账号仅有查看权限。修改需网站管理员及邮件管理权限。</em></p>';
        }
        echo '<table class="form-table" role="presentation"><tbody>';
        self::checkbox_row( 'registration_admin', '新用户注册 → 管理员', '仅 WordPress 原生管理员注册通知；用户账户验证及设置密码邮件不受影响。', $settings, $editable );
        $native = self::native_newsletter_status();
        echo '<tr><th scope="row">Andy Core 原生 Newsletter → 管理员</th><td>';
        echo '<strong>不发送（内置规则，无需开关）</strong>';
        echo '<p class="description">原生订阅不会产生管理员提醒；客户订阅确认由 Newsletter 外发授权及 Mail Transport 控制。</p>';
        echo '<p class="description">订阅服务：' . ( $native['enabled'] ? '已启用' : '未启用' ) .
            '；客户确认邮件外发：' . ( $native['outbound'] ? '已授权' : '未授权' ) .
            '；整体就绪：' . ( $native['ready'] ? '是' : '否' ) . '</p></td></tr>';
        if ( self::wpforms_available() ) {
            echo '<tr><th colspan="2"><h3>WPForms 旧站兼容通知</h3><p class="description">仅安装 WPForms 的网站显示；不影响 Andy Core 原生 Newsletter。</p></th></tr>';
            self::checkbox_row( 'newsletter_admin', 'WPForms Newsletter → 管理员', '仅适用于指定的 WPForms 表单。', $settings, $editable );
            echo '<tr><th scope="row"><label for="yby-newsletter-forms">WPForms 表单 ID</label></th><td>';
            echo '<input class="regular-text" id="yby-newsletter-forms" name="mail_admin[newsletter_form_ids]" value="' . esc_attr( $settings['newsletter_form_ids'] ) . '" placeholder="例如：26680" ' . ( $editable ? '' : 'disabled' ) . '>';
            echo '<p class="description">仅对所列旧站表单生效。不会自动安装或创建 WPForms。</p></td></tr>';
            echo '<tr><th scope="row"><label for="yby-newsletter-admins">额外管理员收件地址（WPForms）</label></th><td>';
            echo '<input class="regular-text" type="text" id="yby-newsletter-admins" name="mail_admin[newsletter_admin_recipients]" value="' . esc_attr( $settings['newsletter_admin_recipients'] ) . '" ' . ( $editable ? '' : 'disabled' ) . '>';
            echo '<p class="description">只匹配确认为订阅管理通知的管理员收件地址。</p></td></tr>';
        }
        echo '</tbody></table>';
        if ( $editable ) { submit_button( '保存管理员通知规则' ); echo '</form>'; }
        echo '<hr><h3>WooCommerce 邮件通知</h3>';
        echo '<p class="description">统一入口，状态从 WooCommerce 实时读取；具体开关仍由 WooCommerce 原生设置负责，避免多处配置不一致。</p>';
        self::render_woo_email_summary();
        echo '</div>';
    }

    private static function checkbox_row( $key, $label, $description, $settings, $editable ) {
        echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><label>';
        echo '<input type="checkbox" name="mail_admin[' . esc_attr( $key ) . ']" value="1" ' .
            checked( ! empty( $settings[ $key ] ), true, false ) . ( $editable ? '' : ' disabled' ) . '> 发送该类管理员通知';
        echo '</label><p class="description">' . esc_html( $description ) . '</p></td></tr>';
    }

    public static function render_woo_email_summary() {
        if ( ! function_exists( 'WC' ) || ! is_object( WC() ) ||
             ! method_exists( WC(), 'mailer' ) ) {
            echo '<p>WooCommerce 未启用；不修改任何邮件设置。</p>';
            return;
        }
        $mailer = WC()->mailer();
        if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'get_emails' ) ) {
            echo '<p>WooCommerce 邮件配置暂不可读取。</p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>邮件</th><th>状态</th><th>管理位置</th></tr></thead><tbody>';
        foreach ( $mailer->get_emails() as $email ) {
            if ( ! is_object( $email ) || ! isset( $email->id ) ) { continue; }
            $name = isset( $email->title ) ? $email->title : $email->id;
            $enabled = method_exists( $email, 'is_enabled' ) && $email->is_enabled();
            $url = admin_url( 'admin.php?page=wc-settings&tab=email&section=' . rawurlencode( strtolower( (string) $email->id ) ) );
            echo '<tr><td>' . esc_html( $name ) . '</td><td>' . ( $enabled ? '已启用' : '已关闭 / 手动触发' ) .
                 '</td><td><a href="' . esc_url( $url ) . '">打开 WooCommerce 设置</a></td></tr>';
        }
        echo '</tbody></table>';
    }
}