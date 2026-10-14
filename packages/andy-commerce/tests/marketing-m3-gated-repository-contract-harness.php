<?php
/**
 * M3 gated repository contract smoke: no WordPress or DB needed.
 * Requires php 8.1+; no real persistence or network calls.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-sandbox.php';
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-repository.php';
require_once dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-admin-adapter.php';

final class M3SchemaFakeWpdb {
    public string $prefix = 'wp_m3isolated_';
}
$cases = 0;
$assert = static function(bool $yes, string $message) use (&$cases): void {
    $cases++;
    if (!$yes) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
};
$repo = new Andy_Commerce_M3_Draft_Repository(new M3SchemaFakeWpdb());
$ddl = $repo->schemaSql('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$assert(count($ddl) === 2, 'two schema definitions');
$assert(str_contains($ddl[0], 'wp_m3isolated_andy_commerce_campaign_drafts'), 'draft table scoped to prefix');
$assert(str_contains($ddl[1], 'wp_m3isolated_andy_commerce_campaign_audit'), 'audit table scoped to prefix');
$assert(str_contains($ddl[0], "DEFAULT 0"), 'active defaults to zero');
$assert(str_contains($ddl[0], 'UNIQUE KEY campaign_id'), 'unique campaign');
$assert(str_contains($ddl[1], 'UNIQUE KEY campaign_revision'), 'unique audit revision');
$assert(!str_contains(implode("\n", $ddl), 'wp_options'), 'no existing WP options alteration');
$adapter = new ReflectionClass(Andy_Commerce_M3_Draft_Admin_Adapter::class);
$assert($adapter->hasMethod('create'), 'create entry point');
$assert($adapter->hasMethod('revise'), 'revise entry point');
$assert($adapter->hasMethod('archive'), 'archive entry point');
$assert(!method_exists(Andy_Commerce_M3_Draft_Admin_Adapter::class, 'publish'), 'no publish entry point');
$assert(!method_exists(Andy_Commerce_M3_Draft_Repository::class, 'activate'), 'no activation entry point');
echo "M3_GATED_CONTRACT_PASS={$cases}\n";
