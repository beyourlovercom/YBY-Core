<?php
/**
 * M0 read-only audit for the known isolated Development WordPress site.
 * Usage: wp eval-file scripts/wholesale-v2-m0-audit.php
 * No writes or HTTP requests. Output excludes full Code blocks and contact data.
 */
if (!defined('ABSPATH') || !defined('WP_CLI')) { exit(1); }
$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
$env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : '';
if ('dev.ybyirrigation.com' !== $host || 'development' !== $env) {
    WP_CLI::error('Refusing audit outside isolated development WordPress');
}
global $wpdb;
$out = [
  'site' => $host, 'environment' => $env,
  'posts' => [], 'meta_rows' => [], 'nodes' => [], 'code_semantics' => [],
  'project_links' => [], 'runtime' => [],
];
foreach ([215, 310, 699, 292] as $id) {
  $p = get_post($id);
  $out['posts'][$id] = $p ? [
    'type' => $p->post_type, 'status' => $p->post_status, 'slug' => $p->post_name,
    'title' => $p->post_title, 'content_sha256' => hash('sha256', (string)$p->post_content),
  ] : null;
}
$rows = $wpdb->get_results($wpdb->prepare(
  "SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id",
  215, '_bricks_page_content_2'
), ARRAY_A);
foreach ($rows as $r) {
  $nodes = maybe_unserialize($r['meta_value']);
  $out['meta_rows'][] = [
    'meta_id' => (int)$r['meta_id'],
    'sha256' => hash('sha256', $r['meta_value']),
    'node_count' => is_array($nodes) ? count($nodes) : null,
    'node_types' => is_array($nodes) ? array_map(static function($n){return $n['name'] ?? '';},$nodes) : [],
  ];
}
$nodes = get_post_meta(215, '_bricks_page_content_2', true);
if (!is_array($nodes)) { WP_CLI::error('Source Bricks data invalid'); }
$patterns = [
  'form_tag' => '~<form\\b~i',
  'form_action' => '~<form[^>]*action=~i',
  'contact_fields' => '~name=[\x22\x27](name|email|phone|company)[\x22\x27]~i',
  'submission_api' => '~(admin-ajax|wp-json|fetch\\s*\\(|XMLHttpRequest|formData|submit)~i',
  'lead_hook' => '~(inquiry|lead|modal|popup|quotation|quote|whatsapp)~i',
  'analytics' => '~(gtag|dataLayer|google_ads|googletag|gclid|utm_|G-[A-Z0-9]|AW-[0-9]+)~i',
  'locale' => '~(Tanzania|Germany|United States|\bChina\b|\bWholesale\b|\bDistributor\b)~i',
  'thank_you' => '~(thank-you|thank_you|thankyou)~i',
];
foreach ($nodes as $n) {
  $id = (string)($n['id'] ?? '');
  $name = (string)($n['name'] ?? '');
  $settings = (array)($n['settings'] ?? []);
  $out['nodes'][] = [
    'id' => $id, 'name' => $name,
    'settings_keys' => array_keys($settings),
    'signed' => !empty($settings['signature']),
    'signature_len' => isset($settings['signature']) ? strlen((string)$settings['signature']) : 0,
    'child_count' => is_array($n['children'] ?? null) ? count($n['children']) : 0,
  ];
  if ('code' === $name) {
    $html = (string)($settings['code'] ?? '');
    $checks = [];
    foreach ($patterns as $key => $regex) { $checks[$key] = preg_match_all($regex, $html); }
    preg_match_all('~<button[^>]*>[^<]{0,85}~i', $html, $buttons);
    preg_match_all('~<a[^>]+(?:href|data-[a-z-]+)=[\x22\x27][^\x22\x27]{1,110}[\x22\x27]~i', $html, $links);
    $out['code_semantics'][] = [
      'node_id' => $id,'code_len' => strlen($html),'code_sha256' => hash('sha256', $html),
      'css_len' => strlen((string)($settings['cssCode'] ?? '')),
      'checks' => $checks,
      'button_samples' => array_slice(array_map(static function($x){ return strip_tags(substr($x,0,130));},$buttons[0]??[]),0,14),
      'link_samples' => array_slice($links[0]??[],0,12),
    ];
  }
}
$settings=get_post_meta(215,'_bricks_page_settings',true);
$out['runtime'] = [
  'page_settings_keys' => is_array($settings)?array_keys($settings):[],
  'content_node_count' => count($nodes),
  'bricks_theme_version' => (string)wp_get_theme('bricks')->get('Version'),
  'andy_core_version' => (string)(get_plugin_data(WP_PLUGIN_DIR.'/yby-core/yby-core.php')['Version'] ?? ''),
];
foreach ([215,310,518,815] as $id) {
  $all=get_post_meta($id);
  $out['project_links'][$id]=array_values(array_filter(array_keys($all),static function($k) {
    return (bool)preg_match('~(project|landing|country|source|clone|redirect|thank)~i',$k);
  }));
}
WP_CLI::line(wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
