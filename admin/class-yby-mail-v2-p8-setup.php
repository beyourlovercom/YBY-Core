<?php
/**
 * P8 one-time Dev-only private MySQL configuration form.
 * Only a WordPress administrator who can manage Email OS can submit.
 * Stores the one separate Hostinger DB account in a private mode-0600 file
 * outside public_html; no options, postmeta, logs, notices or GitHub secrets.
 *
 * Does NOT create a DB, write MySQL tables/rows, send mail or start workers.
 */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P8_Setup {
    const ACTION = 'yby_mail_v2_p8_save_config';
    const NONCE = 'yby_mail_v2_p8_setup';
    const SOCKET = '/var/lib/mysql/mysql.sock';
    const RELATIVE = '.byl-dev-canary/mail-v2-p8-dev-readonly';
    public static function available() {
        return class_exists('YBY_Mail_V2_P8_Readonly') &&
            YBY_Mail_V2_P8_Readonly::eligible_site() &&
            current_user_can('manage_options') &&
            current_user_can('andy_core_mail_view');
    }
    public static function register() {
        if (function_exists('add_action') &&
            class_exists('YBY_Mail_V2_P8_Readonly') &&
            YBY_Mail_V2_P8_Readonly::eligible_site()) {
            add_action('admin_post_'.self::ACTION,array(__CLASS__,'save'));
        }
    }
    public static function render() {
        if (!self::available()) {wp_die(esc_html__('Permission denied.','yby-core'));}
        $existing = YBY_Mail_V2_P8_Readonly::configured();
        echo '<div class="notice notice-warning inline"><p><strong>P8 独立数据库连接</strong>：';
        echo '仅连接 Dev 新建的隔离 MySQL 数据库。不连接 WordPress 业务数据库；';
        echo '不会建表、发送邮件或运行 Worker。</p></div>';
        if ($existing) {
            echo '<p>私有连接文件已保存。必须先完成独立数据库合成任务初始化及权限降级至仅 SELECT，';
            echo '实时预览才会读取任务。连接密码不会在这里显示。</p>';
            return;
        }
        echo '<p>请输入 hPanel 中新建的完整数据库名、完整用户名及该数据库密码。';
        echo '凭据仅保存在服务器私有目录（0600），不进入 WordPress 数据库或 GitHub。</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" autocomplete="off">';
        echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION).'">';
        wp_nonce_field(self::NONCE);
        echo '<table class="form-table"><tbody>';
        foreach (array('p8_dbname'=>'新数据库完整名称','p8_user'=>'新数据库完整用户名') as $name=>$label) {
            echo '<tr><th><label for="'.esc_attr($name).'">'.esc_html($label).'</label></th>';
            echo '<td><input class="regular-text" required maxlength="64" id="'.esc_attr($name).
                '" name="'.esc_attr($name).'" type="text" autocomplete="off"></td></tr>';
        }
        echo '<tr><th><label for="p8_password">新数据库密码</label></th><td>';
        echo '<input class="regular-text" required maxlength="256" id="p8_password" ';
        echo 'name="p8_password" type="password" autocomplete="new-password">';
        echo '<p class="description">请勿把密码发送到聊天。只验证新数据库，不会修改其他数据库。</p>';
        echo '</td></tr></tbody></table>';
        submit_button('验证独立数据库并保存私有凭据');
        echo '</form>';
    }
    private static function failure() {
        wp_die(esc_html__('P8 独立数据库连接尚未通过隔离或权限预检；未保存配置。请核对数据库名、账户和权限。','yby-core'));
    }
    private static function inspect($pdo,$database) {
        if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$database) {return false;}
        $grants=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
        $scope='[\\x60]?'.preg_quote($database,'/').'[\\x60]?[.][*]';
        $scoped=false;
        foreach ($grants as $grant) {
            $grant=(string)$grant;
            if (preg_match('/\bGRANT USAGE ON [*][.][*] TO\b/i',$grant)) {continue;}
            if (!preg_match('/\bGRANT .+ ON '.$scope.' TO\b/i',$grant)) {return false;}
            if (!preg_match('/\b(?:CREATE|ALL PRIVILEGES)\b/i',$grant) ||
                !preg_match('/\b(?:INSERT|ALL PRIVILEGES)\b/i',$grant)) {return false;}
            $scoped=true;
        }
        if (!$scoped) {return false;}
        $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');
        $q->execute(array($database));
        return (int)$q->fetchColumn()===0;
    }
    public static function save() {
        if (!self::available() || !is_ssl() || $_SERVER['REQUEST_METHOD']!=='POST') {self::failure();}
        check_admin_referer(self::NONCE);
        foreach (array('p8_dbname','p8_user','p8_password') as $key) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) {self::failure();}
        }
        $database=(string)wp_unslash($_POST['p8_dbname']);
        $user=(string)wp_unslash($_POST['p8_user']);
        $password=(string)wp_unslash($_POST['p8_password']);
        if (!preg_match('/\A[A-Za-z0-9_]{1,64}\z/D',$database) ||
            !preg_match('/\A[A-Za-z0-9_]{1,64}\z/D',$user) ||
            !is_string($password) || $password==='' || strlen($password)>256 ||
            (defined('DB_NAME') && $database===DB_NAME) ||
            !file_exists(self::SOCKET) || is_link(self::SOCKET) ||
            YBY_Mail_V2_P8_Readonly::configured()) {self::failure();}
        try {
            $pdo=new PDO('mysql:unix_socket='.self::SOCKET.';dbname='.$database.';charset=ascii',
                $user,$password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3));
            if (!self::inspect($pdo,$database)) {self::failure();}
            $pdo=null;
        } catch (Throwable $e) {self::failure();}
        $dir=dirname(ABSPATH,3).'/'.self::RELATIVE;
        if (realpath($dir)!==$dir || is_link($dir) || (fileperms($dir)&0077)!==0) {self::failure();}
        $file=$dir.'/connection.php';
        if (file_exists($file) || is_link($file)) {self::failure();}
        $data=array('dbname'=>$database,'user'=>$user,'password'=>$password,
            'socket'=>self::SOCKET,'table'=>YBY_Mail_V2_P8_Readonly::TABLE);
        $php="<?php\n// Private P8 Dev-only credentials. Never commit to GitHub.\nreturn ".var_export($data,true).";\n";
        $old=umask(0077);
        $handle=@fopen($file,'x');
        umask($old);
        if (!$handle) {self::failure();}
        $ok=false;
        try {
            if (fwrite($handle,$php)===strlen($php) && fflush($handle) &&
                chmod($file,0600) && (fileperms($file)&0077)===0) {
                $ok=true;
            }
        } finally {
            fclose($handle);
            if (!$ok) {@unlink($file);}
        }
        if (!$ok) {self::failure();}
        // Never include submitted password/name in URL, notice, log or email.
        wp_safe_redirect(YBY_Three_OS_Admin::url(YBY_Three_OS_Admin::EMAIL,'p8_setup'),303);
        exit;
    }
}
YBY_Mail_V2_P8_Setup::register();
