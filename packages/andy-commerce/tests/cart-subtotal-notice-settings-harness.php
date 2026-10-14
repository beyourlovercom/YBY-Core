<?php
/** Offline Woo-free unit checks for Cart reminder option. */
define('ABSPATH','/test');
$GLOBALS['options']=[];
function get_option($key,$fallback=false){return $GLOBALS['options'][$key]??$fallback;}
function update_option($key,$value,$autoload=null){$GLOBALS['options'][$key]=$value;return true;}
function delete_option($key){unset($GLOBALS['options'][$key]);return true;}
function sanitize_text_field($text){return trim(strip_tags(preg_replace('/\\s+/u',' ',$text)));}
function wp_html_excerpt($text,$len,$more=''){return substr($text,0,$len).$more;}
require_once __DIR__.'/../includes/class-andy-commerce-cart-notice-settings.php';
$n=0;
$ok=static function($value,$label)use(&$n){++$n;if(!$value)throw new RuntimeException('FAIL '.$label);};
$defaults=Andy_Commerce_Cart_Notice_Settings::defaults();
$ok($defaults['enabled']===true,'enabled default');
$ok(str_contains($defaults['text'],'import duties'),'text default');
$ok(Andy_Commerce_Cart_Notice_Settings::get()===$defaults,'get missing');
Andy_Commerce_Cart_Notice_Settings::save(['enabled'=>'1','text'=>'  Delivery duties may apply.  ']);
$ok(Andy_Commerce_Cart_Notice_Settings::get()['text']==='Delivery duties may apply.','edit text');
$ok(Andy_Commerce_Cart_Notice_Settings::get()['enabled']===true,'edit enabled');
Andy_Commerce_Cart_Notice_Settings::save(['text'=>'<b>Notice</b>']);
$ok(Andy_Commerce_Cart_Notice_Settings::get()['enabled']===false,'disabled');
$ok(Andy_Commerce_Cart_Notice_Settings::get()['text']==='Notice','strip markup');
Andy_Commerce_Cart_Notice_Settings::save(['enabled'=>'1','text'=>str_repeat('A',400)]);
$ok(strlen(Andy_Commerce_Cart_Notice_Settings::get()['text'])===240,'max length');
Andy_Commerce_Cart_Notice_Settings::save(['enabled'=>'1','text'=>'']);
$ok(Andy_Commerce_Cart_Notice_Settings::get()['text']===$defaults['text'],'empty fallback');
Andy_Commerce_Cart_Notice_Settings::reset();
$ok(Andy_Commerce_Cart_Notice_Settings::get()===$defaults,'restore default');
$view=file_get_contents(__DIR__.'/../admin/views/cart-subtotal-notice-settings.php');
$ok(str_contains($view,'andy_cart_notice_nonce')&&str_contains($view,'esc_textarea'),'nonce and escaping');
echo 'CART_SUBTOTAL_NOTICE_SETTINGS_PASS='.$n.'; ORDER_WRITES=0'.PHP_EOL;
