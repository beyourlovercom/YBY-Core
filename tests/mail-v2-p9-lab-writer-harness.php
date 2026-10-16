<?php
/**
 * P9 isolated synthetic Writer contract tests — no real PDO network and no DB
 * access. Production WordPress is never bootstrapped; no emails can be sent.
 */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p2-planner.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php';
$n=0;
function p9ok($yes,$label){global $n;++$n;if(!$yes)throw new RuntimeException('P9_LAB_WRITER_FAIL:'.$label);}

class P9TestStatement extends PDOStatement {
    public $rows=array();public $count=0;
    private $db;private $sql;
    public function __construct($db,$sql,$rows=array()){$this->db=$db;$this->sql=$sql;$this->rows=$rows;}
    public function execute(?array $params=null):bool {
        $sql=$this->sql;$params=$params??array();
        if(strpos($sql,'information_schema.TABLES')!==false){
            $this->rows=$this->db->tables;return true;
        }
        if(strpos($sql,'information_schema.COLUMNS')!==false){
            $this->rows=$this->db->columns;return true;
        }
        if(strpos($sql,'information_schema.STATISTICS')!==false){
            $this->rows=$this->db->indexes;return true;
        }
        if(strpos($sql,'INSERT INTO p9_yby_mail_v2_outbox')!==false){
            $key=$params[1];
            foreach($this->db->inserted as $existing){
                if($existing['idempotency_key']===$key){
                    throw new PDOException('UNIQUE violation',23000);
                }
            }
            $fields=YBY_Mail_V2_P9_Lab_Writer::COLUMNS;
            $row=array_combine($fields,$params);
            $this->db->inserted[]=$row;
            $this->count=1;return true;
        }
        if(strpos($sql,'SELECT opaque_id,event_hash,channel FROM')!==false){
            $this->rows=array_values(array_filter($this->db->inserted,static function($r)use($params){
                return $r['idempotency_key']===$params[0];
            }));
            return true;
        }
        if(strpos($sql,'UPDATE p9_yby_mail_v2_outbox')!==false){
            foreach($this->db->inserted as &$row){
                if($row['opaque_id']===$params[2] && $row['state']==='queued' &&
                    $row['state_version']===$params[3] && $row['next_attempt_at']<=$params[4]){
                    $row['state']='leased';++$row['state_version'];
                    $row['lease_token_hash']=$params[0];$row['lease_expires_at']=$params[1];
                    $this->count=1;break;
                }
            } unset($row);
            return true;
        }
        throw new RuntimeException('UNRECOGNIZED_SIMULATED_SQL');
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array {
        if($mode===PDO::FETCH_COLUMN){
            return array_map(static function($r){return is_array($r)?reset($r):$r;},$this->rows);
        }
        return $this->rows;
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{
        return $this->rows[0]??false;
    }
    public function fetchColumn(int $column=0):mixed{return $this->rows[0][$column]??null;}
    public function rowCount():int{return $this->count;}
}
class P9TestPDO extends PDO {
    public $schema='p9_mail_synthetic_uat';public $skip=1;
    public $datadir='/Users/a/.byl-dev-canary/mail-v2-p9-lab/data/';
    public $who='p9_writer@localhost';public $grants=array();
    public $tables=array();public $columns=array();public $indexes=array();public $inserted=array();
    public function __construct(){
        $g=chr(96);
        $this->grants=array("GRANT USAGE ON *.* TO 'p9_writer'@'localhost'",
           "GRANT SELECT, INSERT, UPDATE ON {$g}p9_mail_synthetic_uat{$g}.* TO 'p9_writer'@'localhost'");
        $this->tables=array(array('TABLE_NAME'=>'p9_yby_mail_v2_outbox','ENGINE'=>'InnoDB','TABLE_COLLATION'=>'ascii_bin'));
        foreach(YBY_Mail_V2_P6_Outbox_Plan::columns() as $k=>$v){
            $nullable=!str_ends_with(strtolower($v),'not null');
            $this->columns[]=array('COLUMN_NAME'=>$k,'COLUMN_TYPE'=>strtolower(preg_replace('/ (?:NOT )?NULL\z/i','',$v)),'IS_NULLABLE'=>$nullable?'YES':'NO');
        }
        foreach(YBY_Mail_V2_P6_Outbox_Plan::INDEXES as $key=>$fields){
            foreach($fields as $col)$this->indexes[]=array('INDEX_NAME'=>$key,'COLUMN_NAME'=>$col,
                'NON_UNIQUE'=>in_array($key,array('PRIMARY','uq_idempotency_key'),true)?0:1);
        }
    }
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false{
        if($query==='SELECT DATABASE()')return new P9TestStatement($this,$query,array(array($this->schema)));
        if($query==='SELECT @@skip_networking')return new P9TestStatement($this,$query,array(array($this->skip)));
        if($query==='SELECT @@datadir')return new P9TestStatement($this,$query,array(array($this->datadir)));
        if($query==='SELECT CURRENT_USER()')return new P9TestStatement($this,$query,array(array($this->who)));
        if($query==='SHOW GRANTS')return new P9TestStatement($this,$query,array_map(static function($x){return array($x);},$this->grants));
        if($query==='SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')return new P9TestStatement($this,$query,array(array(count($this->inserted))));
        throw new RuntimeException('UNEXPECTED_P9_QUERY');
    }
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new P9TestStatement($this,$query);}
}
$fake=new P9TestPDO();
putenv('MAIL_P9_LAB_ONLY');
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'disabled by default');
putenv('MAIL_P9_LAB_ONLY=EXPLICIT_ISOLATED_SYNTHETIC_ONLY');
$fake->skip=0;p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'TCP networking blocked');
$fake->skip=1;$fake->schema='other_db';
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'wrong DB blocked');
$fake->schema='p9_mail_synthetic_uat';$fake->who='root@localhost';
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'root identity blocked');
$fake->who='p9_writer@localhost';$fake->datadir='/Users/a/.byl-dev-canary/mail-v2-p7-mysql-20261014/data';
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'P7 lab never reused');
$fake->datadir='/Users/a/.byl-dev-canary/mail-v2-p9-lab/data';
$g=chr(96);
$fake->grants[]="GRANT DELETE ON {$g}p9_mail_synthetic_uat{$g}.* TO 'p9_writer'@'localhost'";
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'extra DELETE grant blocked');
array_pop($fake->grants);
$fake->tables[]=array('TABLE_NAME'=>'customer_data','ENGINE'=>'InnoDB','TABLE_COLLATION'=>'ascii_bin');
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'extra tables blocked');
array_pop($fake->tables);
$fake->columns[0]['COLUMN_TYPE']='varchar(63)';
p9ok(YBY_Mail_V2_P9_Lab_Writer::open($fake)===null,'column drift blocked');
$fake=new P9TestPDO();
$writer=YBY_Mail_V2_P9_Lab_Writer::open($fake);
p9ok($writer instanceof YBY_Mail_V2_P9_Lab_Writer,'isolated lab opened');
$job=array('synthetic_only'=>true,'opaque_id'=>hash('sha256','p9-opaque-1'),
    'idempotency_key'=>hash('sha256','p9-idempotency-1'),
    'event_hash'=>hash('sha256','p9-event-1'),'channel'=>'critical_system',
    'sender_key'=>'system_tx','next_attempt_at'=>100);
p9ok($writer->enqueue($job)['status']==='inserted','first insert');
p9ok($writer->enqueue($job)['status']==='duplicate','idempotent exact replay');
p9ok(count($fake->inserted)===1,'no duplicate SQL rows');
$changed=$job;$changed['event_hash']=hash('sha256','altered');
p9ok($writer->enqueue($changed)['reason']==='idempotency_collision','same key different event denied');
$changed=$job;$changed['synthetic_only']=false;
p9ok($writer->enqueue($changed)['status']==='blocked','real data denied');
$changed=$job;$changed['sender_key']='newsletter_bulk';
p9ok($writer->enqueue($changed)['status']==='blocked','wrong sender denied');
$changed=$job;$changed['opaque_id']='email@example.com';
p9ok($writer->enqueue($changed)['reason']==='digest','raw email never valid identifier');
p9ok($writer->claim($job['opaque_id'],0,99,120,hash('sha256','lease'))['status']==='not_claimed','not yet due');
p9ok($writer->claim($job['opaque_id'],0,100,120,hash('sha256','lease'))['status']==='claimed','atomic first claim');
p9ok($writer->claim($job['opaque_id'],0,100,120,hash('sha256','lease'))['status']==='not_claimed','stale claim denied');
p9ok($writer->claim($job['opaque_id'],1,100,120,hash('sha256','lease'))['status']==='not_claimed','leased cannot reclaim');
p9ok($fake->inserted[0]['state']==='leased' && $fake->inserted[0]['state_version']===1,'state version CAS');
p9ok($writer->claim($job['opaque_id'],1,100,100,hash('sha256','lease'))['status']==='blocked','invalid lease bound');
$src=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php');
p9ok(!preg_match('/\b(?:wp_mail|add_action|as_schedule_single_action|wp_schedule_event|dbDelta|update_option)\s*\(/i',$src),'no WordPress or SMTP side effects');
echo 'MAIL_P9_INDEPENDENT_WRITER_CONTRACT_PASS assertions='.$n.' fixture=synthetic_only SQL=mock_only'."\n";
