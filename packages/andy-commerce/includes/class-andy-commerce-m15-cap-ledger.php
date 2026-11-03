<?php
/**
 * M15 opt-in usage cap ledger. No auto migration, cron, Woo hooks or writes at load.
 * Each explicit transition holds a MySQL campaign advisory lock + InnoDB tx.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M15_Cap_Ledger {
    private string $caps;
    private string $claims;
    public function __construct(private wpdb $db) {
        $prefix=$db->prefix;
        if(!is_string($prefix)||!preg_match('/^[A-Za-z0-9_]{1,45}$/D',$prefix)) {
            throw new InvalidArgumentException('Invalid MySQL WP table prefix');
        }
        $this->caps=$prefix.'aicc_m15_campaign_caps';
        $this->claims=$prefix.'aicc_m15_campaign_claims';
    }

    /** Run only through isolated schema setup or explicit authorized migration. */
    public function schema_sql(): array {
        return [
            "CREATE TABLE IF NOT EXISTS `{$this->caps}` (
                campaign_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
                max_uses INT UNSIGNED NOT NULL,
                used_count INT UNSIGNED NOT NULL DEFAULT 0,
                held_count INT UNSIGNED NOT NULL DEFAULT 0
             ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS `{$this->claims}` (
                token VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
                campaign_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                state ENUM('reserved','committed','released') NOT NULL,
                order_id BIGINT UNSIGNED NULL,
                created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                INDEX by_campaign (campaign_id),
                UNIQUE KEY one_campaign_per_woo_order (campaign_id, order_id)
             ) ENGINE=InnoDB",
        ];
    }

    private static function valid_id(string $v): bool {
        return (bool)preg_match('/^[A-Za-z0-9_-]{8,64}$/D',$v);
    }
    private function query(string $q): void {
        if($this->db->query($q)===false) throw new RuntimeException('M15 SQL transaction failure');
    }
    private function locked(string $campaign,callable $fn): array {
        if(!self::valid_id($campaign))throw new InvalidArgumentException('Invalid campaign ID');
        // Hash fits MySQL 64-character named-lock limit and avoids collisions.
        $lockName='ac_m15_'.substr(hash('sha256',$campaign),0,54);
        $acquired=$this->db->get_var($this->db->prepare('SELECT GET_LOCK(%s, 5)',$lockName));
        if((string)$acquired!=='1')throw new RuntimeException('M15 quota busy/fail-closed');
        try {
            $this->query('START TRANSACTION');
            try {
                $result=$fn();
                $this->query('COMMIT');
                return $result;
            } catch(Throwable $e){
                $this->db->query('ROLLBACK');
                throw $e;
            }
        } finally {
            $this->db->get_var($this->db->prepare('SELECT RELEASE_LOCK(%s)',$lockName));
        }
    }

    public function reserve(string $campaign,string $token,int $limit): array {
        if(!self::valid_id($token)||$limit<1||$limit>1000000) {
            throw new InvalidArgumentException('Invalid cap or idempotency token');
        }
        return $this->locked($campaign,function()use($campaign,$token,$limit):array{
            $this->query($this->db->prepare(
                "INSERT IGNORE INTO `{$this->caps}` (campaign_id,max_uses,used_count,held_count)
                 VALUES (%s,%d,0,0)",$campaign,$limit
            ));
            $cap=$this->db->get_row($this->db->prepare(
                "SELECT max_uses,used_count,held_count FROM `{$this->caps}` WHERE campaign_id=%s FOR UPDATE",
                $campaign
            ),ARRAY_A);
            if(!$cap||(int)$cap['max_uses']!==$limit)
                throw new RuntimeException('M15 cap version mismatch');
            $claim=$this->db->get_row($this->db->prepare(
                "SELECT campaign_id,state,order_id FROM `{$this->claims}` WHERE token=%s FOR UPDATE",
                $token
            ),ARRAY_A);
            if($claim){
                if($claim['campaign_id']!==$campaign||$claim['state']==='released')
                    throw new RuntimeException('M15 reused invalid reservation token');
                return ['accepted'=>true,'status'=>$claim['state'],'idempotent'=>true];
            }
            if((int)$cap['held_count']+(int)$cap['used_count'] >=$limit)
                return ['accepted'=>false,'status'=>'exhausted','idempotent'=>false];
            $this->query($this->db->prepare(
                "UPDATE `{$this->caps}` SET held_count=held_count+1
                 WHERE campaign_id=%s AND used_count+held_count<max_uses",$campaign
            ));
            if($this->db->rows_affected!==1)
                throw new RuntimeException('M15 reserve compare-and-swap rejected');
            $this->query($this->db->prepare(
                "INSERT INTO `{$this->claims}` (token,campaign_id,state)
                 VALUES (%s,%s,'reserved')",$token,$campaign
            ));
            return ['accepted'=>true,'status'=>'reserved','idempotent'=>false];
        });
    }

    /** Commit only once the real Woo order is durably persisted. */
    public function commit(string $campaign,string $token,int $orderId): array {
        if(!self::valid_id($token)||$orderId<1)
            throw new InvalidArgumentException('Invalid Woo order claim');
        return $this->transition($campaign,$token,'committed',$orderId);
    }
    /** For an aborted checkout, release the held place; never decrement a sale. */
    public function release(string $campaign,string $token): array {
        if(!self::valid_id($token))throw new InvalidArgumentException('Invalid claim token');
        return $this->transition($campaign,$token,'released',null);
    }
    private function transition(string $campaign,string $token,string $to,?int $orderId):array{
        return $this->locked($campaign,function()use($campaign,$token,$to,$orderId):array{
            $cap=$this->db->get_row($this->db->prepare(
                "SELECT used_count,held_count FROM `{$this->caps}` WHERE campaign_id=%s FOR UPDATE",$campaign
            ),ARRAY_A);
            $claim=$this->db->get_row($this->db->prepare(
                "SELECT campaign_id,state,order_id FROM `{$this->claims}` WHERE token=%s FOR UPDATE",$token
            ),ARRAY_A);
            if(!$cap||!$claim||$claim['campaign_id']!==$campaign)
                throw new RuntimeException('Unowned M15 claim');
            if($claim['state']===$to) {
                if($to==='committed'&&(int)$claim['order_id']!==$orderId)
                    throw new RuntimeException('Committed reservation cannot move orders');
                return ['status'=>$to,'idempotent'=>true];
            }
            if($claim['state']!=='reserved'||(int)$cap['held_count']<1)
                throw new RuntimeException('Invalid M15 state transition');
            $used=($to==='committed')?'used_count=used_count+1,':'';
            $this->query($this->db->prepare(
                "UPDATE `{$this->caps}` SET {$used} held_count=held_count-1 WHERE campaign_id=%s AND held_count>0",
                $campaign
            ));
            if($this->db->rows_affected!==1)throw new RuntimeException('M15 claim debit lost');
            $this->query($to==='committed'
                ? $this->db->prepare(
                    "UPDATE `{$this->claims}` SET state='committed',order_id=%d WHERE token=%s",
                    $orderId,$token)
                : $this->db->prepare(
                    "UPDATE `{$this->claims}` SET state='released',order_id=NULL WHERE token=%s",
                    $token)
            );
            return ['status'=>$to,'idempotent'=>false];
        });
    }

    public function counters(string $campaign): array {
        if(!self::valid_id($campaign))throw new InvalidArgumentException('Invalid cap key');
        $row=$this->db->get_row($this->db->prepare(
            "SELECT max_uses,used_count,held_count FROM `{$this->caps}` WHERE campaign_id=%s",$campaign
        ),ARRAY_A);
        if(!$row) return ['max'=>0,'used'=>0,'held'=>0];
        return [
            'max'=>(int)$row['max_uses'],
            'used'=>(int)$row['used_count'],
            'held'=>(int)$row['held_count'],
        ];
    }
}
