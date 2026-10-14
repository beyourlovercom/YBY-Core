<?php
/** M3 draft editor static safety contract (no WordPress or database needed). */
declare(strict_types=1);
$path = dirname(__DIR__) . '/admin/views/marketing-m3-draft-editor.php';
$source = file_get_contents($path);
if ($source === false) { throw new RuntimeException('Editor missing'); }
$cases = [
 'nonce' => "wp_nonce_field('andy_commerce_m3_draft_write')",
 'permission' => "current_user_can('manage_woocommerce')",
 'submit' => "admin-post.php",
 'create' => '创建营销活动',
 'revise' => '编辑营销活动',
 'archive' => '归档草稿',
 'version' => 'expected_revision',
 'off' => 'OFF',
 'safe textarea' => 'esc_textarea',
 'escaped action' => 'esc_url',
 'escape attribute' => 'esc_attr',
 'operator form' => 'campaign_fields[name]',
 'spend save field' => 'campaign_fields[rule][threshold]',
 'product search' => 'wc-product-search'
];
foreach ($cases as $name=>$needle) {
 if (!str_contains($source,$needle)) { throw new RuntimeException('Missing safe editor contract: '.$name); }
}
if (preg_match('/add_action\s*\(|woocommerce_(?:cart|checkout|shipping|order)/', $source)) {
 throw new RuntimeException('Editor must not register runtime hooks');
}
echo 'M3_EDITOR_CONTRACT_PASS='.count($cases)."\n";
