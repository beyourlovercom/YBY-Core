<?php
/** Offline M3 request security harness; no DB, WP bootstrap or HTTP. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
function current_user_can(string $cap): bool { return $GLOBALS['m3_allowed'] ?? false; }
function wp_verify_nonce(string $nonce, string $action): bool {
    return $nonce === 'synthetic-valid-nonce' && $action === 'andy_commerce_m3_draft_write';
}
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-request-controller.php';
$controller = (new ReflectionClass(Andy_Commerce_M3_Draft_Request_Controller::class))->newInstanceWithoutConstructor();
$tests = 0;
$denied = static function(array $post, string $case) use ($controller, &$tests): void {
    try { $controller->handle($post); } catch (DomainException $e) { $tests++; return; }
    throw new RuntimeException('Fail closed test failed: ' . $case);
};
$denied(['draft_action'=>'create'], 'no capability');
$GLOBALS['m3_allowed'] = true;
$denied(['draft_action'=>'create'], 'no nonce');
$denied(['_wpnonce'=>'wrong','draft_action'=>'create'], 'incorrect nonce');
$ok = ['_wpnonce'=>'synthetic-valid-nonce'];
$denied($ok + ['draft_action'=>'publish'], 'publish forbidden');
$denied($ok + ['draft_action'=>'activate'], 'activate forbidden');
$denied($ok + ['draft_action'=>'schedule'], 'schedule forbidden');
$denied($ok + ['draft_action'=>'create','campaign_json'=>'{}'], 'unknown campaign defaults rejected');
$denied($ok + ['draft_action'=>'create','campaign_json'=>'[]'], 'list not an object');
$denied($ok + ['draft_action'=>'create','campaign_json'=>'oops'], 'malformed JSON');
$denied($ok + ['draft_action'=>'create','campaign_json'=>str_repeat('x',65537)], 'payload quota');
$denied($ok + ['draft_action'=>'create','campaign_json'=>'{"status":"published","active":true}'], 'published activation forbidden');
$denied($ok + ['draft_action'=>'archive','campaign_id'=>'sample','expected_revision'=>'0'], 'bad archive revision');
$denied($ok + ['draft_action'=>'archive','campaign_id'=>['bad'],'expected_revision'=>'1'], 'non-scalar ID');
echo "M3_REQUEST_SECURITY_PASS={$tests}\n";
