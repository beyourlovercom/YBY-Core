<?php
/**
 * M7 read-only administrative summaries. No credentials, subscriber writes,
 * deliveries, Action Scheduler hooks, migrations, or simulated production data.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Three_OS_Readonly {
    /** Count aggregated leads with exactly the same salesperson scope as list_leads(). */
    public static function inquiry_status_counts() {
        if ( ! YBY_Security::can_view_leads() || ! YBY_Database::leads_table_exists() ||
             ! YBY_Database::management_tables_exist() ) { return null; }
        global $wpdb;
        $leads = YBY_Database::leads_table_name();
        $management = YBY_Database::management_table_name();
        $sql = "SELECT COALESCE(m.status,'new') AS state, COUNT(*) AS n
                  FROM {$leads} l LEFT JOIN {$management} m ON m.lead_id=l.id";
        $restricted = YBY_Security::is_salesperson() && ! current_user_can( 'manage_options' );
        if ( $restricted ) {
            $sql .= ' WHERE m.owner_user_id=%d';
            $sql = $wpdb->prepare( $sql, get_current_user_id() );
        }
        $sql .= " GROUP BY COALESCE(m.status,'new')";
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        if ( ! is_array( $rows ) || '' !== $wpdb->last_error ) { return null; }
        $counts = array_fill_keys( YBY_Lead_Management::STATUSES, 0 );
        foreach ( $rows as $row ) {
            if ( isset( $counts[ $row['state'] ] ) ) {
                $counts[ $row['state'] ] = max( 0, (int) $row['n'] );
            }
        }
        return array( 'counts' => $counts, 'total' => array_sum( $counts ), 'scoped' => $restricted );
    }

    /** Read the existing M1 subscription status: do not expose consent tokens. */
    public static function newsletter_status_counts() {
        if ( ! current_user_can( YBY_Three_OS_Admin::NEWSLETTER_VIEW ) ||
             ! YBY_Module_Registry::is_enabled( 'newsletter_os' ) ||
             ! YBY_Newsletter_Store::exists() ) { return null; }
        global $wpdb;
        $table = YBY_Newsletter_Store::table_name();
        $rows = $wpdb->get_results( "SELECT status,COUNT(*) AS n FROM {$table} GROUP BY status", ARRAY_A );
        if ( ! is_array( $rows ) || '' !== $wpdb->last_error ) { return null; }
        $counts = array_fill_keys( array( 'pending', 'subscribed', 'unsubscribed', 'suppressed' ), 0 );
        foreach ( $rows as $row ) {
            if ( isset( $counts[ $row['status'] ] ) ) {
                $counts[ $row['status'] ] = max( 0, (int) $row['n'] );
            }
        }
        return array( 'counts' => $counts, 'total' => array_sum( $counts ) );
    }

    /** Snapshot of existing transport metadata. Sending capability is always FALSE. */
    public static function email_health() {
        if ( ! current_user_can( YBY_Three_OS_Admin::MAIL_VIEW ) ||
             ! YBY_Module_Registry::is_enabled( 'email_os' ) ) { return null; }
        $h = ( new YBY_Email_Health_Center() )->transport_status();
        return array(
            'transport_plugin' => sanitize_text_field( (string) ( $h['plugin'] ?? 'Unknown' ) ),
            'provider' => sanitize_text_field( (string) ( $h['provider'] ?? 'Unavailable' ) ),
            'configured' => ! empty( $h['configured'] ),
            'mail_v2_queue' => false, 'mail_v2_worker' => false,
            'mail_v2_outbound' => false, 'quota_verified' => false,
        );
    }

    public static function card( $label, $value, $note = '' ) {
        echo '<div class="yby-os-stat"><div class="yby-os-stat-label">' . esc_html( $label ) .
             '</div><strong>' . esc_html( (string) $value ) . '</strong><small>' . esc_html( $note ) . '</small></div>';
    }
    public static function render_inquiry_stats() {
        if ( ! YBY_Security::can_view_leads() ) { wp_die( esc_html__( 'Permission denied.', 'yby-core' ) ); }
        $report = self::inquiry_status_counts();
        if ( null === $report ) { echo '<p>当前询盘数据不可用，请检查现有询盘表与只读权限。</p>'; return; }
        echo '<div class="yby-os-stats">';
        self::card( '询盘总量', number_format_i18n( $report['total'] ), $report['scoped'] ? '仅统计本人负责的询盘' : '现有询盘记录' );
        self::card( '赢单', number_format_i18n( $report['counts']['won'] ), '仅依据现有状态字段' );
        self::card( '待联系', number_format_i18n( $report['counts']['pending_contact'] ), '状态汇总' );
        self::card( '跟进中', number_format_i18n( $report['counts']['following_up'] ), '状态汇总' );
        echo '</div><div class="yby-os-status"><h2>状态分布</h2><table class="widefat striped"><thead><tr><th>状态</th><th>数量</th></tr></thead><tbody>';
        foreach ( $report['counts'] as $state => $n ) {
            echo '<tr><td>' . esc_html( $state ) . '</td><td>' . esc_html( number_format_i18n( $n ) ) . '</td></tr>';
        }
        echo '</tbody></table><p class="description">只读聚合数据；未对潜在重复线索或赢单金额进行推断。</p></div>';
    }
    public static function render_newsletter_consent() {
        if ( ! current_user_can( YBY_Three_OS_Admin::NEWSLETTER_VIEW ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        $r = self::newsletter_status_counts();
        if ( null === $r ) { echo '<p>当前 Newsletter 订阅表不可用；不会自动建表或补造数据。</p>'; return; }
        echo '<div class="yby-os-stats">';
        $labels = array( 'pending' => '待确认', 'subscribed' => '已订阅', 'unsubscribed' => '已退订', 'suppressed' => '禁止发送' );
        foreach ( $labels as $status => $label ) {
            self::card( $label, number_format_i18n( $r['counts'][ $status ] ), '现有 Newsletter 记录' );
        }
        echo '</div><p class="description">订阅状态来自现有数据库；退订与双重确认由现有处理器执行。本页不允许人工订阅、恢复退订、导出或群发。</p>';
    }
    public static function render_email_summary( $tab ) {
        if ( ! current_user_can( YBY_Three_OS_Admin::MAIL_VIEW ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        $r = self::email_health();
        if ( null === $r ) { echo '<p>无法读取邮件基础设施状态。</p>'; return; }
        echo '<div class="yby-os-stats">';
        self::card( '传输插件', $r['transport_plugin'], '来源：现有 WordPress 发信插件' );
        self::card( '传输身份', $r['provider'], '只读取公开的提供商名称' );
        self::card( '配置检查', $r['configured'] ? '已配置' : '未确认', '仅配置完整性，不代表实际可投递' );
        self::card( 'Mail OS V2 Worker', '未启用', 'Outbox、重试与自动群发均未激活' );
        echo '</div>';
        if ( 'transport' === $tab ) {
            echo '<p class="description">此处不展示 SMTP 凭据、服务器地址、端口或发信秘钥。配置修改仍由现有邮件插件管理。</p>';
        } else {
            echo '<p class="description">此状态页面不发送测试邮件，也不表示邮件已投递。真实队列与事件日志待独立开发及安全授权。</p>';
        }
    }
}
