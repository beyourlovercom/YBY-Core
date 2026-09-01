<?php
/** Focused M3 provider mapping and pagination harness. */
define( 'ABSPATH', __DIR__ );
define( 'AFFILIATEWP_VERSION', '2.35.0' );
define( 'WC_VERSION', '10.9.4' );
$options = array(); $last_aff_args = $last_ref_args = $last_payout_args = $last_coupon_args = array();
function get_option( $key, $default = false ) { global $options; return $options[ $key ] ?? $default; }
function get_bloginfo( $show = '' ) { return '7.1'; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function is_ssl() { return true; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function absint( $value ) { return abs( (int) $value ); }
function get_userdata( $id ) { return (object) array( 'user_login' => 'user' . $id, 'display_name' => 'User ' . $id, 'user_email' => 'u' . $id . '@example.test' ); }
function affwp_get_affiliate_rate( $affiliate ) { return (string) $affiliate->rate; }
function affwp_get_affiliate_rate_type( $affiliate ) { return (string) $affiliate->rate_type; }
function affwp_get_affiliate_payment_email( $affiliate ) { return (string) $affiliate->payment_email; }
function affwp_get_currency() { return 'USD'; }
function affwp_get_referral_statuses( $include_internal = false ) { $statuses=array('paid'=>'Paid','unpaid'=>'Unpaid','rejected'=>'Rejected','pending'=>'Pending'); if($include_internal){$statuses['draft']='Draft';$statuses['failed']='Failed';} return $statuses; }
function affwp_get_payout_referrals( $payout ) { if ( 4 === (int) ( $payout->payout_id ?? 0 ) ) { return array( false ); } return array( (object) array( 'referral_id' => 91 ), (object) array( 'referral_id' => 92 ) ); }
function affwp_get_coupon( $code ) { return 'alpha10' === $code ? (object) array( 'affiliate_id' => 7 ) : false; }
function get_post_meta( $id, $key, $single = true ) { return 102 === (int) $id && 'affwp_discount_affiliate' === $key ? '8' : ''; }
class WooCommerce {}
class WP_Error { public $code; public $data; public function __construct( $code, $message = '', $data = array() ) { $this->code=$code; $this->data=$data; } }
class SnapshotProviderRequest { public $params; public function __construct( $params = array() ) { $this->params=$params; } public function get_param( $key ) { return $this->params[$key] ?? null; } }
function provider_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }
class StubAffiliates {
	public $payouts;
	public function __construct() { $this->payouts = new StubPayouts(); }
	public function get_affiliates( $args ) { global $last_aff_args; $last_aff_args=$args; $rows=array(
		(object) array( 'affiliate_id'=>1,'user_id'=>11,'rate'=>'30','rate_type'=>'percentage','payment_email'=>'pay1@example.test','status'=>'active','date_registered'=>'2026-01-01 00:00:00' ),
		(object) array( 'affiliate_id'=>2,'user_id'=>12,'rate'=>'25','rate_type'=>'percentage','payment_email'=>'pay2@example.test','status'=>'pending','date_registered'=>'2026-02-01 00:00:00' ) ); return array_slice($rows,$args['offset'],$args['number']); }
}
class StubReferrals {
	public function get_referrals( $args ) { global $last_ref_args; $last_ref_args=$args; $rows=array(
		(object) array( 'referral_id'=>91,'affiliate_id'=>1,'context'=>'woocommerce','reference'=>'5001','amount'=>'12.50','currency'=>'USD','status'=>'unpaid','description'=>'Order 5001','date'=>'2026-03-01 00:00:00','payout_id'=>0 ),
		(object) array( 'referral_id'=>92,'affiliate_id'=>1,'context'=>'woocommerce','reference'=>'5002','amount'=>'8.00','currency'=>'USD','status'=>'paid','description'=>'Order 5002','date'=>'2026-03-02 00:00:00','payout_id'=>3 ),
		(object) array( 'referral_id'=>93,'affiliate_id'=>2,'context'=>'woocommerce','reference'=>'5003','amount'=>'4.00','currency'=>'USD','status'=>'failed','description'=>'Order 5003','date'=>'2026-03-03 00:00:00','payout_id'=>0 ),
		(object) array( 'referral_id'=>94,'affiliate_id'=>2,'context'=>'woocommerce','reference'=>'5004','amount'=>'1.00','currency'=>'USD','status'=>'draft','description'=>'Order 5004','date'=>'2026-03-04 00:00:00','payout_id'=>0 ) );
		if ( empty($args['status']) ) { $rows=array_values(array_filter($rows,fn($row)=>!in_array($row->status,array('draft','failed'),true))); }
		elseif ( is_array($args['status']) ) { $rows=array_values(array_filter($rows,fn($row)=>in_array($row->status,$args['status'],true))); }
		return array_slice($rows,$args['offset'],$args['number']); }
}
class StubPayouts {
	public function get_payouts( $args ) { global $last_payout_args; $last_payout_args=$args; $rows=array(
		(object) array( 'payout_id'=>3,'affiliate_id'=>1,'amount'=>'20.50','payout_method'=>'manual','status'=>'paid','date'=>'2026-03-03 00:00:00' ),
		(object) array( 'payout_id'=>4,'affiliate_id'=>2,'amount'=>'18.96','payout_method'=>'manual','status'=>'paid','date'=>'2026-03-04 00:00:00' ) ); return array_slice($rows,$args['offset'],$args['number']); }
}
class StubAffiliateWP { public $affiliates; public $referrals; public function __construct(){ $this->affiliates=new StubAffiliates(); $this->referrals=new StubReferrals(); } }
function affiliate_wp() { static $api; if ( ! $api ) { $api=new StubAffiliateWP(); } return $api; }
function get_posts( $args ) { global $last_coupon_args; $last_coupon_args=$args; return array_slice( array( 101, 102 ), $args['offset'], $args['posts_per_page'] ); }
class WC_Coupon {
	private $id;
	public function __construct( $id ) { $this->id=(int)$id; }
	public function get_id(){ return $this->id; }
	public function get_code(){ return 101===$this->id ? 'alpha10' : 'Beta20'; }
	public function get_discount_type(){ return 'percent'; }
	public function get_amount(){ return 101===$this->id ? '10' : '20'; }
	public function get_status(){ return 'publish'; }
	public function get_date_expires(){ return null; }
	public function get_date_modified(){ return new DateTime( 101===$this->id ? '2026-04-01T00:00:00+00:00' : '2026-04-02T00:00:00+00:00' ); }
}
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';

$aff = YBY_Connector::snapshot( 'affiliates', new SnapshotProviderRequest( array( 'limit'=>'1' ) ) );
provider_assert( 1 === count($aff['items']) && 1 === $aff['items'][0]['affiliate_id'] && 'user11' === $aff['items'][0]['username'], 'Affiliate mapping must preserve provider/user facts.' );
provider_assert( null !== $aff['next_cursor'], 'Affiliate first page must return a cursor.' );
$aff2 = YBY_Connector::snapshot( 'affiliates', new SnapshotProviderRequest( array( 'limit'=>'1','cursor'=>$aff['next_cursor'] ) ) );
provider_assert( 2 === $aff2['items'][0]['affiliate_id'], 'Affiliate cursor must advance the provider offset.' );
$cross = YBY_Connector::snapshot( 'referrals', new SnapshotProviderRequest( array( 'cursor'=>$aff['next_cursor'] ) ) );
provider_assert( is_wp_error($cross) && 'VALIDATION_FAILED' === $cross->code, 'Cursor must not cross resources.' );
$ref = YBY_Connector::snapshot( 'referrals', new SnapshotProviderRequest() );
provider_assert( 91 === $ref['items'][0]['referral_id'] && 'unpaid' === $ref['items'][0]['status'] && 'USD' === $ref['items'][0]['currency'], 'Referral mapping must preserve IDs/status/currency.' );
provider_assert( array('paid','unpaid','rejected','pending','draft','failed') === array_values($last_ref_args['status'] ?? array()), 'Referral snapshot must explicitly request all AffiliateWP statuses, including internal draft/failed.' );
provider_assert( 4 === count($ref['items']) && 'failed' === $ref['items'][2]['status'] && 'draft' === $ref['items'][3]['status'], 'Referral snapshot must include AffiliateWP failed/draft provider truth.' );
$payout = YBY_Connector::snapshot( 'payouts', new SnapshotProviderRequest() );
provider_assert( 3 === $payout['items'][0]['payout_id'] && array(91,92) === $payout['items'][0]['referral_ids'] && 'USD' === $payout['items'][0]['currency'], 'Payout mapping must expose exact referral IDs and provider currency.' );
provider_assert( 4 === $payout['items'][1]['payout_id'] && array() === $payout['items'][1]['referral_ids'], 'Historical payout with no referrals must expose an empty referral_ids list, never null/zero placeholders.' );
$coupons = YBY_Connector::snapshot( 'coupons', new SnapshotProviderRequest() );
provider_assert( 101 === $coupons['items'][0]['coupon_id'] && 'alpha10' === $coupons['items'][0]['normalized_code'] && 7 === $coupons['items'][0]['linked_affiliate_id'], 'Coupon mapping must expose exact code and AffiliateWP binding.' );
provider_assert( 8 === $coupons['items'][1]['linked_affiliate_id'], 'Coupon mapping must support the installed AffiliateWP WooCommerce legacy binding meta.' );
provider_assert( '2026-04-01T00:00:00+00:00' === $coupons['items'][0]['provider_modified_at'], 'Coupon modified time must come from WooCommerce.' );

$after='2026-01-15T00:00:00+00:00';
YBY_Connector::snapshot( 'affiliates', new SnapshotProviderRequest( array('updated_after'=>$after) ) );
provider_assert( isset($last_aff_args['date_registered']['start']), 'Affiliate updated_after must use AffiliateWP date_registered provider query.' );
YBY_Connector::snapshot( 'referrals', new SnapshotProviderRequest( array('updated_after'=>$after) ) );
provider_assert( isset($last_ref_args['date']['start']), 'Referral updated_after must use AffiliateWP date range provider query.' );
YBY_Connector::snapshot( 'payouts', new SnapshotProviderRequest( array('updated_after'=>$after) ) );
provider_assert( isset($last_payout_args['date']['start']), 'Payout updated_after must use AffiliateWP date range provider query.' );
YBY_Connector::snapshot( 'coupons', new SnapshotProviderRequest( array('updated_after'=>$after) ) );
provider_assert( isset($last_coupon_args['date_query'][0]['after']) && false === $last_coupon_args['date_query'][0]['inclusive'], 'Coupon updated_after must use WooCommerce post modified time.' );
echo "Connector M3 provider mapping harness passed.\n";
