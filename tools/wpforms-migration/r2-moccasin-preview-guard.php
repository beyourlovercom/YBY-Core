<?php
/**
 * R2 Hostinger PILOT safety guard. Install as a site-local MU plugin only at
 * moccasin-badger-830954.hostingersite.com. NOT a normal Andy Core runtime file.
 *
 * Blocks ALL Andy Core public lead submissions during visual-only R2 UAT.
 * Keeps WPForms untouched and does not disable its own submission endpoint.
 * Delete this MU plugin ONLY with a separately approved submission test plan.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( 'moccasin-badger-830954.hostingersite.com' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) { return; }

/**
 * Restrict Inquiry OS runtime to the password-protected preview (and the
 * intentionally blocked leads route). Existing home/Contact pages must not
 * acquire Andy Core global popup, dock, JS, or public form handlers.
 *
 * CLI and WP admin retain the stored module configuration for safe inspection.
 */
add_filter( 'pre_option_yby_core_enabled_modules_v1', static function ( $pre ) {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() ) {
		return $pre;
	}
	$path = wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return array();
	}
	if ( 0 === strpos( $path, '/andy-core-r2-moccasin-429-20261112/' )
		|| 0 === strpos( $path, '/wp-json/yby/v1/leads' ) ) {
		return $pre;
	}
	return array();
}, 1 );

add_filter( 'rest_pre_dispatch', static function ( $result, $server, $request ) {
	if ( '/yby/v1/leads' === $request->get_route() ) {
		return new WP_Error(
			'andy_r2_leads_disabled',
			'R2 visual preview only. Andy Core lead submissions are temporarily disabled on this pilot site.',
			array( 'status' => 403 )
		);
	}
	return $result;
}, 5, 3 );

add_filter( 'wp_robots', static function ( $robots ) {
	if ( is_page( 'andy-core-r2-moccasin-429-20261112' ) ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}, 20 );

add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_page( 'andy-core-r2-moccasin-429-20261112' ) ) { return; }
	wp_register_script( 'andy-r2-moccasin-preview-lock', false, array(), '1', true );
	wp_enqueue_script( 'andy-r2-moccasin-preview-lock' );
	$script = <<<'JS'
(function () {
  function lockR2() {
    document.querySelectorAll('[data-yby-inquiry-modal][data-yby-preset="site_wpforms_429"] form').forEach(function(form) {
      form.setAttribute('data-andy-preview-only', '1');
      form.addEventListener('submit', function (event) {
        event.preventDefault(); event.stopImmediatePropagation();
      }, true);
      var button = form.querySelector('[data-yby-inquiry-submit]');
      if (button) { button.disabled = true; button.setAttribute('aria-disabled', 'true'); }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', lockR2);
  } else { lockR2(); }
})();
JS;
	wp_add_inline_script( 'andy-r2-moccasin-preview-lock', $script );
}, 100 );
