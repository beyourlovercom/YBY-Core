<?php
/** P8 Dev ONLY: read-only, separate MySQL database. No DDL, sends or worker. */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P8_Readonly {
    const NONCE = 'yby_mail_v2_p8_live_readonly';
    const DIR = '.byl-dev-canary/mail-v2-p8-dev-readonly';
    const TABLE = 'p8_yby_mail_v2_outbox';
    const MAX_ROWS = 100;
    const FIELDS = array('opaque_id','state','channel','sender_key','priority','attempt','max_attempts','next_attempt_at','diagnostic_code','state_version');
    const STATES = array('queued','leased','sending','accepted_by_provider','deferred','permanent_failed','uncertain','cancelled');
    const INDEXES = array('PRIMARY'=>array('opaque_id'),'uq_idempotency_key'=>array('idempotency_key'),
        'idx_state_priority_due'=>array('state','priority','next_attempt_at'),
        'idx_sender_state_due'=>array('sender_key','state','next_attempt_at'));
    const COLUMNS = array(
        'opaque_id'=>'char(64) not null','idempotency_key'=>'char(64) not null',
        'event_hash'=>'char(64) not null','payload_ref'=>'char(64) null',
        'channel'=>'varchar(32) not null','sender_key'=>'varchar(48) not null',
        'template_key'=>'varchar(48) not null','template_version'=>'int unsigned not null',
        'consent_snapshot_hash'=>'char(64) null','priority'=>'tinyint unsigned not null',
        'state'=>'varchar(32) not null','state_version'=>'bigint unsigned not null',
        'attempt'=>'tinyint unsigned not null','max_attempts'=>'tinyint unsigned not null',
        'next_attempt_at'=>'bigint unsigned not null','lease_token_hash'=>'char(64) null',
        'lease_expires_at'=>'bigint unsigned null','diagnostic_code'=>'varchar(32) not null',
        'created_at'=>'datetime(6) not null','updated_at'=>'datetime(6) not null');
    public static function eligible_site() {
        return function_exists('site_url') && parse_url(site_url(),PHP_URL_HOST)==='dev.beyourlover.com'
            && function_exists('is_admin') && is_admin() && extension_loaded('pdo_mysql');
    }
    private static function config_path() {
        if (!self::eligible_site() || !defined('ABSPATH')) { return null; }
        $dir=dirname(ABSPATH,3).'/'.self::DIR;
        $file=$dir.'/connection.php';
        if (realpath($dir)!==$dir || is_link($dir) ||
            !is_file($file) || is_link($file) || realpath($file)!==$file ||
            !is_readable($file) || (fileperms($file)&0077)!==0) { return null; }
        return $file;
    }
    public static function configured() { return null!==self::config_path(); }
    public static function url() {
        if (!self::configured()) {return '';}
        return wp_nonce_url(YBY_Three_OS_Admin::url(YBY_Three_OS_Admin::EMAIL,'live_readonly'),self::NONCE);
    }
    private static function permitted() {
        $nonce=isset($_GET['_wpnonce'])&&is_scalar($_GET['_wpnonce'])?
            sanitize_text_field(wp_unslash($_GET['_wpnonce'])):'';
        return self::eligible_site() && current_user_can('manage_options') &&
            current_user_can('andy_core_mail_view') && wp_verify_nonce($nonce,self::NONCE);
    }
    private static function config() {
        $path=self::config_path();
        if (null===$path) {return null;}
        $c=require $path;
        if (!is_array($c) || array_keys($c)!==array('dbname','user','password','socket','table')) {return null;}
        foreach ($c as $v) {if (!is_string($v) || $v==='') {return null;}}
        if (!preg_match('/\A[A-Za-z0-9_]{1,64}\z/D',$c['dbname']) ||
            !preg_match('/\A[A-Za-z0-9_]{1,64}\z/D',$c['user']) ||
            $c['table']!==self::TABLE ||
            (defined('DB_NAME') && $c['dbname']===DB_NAME) ||
            !in_array($c['socket'],array('/var/lib/mysql/mysql.sock','/run/mysqld/mysqld.sock'),true) ||
            !file_exists($c['socket']) || is_link($c['socket'])) {return null;}
        return $c;
    }
    private static function safe_grants($pdo,$schema) {
        $grants=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
        $has_select=false;
        foreach ($grants as $grant) {
            $grant=(string)$grant;
            // No secrets / grants logged; deny extra privileges and broad SELECT.
            if (preg_match('/\b(?:INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|EXECUTE|TRIGGER|EVENT|FILE|SUPER|PROCESS|REFERENCES|RELOAD|GRANT OPTION|ALL PRIVILEGES)\b/i',$grant)) {return false;}
            $prefix='/\bGRANT SELECT ON [\x60]?'.preg_quote($schema,'/').'[\x60]?[.][*] TO\b/i';
            if (preg_match($prefix,$grant)) {$has_select=true;}
            elseif (!preg_match('/\bGRANT USAGE ON [*][.][*] TO\b/i',$grant)) {return false;}
        }
        return $has_select;
    }
    private static function verify_schema($pdo,$schema) {
        $stmt=$pdo->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $stmt->execute(array($schema,self::TABLE));$table=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$table || $table['ENGINE']!=='InnoDB' || strtolower($table['TABLE_COLLATION'])!=='ascii_bin') {return false;}
        $stmt=$pdo->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
        $stmt->execute(array($schema,self::TABLE));$cols=$stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($cols)!==count(self::COLUMNS)) {return false;}
        $names=array_keys(self::COLUMNS);$defs=array_values(self::COLUMNS);
        foreach ($cols as $i=>$r) {
            $type=strtolower((string)$r['COLUMN_TYPE']);
            // MariaDB 11.x reports legacy integer display widths (e.g.
            // bigint(20) unsigned); MySQL 8.x omits them. Width does not
            // change the stored integer type. Normalize only int families.
            $type=preg_replace('/\\A(tinyint|int|bigint)\\([0-9]+\\)( unsigned)?\\z/D',
                '$1$2',$type);
            $observed=$type.($r['IS_NULLABLE']==='YES'?' null':' not null');
            if ($r['COLUMN_NAME']!==$names[$i] || $observed!==$defs[$i]) {return false;}
        }
        $stmt=$pdo->prepare('SELECT INDEX_NAME,COLUMN_NAME,NON_UNIQUE,SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX');
        $stmt->execute(array($schema,self::TABLE));$indexes=array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $key=$r['INDEX_NAME'];
            $indexes[$key]['columns'][]=$r['COLUMN_NAME'];
            $indexes[$key]['nonunique']=(int)$r['NON_UNIQUE'];
        }
        if (count($indexes)!==count(self::INDEXES)) {return false;}
        foreach (self::INDEXES as $key=>$fields) {
            if (!isset($indexes[$key]) || $indexes[$key]['columns']!==$fields ||
                $indexes[$key]['nonunique']!==(in_array($key,array('PRIMARY','uq_idempotency_key'),true)?0:1)) {return false;}
        }
        return true;
    }
    public static function read($filter,$page) {
        if (!self::permitted()) {return array('status'=>'blocked','reason'=>'auth');}
        if (!in_array($filter,array('all','failures'),true) || !is_int($page) ||
            $page<1 || $page>5) {return array('status'=>'blocked','reason'=>'request');}
        $c=self::config();
        if (!$c) {return array('status'=>'blocked','reason'=>'config');}
        try {
            $pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['dbname'].';charset=ascii',
                $c['user'],$c['password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3));
            if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$c['dbname'] ||
                !self::safe_grants($pdo,$c['dbname']) ||
                !self::verify_schema($pdo,$c['dbname'])) {
                return array('status'=>'blocked','reason'=>'source');
            }
            $pdo->exec('SET SESSION TRANSACTION READ ONLY');
            $pdo->beginTransaction();
            $total=(int)$pdo->query('SELECT COUNT(*) FROM '.self::TABLE)->fetchColumn();
            if ($total<1 || $total>self::MAX_ROWS) {$pdo->rollBack();return array('status'=>'blocked','reason'=>'count');}
            $counts=array_fill_keys(self::STATES,0);
            foreach ($pdo->query('SELECT state,COUNT(*) AS n FROM '.self::TABLE.' GROUP BY state')->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (!array_key_exists($r['state'],$counts)) {$pdo->rollBack();return array('status'=>'blocked','reason'=>'state');}
                $counts[$r['state']]=(int)$r['n'];
            }
            if (array_sum($counts)!==$total) {$pdo->rollBack();return array('status'=>'blocked','reason'=>'count');}
            $fail='failures'===$filter;
            $where=$fail?" WHERE state IN ('deferred','permanent_failed','uncertain')":'';
            $visible=$fail?$counts['deferred']+$counts['permanent_failed']+$counts['uncertain']:$total;
            $sql='SELECT '.implode(',',self::FIELDS).' FROM '.self::TABLE.$where.
                ' ORDER BY priority ASC,next_attempt_at ASC,opaque_id ASC LIMIT ? OFFSET ?';
            $stmt=$pdo->prepare($sql);
            $stmt->bindValue(1,20,PDO::PARAM_INT);
            $stmt->bindValue(2,($page-1)*20,PDO::PARAM_INT);
            $stmt->execute();$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                if (array_keys($r)!==self::FIELDS ||
                    !preg_match('/\A[a-f0-9]{64}\z/D',$r['opaque_id']) ||
                    !in_array($r['state'],self::STATES,true) ||
                    !preg_match('/\A[a-z][a-z0-9_]{0,47}\z/D',$r['sender_key'])) {
                    $pdo->rollBack();return array('status'=>'blocked','reason'=>'row');
                }
                foreach (array('priority','attempt','max_attempts','next_attempt_at','state_version') as $field) {
                    if (!ctype_digit((string)$r[$field])) {$pdo->rollBack();return array('status'=>'blocked','reason'=>'row');}
                    $r[$field]=(int)$r[$field];
                }
            } unset($r);
            $pdo->rollBack();
            return array('status'=>'read_only','total'=>$total,'visible'=>$visible,
                'page'=>$page,'filter'=>$filter,'counts'=>$counts,'rows'=>$rows);
        } catch (Throwable $e) {
            return array('status'=>'blocked','reason'=>'unavailable');
        }
    }
    public static function render() {
        if (!self::permitted()) {wp_die(esc_html__('Permission denied.','yby-core'));}
        $filter=isset($_GET['filter'])&&is_scalar($_GET['filter'])?
            sanitize_key(wp_unslash($_GET['filter'])):'all';
        $page=max(1,min(5,absint($_GET['paged']??1)));
        echo '<div class="notice notice-warning inline"><p><strong>P8 独立 Dev MySQL · 实时只读</strong>：';
        echo '仅合成任务，非正式邮件队列。SMTP 和 Worker 未启用。</p></div>';
        $r=self::read($filter,$page);
        if ($r['status']!=='read_only') {echo '<p>独立测试库、只读权限或表结构未就绪；预览已安全关闭。</p>';return;}
        echo '<p>实时 SELECT：总计 '.esc_html($r['total']).' 条合成任务；当前筛选 '.esc_html($r['visible']).' 条。</p>';
        echo '<p><a class="button" href="'.esc_url(add_query_arg('filter','all',self::url())).'">全部任务</a> ';
        echo '<a class="button" href="'.esc_url(add_query_arg('filter','failures',self::url())).'">失败/待复核</a></p>';
        echo '<div style="overflow-x:auto"><table class="widefat striped" style="min-width:780px"><thead><tr>';
        foreach (array('测试编号','状态','通道','发送身份','优先级','尝试次数','模拟时间','诊断代码','版本') as $label) {echo '<th>'.esc_html($label).'</th>';}
        echo '</tr></thead><tbody>';
        foreach ($r['rows'] as $item) {
            $cells=array(substr($item['opaque_id'],0,12),$item['state'],$item['channel'],
                $item['sender_key'],$item['priority'],$item['attempt'].'/'.$item['max_attempts'],
                $item['next_attempt_at'],$item['diagnostic_code'],$item['state_version']);
            echo '<tr>';foreach($cells as $v){echo '<td>'.esc_html((string)$v).'</td>';}echo '</tr>';
        }
        echo '</tbody></table></div>';
        if ($page>1){echo '<a class="button" href="'.esc_url(add_query_arg(array('paged'=>$page-1,'filter'=>$filter),self::url())).'">上一页</a> ';}
        if ($page*20<$r['visible']){echo '<a class="button" href="'.esc_url(add_query_arg(array('paged'=>$page+1,'filter'=>$filter),self::url())).'">下一页</a>';}
    }
}
