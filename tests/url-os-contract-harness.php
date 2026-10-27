<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
require_once dirname( __DIR__ ) . '/inc/class-yby-url-os-contract.php';
$assert = static function( $ok, $name ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$name}\n" ); exit( 1 ); } };
foreach ( array( 'blog/%postname%', '%pagename%', '%product%', '%product_cat_slug%', 'docs/%doc_category%/%docs%', 'brand/%product_brand%', 'lp/%landing_page%' ) as $template ) {
    $assert( YBY_URL_OS_Contract::validate_template( $template )['valid'], 'valid template ' . $template );
}
$assert( YBY_URL_OS_Contract::validate_template( '', true )['valid'], 'blank attachment template allowed' );
$assert( ! YBY_URL_OS_Contract::validate_template( '' )['valid'], 'non-attachment blank forbidden' );
foreach ( array( 'https://evil.test', '../wp-config.php', 'shop/?p=1', 'blog/%notclosed', 'wp-content//uploads', "a\nb" ) as $bad ) {
    $assert( ! YBY_URL_OS_Contract::validate_template( $bad )['valid'], 'unsafe template blocked' );
}
$assert( 'checkout' === YBY_URL_OS_Contract::reserved_root( 'checkout/order-pay/11' ), 'checkout protected' );
$assert( 'wp-json' === YBY_URL_OS_Contract::reserved_root( 'wp-json/wc/store/v1/' ), 'REST protected' );
$assert( '' === YBY_URL_OS_Contract::reserved_root( 'blog/my-post' ), 'normal blog allowed' );
$rows = array(
    array( 'kind' => 'post', 'id' => 41712, 'uri' => 'blog/shower-sex-tips' ),
    array( 'kind' => 'post', 'id' => 41712, 'uri' => '/blog/shower-sex-tips/' ),
    array( 'kind' => 'product', 'id' => 39480, 'uri' => 'product/tara-s2' ),
    array( 'kind' => 'product', 'id' => 39481, 'uri' => '/BLOG/SHOWER-SEX-TIPS/' ),
    array( 'kind' => 'term', 'id' => 2546, 'uri' => '' ),
);
$found = YBY_URL_OS_Contract::collision_candidates( $rows );
$assert( 1 === count( $found ), 'distinct-object conflict detected, same-object repetition ignored' );
$assert( 'blog/shower-sex-tips' === $found[0]['uri'], 'normalized collision path' );
$assert( '' === YBY_URL_OS_Contract::normalize_candidate( '' ), 'blank is not a route' );
$assert( null === YBY_URL_OS_Contract::normalize_candidate( '../bad' ), 'traversal blocked' );
echo "PASS url-os-contract-harness\n";
