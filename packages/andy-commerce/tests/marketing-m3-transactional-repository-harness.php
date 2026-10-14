<?php
/** M3 persistence transactional lifecycle harness with in-memory wpdb double.
 * No WP bootstrap, MySQL, external network, or real writes.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
function wp_json_encode($value): string|false { return json_encode($value, JSON_UNESCAPED_SLASHES); }
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-sandbox.php';
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-repository.php';
final class M3WpdbDouble {
    public string $prefix = 'isolated_';
    public array $drafts = [];
    public array $audit = [];
    public array $queries = [];
    public bool $failAudit = false;
    private array $snapshot = [];
    public function prepare(string $sql, ...$values): array { return [$sql, $values]; }
    public function get_row($query, $mode): ?array {
        $id = $query[1][0] ?? '';
        return $this->drafts[$id] ?? null;
    }
    public function query(string $sql): int|false {
        $this->queries[] = $sql;
        if ($sql === 'START TRANSACTION') {
            $this->snapshot = [$this->drafts,$this->audit];
        } elseif ($sql === 'ROLLBACK') {
            [$this->drafts,$this->audit] = $this->snapshot;
        }
        return 1;
    }
    public function insert(string $table, array $data): int|false {
        if (str_ends_with($table, '_audit')) {
            if ($this->failAudit) { return false; }
            $this->audit[] = $data;
        } else {
            if (isset($this->drafts[$data['campaign_id']])) { return false; }
            $this->drafts[$data['campaign_id']] = $data;
        }
        return 1;
    }
    public function update(string $table, array $data, array $where, array $formats = [], array $whereFormats = []): int {
        $id = $where['campaign_id'];
        if (!isset($this->drafts[$id])) { return 0; }
        foreach ($where as $field=>$value) {
            if (($this->drafts[$id][$field] ?? null) != $value) { return 0; }
        }
        $this->drafts[$id] = array_merge($this->drafts[$id], $data);
        return 1;
    }
}
function candidate(string $id='test-m3-draft'): array {
    return ['schema_version'=>1,'id'=>$id,'name'=>'Synthetic offer',
        'type'=>'spend_save','currency'=>'USD','status'=>'draft','active'=>false,
        'priority'=>0,'countries'=>['US'],
        'rule'=>['threshold_minor'=>10000,'discount_minor'=>1000]];
}
$tests = 0;
$check = static function(bool $result, string $message) use (&$tests): void {
    $tests++;
    if (!$result) { throw new RuntimeException('M3 transaction test failed: '.$message); }
};
$db = new M3WpdbDouble();
$repo = new Andy_Commerce_M3_Draft_Repository($db);
$a = $repo->save(candidate(),42);
$check($a['revision'] === 1 && !$a['active'], 'create OFF');
$check(count($db->audit) === 1 && $db->audit[0]['event'] === 'created','audit create');
$b = $repo->save(candidate(),42,1);
$check($b['revision'] === 2, 'revision 2');
$check(count($db->audit) === 2 && $db->audit[1]['event'] === 'revised','audit revise');
try { $repo->save(candidate(),42,1); throw new RuntimeException('stale accepted'); }
catch (DomainException $e) { $check(true,'stale revision rejected'); }
$check(count($db->audit) === 2,'no stale audit');
$repo->archive('test-m3-draft',2,42);
$check($db->drafts['test-m3-draft']['status'] === 'archived' && $db->drafts['test-m3-draft']['active'] === 0,'archive OFF');
$check(count($db->audit) === 3 && $db->audit[2]['event'] === 'archived','archive audit');
try { $repo->save(candidate(),42,3); throw new RuntimeException('archived revised'); }
catch (DomainException $e) { $check(true,'immutable archived'); }
$db->failAudit = true;
try { $repo->save(candidate('failed-audit'),42); throw new RuntimeException('audit fail accepted'); }
catch (RuntimeException $e) {
    $check(!isset($db->drafts['failed-audit']), 'audit failure rolls back draft');
    $check(in_array('ROLLBACK',$db->queries,true),'rollback executed');
}
echo "M3_TRANSACTIONAL_DOUBLE_PASS={$tests}\n";
