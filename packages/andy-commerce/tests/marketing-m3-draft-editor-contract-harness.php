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
 'create' => 'Save inactive draft',
 'revise' => 'Save draft revision',
 'archive' => 'Archive draft',
 'version' => 'expected_revision',
 'off' => 'Draft only — OFF.',
 'safe textarea' => 'esc_textarea',
 'escaped action' => 'esc_url',
 'escape attribute' => 'esc_attr',
];
foreach ($cases as $name=>$needle) {
 if (!str_contains($source,$needle)) { throw new RuntimeException('Missing safe editor contract: '.$name); }
}
if (preg_match('/add_action\s*\(|woocommerce_(?:cart|checkout|shipping|order)/', $source)) {
 throw new RuntimeException('Editor must not register runtime hooks');
}
echo 'M3_EDITOR_CONTRACT_PASS='.count($cases)."\n";
