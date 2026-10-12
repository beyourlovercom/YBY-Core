<?php
/** Andy Commerce M3 offline draft-only harness. No WP, MySQL or network. */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/class-andy-commerce-marketing-draft-sandbox.php';

function check(bool $v,string $why):void {
    if(!$v) { fwrite(STDERR, "FAIL: ".$why."\n"); exit(1); }
}
function blocked(callable $cb,string $why):void {
    try { $cb(); } catch (DomainException | InvalidArgumentException $e) { return; }
    check(false, $why);
}
function guard(array $extra=[]):array {
    return array_replace(['sandbox'=>true,'manage_woocommerce_checked'=>true,
        'nonce_checked'=>true,'actor_id'=>12,'now_unix'=>1791770000],$extra);
}
function campaign(string $type='spend_save',array $changes=[]):array {
    $rules=[
      'spend_save'=>['threshold_minor'=>10000,'discount_minor'=>1000],
      'bogo'=>['buy_product_ids'=>[10],'get_product_ids'=>[11],
          'buy_qty'=>2,'get_qty'=>1,'reward_bps'=>10000],
      'bundle'=>['requires'=>[['product_id'=>10,'quantity'=>1],['product_id'=>11,'quantity'=>1]],
          'bundle_price_minor'=>7500],
      'tiered'=>['tiers'=>[['min_qty'=>2,'discount_bps'=>1000],
                           ['min_qty'=>3,'discount_bps'=>2000]]],
      'free_gift'=>['threshold_minor'=>10000,'reward_product_id'=>111,'max_gift_qty'=>1],
      'free_shipping'=>['threshold_minor'=>4900,'shipping_method_id'=>'free_shipping:7']
    ];
    return array_replace([
       'schema_version'=>1,'id'=>'marketing-test-100','name'=>'Autumn test activity',
       'type'=>$type,'status'=>'draft','active'=>false,'currency'=>'USD',
       'priority'=>10,'countries'=>['US','CA'],
       'start_utc'=>'2026-10-12T00:00:00Z','end_utc'=>'2026-11-12T23:59:59Z',
       'stack_with_coupon'=>false,'rule'=>$rules[$type]
    ],$changes);
}

$sandbox=new Andy_Commerce_Marketing_Draft_Sandbox();
$c=campaign();
$d=$sandbox->create($c,guard());
check($d['revision']===1 && !$d['active'] && $d['status']==='draft','new campaign draft/off');
check($d['eligibility_basis']==='post_discount_merchandise_subtotal_excluding_tax_and_shipping',
  'post-discount ex-tax shipping basis');
check($d['requires_publish_owner_gate'] && !$d['runtime_enabled'],'no promotion activation');
check(count($sandbox->listDrafts())===1,'one synthetic draft');
blocked(fn()=> $sandbox->create($c,guard()),'duplicate draft ID collision');
blocked(fn()=> $sandbox->create(campaign('bogo',['id'=>'next-bogo']),guard(['nonce_checked'=>false])),
  'nonce guard fail-closed');
blocked(fn()=> $sandbox->create(campaign('bogo',['id'=>'next-bogo']),guard(['manage_woocommerce_checked'=>false])),
  'capability guard fail-closed');
blocked(fn()=> $sandbox->create(campaign('bogo',['id'=>'next-bogo']),guard(['sandbox'=>false])),
  'runtime access fail-closed');
blocked(fn()=> $sandbox->create(campaign('bogo',['id'=>'next-bogo']),guard(['actor_id'=>0])),
  'actor missing');
blocked(fn()=> $sandbox->create(campaign('bogo',['id'=>'next-bogo']),guard(['now_unix'=>'today'])),
  'non-synthetic clock rejected');

$updated=$sandbox->revise('marketing-test-100',1,campaign('spend_save',
    ['name'=>'Autumn changed','rule'=>['threshold_minor'=>15000,'discount_bps'=>1000]]),guard());
check($updated['revision']===2 && $updated['rule']['threshold_minor']===15000,'valid optimistic revision');
blocked(fn()=> $sandbox->revise('marketing-test-100',1,$c,guard()),
   'old/stale revision never overwrites current record');
blocked(fn()=> $sandbox->revise('marketing-test-100',2,campaign('bogo'),guard()),
   'type immutability');
blocked(fn()=> $sandbox->revise('marketing-test-100',2,campaign('spend_save',['id'=>'different-id']),guard()),
   'id immutability');
$archived=$sandbox->archive('marketing-test-100',2,guard());
check($archived['archived']===true && $archived['revision']===3 && !$archived['active'],
   'soft archive never publishes');
blocked(fn()=> $sandbox->archive('marketing-test-100',3,guard()),'repeat archival');
blocked(fn()=> $sandbox->revise('marketing-test-100',3,$c,guard()),'cannot edit archived');
check($sandbox->find('marketing-test-100')['archived']===true,'archive state persisted in memory only');
check(count($sandbox->listDrafts())===0,'archived campaigns disappear from actionable draft list');
$log=$sandbox->auditEvents();
check(count($log)===3 && array_column($log,'event')===['create','revise','archive'],
   'append-only synthetic audit');
check(array_column($log,'revision')===[1,2,3],'audit version increments');
check(!isset($log[0]['customer_email']) && !isset($log[0]['password']),'no PII recorded');

foreach(['bogo','bundle','spend_save','tiered','free_gift','free_shipping'] as $type) {
    $v=Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign($type));
    check($v['type']===$type && $v['active']===false && $v['status']==='draft',
          $type.' should normalize as OFF-only draft');
    check($v['runtime_enabled']===false,'no runtime pathway');
}
check(Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign('free_shipping'))
    ['requires_legacy_shipping_migration']===true,'BYL49 policy change must remain gated');
check(Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign('free_gift'))
    ['requires_publish_owner_gate']===true,'gift cannot automatically publish');

$invalid=[
    ['active'=>true],['status'=>'active'],['status'=>'scheduled'],['status'=>'published'],
    ['schema_version'=>2],['currency'=>'usd'],['priority'=>'100'],
    ['countries'=>['US','US']],['countries'=>['ZZZ']],
    ['start_utc'=>'2026-02-30T00:00:00Z'],['end_utc'=>'2026-10-11T00:00:00Z'],
    ['start_utc'=>'2026-10-12T00:00:00+08:00'],['stack_with_coupon'=>'yes'],
    ['name'=>''],['id'=>'../../evil'],['unknown_field'=>'malicious'],
    ['notes'=>str_repeat('x',1001)],
    ['rule'=>['threshold_minor'=>10000,'discount_minor'=>1000,'coupon'=>'INJECTED']]
];
foreach($invalid as $case) {
    blocked(fn()=>Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign('spend_save',$case)),
        'invalid campaign must fail: '.json_encode($case));
}
$badRules=[
   ['bogo',['buy_product_ids'=>[10],'get_product_ids'=>[10,11],'buy_qty'=>1,'get_qty'=>1]],
   ['bogo',['buy_product_ids'=>[10],'get_product_ids'=>[11],'buy_qty'=>0,'get_qty'=>1]],
   ['bogo',['buy_product_ids'=>[10],'get_product_ids'=>[11],'buy_qty'=>2,'get_qty'=>1,'reward_bps'=>0]],
   ['bundle',['requires'=>[['product_id'=>10,'quantity'=>1]]]],
   ['bundle',['requires'=>[['product_id'=>10,'quantity'=>1],['product_id'=>10,'quantity'=>2]],'bundle_price_minor'=>5000]],
   ['bundle',['requires'=>[['product_id'=>10,'quantity'=>1],['product_id'=>11,'quantity'=>1]],'bundle_price_minor'=>5000,'discount_bps'=>1000]],
   ['spend_save',['threshold_minor'=>10000,'discount_minor'=>1000,'discount_bps'=>1000]],
   ['spend_save',['threshold_minor'=>10000,'discount_minor'=>-500]],
   ['tiered',['tiers'=>[['min_qty'=>3,'discount_bps'=>1000],['min_qty'=>2,'discount_bps'=>2000]]]],
   ['tiered',['tiers'=>[['min_qty'=>2,'discount_bps'=>1000],['min_qty'=>2,'discount_bps'=>2000]]]],
   ['free_gift',['threshold_minor'=>10000,'reward_product_id'=>0]],
   ['free_shipping',['threshold_minor'=>10000,'shipping_method_id'=>'flat_rate:7']]
];
foreach($badRules as [$type,$rule]) {
    blocked(fn()=>Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign($type,['rule'=>$rule])),
        'unsafe rule must fail: '.$type);
}
// Coupon and product payloads cannot sneak in as unknown fields or published state.
blocked(fn()=>Andy_Commerce_Marketing_Draft_Sandbox::validate(campaign('bogo',
    ['rule'=>['buy_product_ids'=>[1], 'get_product_ids'=>[2],'buy_qty'=>1,'get_qty'=>1,
             'woocommerce_cart_calculate_fees'=>'on']])), 'no runtime hooks in rule');

// Strictly offline, new class not registered in WP runtime.
$entry=file_get_contents(dirname(__DIR__).'/andy-commerce.php');
$bootstrap=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
check(!str_contains($entry,'marketing-draft-sandbox')&&!str_contains($bootstrap,'marketing-draft-sandbox'),
 'draft sandbox cannot load in WP runtime');
$code=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-marketing-draft-sandbox.php');
foreach(['add_action(', 'add_filter(', 'update_option(', '$wpdb', 'wp_insert_post(', 'setcookie(', 'woocommerce_cart_calculate_fees'] as $danger) {
    check(!str_contains($code,$danger),'no live hook or persistence: '.$danger);
}
echo "PASS Marketing M3 offline draft schema, 6 OFF templates, optimistic lock, archive, audit and no runtime writes\n";
