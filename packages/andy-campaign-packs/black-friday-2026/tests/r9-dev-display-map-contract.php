<?php
define('ABSPATH','/');
$base=dirname(__DIR__);
$doc=json_decode(file_get_contents($base.'/config/dev-sale-candidates-20261102.json'),true);
if (count($doc['items'])!==24) { throw new Exception('manifest missing'); }
$rows=[];foreach($doc['items'] as $row){$rows[$row['product_id']]=$row['sku'];}
$site='dev.beyourlover.com';
class BF_Fake_Product {
    public function __construct(private int $id) {}
    public function is_type($x) { return $x==='simple'; }
    public function get_sku() { global $rows;return $rows[$this->id]??''; }
    public function get_image_id() { return $this->id+1000; }
    public function get_id() { return $this->id; }
}
function home_url($x='/'){global $site;return 'https://'.$site.$x;}
function wp_parse_url($x,$part){return parse_url($x,$part);}
function apply_filters($name,$value){return $value;}
function wc_get_product($id){global $rows;return isset($rows[$id])?new BF_Fake_Product($id):false;}
function get_permalink($id){return '/product/'.$id;}
function wp_get_attachment_image($id,$size,$icon,$attrs){return '<img src="/mock/'.$id.'.jpg" alt="preview">';}
function plugins_url($p,$f){return '/plugin/'.$p;}
function wp_enqueue_style(...$x){}
function esc_html($x){return htmlspecialchars((string)$x,ENT_QUOTES);}
function esc_url($x){return htmlspecialchars((string)$x,ENT_QUOTES);}
require $base.'/includes/class-byl-bf2026-landing-preview.php';
function image_count(){return substr_count(BYL_BF2026_Landing_Preview::render(),'<img');}
if(image_count()!==24)throw new Exception('dev should map 24 images');
$rows[9095]='WRONG-SKU';
if(image_count()!==23)throw new Exception('wrong sku not blocked');
$rows[9095]='VX007H';
$site='www.beyourlover.com';
if(image_count()!==0)throw new Exception('WWW must not load Dev candidate map');
echo 'BF2026_DEV_DISPLAY_MAP_PASS=3'.PHP_EOL;