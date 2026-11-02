<?php
/** R10-H3-C no-grant manual provenance contract. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
class WP_REST_Request {
 private string $method,$route;private array $params;
 function __construct($method,$route,$params=[]){$this->method=$method;$this->route=$route;$this->params=$params;}
 function get_method(){return $this->method;}
 function get_route(){return $this->route;}
 function get_param($name){return $this->params[$name]??null;}
}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-manual-request-shadow.php';
$c='Andy_Commerce_R10H3C_Manual_Request_Shadow';
$cases=[
 ['classic_form_shape',$c::classic('POST',['apply_coupon'=>'Apply coupon','coupon_code'=>'SAVE10']),'candidate_requires_trusted_server_interceptor'],
 ['classic_get_rejected',$c::classic('GET',['apply_coupon'=>'Apply','coupon_code'=>'SAVE10']),'not_proven'],
 ['classic_no_action',$c::classic('POST',['coupon_code'=>'SAVE10']),'not_proven'],
 ['classic_no_code',$c::classic('POST',['apply_coupon'=>'Apply']),'not_proven'],
 ['classic_bad_code',$c::classic('POST',['apply_coupon'=>'Apply','coupon_code'=>'bad code']),'not_proven'],
 ['classic_remove_conflict',$c::classic('POST',['apply_coupon'=>'Apply','coupon_code'=>'SAVE10','remove_coupon'=>'x']),'not_proven'],
 ['store_route',$c::store_api(new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon',['code'=>'SAVE10'])),'candidate_requires_store_api_auth_and_interceptor'],
 ['store_get_rejected',$c::store_api(new WP_REST_Request('GET','/wc/store/v1/cart/apply-coupon',['code'=>'SAVE10'])),'not_proven'],
 ['store_wrong_route',$c::store_api(new WP_REST_Request('POST','/wc/store/v1/cart/remove-coupon',['code'=>'SAVE10'])),'not_proven'],
 ['store_no_code',$c::store_api(new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon')),'not_proven'],
 ['store_code_injection',$c::store_api(new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon',['code'=>'a b'])),'not_proven'],
 ['untrusted_no_request',$c::store_api(null),'not_proven'],
];
$fail=0;foreach($cases as [$label,$out,$expected]){
 $ok=$out['status']===$expected&&$out['manual_origin_verified']===false
  &&$out['replacement_authorized']===false&&$out['writes_performed']===0;
 echo 'R10H3C_ORIGIN_'.strtoupper($label).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)$fail++;
}
echo 'R10H3C_ORIGIN_FAIL_CLOSED='.(count($cases)-$fail).'/'.count($cases)."\n";
exit($fail?1:0);
