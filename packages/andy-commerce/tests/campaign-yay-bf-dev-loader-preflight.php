<?php
/**
 * Zero-mutation Dev BF/Yay Pro loader preflight. Reports only boolean gates,
 * never credentials, personal data, cookie values or full file paths.
 */
$gates=[
'WP_CLI'=>defined('WP_CLI')&&WP_CLI,
'DEV_HOME'=>function_exists('home_url') && wp_parse_url(home_url('/'),PHP_URL_HOST)==='dev.beyourlover.com',
'USD_BASE'=>function_exists('get_option') && get_option('woocommerce_currency')==='USD',
'CAMPAIGN_MASTER_OFF'=>function_exists('get_option') && get_option('andy_campaign_lite_master','no')==='no',
'LIVE_BF_CLASS_LOADED'=>class_exists('BYL_BF2026_Gifts',false),
'WOO_CART_CLASS'=>class_exists('WC_Cart'),
'YAY_HELPER_CLASS'=>class_exists('Yay_Currency\Helpers\YayCurrencyHelper'),
'PRO_COOKIE_ALLOWED'=>in_array(getenv('AICC_YAY_TEST_CURRENCY_ID'),['40058','40060','40062','40063','40064'],true),
'BF_SOURCE_HASH'=>is_file('/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026/includes/class-byl-bf2026-gifts.php')
    && hash_file('sha256','/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026/includes/class-byl-bf2026-gifts.php')==='b08dc6993b533387eb2811c9d56ef5b216b65d0c04bd901b92ccd8a1587e147d',
'STAGED_BF_HASH'=>is_file('/home/u247343654/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php')
    && hash_file('sha256','/home/u247343654/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php')==='9111c0cb31605ff1111a40586b399218d84ec5c70f2d3c15ec55a68ea017f58a',
];
foreach($gates as $name=>$ok)echo 'BF_DEV_LOADER_GATE_'.$name.'='.($ok?'PASS':'FAIL').PHP_EOL;
echo 'BF_DEV_LOADER_GATES='.count($gates).' failed='.count(array_filter($gates,static fn($ok)=>!$ok)).PHP_EOL;
