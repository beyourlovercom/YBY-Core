<?php
/**
 * M3 isolated MySQL persistence integration harness.
 * Run ONLY against a disposable database created for this test.
 * Never auto-run in WordPress or CI without the explicit opt-in.
 */
declare(strict_types=1);
if (getenv('ANDY_M3_DISPOSABLE_DB') !== 'YES') {
    echo "M3_MYSQL_SKIPPED_NO_DISPOSABLE_DB\n";
    exit(0);
}
$host = getenv('ANDY_M3_TEST_DB_HOST');
$name = getenv('ANDY_M3_TEST_DB_NAME');
$user = getenv('ANDY_M3_TEST_DB_USER');
$password = getenv('ANDY_M3_TEST_DB_PASSWORD');
if (!$host || !$name || !$user || $password === false
    || !preg_match('/^andy_m3_disposable_[a-zA-Z0-9_]+$/D', $name)) {
    fwrite(STDERR, "M3_FAIL_CLOSED_DB_IDENTITY\n");
    exit(2);
}
if (!extension_loaded('mysqli')) { fwrite(STDERR, "M3_MYSQLI_MISSING\n"); exit(2); }
$db = new mysqli($host, $user, $password, $name);
if ($db->connect_errno) { fwrite(STDERR, "M3_DB_CONNECT_FAILED\n"); exit(2); }
$db->set_charset('utf8mb4');
if ($db->query('SELECT DATABASE()')->fetch_row()[0] !== $name) {
    fwrite(STDERR, "M3_DB_MISMATCH\n"); exit(2);
}
$prefix='m3test_';
foreach ([$prefix.'andy_commerce_campaign_drafts', $prefix.'andy_commerce_campaign_audit'] as $table) {
    $check=$db->query("SHOW TABLES LIKE '".$db->real_escape_string($table)."'");
    if ($check && $check->num_rows) { fwrite(STDERR, "M3_TABLE_COLLISION\n"); exit(2); }
}
define('ABSPATH',__DIR__);
define('ARRAY_A','ARRAY_A');
function wp_json_encode($a):string|false { return json_encode($a,JSON_UNESCAPED_SLASHES); }
require_once dirname(__DIR__).'/includes/class-andy-commerce-marketing-draft-sandbox.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-marketing-draft-repository.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m3-human-form.php';
/* Stub implements only wpdb API exercised by repository. */
final class M3SqlWpdb {
    public string $prefix='m3test_';
    private mysqli $connection;
    public function __construct(mysqli $conn) { $this->connection=$conn; }
    public function prepare(string $sql,...$args):string {
        $index=0;
        return preg_replace_callback('/%[sd]/', function(array $match) use ($args,&$index):string {
            if (!array_key_exists($index,$args)) { throw new RuntimeException('Missing SQL argument'); }
            $v=$args[$index++];
            return $match[0]==='%d' ? (string)(int)$v
                : "'".$this->connection->real_escape_string((string)$v)."'";
        },$sql);
    }
    public function esc_like(string $search):string {
        return addcslashes($search,'_%\\\\');
    }
    public function get_var(string $sql) {
        $result=$this->connection->query($sql);
        if (!$result || !$result->num_rows) { return null; }
        $row=$result->fetch_row();return $row[0];
    }
    public function get_results(string $sql,$mode):array {
        $result=$this->connection->query($sql);
        return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function query(string $sql):int|false {
        $r=$this->connection->query($sql);
        return $r === false ? false : ($r === true ? 1 : $r->num_rows);
    }
    public function get_row(string $sql,$mode):?array {
        $r=$this->connection->query($sql);
        return $r instanceof mysqli_result ? ($r->fetch_assoc() ?: null) : null;
    }
    public function insert(string $table,array $data):int|false {
        $cols=array_map(static fn($x)=>' `'.str_replace('`','',$x).'` ',array_keys($data));
        $vals=array_map(fn($v)=>"'".$this->connection->real_escape_string((string)$v)."'",array_values($data));
        $r=$this->connection->query('INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')');
        return $r ? 1:false;
    }
    public function update(string $table,array $data,array $where,array $formats=[],array $whereFormats=[]):int|false {
        $pieces=[];
        foreach ($data as $col=>$val) $pieces[]='`'.$col.'`='."'".$this->connection->real_escape_string((string)$val)."'";
        $pred=[];
        foreach ($where as $col=>$val) $pred[]='`'.$col.'`='."'".$this->connection->real_escape_string((string)$val)."'";
        $r=$this->connection->query('UPDATE `'.$table.'` SET '.implode(',',$pieces).' WHERE '.implode(' AND ',$pred));
        return $r ? $this->connection->affected_rows:false;
    }
}
$wpdb=new M3SqlWpdb($db);
$repo=new Andy_Commerce_M3_Draft_Repository($wpdb);
$tables=$repo->schemaSql('DEFAULT CHARACTER SET utf8mb4');
try {
    foreach ($tables as $sql) if (!$db->query($sql)) throw new RuntimeException('Create table failed');
    $payload=['schema_version'=>1,'id'=>'m3-sql-test-001','name'=>'Isolation Test','type'=>'spend_save','currency'=>'USD','status'=>'draft','active'=>false,'priority'=>0,'rule'=>['threshold_minor'=>10000,'discount_minor'=>500]];
    $a=$repo->save($payload,9);
    if ($a['revision']!==1) throw new RuntimeException('Create mismatch');
    $b=$repo->save($payload,9,1);
    if ($b['revision']!==2) throw new RuntimeException('Revise mismatch');
    try { $repo->save($payload,9,1); throw new RuntimeException('Stale edit accepted'); }
    catch (DomainException $e) { /* expected */ }
    $copy=$repo->duplicate('m3-sql-test-001',2,9,'m3-sql-copy-001');
    if ($copy['revision']!==1 || $copy['active']!==false) throw new RuntimeException('Copy not inactive');
    if ($repo->countByStatus('draft')!==2) throw new RuntimeException('Wrong All count');
    $listing=$repo->listCampaigns('draft','Isolation',1);
    if ($listing['total']!==2 || count($listing['items'])!==2) throw new RuntimeException('Search/list count wrong');
    if (!str_contains($listing['items'][0]['summary'],'立减')) throw new RuntimeException('Human-readable rules missing');
    try { $repo->duplicate('m3-sql-test-001',1,9,'m3-sql-copy-old'); throw new RuntimeException('Stale copied'); }
    catch (DomainException $e) { /* expected */ }
    if ($repo->archiveBatch(['m3-sql-test-001'=>2,'m3-sql-copy-001'=>1],9)!==2)
        throw new RuntimeException('Bulk archive count wrong');
    $row=$repo->find('m3-sql-test-001');
    $copyRow=$repo->find('m3-sql-copy-001');
    if (!$row || $row['status']!=='archived' || (int)$row['active']!==0 || (int)$row['revision']!==3
        || !$copyRow || $copyRow['status']!=='archived' || (int)$copyRow['revision']!==2)
        throw new RuntimeException('Bulk archive state wrong');
    if ($repo->countByStatus('archived')!==2 || $repo->countByStatus('draft')!==0)
        throw new RuntimeException('Trash counts wrong');
    $trash=$repo->listCampaigns('archived','',1);
    if ($trash['total']!==2) throw new RuntimeException('Trash list unavailable');
    try { $repo->archiveBatch(['m3-sql-copy-001'=>1],9); throw new RuntimeException('Stale archived twice'); }
    catch (DomainException $e) { /* expected */ }
    $count=$db->query('SELECT COUNT(*) AS n FROM `'.$prefix.'andy_commerce_campaign_audit`')->fetch_assoc()['n'];
    if ((int)$count!==5) throw new RuntimeException('Audit mismatch');
    echo "M3_DISPOSABLE_MYSQL_LIFECYCLE_PASS\n";
} finally {
    // Only tables created by this exact harness in a strictly named disposable DB.
    $db->query('DROP TABLE IF EXISTS `'.$prefix.'andy_commerce_campaign_audit`');
    $db->query('DROP TABLE IF EXISTS `'.$prefix.'andy_commerce_campaign_drafts`');
    $db->close();
}
