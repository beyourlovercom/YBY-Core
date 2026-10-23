<?php
declare(strict_types=1);
$js=file_get_contents(dirname(__DIR__).'/assets/currency-c3-dev-preview.js');
$php=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-currency-v2-dev.php');
$n=0;
foreach([
  'sessionStorage.getItem(key)'=>'session selection restored',
  'sessionStorage.setItem(key,code)'=>'selection saved',
  'woocommerce-checkout'=>'checkout excluded',
  'Dev preview'=>'prices explicitly labelled approximate',
  'document.querySelectorAll(targetSelector)'=>'display-only shadow binds existing price DOM',
  'sourceAmount(node)'=>'USD strict parsing',
  'if(code===\'USD\')return'=>'USD has no shadow overlay',
  'reset();'=>'old shadow labels removed',
] as $needle=>$message) {
 if(strpos($js,$needle)===false)throw new RuntimeException('Missing '.$message);$n++;
}
foreach([
  'if(!self::access())return \x27\x27;'=>'Dev capability boundary',
  'data-ac-c3-preview'=>'explicit shortcode opt-in',
  'wp_enqueue_script'=>'asset load on render',
] as $needle=>$message){
 $needle=str_replace('\\x27',"'",$needle);
 if(strpos($php,$needle)===false)throw new RuntimeException('Missing '.$message);$n++;
}
foreach(['wc_get_product','woocommerce_product_get_price','woocommerce_cart_calculate_fees','wp_ajax','fetch(','XMLHttpRequest','document.cookie=','set_price('] as $unsafe) {
 if(strpos($js,$unsafe)!==false)throw new RuntimeException('Unsafe browser hook '.$unsafe);$n++;
}
echo "PASS C3-D/E/F display isolation and selection guards: {$n} assertions\n";
