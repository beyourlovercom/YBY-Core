<?php
/**
 * P9-5 bounded retry and dead-letter HOLD, entirely offline PDO simulation.
 * Reuses P9-1 identity/schema mock, never bootstraps WordPress or SMTP.
 */
require __DIR__.'/mail-v2-p9-lab-writer-harness.php';
class P9RecoveryStatement extends PDOStatement {
    public $rows=array();public $affected=0;
    private $db;private $sql;
    public function __construct($db,$sql){$this->db=$db;$this->sql=$sql;}
    public function execute(?array $args=null):bool{
        $p=$args??array();$sql=$this->sql;
        if(strpos($sql,'SELECT attempt,max_attempts,idempotency_key')!==false){
            $this->rows=array();
            foreach($this->db->inserted as $row){
                if($row['opaque_id']===$p[0] && $row['state']==='leased' &&
                   $row['state_version']===$p[1] && $row['lease_token_hash']===$p[2] &&
                   $row['lease_expires_at']>$p[3] && $row['template_key']===$p[4]){
                    $this->rows[]=array('attempt'=>$row['attempt'],
                        'max_attempts'=>$row['max_attempts'],'idempotency_key'=>$row['idempotency_key']);
                }
            }
            return true;
        }
        if(strpos($sql,"SET state='deferred'")!==false){
            foreach($this->db->inserted as &$row){
                if($row['opaque_id']===$p[2] && $row['state']==='leased' &&
                    $row['state_version']===$p[3] &&
                    $row['lease_token_hash']===$p[4] &&
                    $row['lease_expires_at']>$p[5] &&
                    $row['template_key']==='synthetic_only' &&
                    $row['attempt']===$p[6] && $row['max_attempts']===$p[7] &&
                    $row['attempt']<$row['max_attempts']){
                    $row['state']='deferred';$row['state_version']++;
                    $row['attempt']++;$row['next_attempt_at']=$p[0];
                    $row['diagnostic_code']=$p[1];
                    $row['lease_token_hash']=null;$row['lease_expires_at']=null;
                    $this->affected=1;break;
                }
            }unset($row);
            return true;
        }
        if(strpos($sql,"SET state='leased'")!==false){
            foreach($this->db->inserted as &$row){
                if($row['opaque_id']===$p[2] &&
                    in_array($row['state'],array('queued','deferred'),true) &&
                    $row['attempt']<$row['max_attempts'] &&
                    $row['state_version']===$p[3] &&
                    $row['next_attempt_at']<=$p[4]){
                    $row['state']='leased';$row['state_version']++;
                    $row['lease_token_hash']=$p[0];$row['lease_expires_at']=$p[1];
                    $this->affected=1;break;
                }
            }unset($row);
            return true;
        }
        throw new RuntimeException('P9_UNEXPECTED_RECOVERY_SQL');
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed {
        return $this->rows[0]??false;
    }
    public function rowCount():int{return $this->affected;}
}
class P9RecoveryPDO extends P9TestPDO {
    public function prepare(string $sql,array $options=[]):PDOStatement|false {
        if(strpos($sql,'SELECT attempt,max_attempts,idempotency_key')!==false ||
            strpos($sql,"SET state='deferred'")!==false ||
            strpos($sql,"SET state='leased'")!==false){
            return new P9RecoveryStatement($this,$sql);
        }
        return parent::prepare($sql,$options);
    }
    public function query(string $sql,?int $fetchMode=null,mixed ...$args):PDOStatement|false{
        if(strpos($sql,'SELECT state,diagnostic_code,COUNT(*) AS n')!==false){
            $count=array();
            foreach($this->inserted as $row){
                $k=$row['state'].'|'.$row['diagnostic_code'];
                if(!isset($count[$k]))$count[$k]=array('state'=>$row['state'],
                    'diagnostic_code'=>$row['diagnostic_code'],'n'=>0);
                ++$count[$k]['n'];
            }
            return new P9TestStatement($this,$sql,array_values($count));
        }
        return parent::query($sql,$fetchMode,...$args);
    }
}
$count9=0;function recok($condition,$message){global $count9;++$count9;
    if(!$condition)throw new RuntimeException('P9_RECOVERY_FAIL_'.$message);
}
$db=new P9RecoveryPDO();
$w=YBY_Mail_V2_P9_Lab_Writer::open($db);
recok($w!==null,'writer-open');
$id=hash('sha256','safe-nontransmission-case');
$key=hash('sha256','safe-nontransmission-key');
$job=array('synthetic_only'=>true,'opaque_id'=>$id,
 'idempotency_key'=>$key,'event_hash'=>hash('sha256','event'),
 'channel'=>'critical_system','sender_key'=>'system_tx','next_attempt_at'=>100);
recok($w->enqueue($job)['status']==='inserted','enqueue');
$lease=hash('sha256','lease-one');
recok($w->claim($id,0,100,150,$lease)['status']==='claimed','first-lease');
recok($w->record_mock_nontransmission($id,1,$lease,110,'timeout')['status']==='blocked',
 'unknown-must-not-retry');
recok($w->record_mock_nontransmission($id,1,hash('sha256','wrong'),110,
    'verified_synthetic_preacceptance_only')['status']==='not_recorded','wrong-token');
recok($w->record_mock_nontransmission($id,1,$lease,150,
    'verified_synthetic_preacceptance_only')['status']==='not_recorded','expired');
$r=$w->record_mock_nontransmission($id,1,$lease,110,
    'verified_synthetic_preacceptance_only');
recok($r['status']==='deferred' && $r['retry_authorized']===false,'first-failure');
$due=$r['next_attempt_at'];
recok($due>=170 && $due<=201,'bounded-first-backoff');
recok($w->record_mock_nontransmission($id,1,$lease,110,
    'verified_synthetic_preacceptance_only')['status']==='not_recorded','replay-rejected');
recok($w->claim($id,2,$due-1,$due+30,hash('sha256','early'))['status']==='not_claimed','before-backoff');
$lease2=hash('sha256','lease-two');
recok($w->claim($id,2,$due,$due+100,$lease2)['status']==='claimed','second-lease');
$r2=$w->record_mock_nontransmission($id,3,$lease2,$due+5,
    'verified_synthetic_preacceptance_only');
recok($r2['status']==='deferred' && $r2['attempt']===2,'second-failure');
$due2=$r2['next_attempt_at'];
recok($due2>$due+5,'second-backoff');
$lease3=hash('sha256','lease-three');
recok($w->claim($id,4,$due2,$due2+100,$lease3)['status']==='claimed','third-lease');
$r3=$w->record_mock_nontransmission($id,5,$lease3,$due2+5,
    'verified_synthetic_preacceptance_only');
recok($r3['status']==='retry_exhausted' && $r3['attempt']===3 &&
    $r3['retry_authorized']===false,'hard-limit');
recok($w->claim($id,6,$due2+5,$due2+30,hash('sha256','fourth'))['status']==='not_claimed',
    'exhausted-unclaimable');
recok($db->inserted[0]['state']==='deferred' &&
    $db->inserted[0]['attempt']===3 &&
    $db->inserted[0]['diagnostic_code']==='retry_exhausted','persistent-hold');
$health=$w->synthetic_health();
recok($health['status']==='synthetic_snapshot' && $health['total']===1 &&
    $health['states']['retry_exhausted']===1 &&
    $health['contains_pii']===false && $health['event_log_verified']===false,
    'redacted-health');
$g=YBY_Mail_V2_P9_Lab_Writer::manual_replay_gate('uncertain','acceptance_unknown',true);
recok($g['can_execute']===false && $g['requeue']===false &&
    $g['requires_new_authorization']===true,'uncertain-manual-review-no-replay');
$g=YBY_Mail_V2_P9_Lab_Writer::manual_replay_gate('deferred','retry_exhausted',true);
recok($g['status']==='owner_review_required' && $g['smtp']===false,
    'exhausted-manual-review-no-send');
recok(YBY_Mail_V2_P9_Lab_Writer::manual_replay_gate('queued','none',true)['status']==='blocked',
    'manual-nonfailure-denied');
$source=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php');
recok(!preg_match('/\\b(?:wp_mail|add_action|as_schedule_single_action|wp_schedule_event|dbDelta)\\s*\\(/i',$source),
    'no-real-send-hooks');
echo "MAIL_P9_SYNTHETIC_BOUNDED_RECOVERY_PASS assertions=$count9 attempts=3 exhausted=held mail=0 worker=off\\n";
