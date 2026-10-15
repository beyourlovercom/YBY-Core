<?php
/**
 * P6 DEV-ONLY synthetic Outbox preview. Deliberately uses a private SQLite
 * fixture, never the WordPress MySQL DB, P6 real Outbox, SMTP or Worker.
 * No hooks, sends, DB writes or background work.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class YBY_Mail_V2_P6_Dev_Preview {
    const NONCE = 'yby_mail_v2_p6_dev_preview';
    const DIR = '.byl-dev-canary/mail-v2-p6-uat-20261014';
    const FILE = 'outbox.sqlite';
    const MYSQL_SNAPSHOT_SHA256 = 'aa10d6ee5a12ecfdeb6ac580f464a6fe75acea1e89ce217464ba7ed7f15e04c4';
    const MYSQL_SNAPSHOT_DIR = '.byl-dev-canary/mail-v2-p7-verified-snapshot-20261014';
    const MAX_FIXTURES = 100;
    const STATES = array(
        'queued', 'leased', 'sending', 'accepted_by_provider',
        'deferred', 'permanent_failed', 'uncertain', 'cancelled',
    );
    const COLUMNS = array(
        'opaque_id',
        'idempotency_key',
        'event_hash',
        'payload_ref',
        'channel',
        'sender_key',
        'template_key',
        'template_version',
        'consent_snapshot_hash',
        'priority',
        'state',
        'state_version',
        'attempt',
        'max_attempts',
        'next_attempt_at',
        'lease_token_hash',
        'lease_expires_at',
        'diagnostic_code',
        'created_at',
        'updated_at',
    );

    public static function eligible_site() {
        return function_exists( 'site_url' ) &&
            'dev.beyourlover.com' === parse_url( site_url(), PHP_URL_HOST ) &&
            function_exists( 'is_admin' ) && is_admin() &&
            extension_loaded( 'sqlite3' );
    }
    public static function url( $tab ) {
        if ( ! in_array( $tab, array( 'preview_queue', 'preview_failures', 'mysql_snapshot' ), true ) ) {
            return '';
        }
        return wp_nonce_url(
            YBY_Three_OS_Admin::url( YBY_Three_OS_Admin::EMAIL, $tab ),
            self::NONCE
        );
    }
    private static function path() {
        if ( ! defined( 'ABSPATH' ) ) { return null; }
        $parent = dirname( ABSPATH, 3 );
        $dir = $parent . '/' . self::DIR;
        $resolved = realpath( $dir );
        if ( false === $resolved || $resolved !== $dir || is_link( $dir ) ) {
            return null;
        }
        $target = $resolved . '/' . self::FILE;
        return is_file( $target ) && ! is_link( $target ) &&
               realpath( $target ) === $target && is_readable( $target )
            ? $target : null;
    }
    private static function permitted() {
        $nonce = isset( $_GET['_wpnonce'] ) && is_scalar( $_GET['_wpnonce'] )
            ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        return self::eligible_site() &&
            current_user_can( 'andy_core_mail_view' ) &&
            current_user_can( 'manage_options' ) &&
            wp_verify_nonce( $nonce, self::NONCE );
    }
    public static function render( $tab ) {
        if ( ! self::permitted() ) {
            wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
        }
        if ( ! in_array( $tab, array( 'preview_queue', 'preview_failures', 'mysql_snapshot' ), true ) ) {
            wp_die( esc_html__( 'Invalid preview.', 'yby-core' ) );
        }
        if ( 'mysql_snapshot' === $tab ) {
            self::render_mysql_snapshot();
            return;
        }
        echo '<div class="notice notice-warning inline"><p><strong>DEV 隔离测试数据</strong>：';
        echo '这些均为合成任务；不是实际邮件、正式队列或投递结果。SMTP 与 Worker 均关闭。</p></div>';
        $path = self::path();
        if ( null === $path ) {
            echo '<p>隔离测试数据源不存在或不可读；未查询 WordPress 业务数据库。</p>';
            return;
        }
        $db = null;
        try {
            $db = new SQLite3( $path, SQLITE3_OPEN_READONLY );
            $db->enableExceptions( true );
            $cols = array();
            $metadata = $db->query( 'PRAGMA table_info(outbox)' );
            while ( $r = $metadata->fetchArray( SQLITE3_ASSOC ) ) { $cols[] = $r['name']; }
            if ( $cols !== self::COLUMNS ) {
                echo '<p>隔离数据库结构不匹配；预览已安全关闭。</p>';
                return;
            }
            $unique = false;
            $indexes = $db->query( 'PRAGMA index_list(outbox)' );
            while ( $i = $indexes->fetchArray( SQLITE3_ASSOC ) ) {
                if ( 'uq_idempotency_key' === $i['name'] && 1 === (int) $i['unique'] ) {
                    $unique = true;
                }
            }
            if ( ! $unique ) {
                echo '<p>唯一幂等索引缺失；预览已安全关闭。</p>';
                return;
            }
            $all = (int) $db->querySingle( 'SELECT COUNT(*) FROM outbox' );
            if ( $all < 1 || $all > self::MAX_FIXTURES ) {
                echo '<p>测试任务数量不符合上限；预览已安全关闭。</p>';
                return;
            }
            $failure = 'preview_failures' === $tab;
            $where = $failure ? " WHERE state IN ('deferred','permanent_failed','uncertain')" : '';
            $total = (int) $db->querySingle( 'SELECT COUNT(*) FROM outbox' . $where );
            $page = max( 1, min( 5, absint( $_GET['paged'] ?? 1 ) ) );
            $sql = 'SELECT opaque_id,state,channel,sender_key,priority,attempt,max_attempts,'
                 . 'next_attempt_at,diagnostic_code,state_version FROM outbox'
                 . $where . ' ORDER BY priority ASC,next_attempt_at ASC,opaque_id ASC'
                 . ' LIMIT :take OFFSET :skip';
            $stmt = $db->prepare( $sql );
            $stmt->bindValue( ':take', 20, SQLITE3_INTEGER );
            $stmt->bindValue( ':skip', ( $page - 1 ) * 20, SQLITE3_INTEGER );
            $results = $stmt->execute();
            $records = array();
            while ( $row = $results->fetchArray( SQLITE3_ASSOC ) ) {
                if ( ! in_array( $row['state'], self::STATES, true ) ||
                     ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['opaque_id'] ) ||
                     ! preg_match( '/\A[a-z][a-z0-9_]{0,47}\z/D', $row['sender_key'] ) ) {
                    echo '<p>检测到异常测试记录；预览已安全关闭。</p>';
                    return;
                }
                $records[] = $row;
            }
            echo '<p>独立测试库：' . esc_html( number_format_i18n( $all ) ) .
                 ' 条合成任务；当前筛选：' . esc_html( number_format_i18n( $total ) ) .
                 ' 条。仅显示脱敏元数据，不提供发送、重试、删除或修改按钮。</p>';
            echo '<div style="overflow-x:auto"><table class="widefat striped" style="min-width:780px">';
            echo '<thead><tr><th>测试编号</th><th>状态</th><th>通道</th><th>发送身份</th><th>优先级</th>';
            echo '<th>尝试次数</th><th>下次模拟时间</th><th>诊断代码</th><th>版本</th></tr></thead><tbody>';
            foreach ( $records as $r ) {
                $cells = array( substr( $r['opaque_id'], 0, 12 ), $r['state'],
                    $r['channel'], $r['sender_key'], (string) $r['priority'],
                    $r['attempt'] . '/' . $r['max_attempts'],
                    (string) $r['next_attempt_at'], $r['diagnostic_code'],
                    (string) $r['state_version'] );
                echo '<tr>';
                foreach ( $cells as $cell ) { echo '<td>' . esc_html( $cell ) . '</td>'; }
                echo '</tr>';
            }
            echo '</tbody></table></div><div style="margin-top:14px">';
            if ( $page > 1 ) {
                echo '<a class="button" href="' . esc_url( add_query_arg( 'paged', $page - 1, self::url( $tab ) ) ) . '">上一页</a> ';
            }
            if ( $page * 20 < $total ) {
                echo '<a class="button" href="' . esc_url( add_query_arg( 'paged', $page + 1, self::url( $tab ) ) ) . '">下一页</a>';
            }
            echo '</div>';
        } catch ( Throwable $e ) {
            // Do not expose SQL, filesystem paths, DB messages or credentials.
            echo '<p>测试数据读取不可用；已安全停止。</p>';
        } finally {
            if ( $db instanceof SQLite3 ) { $db->close(); }
        }
    }
    /** P7 exact-SHA, point-in-time MySQL laboratory export. Not a live queue. */
    private static function render_mysql_snapshot() {
        echo '<div class="notice notice-warning inline"><p><strong>P7 MySQL 8.4 隔离验证快照</strong>：';
        echo '100 条合成任务，从独立 MySQL 测试库导出。此页非实时、非正式 Outbox，';
        echo '不是邮件投递结果；SMTP 与 Worker 均未启用。</p></div>';
        $root = dirname( ABSPATH, 3 );
        $dir = $root . '/' . self::MYSQL_SNAPSHOT_DIR;
        $file = $dir . '/p7-mysql-verified-snapshot.json';
        if ( realpath( $dir ) !== $dir || is_link( $dir ) ||
             ! is_file( $file ) || is_link( $file ) ||
             realpath( $file ) !== $file || ! is_readable( $file ) ||
             filesize( $file ) < 10000 || filesize( $file ) > 60000 ||
             ! hash_equals( self::MYSQL_SNAPSHOT_SHA256, hash_file( 'sha256', $file ) ) ) {
            echo '<p>MySQL 测试快照缺失、被修改或无法核验；预览已安全关闭。</p>';
            return;
        }
        $data = json_decode( file_get_contents( $file ), true );
        $keys = array( 'opaque_id', 'state', 'channel', 'sender_key', 'priority',
            'attempt', 'max_attempts', 'next_attempt_at', 'diagnostic_code', 'state_version' );
        if ( ! is_array( $data ) ||
             array_keys( $data ) !== array( 'schema', 'source', 'snapshot_not_live',
                'mysql_network_disabled', 'email_sent', 'worker_enabled', 'total', 'counts', 'rows' ) ||
             ( $data['schema'] ?? '' ) !== 'mail-v2-p7-mysql8-synthetic-snapshot-v1' ||
             ( $data['source'] ?? '' ) !== 'isolated_mysql_8_4_point_in_time' ||
             $data['snapshot_not_live'] !== true ||
             $data['mysql_network_disabled'] !== true ||
             $data['email_sent'] !== false || $data['worker_enabled'] !== false ||
             $data['total'] !== 100 || ! is_array( $data['rows'] ) ||
             count( $data['rows'] ) !== 100 || ! is_array( $data['counts'] ) ) {
            echo '<p>快照协议校验失败；未展示任何任务信息。</p>';
            return;
        }
        $counts = array_fill_keys( self::STATES, 0 );
        $seen = array();
        foreach ( $data['rows'] as $item ) {
            if ( ! is_array( $item ) || array_keys( $item ) !== $keys ||
                 ! is_string( $item['opaque_id'] ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $item['opaque_id'] ) ||
                 isset( $seen[ $item['opaque_id'] ] ) ||
                 ! in_array( $item['state'], self::STATES, true ) ||
                 ! in_array( $item['channel'], array( 'critical_system','account','newsletter','campaign','low_priority' ), true ) ||
                 ! preg_match( '/\A[a-z][a-z0-9_]{0,47}\z/D', $item['sender_key'] ) ||
                 ! in_array( $item['diagnostic_code'], array( 'none','preacceptance_reject',
                     'acceptance_unknown','permanent_reject','provider_accepted' ), true ) ) {
                echo '<p>快照记录异常；未展示任何任务信息。</p>';
                return;
            }
            foreach ( array( 'priority','attempt','max_attempts','next_attempt_at','state_version' ) as $num ) {
                if ( ! is_int( $item[ $num ] ) || $item[ $num ] < 0 ) {
                    echo '<p>快照字段校验失败。</p>';
                    return;
                }
            }
            $seen[ $item['opaque_id'] ] = true;
            ++$counts[ $item['state'] ];
        }
        if ( $counts !== $data['counts'] ||
             $counts['deferred'] + $counts['uncertain'] + $counts['permanent_failed'] !== 60 ) {
            echo '<p>快照状态与汇总不一致。</p>';
            return;
        }
        $filter = isset( $_GET['filter'] ) && is_scalar( $_GET['filter'] )
            ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
        if ( ! in_array( $filter, array( 'all','failures' ), true ) ) { $filter = 'all'; }
        $rows = 'failures' === $filter
            ? array_values( array_filter( $data['rows'], static function ( $r ) {
                return in_array( $r['state'], array( 'deferred','permanent_failed','uncertain' ), true );
              } ) )
            : $data['rows'];
        $page = max( 1, min( 5, absint( $_GET['paged'] ?? 1 ) ) );
        $shown = array_slice( $rows, ( $page - 1 ) * 20, 20 );
        echo '<p>已核验的 MySQL 快照：100 条合成任务；失败/待复核 60 条。';
        echo '只读快照，不提供发送、重试、取消或删除操作。</p>';
        echo '<p><a class="button" href="' . esc_url( add_query_arg( 'filter', 'all', self::url( 'mysql_snapshot' ) ) ) .
            '">全部 100</a> <a class="button" href="' .
            esc_url( add_query_arg( 'filter', 'failures', self::url( 'mysql_snapshot' ) ) ) .
            '">失败/待复核 60</a></p>';
        echo '<div style="overflow-x:auto"><table class="widefat striped" style="min-width:750px"><thead><tr>';
        foreach ( array( '测试编号','状态','通道','发送身份','优先级','尝试次数','模拟时间','诊断代码','版本' ) as $label ) {
            echo '<th scope="col">' . esc_html( $label ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $shown as $r ) {
            $cells = array( substr( $r['opaque_id'], 0, 12 ), $r['state'], $r['channel'],
                $r['sender_key'], (string)$r['priority'],
                $r['attempt'].'/'.$r['max_attempts'], (string)$r['next_attempt_at'],
                $r['diagnostic_code'], (string)$r['state_version'] );
            echo '<tr>';
            foreach ( $cells as $cell ) { echo '<td>' . esc_html( $cell ) . '</td>'; }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '<p>第 ' . esc_html( $page ) . ' 页 / ' .
             esc_html( (int)ceil( count( $rows ) / 20 ) ) . ' 页。';
        if ( $page > 1 ) {
            echo ' <a class="button" href="' . esc_url( add_query_arg(
                array( 'filter' => $filter, 'paged' => $page - 1 ), self::url( 'mysql_snapshot' ) ) ) . '">上一页</a>';
        }
        if ( $page * 20 < count( $rows ) ) {
            echo ' <a class="button" href="' . esc_url( add_query_arg(
                array( 'filter' => $filter, 'paged' => $page + 1 ), self::url( 'mysql_snapshot' ) ) ) . '">下一页</a>';
        }
        echo '</p>';
    }

}

require_once __DIR__ . '/class-yby-mail-v2-p8-readonly.php';
require_once __DIR__ . '/class-yby-mail-v2-p8-setup.php';
