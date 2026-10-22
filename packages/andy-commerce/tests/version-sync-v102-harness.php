<?php
/** Version contract: every Andy Commerce candidate has one version and matching native-order cache assets. */
$base = dirname( __DIR__ );
$plugin = file_get_contents( $base . '/andy-commerce.php' );
$readme = file_get_contents( $base . '/readme.txt' );
$native = file_get_contents( $base . '/includes/class-andy-commerce-native-orders-detail.php' );
$check = static function ( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); }
};
preg_match( '/^ \* Version:\s+([0-9]+\.[0-9]+\.[0-9]+)$/m', $plugin, $header );
preg_match( "/define\( 'ANDY_COMMERCE_VERSION', '([^']+)' \)/", $plugin, $constant );
preg_match( '/^Stable tag:\s+([0-9]+\.[0-9]+\.[0-9]+)$/m', $readme, $stable );
preg_match( "/const VERSION = '([^']+)';/", $native, $assets );
$check( ! empty( $header[1] ) && ! empty( $constant[1] ) && ! empty( $stable[1] ) && ! empty( $assets[1] ), 'version metadata missing' );
$check( $header[1] === $constant[1] && $constant[1] === $stable[1], 'version metadata differs' );
$check( str_starts_with( $assets[1], $header[1] . '-' ), 'native detail CSS/JS cache prefix differs from plugin' );
$check( version_compare( $header[1], '1.0.1', '>' ), 'candidate must increment last installed Dev version' );
echo "PASS Andy Commerce version metadata/header/readme/native assets aligned to {$header[1]}\n";
