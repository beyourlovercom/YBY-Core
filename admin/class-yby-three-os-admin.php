<?php
/**
 * Andy Core Menu Architecture Canonical V1.
 *
 * Three second-level admin entry points under Andy Core; no mail dispatch,
 * subscriber writes, exports, schema migration or WordPress global mail hooks.
 * Legacy form handlers and data remain their original owners.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Three_OS_Admin {
    const NEWSLETTER = 'andy-core-newsletter';
    const EMAIL = 'andy-core-email';

    /** Explicit, independently grantable capabilities. No marketer role grants. */
    const NEWSLETTER_VIEW = 'andy_core_newsletter_view';
    const MAIL_VIEW = 'andy_core_mail_view';

    public static function newsletter_tabs() {
        return array(
            'subscribers' => '订阅者列表', 'consent' => '双重确认与退订',
            'popup_subscribe' => '订阅弹窗', 'shortcode_subscribe' => '订阅短代码',
            'popup_lottery' => '弹窗抽奖', 'campaigns' => '营销活动',
            'approvals' => '审批与发送计划', 'statistics' => '活动统计',
        );
    }

    /** M6: keep route metadata for old bookmarks, but show only usable pages. */
    public static function newsletter_visible_tabs() {
        return array_intersect_key( self::newsletter_tabs(), array_flip( array(
            'subscribers', 'consent', 'popup_subscribe', 'shortcode_subscribe',
        ) ) );
    }
    public static function email_visible_tabs() {
        $tabs = array_intersect_key( self::email_tabs(), array_flip( array(
            'overview', 'email_templates', 'transport', 'health',
        ) ) );
        if ( class_exists( 'YBY_Mail_V2_P6_Dev_Preview' ) &&
             YBY_Mail_V2_P6_Dev_Preview::eligible_site() ) {
            $tabs['preview_queue'] = '队列（Dev 预览）';
            $tabs['preview_failures'] = '失败处理（Dev 预览）';
        }
        return $tabs;
    }

    public static function email_tabs() {
        return array(
            'overview' => '总览', 'queue' => '邮件队列',
            'failures' => '失败处理', 'logs' => '事件日志',
            'email_templates' => '邮件模板中心', 'transport' => '发送通道',
            'health' => '系统健康',
        );
    }

    public static function inquiry_tabs() {
        return array(
            'leads' => '询盘列表', 'stats' => '询盘统计',
            'floating_inquiry' => '悬浮询盘', 'popup_inquiry' => '询盘弹窗',
            'shortcode_inquiry' => '询盘短代码', 'settings' => '询盘设置', 'notification' => '询盘通知',
        );
    }

    /** Keep independently securable menu capabilities, unlike read/manage_options. */
    public function register_menus() {
        if ( YBY_Module_Registry::is_enabled( 'newsletter_os' ) ) {
            add_submenu_page(
                YBY_Project_Studio::menu_slug(), 'Newsletter OS', 'Newsletter OS',
                self::NEWSLETTER_VIEW, self::NEWSLETTER, array( $this, 'render_newsletter' )
            );
        }
        if ( YBY_Module_Registry::is_enabled( 'email_os' ) ) {
            add_submenu_page(
                YBY_Project_Studio::menu_slug(), 'Email OS', 'Email OS',
                self::MAIL_VIEW, self::EMAIL, array( $this, 'render_email' )
            );
        }
    }

    /** Settings business tabs migrate to Inquiry OS while legacy POST remains local. */
    public function redirect_legacy_settings_get() {
        if ( ! is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ||
             ! isset( $_GET['page'], $_GET['tab'] ) ||
             ! is_scalar( $_GET['page'] ) || ! is_scalar( $_GET['tab'] ) ||
             YBY_Helpers::admin_page_slug() !== sanitize_key( wp_unslash( $_GET['page'] ) ) ||
             ! YBY_Module_Registry::is_enabled( 'inquiry_os' ) ||
             ! current_user_can( 'andy_core_settings_manage' ) ||
             ! current_user_can( 'andy_core_leads_view' ) ) {
            return;
        }
        $old = sanitize_key( wp_unslash( $_GET['tab'] ) );
        $map = array( 'inquiry' => 'settings', 'inquiry-notification' => 'notification' );
        if ( isset( $map[ $old ] ) ) {
            wp_safe_redirect( self::url( YBY_Inquiry_Admin::page_slug(), $map[ $old ] ) );
            exit;
        }
    }

    /** Hide the old mixed navigation, NOT its registered page or POST handler. */
    public function hide_old_menu() {
        if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) ||
             YBY_Module_Registry::is_enabled( 'email_os' ) ) {
            remove_submenu_page( YBY_Project_Studio::menu_slug(), 'yby-core-popups' );
        }
    }

    /** Place the three business modules together; keep every other Core menu. */
    public function order_os_menus() {
        global $submenu;
        $parent = YBY_Project_Studio::menu_slug();
        if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) { return; }
        $ordered = array();
        $rest = array();
        $wanted = array( YBY_Inquiry_Admin::page_slug(), self::NEWSLETTER, self::EMAIL );
        foreach ( $submenu[ $parent ] as $item ) {
            $slug = isset( $item[2] ) ? (string) $item[2] : '';
            if ( in_array( $slug, $wanted, true ) ) { $ordered[ $slug ] = $item; }
            else { $rest[] = $item; }
        }
        $first = array();
        foreach ( $wanted as $slug ) {
            if ( isset( $ordered[ $slug ] ) ) { $first[] = $ordered[ $slug ]; }
        }
        $submenu[ $parent ] = array_merge( $first, $rest );
    }

    /** Existing CSS/JS needed by the reused popup and template renderers. */
    public function enqueue_assets( $hook ) {
        $page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
            ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( ! in_array( $page, array( self::NEWSLETTER, self::EMAIL, YBY_Inquiry_Admin::page_slug() ), true ) ) {
            return;
        }
        wp_enqueue_style( 'yby-three-os', YBY_CORE_PLUGIN_URL . 'assets/css/yby-three-os-admin.css', array(), YBY_CORE_VERSION );
        if ( in_array( $page, array( self::NEWSLETTER, YBY_Inquiry_Admin::page_slug() ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_style( 'yby-global-inquiry-dock-preview', YBY_CORE_PLUGIN_URL . 'public/css/yby-global-inquiry-dock.css', array(), YBY_CORE_VERSION );
            wp_enqueue_style( 'yby-email-shortcodes', YBY_CORE_PLUGIN_URL . 'public/css/yby-email-shortcodes.css', array(), YBY_CORE_VERSION );
            wp_enqueue_script( 'yby-core-admin', YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js', array( 'jquery' ), YBY_CORE_VERSION, true );
        }
    }

    /** Old GET bookmarks redirect; POST forms retain their original processing. */
    public function redirect_legacy_get() {
        if ( ! is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ||
             ! isset( $_GET['page'] ) ||
             'yby-core-popups' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ||
             ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
        if ( '' === $tab ) {
            $tab = YBY_Module_Registry::is_enabled( 'inquiry_os' ) ? 'floating_inquiry' : 'email_templates';
        }
        $aliases = array( 'inquiry' => 'popup_inquiry', 'subscribe' => 'popup_subscribe' );
        $tab = $aliases[ $tab ] ?? $tab;
        $inquiry = array( 'floating_inquiry', 'popup_inquiry', 'shortcode_inquiry' );
        $newsletter = array( 'popup_subscribe', 'shortcode_subscribe', 'popup_lottery', 'bulk_marketing' );
        $target = '';
        if ( in_array( $tab, $inquiry, true ) && YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) {
            $target = self::url( YBY_Inquiry_Admin::page_slug(), $tab );
        } elseif ( in_array( $tab, $newsletter, true ) && YBY_Module_Registry::is_enabled( 'newsletter_os' ) ) {
            $target = self::url( self::NEWSLETTER, 'bulk_marketing' === $tab ? 'campaigns' : $tab );
        } elseif ( 'email_templates' === $tab && YBY_Module_Registry::is_enabled( 'email_os' ) ) {
            $target = self::url( self::EMAIL, 'email_templates' );
            // Preserve mail-template deep-link selections.
            foreach ( array( 'email_section', 'template_key' ) as $key ) {
                if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
                    $target = add_query_arg( $key, sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), $target );
                }
            }
        }
        if ( $target ) { wp_safe_redirect( $target ); exit; }
        // Unknown/disabled legacy tabs continue through the original handler.
    }

    public static function url( $page, $tab = '' ) {
        $args = array( 'page' => $page );
        if ( '' !== $tab ) { $args['tab'] = $tab; }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    public static function render_tabs( $page, $tabs, $active, $heading ) {
        echo '<div class="wrap yby-three-os"><h1>' . esc_html( $heading ) . '</h1>';
        echo '<nav class="nav-tab-wrapper" style="display:flex;flex-wrap:wrap;gap:2px;margin:16px 0 20px" aria-label="' . esc_attr( $heading ) . '">';
        foreach ( $tabs as $key => $label ) {
            $link = self::EMAIL === $page && in_array( $key, array( 'preview_queue', 'preview_failures' ), true )
                ? YBY_Mail_V2_P6_Dev_Preview::url( $key ) : self::url( $page, $key );
            echo '<a class="nav-tab ' . ( $key === $active ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $link ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
    }

    public static function render_inquiry_component( $tab ) {
        if ( ! array_key_exists( $tab, self::inquiry_tabs() ) ||
             ! in_array( $tab, array( 'floating_inquiry', 'popup_inquiry', 'shortcode_inquiry' ), true ) ||
             ! YBY_Module_Registry::is_enabled( 'inquiry_os' ) ||
             ! current_user_can( 'andy_core_settings_manage' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        self::render_tabs( YBY_Inquiry_Admin::page_slug(), self::inquiry_tabs(), $tab, 'Inquiry OS · 询盘中心' );
        ( new YBY_Popup_Admin( 'yby-core', YBY_CORE_VERSION ) )->render_embedded( $tab );
        echo '</div>';
    }

    public function render_newsletter() {
        if ( ! YBY_Module_Registry::is_enabled( 'newsletter_os' ) ||
             ! current_user_can( self::NEWSLETTER_VIEW ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        $tabs = self::newsletter_tabs();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'subscribers';
        if ( 'bulk_marketing' === $tab ) { $tab = 'campaigns'; }
        if ( ! isset( $tabs[ $tab ] ) ) { $tab = 'subscribers'; }
        self::render_tabs( self::NEWSLETTER, self::newsletter_visible_tabs(), $tab, 'Newsletter OS · 订阅与营销' );
        if ( 'subscribers' === $tab ) {
            $this->subscribers();
        } elseif ( 'consent' === $tab ) {
            YBY_Three_OS_Readonly::render_newsletter_consent();
        } elseif ( in_array( $tab, array( 'popup_subscribe', 'shortcode_subscribe' ), true ) ) {
            if ( current_user_can( 'manage_options' ) && YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) {
                ( new YBY_Popup_Admin( 'yby-core', YBY_CORE_VERSION ) )->render_embedded( $tab );
            } else {
                $this->notice( '现有订阅组件的设置处理器仍需要管理员权限；普通营销人员暂不能修改。' );
            }
        } else {
            $notices = array(
                'consent' => '双重确认与退订由现有 Newsletter Consent / REST 控制器处理；此页只读，不能人工恢复退订。',
                'campaigns' => '营销活动尚未启用真实群发。专员草稿、经理审批和发送计划将在独立授权后接入。',
                'approvals' => '营销审批：专员创建草稿、经理审批、Owner 控制首次启用和高风险操作。目前不执行批准或发送。',
                'statistics' => '真实活动统计尚未接入。此页不提供模拟成真实的数据。',
            );
            $this->notice( $notices[ $tab ] ?? '当前功能尚未接入。' );
        }
        echo '</div>';
    }

    public function render_email() {
        if ( ! YBY_Module_Registry::is_enabled( 'email_os' ) ||
             ! current_user_can( self::MAIL_VIEW ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        $tabs = self::email_tabs();
        if ( YBY_Mail_V2_P6_Dev_Preview::eligible_site() ) {
            $tabs['preview_queue'] = '队列（Dev 预览）';
            $tabs['preview_failures'] = '失败处理（Dev 预览）';
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        if ( ! isset( $tabs[ $tab ] ) ) { $tab = 'overview'; }
        self::render_tabs( self::EMAIL, self::email_visible_tabs(), $tab, 'Email OS · 邮件基础设施' );
        if ( in_array( $tab, array( 'preview_queue', 'preview_failures' ), true ) ) {
            YBY_Mail_V2_P6_Dev_Preview::render( $tab );
        } elseif ( 'email_templates' === $tab ) {
            if ( current_user_can( 'andy_core_settings_manage' ) ) {
                ( new YBY_Email_Template_Admin() )->render();
            } else {
                $this->notice( '邮件模板的编辑、发布和测试发送仍需单独的系统设置权限。' );
            }
        } elseif ( in_array( $tab, array( 'overview', 'health', 'transport' ), true ) ) {
            YBY_Three_OS_Readonly::render_email_summary( $tab );
        } else {
            $descriptions = array(
                'overview' => '发送与队列总览。Mail OS V2 仍未启用真实队列、Worker 或自动重试。',
                'queue' => '邮件队列尚未接入真实 Outbox 表；P1/P2 的队列与调度方案均为未合并的代码契约。',
                'failures' => '失败任务和 uncertain 人工复核入口待真实只读数据源接入；禁止盲目重发。',
                'logs' => '事件日志尚未接入脱敏数据源，不展示或导出邮件内容、收件人和 SMTP 响应。',
                'transport' => '当前 SMTP 由现有发信插件管理。Mail OS V2 不修改服务商、凭据或发送路由。',
                'health' => 'Worker：关闭。Outbox：未安装。真实配额与系统并发仍未验收。',
            );
            $this->notice( $descriptions[ $tab ] );
            // Legacy direct links remain informative; inactive operations stay hidden from navigation.
        }
        echo '</div>';
    }

    /** Strict read-only, paged view of existing M1 Newsletter table. */
    private function subscribers() {
        if ( ! YBY_Newsletter_Store::exists() ) {
            $this->notice( '订阅者数据表尚未在当前环境建立；不自动创建表，也不导入订阅者。' );
            return;
        }
        global $wpdb;
        $status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all';
        if ( ! in_array( $status, array( 'all', 'pending', 'subscribed', 'unsubscribed', 'suppressed' ), true ) ) { $status = 'all'; }
        $search = isset( $_GET['s'] ) && is_scalar( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $search = substr( $search, 0, 120 );
        $page = min( 100000, max( 1, absint( $_GET['paged'] ?? 1 ) ) );
        $per_page = 20;
        $where = '';
        $values = array();
        if ( 'all' !== $status ) {
            $where = ' WHERE status = %s';
            $values[] = $status;
        }
        if ( '' !== $search ) {
            $where .= $where ? ' AND email LIKE %s' : ' WHERE email LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
        }
        $table = YBY_Newsletter_Store::table_name();
        $count_query = 'SELECT COUNT(*) FROM ' . $table . $where;
        $total = (int) $wpdb->get_var( $values ? $wpdb->prepare( $count_query, $values ) : $count_query );
        $offset = ( $page - 1 ) * $per_page;
        $sql = 'SELECT id,email,status,form_source,submitted_at,status_updated_at FROM ' . $table .
               $where . ' ORDER BY id DESC LIMIT %d OFFSET %d';
        $found = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( $per_page, $offset ) ) ), ARRAY_A );
        echo '<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px">';
        echo '<input type="hidden" name="page" value="' . esc_attr( self::NEWSLETTER ) . '">';
        echo '<input type="hidden" name="tab" value="subscribers">';
        echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="搜索订阅者邮箱" style="min-width:200px;max-width:100%">';
        echo '<select name="status">';
        foreach ( array( 'all' => '全部状态', 'pending' => '待确认', 'subscribed' => '已订阅', 'unsubscribed' => '已退订', 'suppressed' => '已抑制' ) as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '"' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select><button class="button button-primary" type="submit">查询</button></form>';
        echo '<p>匹配记录：' . esc_html( number_format_i18n( $total ) ) . ' 条。只读列表；不提供导出、批量修改或发信操作。</p>';
        echo '<div style="max-width:100%;overflow-x:auto;border:1px solid #e0e7e2;border-radius:8px"><table class="widefat striped" style="min-width:660px"><thead><tr>';
        foreach ( array( '邮箱', '订阅状态', '采集来源', '提交时间 (UTC)', '状态更新 (UTC)' ) as $heading ) {
            echo '<th scope="col">' . esc_html( $heading ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        if ( is_array( $found ) && $found ) {
            foreach ( $found as $entry ) {
                echo '<tr><td>' . esc_html( (string) $entry['email'] ) . '</td><td>' . esc_html( (string) $entry['status'] ) .
                     '</td><td>' . esc_html( (string) $entry['form_source'] ) . '</td><td>' .
                     esc_html( (string) $entry['submitted_at'] ) . '</td><td>' .
                     esc_html( (string) $entry['status_updated_at'] ) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="5">无匹配的订阅者记录。</td></tr>';
        }
        echo '</tbody></table></div>';
        $pages = max( 1, (int) ceil( $total / $per_page ) );
        echo '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;max-width:660px;margin-top:14px">';
        echo '<span>第 ' . esc_html( $page ) . ' / ' . esc_html( $pages ) . ' 页</span><span>';
        if ( $page > 1 ) {
            echo '<a class="button" href="' . esc_url( add_query_arg( array( 'paged' => $page - 1, 's' => $search, 'status' => $status ), self::url( self::NEWSLETTER, 'subscribers' ) ) ) . '">上一页</a> ';
        }
        if ( $page < $pages ) {
            echo '<a class="button" href="' . esc_url( add_query_arg( array( 'paged' => $page + 1, 's' => $search, 'status' => $status ), self::url( self::NEWSLETTER, 'subscribers' ) ) ) . '">下一页</a>';
        }
        echo '</span></div>';
    }

    private function notice( $text ) {
        echo '<div style="max-width:760px;padding:18px;background:#edf5ef;border:1px solid #d7e9dc;border-radius:10px;margin:16px 0">' .
             '<strong>当前阶段</strong><p>' . esc_html( $text ) . '</p></div>';
    }
}
