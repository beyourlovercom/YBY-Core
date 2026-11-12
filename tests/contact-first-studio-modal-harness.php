<?php
/** Regression: site Studio modal without explicit desktop_fields must render. */
if (!defined('ABSPATH')) define('ABSPATH',__DIR__.'/');
function shortcode_atts($defaults,$input,$tag=''){return array_merge($defaults,(array)$input);}
function sanitize_key($v){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v));}
function sanitize_text_field($v){return trim(strip_tags((string)$v));}
function sanitize_html_class($v){return preg_replace('/[^a-zA-Z0-9_-]/','',(string)$v);}
function esc_url_raw($v){return (string)$v;}
function esc_attr($v){return (string)$v;}
function esc_html($v){return (string)$v;}
function add_shortcode($name,$fn){}
function add_action($name,$fn,$priority=10){}
class YBY_Popup_Field_Studio {
	public static function has_preset($id){return $id==='site_test_contact';}
	public static function render_attributes($id){return array();}
	public static function desktop_fields($id){return array('name','email');}
}
class CF_Test_Presets {
	public function get_preset($id){
		return array('id'=>'site_test_contact','label'=>'Test Contact','enabled'=>true,
			'version'=>'2.0','fields'=>array('name','email'),'mobile_fields'=>array('name','email'));
	}
}
class CF_Test_Fields {
	public function get_fields(){return array(
		'name'=>array('id'=>'name','enabled'=>true,'type'=>'text'),
		'email'=>array('id'=>'email','enabled'=>true,'type'=>'email')
	);}
}
class CF_Test_Manager {
	public function get_preset_manager(){return new CF_Test_Presets();}
	public function get_field_manager(){return new CF_Test_Fields();}
}
class CF_Test_Renderer {
	public $calls=array();
	public function render_modal($preset,$fields,$attr,$mobile){
		$this->calls[]=array('fields'=>array_column($fields,'id'),'mobile'=>array_column($mobile,'id'));
		return '<div data-test-modal></div>';
	}
}
require_once dirname(__DIR__).'/inc/class-yby-inquiry-shortcodes.php';
$r=new CF_Test_Renderer();
$shortcode=new YBY_Inquiry_Shortcodes(new CF_Test_Manager(),$r);
$out=$shortcode->render_modal_shortcode(array('preset'=>'site_test_contact'));
if(false===strpos($out,'yby_inquiry_modal:')||count($r->calls)!==1
	||$r->calls[0]['fields']!==array('name','email')){
	fwrite(STDERR,"FAIL: no-explicit-fields Studio modal not rendered\n");exit(1);
}
$shortcode->render_modal_shortcode(array('preset'=>'site_test_contact','desktop_fields'=>'name,email'));
if(count($r->calls)!==2||$r->calls[1]['fields']!==array('name','email')){
	fwrite(STDERR,"FAIL: explicit-fields modal regression\n");exit(1);
}
echo "PASS Studio modal implicit+explicit field resolution; shared main untouched\n";
