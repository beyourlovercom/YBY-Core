<?php
/**
 * CI-only actual BF2026 candidate class under synthetic Woo/Yay Pro values.
 *
 * NO DB, HTTP, checkout, gift cart mutation, or payments.
 */
require __DIR__.'/campaign-yay-usd-basis-synthetic.php';

$target=(getenv('RUNNER_TEMP') ?: sys_get_temp_dir()).'/bf2026-yay-ci-candidate.php';
if(!is_file($target) || !str_contains((string)file_get_contents($target),
    'Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote($cart)')){
    throw new RuntimeException('BF_CANDIDATE_MISSING_OR_BAD');
}
require $target;
if(!class_exists('BYL_BF2026_Gifts'))throw new RuntimeException('BF_CANDIDATE_CLASS_MISSING');

$test=static function(bool $predicate,string $why):void {
    if(!$predicate)throw new RuntimeException('BF2026_YAY_INTEGRATION_FAILED_'.$why);
};
$count=0;
$GLOBALS['dual_engine']=false;
$GLOBALS['yay_pro_active']=true;
$GLOBALS['yay_free_active']=false;

foreach(['USD','EUR','GBP','AUD','CAD'] as $currency){
    $GLOBALS['currency']=$currency;
    foreach([['0.00',0,0],['198.99',19899,1],['199.00',19900,2],['199.01',19901,2]] as [$usd,$minor,$slots]){
        $q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch($usd)]));
        $test(($q['basis_minor']??null)===$minor && ($q['slots']??null)===$slots,
            $currency.' base_'.$usd.'_'.json_encode($q));$count++;
    }
    $g=[
        'andy_campaign_pack_id'=>'byl-black-friday-2026',
        '_byl_bf2026_gift'=>true,'data'=>new WC_Product('999.00'),
        'quantity'=>1,'line_subtotal'=>'999.00','line_total'=>'999.00',
    ];
    $q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('198.99'),$g]));
    $test(($q['basis_minor']??null)===19899 && ($q['slots']??null)===1,
        $currency.' gift_is_not_qualifying_merch');$count++;

    foreach([['fixed_cart',fixedCart('219.00'),19900,2],
             ['percent',percentCart('220.00'),19800,1],
             ['fixed_product',productCart('209.00'),19900,2]] as [$kind,$cart,$minor,$slots]){
        $q=BYL_BF2026_Gifts::eligibility($cart);
        $test(($q['basis_minor']??null)===$minor && ($q['slots']??null)===$slots,
            $currency.' coupon_'.$kind.'_'.json_encode($q));$count++;
    }
    $q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('219.00')],['mystery']));
    $test(($q['basis_minor']??null)===0 && ($q['slots']??null)===0,
        $currency.' unknown_coupon_fails_closed');$count++;
    $c=Andy_Commerce_Campaign_Yay_USD_Basis_V1::storefront_currency();
    $test($c===$currency,$currency.' selected_currency');$count++;
}
$GLOBALS['currency']='EUR';
$GLOBALS['woo_effective_currency']='USD';
$q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('199.00')]));
$test($q['slots']===2 && $q['basis_minor']===19900,'EUR_shopper_USD_checkout');
unset($GLOBALS['woo_effective_currency']);$count++;
$GLOBALS['dual_engine']=true;
$q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('199.00')]));
$test($q['slots']===0,'duplicate_currency_engine_blocks');$count++;
$GLOBALS['dual_engine']=false;
$GLOBALS['yay_pro_active']=false;
$q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('199.00')]));
$test($q['slots']===0,'missing_pro_blocks');$count++;
$GLOBALS['yay_pro_active']=true;
$GLOBALS['yay_free_active']=true;
$q=BYL_BF2026_Gifts::eligibility(new WC_Cart([merch('199.00')]));
$test($q['slots']===0,'free_plus_pro_blocks');$count++;
echo 'BF2026_YAY_SYNTHETIC_CANDIDATE_PASS='.$count.PHP_EOL;
echo 'BF2026_YAY_CANDIDATE_TEST_ONLY_NO_DB_NO_CHECKOUT=YES'.PHP_EOL;
