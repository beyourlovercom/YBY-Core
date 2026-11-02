<?php
declare(strict_types=1);
define('ABSPATH', '/');
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
$pass=0;
function check_bf(bool $v, string $label): void {
    global $pass;
    if (!$v) { throw new RuntimeException('FAIL '.$label); }
    ++$pass;
}
foreach([0,1,9899,9900,19899,19900,20000] as $amount) {
    foreach(['US','GB','TW',''] as $country) {
        $r=BYL_BF2026_R9_Policy::eligibility($amount,$country);
        $expected=$amount===0?0:($amount>=19900?2:1);
        check_bf($r['gift_slots']===$expected, "gift_slots:$amount:$country");
        check_bf($r['premium_allowed']===($amount>=9900 && $country==='US'), "premium:$amount:$country");
        check_bf($r['premium_progress_minor']===max(0,9900-$amount), "progress:$amount:$country");
    }
}
$e=BYL_BF2026_R9_Policy::eligibility(19900,'US');
check_bf($e['premium_max_slots']===1,'max one paid');
$manifest=[1001=>1000,1002=>1500,1003=>2000,1004=>9999];
check_bf(BYL_BF2026_R9_Policy::verify_premium(1003,2000,$manifest,$e)['ok']===true,'valid +20');
check_bf(BYL_BF2026_R9_Policy::verify_premium(1003,1000,$manifest,$e)['ok']===false,'price tamper');
check_bf(BYL_BF2026_R9_Policy::verify_premium(1004,9999,$manifest,$e)['ok']===false,'unapproved tier');
check_bf(BYL_BF2026_R9_Policy::verify_premium(0,2000,$manifest,$e)['ok']===false,'invalid product');
check_bf(BYL_BF2026_R9_Policy::verify_premium(1001,1000,$manifest,BYL_BF2026_R9_Policy::eligibility(20000,'CA'))['ok']===false,'nonUS');
check_bf(BYL_BF2026_R9_Policy::verify_premium(1001,1000,$manifest,BYL_BF2026_R9_Policy::eligibility(9899,'US'))['ok']===false,'under99');
$bf=[12,24,31];
check_bf(!BYL_BF2026_R9_Policy::influencer_line_eligible(true,12,0,false,$bf),'BF excluded');
check_bf(!BYL_BF2026_R9_Policy::influencer_line_eligible(true,99,24,false,$bf),'variant excluded');
check_bf(BYL_BF2026_R9_Policy::influencer_line_eligible(true,99,0,false,$bf),'regular eligible');
check_bf(!BYL_BF2026_R9_Policy::influencer_line_eligible(true,99,0,true,$bf),'gift excluded');
check_bf(BYL_BF2026_R9_Policy::influencer_line_eligible(false,12,0,false,$bf),'other protected coupon untouched');
$seed='shopper-session-secure-test-seed-2026';
$p=BYL_BF2026_R9_Policy::random_plan([88,22,77,55],2,$seed);
check_bf($p['ok']===true && count($p['ids'])===2,'two random choices');
check_bf(count(array_unique($p['ids']))===2,'distinct choices');
check_bf(BYL_BF2026_R9_Policy::random_plan([55,88,77,22],2,$seed)['ids']===$p['ids'],'order invariant');
$q=BYL_BF2026_R9_Policy::random_plan([22,55,77,88],2,$seed,$p['ids']);
check_bf($q['reused']===true && $q['ids']===$p['ids'],'session stable');
check_bf(BYL_BF2026_R9_Policy::random_plan([22],2,$seed)['ok']===false,'insufficient pool');
check_bf(BYL_BF2026_R9_Policy::random_plan([22,22],2,$seed)['ok']===false,'same sku insufficient');
check_bf(BYL_BF2026_R9_Policy::random_plan([22,77],2,'short')['ok']===false,'short seed');
check_bf(BYL_BF2026_R9_Policy::random_plan([22,'77'],2,$seed)['ok']===false,'bad id');
check_bf(BYL_BF2026_R9_Policy::random_plan([22,77,88],2,$seed,[999,22])['reused']===false,'stale choice replaced');
check_bf(BYL_BF2026_R9_Policy::eligibility(-1,'US')['valid']===false,'negative basis');
check_bf(BYL_BF2026_R9_Policy::eligibility(1000000001,'US')['valid']===false,'overflow basis');
echo 'BF2026_R9_PURE_POLICY_PASS='.$pass.PHP_EOL;