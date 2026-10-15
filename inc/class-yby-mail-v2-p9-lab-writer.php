<?php
/**
 * P9 experimental CLI-only synthetic Outbox. Never loaded by WordPress.
 * Dedicated MySQL8 socket instance, P9 database and p9_writer account only.
 * No SMTP, Worker hooks, recipient, customer, order or production data.
 */
if (!defined('ABSPATH')) { exit; }

final class YBY_Mail_V2_P9_Lab_Writer {
    const DATABASE = 'p9_mail_synthetic_uat';
    const TABLE = 'p9_yby_mail_v2_outbox';
    const COLUMNS = array(
        'opaque_id','idempotency_key','event_hash','payload_ref',
        'channel','sender_key','template_key','template_version',
        'consent_snapshot_hash','priority','state','state_version',
        'attempt','max_attempts','next_attempt_at','lease_token_hash',
        'lease_expires_at','diagnostic_code','created_at','updated_at'
    );
    private $db;
    private function __construct(PDO $db) { $this->db = $db; }

    /** Refuse WordPress, ordinary MySQL, P7/P8 and writer with broad privileges. */
    public static function open(PDO $db) {
        if (PHP_SAPI!=='cli' ||
            getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY' ||
            !class_exists('YBY_Mail_V2_Contract') ||
            !class_exists('YBY_Mail_V2_P4_Routing') ||
            !class_exists('YBY_Mail_V2_P6_Outbox_Plan') ||
            YBY_Mail_V2_P6_Outbox_Plan::table('p9_')!==self::TABLE) {return null;}
        try {
            $schema=(string)$db->query('SELECT DATABASE()')->fetchColumn();
            $net=(int)$db->query('SELECT @@skip_networking')->fetchColumn();
            $dir=(string)$db->query('SELECT @@datadir')->fetchColumn();
            $who=(string)$db->query('SELECT CURRENT_USER()')->fetchColumn();
            if($schema!==self::DATABASE || $net!==1 ||
                strpos($dir,'/mail-v2-p9-')===false ||
                strpos($dir,'mail-v2-p7-')!==false ||
                !preg_match('/\Ap9_writer@(?:localhost|127[.]0[.]0[.]1)\z/D',$who)){
                return null;
            }
            $grants=$db->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
            $wanted=array('SELECT','INSERT','UPDATE');$writer=false;
            foreach($grants as $grant){
                $g=(string)$grant;
                if(preg_match('/\AGRANT USAGE ON [*][.][*] TO\b/i',$g)){continue;}
                if(!preg_match('/\AGRANT (.*?) ON \x60?p9_mail_synthetic_uat\x60?[.][*] TO\b/i',$g,$m)){return null;}
                $actual=array_map('trim',explode(',',$m[1]));sort($actual);
                $sorted=$wanted;sort($sorted);
                if($actual!==$sorted || $writer){return null;}
                $writer=true;
            }
            if(!$writer){return null;}
            $tables=$db->prepare('SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');
            $tables->execute(array(self::DATABASE));
            $all=$tables->fetchAll(PDO::FETCH_ASSOC);
            if(count($all)!==1||$all[0]['TABLE_NAME']!==self::TABLE||
                $all[0]['ENGINE']!=='InnoDB'||strtolower($all[0]['TABLE_COLLATION'])!=='ascii_bin'){return null;}
            $cols=$db->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
            $cols->execute(array(self::DATABASE,self::TABLE));
            $observed=$cols->fetchAll(PDO::FETCH_ASSOC);
            $definitions=YBY_Mail_V2_P6_Outbox_Plan::columns();
            if(count($observed)!==20 || array_keys($definitions)!==self::COLUMNS){return null;}
            $names=array_keys($definitions);$types=array_values($definitions);
            foreach($observed as $i=>$c){
                $type=strtolower((string)$c['COLUMN_TYPE']);
                $type=preg_replace('/\A(tinyint|int|bigint)\([0-9]+\)( unsigned)?\z/D','$1$2',$type);
                if($c['COLUMN_NAME']!==$names[$i] ||
                    $type.($c['IS_NULLABLE']==='YES'?' null':' not null')!==strtolower($types[$i])){return null;}
            }
            $idx=$db->prepare('SELECT INDEX_NAME,COLUMN_NAME,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX');
            $idx->execute(array(self::DATABASE,self::TABLE));$found=array();
            foreach($idx->fetchAll(PDO::FETCH_ASSOC) as $r){
                $name=$r['INDEX_NAME'];
                $found[$name]['fields'][]=$r['COLUMN_NAME'];
                $found[$name]['unique']=((int)$r['NON_UNIQUE']===0);
            }
            if(count($found)!==4){return null;}
            foreach(YBY_Mail_V2_P6_Outbox_Plan::INDEXES as $key=>$fields){
                if(!isset($found[$key])||$found[$key]['fields']!==$fields||
                    $found[$key]['unique']!==in_array($key,array('PRIMARY','uq_idempotency_key'),true)){return null;}
            }
            $n=(int)$db->query('SELECT COUNT(*) FROM '.self::TABLE)->fetchColumn();
            if($n>10000){return null;}
            return new self($db);
        } catch(Throwable $ignored){return null;}
    }

    public function enqueue(array $j) {
        if(array_keys($j)!==array('synthetic_only','opaque_id','idempotency_key','event_hash','channel','sender_key','next_attempt_at') ||
            $j['synthetic_only']!==true ||
            !is_int($j['next_attempt_at']) || $j['next_attempt_at']<0 ||
            !isset(YBY_Mail_V2_Contract::CHANNEL_PRIORITY[$j['channel']]) ||
            ($j['sender_key']??null)!==(YBY_Mail_V2_P4_Routing::CHANNEL_SENDER[$j['channel']]??null)){
            return array('status'=>'blocked','reason'=>'contract');
        }
        foreach(array('opaque_id','idempotency_key','event_hash') as $key) {
            if(!is_string($j[$key])||!preg_match('/\A[a-f0-9]{64}\z/D',$j[$key])){
                return array('status'=>'blocked','reason'=>'digest');
            }
        }
        try {
            if((int)$this->db->query('SELECT COUNT(*) FROM '.self::TABLE)->fetchColumn()>=10000){
                return array('status'=>'blocked','reason'=>'limit');
            }
            $time=gmdate('Y-m-d H:i:s').'.000000';
            $row=array($j['opaque_id'],$j['idempotency_key'],$j['event_hash'],null,
                $j['channel'],$j['sender_key'],'synthetic_only',1,null,
                YBY_Mail_V2_Contract::CHANNEL_PRIORITY[$j['channel']],
                'queued',0,0,3,$j['next_attempt_at'],null,null,'none',$time,$time);
            $sql='INSERT INTO '.self::TABLE.' ('.implode(',',self::COLUMNS).') VALUES ('.
                implode(',',array_fill(0,20,'?')).')';
            $stmt=$this->db->prepare($sql);$stmt->execute($row);
            return array('status'=>'inserted');
        } catch(PDOException $e) {
            if((string)$e->getCode()!=='23000'){return array('status'=>'blocked','reason'=>'sql');}
            try{
                $q=$this->db->prepare('SELECT opaque_id,event_hash,channel FROM '.self::TABLE.' WHERE idempotency_key=?');
                $q->execute(array($j['idempotency_key']));
                $match=$q->fetch(PDO::FETCH_ASSOC);
                if(is_array($match) && $match['opaque_id']===$j['opaque_id'] &&
                    $match['event_hash']===$j['event_hash'] && $match['channel']===$j['channel']){
                    return array('status'=>'duplicate');
                }
            }catch(Throwable $ignored){}
            return array('status'=>'blocked','reason'=>'idempotency_collision');
        } catch(Throwable $ignored){return array('status'=>'blocked','reason'=>'sql');}
    }

    /** Leases a synthetic job only. No "sending" transition and no transport call. */
    public function claim($opaque_id,$state_version,$now,$until,$token_hash) {
        if(!is_string($opaque_id)||!preg_match('/\A[a-f0-9]{64}\z/D',$opaque_id) ||
            !is_string($token_hash)||!preg_match('/\A[a-f0-9]{64}\z/D',$token_hash) ||
            !is_int($state_version)||$state_version<0||
            !is_int($now)||$now<0||
            !is_int($until)||$until<=$now||$until>$now+300){
            return array('status'=>'blocked','reason'=>'claim_contract');
        }
        try {
            $sql="UPDATE ".self::TABLE." SET state='leased',state_version=state_version+1,
                lease_token_hash=?,lease_expires_at=?,updated_at=UTC_TIMESTAMP(6)
                WHERE opaque_id=? AND state='queued' AND state_version=? AND next_attempt_at<=?";
            $q=$this->db->prepare($sql);
            $q->execute(array($token_hash,$until,$opaque_id,$state_version,$now));
            return array('status'=>$q->rowCount()===1?'claimed':'not_claimed');
        } catch(Throwable $ignored){return array('status'=>'blocked','reason'=>'sql');}
    }
    /**
     * Persist a lost/expired synthetic lease as uncertain/HOLD, never retry.
     * Requires the exact lease hash, expected CAS version and elapsed expiry.
     * No transition out of uncertain is provided by this class.
     */
    public function quarantine_expired($opaque_id,$expected_version,$lease_hash,$now) {
        if(!is_string($opaque_id) || !preg_match('/\A[a-f0-9]{64}\z/D',$opaque_id) ||
            !is_string($lease_hash)||!preg_match('/\A[a-f0-9]{64}\z/D',$lease_hash) ||
            !is_int($expected_version)||$expected_version<1 ||
            !is_int($now)||$now<0){
            return array('status'=>'blocked','reason'=>'recovery_contract');
        }
        try {
            $q=$this->db->prepare(
                "UPDATE ".self::TABLE."
                SET state='uncertain',state_version=state_version+1,
                    diagnostic_code='acceptance_unknown',
                    lease_token_hash=NULL,lease_expires_at=NULL,
                    updated_at=UTC_TIMESTAMP(6)
                WHERE opaque_id=? AND state_version=? AND lease_token_hash=?
                    AND lease_expires_at<=? AND state IN ('leased','sending')
                    AND template_key='synthetic_only'"
            );
            $q->execute(array($opaque_id,$expected_version,$lease_hash,$now));
            return array('status'=>$q->rowCount()===1?'quarantined':'not_quarantined',
                'retry_authorized'=>false,'mail_sent'=>false);
        }catch(Throwable $ignored){
            return array('status'=>'blocked','reason'=>'sql','retry_authorized'=>false);
        }
    }

}
