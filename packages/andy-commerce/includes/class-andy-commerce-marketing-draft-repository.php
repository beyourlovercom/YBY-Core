<?php
/**
 * M3 gated persistent repository. NOT bootstrapped by Andy Commerce runtime.
 * Only draft/archived records; no publish, price or Woo hooks.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M3_Draft_Repository {
    private object $db;
    private string $drafts;
    private string $events;

    public function __construct(object $wpdb) {
        $this->db = $wpdb;
        $this->drafts = $wpdb->prefix . 'andy_commerce_campaign_drafts';
        $this->events = $wpdb->prefix . 'andy_commerce_campaign_audit';
    }

    public function schemaSql(string $charset): array {
        return [
            "CREATE TABLE {$this->drafts} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id varchar(60) NOT NULL,
                campaign_type varchar(32) NOT NULL,
                schema_version smallint unsigned NOT NULL DEFAULT 1,
                revision int unsigned NOT NULL DEFAULT 1,
                status varchar(12) NOT NULL DEFAULT 'draft',
                active tinyint(1) NOT NULL DEFAULT 0,
                payload longtext NOT NULL,
                payload_sha256 char(64) NOT NULL,
                created_by bigint(20) unsigned NOT NULL,
                updated_by bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY campaign_id (campaign_id),
                KEY status_updated (status,updated_at)
            ) {$charset}",
            "CREATE TABLE {$this->events} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id varchar(60) NOT NULL,
                revision int unsigned NOT NULL,
                actor_id bigint(20) unsigned NOT NULL,
                event varchar(12) NOT NULL,
                payload_sha256 char(64) NOT NULL,
                recorded_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY campaign_revision (campaign_id,revision),
                KEY recorded_at (recorded_at)
            ) {$charset}"
        ];
    }

    public function find(string $id): ?array {
        $sql = $this->db->prepare("SELECT * FROM {$this->drafts} WHERE campaign_id = %s", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function save(array $raw, int $actor, ?int $expectedRevision = null): array {
        if ($actor < 1) { throw new DomainException('Invalid actor'); }
        $draft = Andy_Commerce_Marketing_Draft_Sandbox::validate($raw);
        $payload = wp_json_encode($draft);
        if (!is_string($payload) || strlen($payload) > 65536) {
            throw new DomainException('Campaign payload invalid or too large');
        }
        $hash = hash('sha256', $payload);
        $id = $draft['id'];
        $now = gmdate('Y-m-d H:i:s');
        if ($this->db->query('START TRANSACTION') === false) { throw new RuntimeException('Transaction unavailable'); }
        try {
            $old = $this->db->get_row($this->db->prepare(
                "SELECT campaign_type,revision,status FROM {$this->drafts} WHERE campaign_id=%s FOR UPDATE", $id
            ), ARRAY_A);
            if ($expectedRevision === null) {
                if ($old) { throw new DomainException('Duplicate campaign'); }
                $revision = 1;
                $ok = $this->db->insert($this->drafts, [
                    'campaign_id'=>$id,'campaign_type'=>$draft['type'],'schema_version'=>1,
                    'revision'=>1,'status'=>'draft','active'=>0,'payload'=>$payload,
                    'payload_sha256'=>$hash,'created_by'=>$actor,'updated_by'=>$actor,
                    'created_at'=>$now,'updated_at'=>$now,
                ]);
            } else {
                if (!$old || $old['status'] !== 'draft' || $old['campaign_type'] !== $draft['type']
                    || (int)$old['revision'] !== $expectedRevision) {
                    throw new DomainException('Stale, archived or missing campaign');
                }
                $revision = $expectedRevision + 1;
                $ok = $this->db->update($this->drafts,
                    ['revision'=>$revision,'payload'=>$payload,'payload_sha256'=>$hash,
                     'updated_by'=>$actor,'updated_at'=>$now],
                    ['campaign_id'=>$id,'revision'=>$expectedRevision,'status'=>'draft'],
                    ['%d','%s','%s','%d','%s'], ['%s','%d','%s']);
                if ($ok !== 1) { throw new DomainException('Concurrent update rejected'); }
            }
            if ($ok !== 1) { throw new RuntimeException('Draft storage failed'); }
            if ($this->db->insert($this->events, [
                'campaign_id'=>$id,'revision'=>$revision,'actor_id'=>$actor,
                'event'=>$expectedRevision === null ? 'created':'revised',
                'payload_sha256'=>$hash,'recorded_at'=>$now,
            ]) !== 1) { throw new RuntimeException('Audit storage failed'); }
            if ($this->db->query('COMMIT') === false) { throw new RuntimeException('Commit failed'); }
            return ['id'=>$id,'revision'=>$revision,'active'=>false,'status'=>'draft'];
        } catch (Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
    }

    public function archive(string $id, int $revision, int $actor): void {
        if ($actor < 1 || $revision < 1) { throw new DomainException('Invalid archive request'); }
        if ($this->db->query('START TRANSACTION') === false) { throw new RuntimeException('Transaction unavailable'); }
        try {
            $row = $this->findForUpdate($id);
            if (!$row || $row['status'] !== 'draft' || (int)$row['revision'] !== $revision) {
                throw new DomainException('Stale or missing draft');
            }
            $next = $revision + 1;
            $now = gmdate('Y-m-d H:i:s');
            $ok = $this->db->update($this->drafts,
                ['status'=>'archived','active'=>0,'revision'=>$next,'updated_by'=>$actor,'updated_at'=>$now],
                ['campaign_id'=>$id,'status'=>'draft','revision'=>$revision]);
            if ($ok !== 1) { throw new RuntimeException('Archive update failed'); }
            if ($this->db->insert($this->events, [
                'campaign_id'=>$id,'revision'=>$next,'actor_id'=>$actor,
                'event'=>'archived','payload_sha256'=>$row['payload_sha256'],'recorded_at'=>$now
            ]) !== 1) { throw new RuntimeException('Archive audit failed'); }
            if ($this->db->query('COMMIT') === false) { throw new RuntimeException('Commit failed'); }
        } catch (Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
    }

    private function findForUpdate(string $id): ?array {
        $row = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->drafts} WHERE campaign_id=%s FOR UPDATE", $id
        ), ARRAY_A);
        return $row ?: null;
    }
}
