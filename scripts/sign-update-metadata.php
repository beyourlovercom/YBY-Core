<?php
if ( $argc < 4 || ! function_exists( 'sodium_crypto_sign_detached' ) ) { exit( 2 ); }
$secret_b64 = getenv( 'ANDY_CORE_UPDATE_SIGNING_SECRET' );
$secret = is_string( $secret_b64 ) ? base64_decode( trim( $secret_b64 ), true ) : false;
$public = base64_decode( trim( (string) $argv[3] ), true );
if ( false === $secret || false === $public || SODIUM_CRYPTO_SIGN_SECRETKEYBYTES !== strlen( $secret ) || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $public ) ) { exit( 3 ); }
if ( ! hash_equals( $public, sodium_crypto_sign_publickey_from_secretkey( $secret ) ) ) { sodium_memzero( $secret ); exit( 4 ); }
$metadata = file_get_contents( $argv[1] );
if ( false === $metadata ) { sodium_memzero( $secret ); exit( 5 ); }
$signature = sodium_crypto_sign_detached( $metadata, $secret );
sodium_memzero( $secret );
if ( false === file_put_contents( $argv[2], base64_encode( $signature ) . PHP_EOL, LOCK_EX ) ) { exit( 6 ); }
