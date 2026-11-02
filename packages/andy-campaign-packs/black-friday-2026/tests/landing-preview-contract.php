<?php
// OFF-only design preview contract: no checkout / coupon / Woo cart mutation.
define('ABSPATH', '/');
$shortcodes = array();
$styles = array();
function add_shortcode($name, $cb) { global $shortcodes; $shortcodes[$name] = $cb; }
function apply_filters($tag, $value) { return $value; }
function wp_enqueue_style(...$args) { global $styles; $styles[] = $args[0]; }
function plugins_url($p, $f) { return '/plugins/black-friday-2026/'.$p; }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function get_permalink($id) { return '/product/'.$id; }
require __DIR__.'/../includes/class-byl-bf2026-landing-preview.php';
BYL_BF2026_Landing_Preview::register();
if (!isset($shortcodes['byl_bf2026_landing'])) { throw new Exception('missing shortcode'); }
$rendered = BYL_BF2026_Landing_Preview::render();
$tests = array(
 'headline' => str_contains($rendered, 'UP TO 50% OFF') && str_contains($rendered,'BLACK FRIDAY'),
 'copy' => str_contains($rendered,'A FREE GIFT WITH EVERY ORDER') && str_contains($rendered,'2 FREE GIFTS ON ORDERS $199+'),
 'dates' => str_contains($rendered,'NOV 23–30'),
 'sale_count' => count(BYL_BF2026_Landing_Preview::SALE_NAMES) === 24,
 'premium_count' => count(BYL_BF2026_Landing_Preview::TIERS) === 3 && array_sum(array_map('count',BYL_BF2026_Landing_Preview::TIERS)) === 15,
 'worldwide' => str_contains($rendered,'worldwide') && !str_contains($rendered,'Selected U.S. warehouse clearance'),
 'us_only_premium' => str_contains($rendered,'U.S. SHIPPING ADDRESSES ONLY'),
 'unlock' => str_contains($rendered,'$99') && str_contains($rendered,'$199+'),
 'no_live_forms' => !str_contains($rendered,'<form') && !str_contains($rendered,'type="submit"'),
 'styles' => in_array('byl-bf2026-landing-preview',$styles,true),
 'no_fabricated_sale_prices' => !str_contains($rendered,'sale-price') && !str_contains($rendered,'<del>'),
 'no_sku_guesses' => !str_contains($rendered,'/product/'),
);
foreach($tests as $name=>$passed){ if(!$passed){throw new Exception('FAIL '.$name);} }
echo 'BF2026_LANDING_SOURCE_ONLY_PASS='.count($tests).PHP_EOL;
