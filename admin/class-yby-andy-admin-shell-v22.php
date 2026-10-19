<?php
/**
 * Andy Core V2.2: server-rendered in-page navigation shell.
 *
 * Adapters call render() around their ORIGINAL controller output. Do not
 * capture, parse, reparent or replace business markup at DOMContentLoaded.
 * The default is always to keep the original screen working unchanged.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Andy_Admin_Shell_V22 {
	/** @var string */
	private static $current = '';

	/** Active only inside a server-rendered, already-authorized adapter. */
	public static function rendering( $page = '' ) {
		return self::$current !== '' && ( '' === $page || self::$current === $page );
	}

	/**
	 * Render original business markup inside one scoped, accessible shell.
	 *
	 * No database I/O, saves, settings writes or role changes. All business
	 * callbacks, POST handlers, notices and form nonces remain their owners'.
	 *
	 * @param string   $page    Existing admin screen slug.
	 * @param string   $heading Visible original title.
	 * @param callable $body    Original page renderer; executed exactly once.
	 * @return void
	 */
	public static function render( $page, $heading, $body ) {
		if ( ! is_callable( $body ) ) { return; }
		// Do not layer shells, never fake authorization or expand permissions.
		$items = self::safe_items( $page );
		if ( self::rendering() || ! $items ) {
			call_user_func( $body );
			return;
		}
		self::$current = $page;
		try {
			echo '<div class="wrap andy-core-shell-v22" data-andy-admin-shell="v2.2">';
			echo '<header class="andy-core-shell-v22__header"><h1>' . esc_html( $heading ) . '</h1></header>';
			echo '<div class="andy-core-shell-v22__columns">';
			echo '<nav class="andy-core-shell-v22__rail" aria-label="' . esc_attr__( 'Current module functions', 'yby-core' ) . '">';
			echo '<p class="andy-core-shell-v22__rail-title">' . esc_html__( '功能导航', 'yby-core' ) . '</p>';
			$current_group = '';
			foreach ( $items as $item ) {
				$title = isset( $item['title'] ) ? (string) $item['title'] : '';
				$url   = isset( $item['url'] ) ? (string) $item['url'] : '';
				if ( '' === $title || '' === $url ) { continue; }
				$group = isset( $item['group'] ) && is_string( $item['group'] ) ? $item['group'] : '';
				if ( '' !== $group && $current_group !== $group ) {
					echo '<p class="andy-core-shell-v22__group">' . esc_html( $group ) . '</p>';
				}
				$current_group = $group;
				$active = self::is_active( $url );
				echo '<a class="andy-core-shell-v22__link" href="' . esc_url( $url ) . '"';
				if ( $active ) { echo ' aria-current="page"'; }
				echo '>' . esc_html( $title ) . '</a>';
			}
			echo '</nav><main class="andy-core-shell-v22__body">';
			// One PHP render invocation; no JavaScript DOM changes.
			call_user_func( $body );
			echo '</main></div></div>';
		} finally {
			self::$current = '';
		}
	}

	/** Capabilities and module gating are delegated to the existing registry. */
	private static function safe_items( $page ) {
		if ( ! is_admin() || ! is_string( $page ) ||
			 ! preg_match( '/^[a-z0-9-]{1,80}$/D', $page ) ||
			 ! class_exists( 'YBY_Andy_OS_Navigation' ) ) {
			return array();
		}
		$items = YBY_Andy_OS_Navigation::tertiary_items( $page );
		return is_array( $items ) ? $items : array();
	}

	/** Compare only exact known admin routes and the current tab. */
	private static function is_active( $target ) {
		if ( ! is_string( $target ) || '' === $target ) { return false; }
		$query = wp_parse_url( $target, PHP_URL_QUERY );
		if ( ! is_string( $query ) ) { return false; }
		parse_str( $query, $parts );
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab = isset( $_GET['tab'] ) && is_scalar( $_GET['tab'] )
			? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$target_page = isset( $parts['page'] ) && is_scalar( $parts['page'] )
			? sanitize_key( $parts['page'] ) : '';
		$target_tab = isset( $parts['tab'] ) && is_scalar( $parts['tab'] )
			? sanitize_key( $parts['tab'] ) : '';
		if ( '' === $page || $target_page !== $page ) { return false; }
		// Original controllers often default tabs without a 'tab' query.
		if ( '' === $tab && in_array( $page,
			array( 'andy-core-leads', 'andy-core-newsletter', 'andy-core-email', 'andy-commerce', 'yby-core' ), true ) ) {
			$defaults = array(
				'andy-core-leads' => 'leads',
				'andy-core-newsletter' => 'subscribers',
				'andy-core-email' => 'overview',
				'andy-commerce' => 'overview',
				'yby-core' => 'general',
			);
			$tab = $defaults[ $page ];
		}
		return $target_tab === $tab;
	}

	/**
	 * Called only by an opted-in original controller that uses this shell.
	 * It may omit the ORIGINAL duplicate top tab bar but must retain all
	 * other original content, actions, permissions and form handlers.
	 */
	public static function suppress_legacy_tabs( $page ) {
		return self::rendering( $page );
	}

	/** Stylesheet is small and can be enqueued in the WordPress admin head. */
	public static function enqueue_styles( $hook = '' ) {
		unset( $hook );
		if ( ! is_admin() ) { return; }
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! self::safe_items( $page ) ) { return; }
		wp_enqueue_style(
			'andy-core-admin-shell-v22',
			YBY_CORE_PLUGIN_URL . 'assets/css/andy-core-admin-shell-v22.css',
			array(), YBY_CORE_VERSION
		);
	}
}
