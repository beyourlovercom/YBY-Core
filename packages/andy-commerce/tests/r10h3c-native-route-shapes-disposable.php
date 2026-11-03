<?php
/** Exact WC11.2.1 REST request-shape proof: shape != authorization. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')
 ||get_option('siteurl')!=='http://127.0.0.1:14141'
 ||!defined('WC_VERSION')||WC_VERSION!=='11.2.1'
 ||!class_exists('WP_REST_Request'))exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-manual-request-shadow.php';
$req=new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon');
$req->set_param('code','LOCALH3CMANUAL');
$result=Andy_Commerce_R10H3C_Manual_Request_Shadow::store_api($req);
if($result['status']!=='candidate_requires_store_api_auth_and_interceptor'
 ||$result['manual_origin_verified']!==false
 ||$result['replacement_authorized']!==false)exit(3);
echo 'H3C_NATIVE_STORE_API_UNTRUSTED_ROUTE_CANDIDATE=PASS'."\n";
$result=Andy_Commerce_R10H3C_Manual_Request_Shadow::classic('POST',[
 'apply_coupon'=>'Apply coupon','coupon_code'=>'LOCALH3CMANUAL',
]);
if($result['status']!=='candidate_requires_trusted_server_interceptor'
 ||$result['manual_origin_verified']!==false
 ||$result['replacement_authorized']!==false)exit(3);
echo 'H3C_NATIVE_CLASSIC_FORM_SHAPE_NOT_AUTH=PASS'."\n";
echo 'H3C_NATIVE_REQUEST_GUARD=2/2; NO_POST_NO_DISPATCH_NO_CART_WRITE'."\n";
