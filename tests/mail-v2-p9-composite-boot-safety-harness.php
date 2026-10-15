<?php
/**
 * P9-6: pinned V2.2/P8 runtime integrity and P9 never-booted regression.
 * This runs only in CLI/disposable CI; it cannot deploy, send, query or mutate.
 */
if (PHP_SAPI !== 'cli') { exit(81); }
$root = dirname(__DIR__);
$assertions = 0;
function composite_guard($pass, $what) {
    global $assertions;
    ++$assertions;
    if (!$pass) { throw new RuntimeException('P9_COMPOSITE_BOOT_FAIL:' . $what); }
}
$baseline = array(
    'yby-core.php' => '087add26f5744481ca7669c38a894ba6816ac9f8',
    'inc/class-yby-core.php' => 'b54ff6134297213378d0ac95d8009e890ce2ce6e',
    'admin/class-yby-three-os-admin.php' => '50d55ac5b7233132ee020f8e27143dc2d5c51c18',
    'admin/class-yby-mail-v2-p8-readonly.php' => 'ea587589f8d76d1a8d4e6d310fe5afcc04822956',
    'admin/class-yby-mail-v2-p6-dev-preview.php' => '463e352584ba956fc594b3bd63b16e33edd97ef2',
    'admin/class-yby-andy-admin-shell-v22.php' => 'f836ebd9ffa800772460e6cd9b22152a47bff196',
);
foreach ($baseline as $path => $expected_blob_sha) {
    $file = $root . '/' . $path;
    $raw = is_file($file) ? file_get_contents($file) : false;
    composite_guard(is_string($raw), 'missing_' . $path);
    // Exact Git blob bytes, not a working-tree timestamp or normalized text.
    $blob = sha1('blob ' . strlen($raw) . "\0" . $raw);
    composite_guard(hash_equals($expected_blob_sha, $blob), 'baseline_drift_' . $path);
}
$bootstrap = file_get_contents($root . '/yby-core.php');
$core = file_get_contents($root . '/inc/class-yby-core.php');
composite_guard(is_string($bootstrap) && is_string($core), 'core_read');
foreach (array($bootstrap, $core) as $i => $body) {
    composite_guard(!preg_match('/inc\\/class-yby-mail-v2-(?:contract|governance|p2-|p3-|p4-|p5-|p6-outbox|p9-)/i', $body),
        'p9_no_bootstrap_' . $i);
    composite_guard(!preg_match('/\\b(?:glob|scandir|RecursiveDirectoryIterator|spl_autoload_register)\\s*\\(/i', $body),
        'p9_no_dynamic_core_autoload_' . $i);
}
$names = array(
    'inc/class-yby-mail-v2-contract.php',
    'inc/class-yby-mail-v2-governance.php',
    'inc/class-yby-mail-v2-p2-planner.php',
    'inc/class-yby-mail-v2-p2-scheduler.php',
    'inc/class-yby-mail-v2-p3-visibility.php',
    'inc/class-yby-mail-v2-p4-routing.php',
    'inc/class-yby-mail-v2-p5-stress.php',
    'inc/class-yby-mail-v2-p6-outbox-plan.php',
    'inc/class-yby-mail-v2-p9-audit-contract.php',
    'inc/class-yby-mail-v2-p9-integration-plan.php',
    'inc/class-yby-mail-v2-p9-lab-writer.php',
    'inc/class-yby-mail-v2-p9-smtp-pro-coexistence.php',
    'inc/class-yby-mail-v2-p9-dev-topology-gate.php',
);
foreach ($names as $path) {
    $raw = @file_get_contents($root . '/' . $path);
    composite_guard(is_string($raw), 'mail_dependency_missing_' . $path);
    composite_guard(!preg_match('/\\b(?:wp_mail|add_action|add_filter|as_enqueue_async_action|as_schedule_single_action|dbDelta)\\s*\\(/i', $raw),
        'mail_dependency_hook_' . $path);
}
composite_guard(strpos($core, "'inc/class-yby-mail-transport.php'") !== false, 'newsletter_provider_preserved');
composite_guard(strpos($core, "'admin/class-yby-three-os-admin.php'") !== false, 'v22_ui_preserved');
composite_guard(strpos($core, "'admin/class-yby-mail-v2-p6-dev-preview.php'") !== false, 'p6_preview_preserved');
composite_guard(strpos($core, "'admin/class-yby-andy-admin-shell-v22.php'") !== false, 'v22_shell_preserved');
echo 'MAIL_P9_COMPOSITE_BOOT_INERT_PASS assertions=' . $assertions .
    ' core_exact_sha=verified p8_exact_sha=verified wp_hooks=0 worker=off smtp=off' . PHP_EOL;
