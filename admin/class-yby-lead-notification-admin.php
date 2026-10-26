<?php
/** Inquiry mail transport audit and controlled resend, desktop WordPress admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Lead_Notification_Admin {
    /** WordPress admin warning visible even when staff do not open Inquiry settings. */
    public static function render_notice() {
        if ( ! current_user_can( 'andy_core_settings_manage' ) ) { return; }
        if ( ! YBY_Database::notification_table_exists() ) {
            echo '<div class="notice notice-error"><p>Andy Core：询盘邮件任务表缺失，通知无法安全发送。请检查数据库升级。</p></div>';
            return;
        }
        $attention = YBY_Lead_Notification_Outbox::actionable_count();
        $missing = YBY_Lead_Notification_Outbox::unqueued_count();
        if ( $attention < 1 && $missing < 1 ) { return; }
        $url = admin_url( 'admin.php?page=andy-core-leads&tab=notification' );
        echo '<div class="notice notice-warning"><p>Andy Core 询盘邮件异常：'
            . esc_html( (string) $attention ) . ' 条发送失败或状态待核对，'
            . esc_html( (string) $missing ) . ' 条未成功入队。 '
            . '<a href="' . esc_url( $url ) . '">查看询盘通知记录</a></p></div>';
    }
    public static function render() {
        if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        echo '<div class="wrap"><h2>询盘邮件通知记录</h2>';
        if ( ! YBY_Database::notification_table_exists() ) {
            echo '<div class="notice notice-error"><p>通知任务表缺失，暂不能发送通知。请检查 Andy Core 数据库升级。</p></div></div>';
            return;
        }
        $notice = '';
        if ( isset( $_POST['yby_mail_retry_lead_id'] ) ) {
            $lead_id = absint( wp_unslash( $_POST['yby_mail_retry_lead_id'] ) );
            check_admin_referer( 'yby_mail_retry_' . $lead_id, 'yby_mail_retry_nonce' );
            $notice = YBY_Lead_Notification_Outbox::request_manual_retry( $lead_id )
                ? '任务已进入重发队列，未确认实际投递。'
                : '重发请求未接受：状态不允许、并发处理中或任务调度失败。';
        }
        if ( '' !== $notice ) {
            echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>';
        }
        $recipient = YBY_Config::get_lead_notification_primary_recipient_email();
        if ( ! is_email( $recipient ) ) {
            echo '<div class="notice notice-error"><p>询盘主通知收件人地址无效。</p></div>';
        }
        $pending = YBY_Lead_Notification_Outbox::actionable_count();
        $missing = YBY_Lead_Notification_Outbox::unqueued_count();
        echo '<p><strong>未成功入队：</strong>' . esc_html( (string) $missing ) . '。恢复前不会发送邮件。</p>';
        echo '<p><strong>需关注任务：</strong>' . esc_html( (string) $pending )
           . '。SMTP accepted 仅代表邮件服务接受，并非收件箱最终送达。</p>';
        $filter = isset( $_GET['mail_status'] ) ? sanitize_key( wp_unslash( $_GET['mail_status'] ) ) : '';
        $allowed = array( '', 'pending', 'processing', 'smtp_accepted', 'failed', 'exhausted' );
        if ( ! in_array( $filter, $allowed, true ) ) { $filter = ''; }
        $url = admin_url( 'admin.php?page=andy-core-leads&tab=notification' );
        echo '<p><label for="mail-status">状态：</label><select id="mail-status" onchange="window.location.href=this.value">';
        $labels = array( '' => '全部', 'pending' => '待发送', 'processing' => '执行中/需核对',
            'smtp_accepted' => 'SMTP 已接受', 'failed' => '失败待重试', 'exhausted' => '重试次数耗尽' );
        foreach ( $labels as $key => $label ) {
            $link = add_query_arg( 'mail_status', $key, $url );
            echo '<option value="' . esc_url( $link ) . '"' . selected( $filter, $key, false ) . '>'
                . esc_html( $label ) . '</option>';
        }
        echo '</select></p>';
        echo '<table class="widefat striped"><thead><tr><th>Case ID</th><th>状态</th><th>尝试</th>'
            . '<th>收件人</th><th>错误代码</th><th>下次尝试</th><th>最近更新</th><th>操作</th></tr></thead><tbody>';
        $rows = YBY_Lead_Notification_Outbox::list_recent( $filter );
        foreach ( $rows as $row ) {
            $lead_id = (int) $row['lead_id'];
            $detail = admin_url( 'admin.php?page=andy-core-leads&tab=leads&lead_id=' . $lead_id );
            echo '<tr><td><a href="' . esc_url( $detail ) . '">' . esc_html( $row['case_id'] ) . '</a></td>'
                . '<td>' . esc_html( $row['status'] ) . '</td>'
                . '<td>' . esc_html( (string) $row['attempts'] ) . '/' . esc_html( (string) YBY_Lead_Notification_Outbox::MAX_ATTEMPTS ) . '</td>'
                . '<td>' . esc_html( $row['recipient_email'] ) . '</td>'
                . '<td>' . esc_html( $row['last_error_code'] ) . '</td>'
                . '<td>' . esc_html( (string) $row['next_retry_at'] ) . '</td>'
                . '<td>' . esc_html( $row['updated_at'] ) . '</td><td>';
            if ( in_array( $row['status'], array( 'failed', 'exhausted' ), true ) ) {
                echo '<form method="post" action="' . esc_url( $url ) . '">';
                wp_nonce_field( 'yby_mail_retry_' . $lead_id, 'yby_mail_retry_nonce' );
                echo '<input type="hidden" name="yby_mail_retry_lead_id" value="' . esc_attr( (string) $lead_id ) . '">';
                echo '<button type="submit" class="button button-secondary">手动重发</button></form>';
            } elseif ( 'processing' === $row['status'] ) {
                echo '状态未明：先核对 SMTP 日志';
            } else {
                echo '—';
            }
            echo '</td></tr>';
        }
        if ( ! $rows ) { echo '<tr><td colspan="8">暂无记录。升级前的历史邮件不自动补发。</td></tr>'; }
        echo '</tbody></table></div>';
    }
}

add_action( 'admin_notices', array( 'YBY_Lead_Notification_Admin', 'render_notice' ) );
