<?php
/** Owned disposable WebToffee genuine auto engine, never Dev or WWW. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')||get_option('siteurl')!=='http://127.0.0.1:14141'
 || !class_exists('Wt_Smart_Coupon_Auto_Coupon_Public',false))exit(3);
$cart=WC()->cart;$session=WC()->session;
if(count($cart->get_cart())||count($cart->get_applied_coupons()))exit(3);
$old=$session->get('auto_applied_coupons',null);$ids=[];$prefix='r10h3wta'.getmypid();
try {
 $p=new WC_Product_Simple();$p->set_name($prefix.'fixture');$p->set_status('publish');$p->set_regular_price('100');$p->set_catalog_visibility('hidden');$pid=$p->save();$ids[]=$pid;
 $c=new WC_Coupon();$c->set_code($prefix.'auto');$c->set_discount_type('fixed_cart');$c->set_amount('8');$c->update_meta_data('_wt_make_auto_coupon','1');$id=$c->save();$ids[]=$id;
 global $wpdb;
 $table=Wt_Smart_Coupon::get_lookup_table_name();
 $row=$wpdb->get_row($wpdb->prepare("SELECT coupon_id,is_auto_coupon,post_status FROM {$table} WHERE coupon_id=%d",$id),ARRAY_A);
 echo 'H3A_WEBTOFFEE_LOOKUP_ROW='.(!empty($row)?'PRESENT':'ABSENT')."\n";
 echo 'H3A_WEBTOFFEE_LOOKUP_AUTO='.($row['is_auto_coupon']??'NO_ROW')."\n";
 $cart->add_to_cart($pid,1);
 $obj=Wt_Smart_Coupon_Auto_Coupon_Public::get_instance();
 $eligible=$obj->get_available_auto_coupons('CODE');
 echo 'H3A_WEBTOFFEE_ELIGIBLE_FIXTURE='.(in_array($c->get_code(),$eligible,true)?'YES':'NO')."\n";
 $obj->auto_apply_coupons();
 $applied=$cart->has_discount($c->get_code());
 $marked=in_array($c->get_code(),(array)$session->get('auto_applied_coupons',[]),true);
 echo 'H3A_WEBTOFFEE_GENUINE_APPLIED='.($applied?'YES':'NO')."\n";
 echo 'H3A_WEBTOFFEE_GENUINE_SESSION_RECORD='.($marked?'YES':'NO')."\n";
 echo 'H3A_WEBTOFFEE_GENUINE_PASS='.(($applied&&$marked)?'YES':'NO')."\n";
 if (!$applied || !$marked || (int)($row['is_auto_coupon']??0)!==1) throw new RuntimeException('R10H3A_WEBTOFFEE_GENUINE_AUTO_NOT_APPLIED');
} finally {
 $cart->empty_cart();
 foreach($ids as $post_id)wp_delete_post($post_id,true);
 if($old===null)$session->__unset('auto_applied_coupons');else $session->set('auto_applied_coupons',$old);
}
