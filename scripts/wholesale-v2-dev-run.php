<?php
/**
 * Explicit, one-shot isolated Dev operator for V2 wholesale drafts.
 * Usage: wp eval-file scripts/wholesale-v2-dev-run.php DE plan --user=ADMIN_ID
 * Usage: wp eval-file scripts/wholesale-v2-dev-run.php DE create --user=ADMIN_ID
 * "create" is intentionally CLI-scoped; runtime flag remains OFF outside this process.
 */
if (!defined('ABSPATH') || !defined('WP_CLI')) { exit(1); }
$code = isset($args[0]) ? strtoupper((string)$args[0]) : 'DE';
$operation = isset($args[1]) ? (string)$args[1] : 'plan';
if (strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)) !== 'dev.ybyirrigation.com' ||
    wp_get_environment_type() !== 'development') {
    WP_CLI::error('Wholesale Dev driver refused outside isolated Dev');
}
$service = dirname(__DIR__).'/inc/class-yby-wholesale-country-clone-v2.php';
if (!is_file($service)) { $service = __DIR__.'/wholesale-v2-class.php'; }
if (!is_file($service)) { WP_CLI::error('V2 service not found'); }
require_once $service;
$plan=YBY_Wholesale_Country_Clone_V2::preflight($code);
if (is_wp_error($plan)) { WP_CLI::error('PREFLIGHT '.$plan->get_error_code().' '.$plan->get_error_message()); }
if ($operation==='plan') {
    WP_CLI::line(wp_json_encode(['mode'=>'plan','status'=>'PASS','plan'=>$plan]));
    return;
}
if ($operation!=='create') { WP_CLI::error('Unknown operator mode'); }
if (!defined('YBY_WHOLESALE_CLONE_V2_DEV_ENABLED')) {
    define('YBY_WHOLESALE_CLONE_V2_DEV_ENABLED',true);
}
$source_before=hash('sha256',serialize(get_post_meta(215,'_bricks_page_content_2',true)));
$result=YBY_Wholesale_Country_Clone_V2::create_drafts($code,$plan['fingerprint']);
if (is_wp_error($result)) { WP_CLI::error('CREATE '.$result->get_error_code().' '.$result->get_error_message()); }
$source_after=hash('sha256',serialize(get_post_meta(215,'_bricks_page_content_2',true)));
if (!hash_equals($source_before,$source_after)) {
    WP_CLI::error('ALERT: canonical source fingerprint changed; investigate.');
}
WP_CLI::line(wp_json_encode([
  'mode'=>'create','status'=>'PASS','country'=>$code,
  'result'=>$result,'source_unchanged'=>true,'source_page_id'=>215,
  'thank_you_unchanged'=>get_post_status(310)==='publish',
  'protected_699_unchanged'=>get_post_status(699)==='draft',
]));
