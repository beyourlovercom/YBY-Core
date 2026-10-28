<?php
/** Pure, no DB or browser, exact-host OFF-state /gift/ canonical safety. */
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['r6_host']='dev.beyourlover.com';
$GLOBALS['r6_published']=[];
$GLOBALS['r6_hooks']=[];
function add_action($name,$callback,$priority=10,$accepted_args=1) {}
function add_filter($name,$callback,$priority=10,$accepted_args=1) {
    $GLOBALS['r6_hooks'][$name]=$callback;
}
function home_url($path='/') { return 'https://'.$GLOBALS['r6_host'].$path; }
function wp_parse_url($url,$part=-1) { return parse_url($url,$part); }
function get_posts($args) {
    if($args['post_status']!=='publish'||$args['name']!=='gift'
       ||!in_array('landing_page',$args['post_type'],true)) {
       throw new RuntimeException('R6_GIFT_DRAFT_LOOKUP_INCORRECT');
    }
    return $GLOBALS['r6_published'];
}
function verify($condition,$message) {
    if(!$condition)throw new RuntimeException('R6_REDIRECT_FAIL '.$message);
    $GLOBALS['r6_count']=($GLOBALS['r6_count']??0)+1;
}
require dirname(__DIR__).'/black-friday-2026.php';
verify(isset($GLOBALS['r6_hooks']['redirect_canonical']), 'guard registered independent of master ON');
$original='https://dev.beyourlover.com/gift-selection-fee/';
$callback=$GLOBALS['r6_hooks']['redirect_canonical'];
verify($callback($original,'https://dev.beyourlover.com/gift/')===false,
    'draft gift slash never redirects to unrelated fee product');
verify($callback($original,'https://dev.beyourlover.com/gift')===false,
    'draft gift without trailing slash never redirects');
verify($callback($original,'https://dev.beyourlover.com/gift/?utm_source=uat')===false,
    'draft gift query never redirects');
verify($callback($original,'https://dev.beyourlover.com/gift-selection-fee/')===$original,
    'other product routes keep native redirects');
$GLOBALS['r6_published']=[777];
verify($callback($original,'https://dev.beyourlover.com/gift/')===$original,
    'Core-published gift landing restores WordPress redirect decision');
$GLOBALS['r6_published']=[];
$GLOBALS['r6_host']='www.beyourlover.com';
verify($callback($original,'https://www.beyourlover.com/gift/')===$original,
    'WWW remains wholly untouched by Dev guard');
echo 'R6_DRAFT_GIFT_REDIRECT_CONTRACT_PASS='.$GLOBALS['r6_count']."\n";
