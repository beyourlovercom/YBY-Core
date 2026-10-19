<?php
/**
 * Render the real Studio PHP view into an offline admin-like fixture.
 *
 * Usage: php tests/popup-field-studio-admin-preview.php > preview.html
 * No production site/WordPress DB or network calls. Save is intentionally inert.
 */
ob_start();
require_once __DIR__ . '/popup-field-studio-v2-harness.php';
ob_end_clean();
$GLOBALS['yby_test_site'] = 'irrigation';
require_once dirname( __DIR__ ) . '/inc/class-yby-inquiry-manager.php';
function get_bloginfo( $key ) { return 'YBY Irrigation — Offline UAT'; }
$settings = array( 'inquiry_preset' => 'site_us_wholesale' );
ob_start();
include dirname( __DIR__ ) . '/admin/views/popup-field-studio.php';
$studio = ob_get_clean();
if ( false === strpos( $studio, 'yby-studio-tbody' ) || false === strpos( $studio, 'yby-studio-bootstrap' ) ) {
	fwrite( STDERR, "FAIL missing Studio admin view contract\n" ); exit( 1 );
}
echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>Andy Core Popup Field Studio V2 · Offline Owner UAT</title>';
echo '<link rel="stylesheet" href="../assets/css/yby-popup-field-studio.css">';
echo '<style>body{margin:0;padding:26px;background:#f7f9f7;font-family:Arial,sans-serif;color:#1a3022}';
echo '.wrap{max-width:1440px;margin:auto}.form-table{width:100%}.button{border:1px solid #cad8cd;background:#fff;padding:8px 12px;border-radius:6px;cursor:pointer}.button-primary{background:#17613e;color:#fff}.button-small{padding:5px 9px;font-size:12px}';
echo 'input[type=text],select,textarea{box-sizing:border-box}header h2{margin:0 0 15px}header p{color:#718578;font-size:13px}</style>';
echo '</head><body><main class="wrap"><header><h2>Andy Core → 询盘管理 → 弹窗</h2><p>离线 UAT 真实 Studio 界面 · 此页面只演示交互，不会保存到 WordPress</p></header>';
echo '<form id="offline-studio" onsubmit="return false"><label style="display:block;margin-bottom:8px">业务表单预设 ';
echo '<select id="andy-popup-business-preset"><option value="irrigation_quick_inquiry">Irrigation · 原默认</option>';
echo '<option value="site_us_wholesale" selected>Irrigation US Wholesale · 本站</option></select></label>';
echo $studio;
echo '<p style="margin:17px 0"><button class="button button-primary" type="button">保存（离线演示禁用写入）</button> <button class="button" type="button">Preview/Test</button></p></form></main>';
echo '<script src="../assets/js/yby-popup-field-studio.js"></script></body></html>';
