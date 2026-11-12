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
		|| 0 === strpos( $path, '/andy-contact-first-r2-20261112/' )
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
	if ( is_page( 'andy-core-r2-moccasin-429-20261112' ) || is_page( 'andy-contact-first-r2-20261112' ) ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}, 20 );

add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_page( 'andy-core-r2-moccasin-429-20261112' ) && ! is_page( 'andy-contact-first-r2-20261112' ) ) { return; }
	wp_register_script( 'andy-r2-moccasin-preview-lock', false, array(), '1', true );
	wp_enqueue_script( 'andy-r2-moccasin-preview-lock' );
	$script = <<<'JS'
(function () {
  function lockR2() {
    document.querySelectorAll('[data-yby-inquiry-modal][data-yby-preset="site_wpforms_429"] form, [data-yby-inquiry-inline][data-yby-preset="site_contact_v1"] form').forEach(function(form) {
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


/** Contact First: only in the protected preview, hide unneeded modal/dock. */
add_action( 'wp', static function () {
	if ( ! is_page( 'andy-contact-first-r2-20261112' ) ) { return; }
	global $wp_filter;
	if ( ! isset( $wp_filter['wp_footer'] ) ) { return; }
	foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = $callback['function'] ?? null;
			if ( is_array( $fn ) && isset( $fn[0], $fn[1] ) && is_object( $fn[0] )
				&& in_array( get_class( $fn[0] ), array( 'YBY_Global_Popup', 'YBY_Global_Inquiry_Dock' ), true ) ) {
				remove_action( 'wp_footer', $fn, $priority );
			}
		}
	}
}, 20 );

/** Preview-only compact two-column contact layout, with mobile single column. */
add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_page( 'andy-contact-first-r2-20261112' ) ) { return; }
	wp_register_style( 'andy-r2-contact-first', false, array(), '1' );
	wp_enqueue_style( 'andy-r2-contact-first' );
	wp_add_inline_style( 'andy-r2-contact-first', '
	.yby-shortcode-inquiry{max-width:1020px;margin:20px auto 48px;padding:10px 20px}
	.yby-shortcode-inquiry .yby-inquiry-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px}
	.yby-shortcode-inquiry .yby-inquiry-field{min-width:0;margin:0}
	.yby-shortcode-inquiry .yby-inquiry-field[data-yby-field-wrapper="message"],
	.yby-shortcode-inquiry .yby-inquiry-field[data-yby-field-wrapper="site_contact_consent"],
	.yby-shortcode-inquiry .yby-inquiry-form__status,
	.yby-shortcode-inquiry .yby-inquiry-form__error,
	.yby-shortcode-inquiry .yby-inquiry-form__submit{grid-column:1/-1}
	.yby-shortcode-inquiry input:not([type="checkbox"]),.yby-shortcode-inquiry textarea{width:100%;max-width:100%}
	.yby-shortcode-inquiry textarea{min-height:130px}
	.yby-shortcode-inquiry [data-yby-responsive="mobile"]{display:none}
	@media(max-width:700px){
	  .yby-shortcode-inquiry{padding:8px 14px}
	  .yby-shortcode-inquiry .yby-inquiry-form{grid-template-columns:minmax(0,1fr);gap:14px}
	  .yby-shortcode-inquiry [data-yby-responsive="desktop"]{display:none}
	  .yby-shortcode-inquiry [data-yby-responsive="mobile"]{display:block}
	}' );
}, 120 );
