<?php
/** M3 Smart Coupon-inspired layout contract; all actions remain Dev draft-only. */
declare(strict_types=1);
$base=dirname(__DIR__);
$view=file_get_contents($base.'/admin/views/marketing-m3-draft-preview.php');
$repository=file_get_contents($base.'/includes/class-andy-commerce-marketing-draft-repository.php');
$request=file_get_contents($base.'/includes/class-andy-commerce-marketing-draft-request-controller.php');
$bridge=file_get_contents($base.'/includes/class-andy-commerce-m3-dev-bridge.php');
if (!$view || !$repository || !$request || !$bridge) throw new RuntimeException('M3 listing files missing');
$requirements=[
 'All tab'=>"'m3_view'=>'all'",
 'Trash tab'=>"'m3_view'=>'trash'",
 'Search'=> 'name="m3_q"',
 'Column settings'=> 'data-m3-column',
 'Campaign title'=> 'data-col="title"',
 'Description'=> 'data-col="description"',
 'Status'=> 'data-col="status"',
 'Conversion'=> 'data-col="usage"',
 'Schedule'=> 'data-col="schedule"',
 'Actions'=> 'data-col="actions"',
 'Enabled toggle'=> 'data-col="toggle"',
 'Edit icon'=>'dashicons-edit',
 'Duplicate icon'=>'dashicons-admin-page',
 'Trash icon'=>'dashicons-trash',
 'Inactive switch'=>'aria-disabled="true"',
 'ON prohibited'=>'aria-checked="false"',
 'Current OFF'=>'全部优惠 OFF',
 'No fake usage'=>'尚未接入',
 'Responsive mobile'=>'@media(max-width:850px)',
 'Bulk version form'=>'campaign_versions[',
 'Archive action'=>'draft_action" value="archive"',
 'Duplicate action'=>'draft_action" value="duplicate"',
 'CSRF nonce'=>"wp_nonce_field($nonce_action)",
 'Real pagination'=>'m3_page',
 'Safe escaping'=>'esc_html',
];
foreach ($requirements as $what=>$needle) {
 if(!str_contains($view,$needle))throw new RuntimeException('Missing layout feature: '.$what);
}
foreach(['listCampaigns(','countByStatus(','duplicate(','archiveBatch('] as $method) {
 if (!str_contains($repository,$method))throw new RuntimeException('Repository missing: '.$method);
}
foreach(["'bulk_archive'","'duplicate'","'archive'"] as $action) {
 if(!str_contains($request,$action))throw new RuntimeException('Controller action missing: '.$action);
}
if (str_contains($view,'woocommerce_cart_calculate_fees')
 || str_contains($view,'wp_insert_post(')
 || str_contains($view,'update_option('))throw new RuntimeException('Unexpected live Woo write');
echo 'M3_SMART_COUPON_LAYOUT_CONTRACT_PASS='.count($requirements)."\n";
