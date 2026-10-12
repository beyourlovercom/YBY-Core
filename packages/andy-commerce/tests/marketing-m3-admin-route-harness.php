<?php
/** Offline M3 create/edit admin navigation safety contract. */
declare(strict_types=1);
$controller = file_get_contents(dirname(__DIR__) . '/admin/class-andy-commerce-admin.php');
$preview = file_get_contents(dirname(__DIR__) . '/admin/views/marketing-m3-draft-preview.php');
$editor = file_get_contents(dirname(__DIR__) . '/admin/views/marketing-m3-draft-editor.php');
if ($controller === false || $preview === false || $editor === false) { throw new RuntimeException('M3 admin files missing'); }
$assertions = [
    ['create is not mistaken for existing ID',str_contains($controller, "$edit_id !== '' && $edit_id !== 'new'")],
    ['create link to new',str_contains($preview, "'m3_edit'=>'new'")],
    ['edit lookup gated',str_contains($controller, "$this->m3_bridge->draft($edit_id)")],
    ['Dev bridge only',str_contains($controller, 'Andy_Commerce_M3_Dev_Bridge::enabled()')],
    ['revision tracked',str_contains($editor,'expected_revision')],
    ['no publish',!str_contains($editor,'Save & Publish')],
];
foreach($assertions as [$label,$success]) { if(!$success)throw new RuntimeException('M3 admin route contract failed: '.$label); }
echo 'M3_ADMIN_ROUTE_PASS='.count($assertions)."\n";
