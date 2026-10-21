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
			echo '<p class="andy-core-shell-v22__rail-title">内容管理</p>';
			self::render_links( $items );
			echo '</nav><main class="andy-core-shell-v22__body">';
			// One PHP render invocation; no JavaScript DOM changes.
			call_user_func( $body );
			echo '</main></div></div>';
		} finally {
			self::$current = '';
		}
	}

 	/**
	 * Native WordPress landing_page list integration.
	 * The original edit.php content, pagination, filters and Screen Options stay
	 * in their core location; this adds an accessible sibling navigation only.
	 * Never runs for a different CPT, editor, frontend or unauthorized user.
	 */

	/** WordPress owns the native list/editor/forms; only sibling L3 is printed. */
	public static function render_native_landing_navigation() {
		self::render_native_content_navigation();
	}
	public static function render_native_content_navigation() {
		if ( ! is_admin() || ! class_exists( 'YBY_Module_Registry' ) ) { return; }
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! isset( $screen->id, $screen->post_type ) ) { return; }
		$landing = 'edit-landing_page' === $screen->id && 'landing_page' === $screen->post_type &&
			current_user_can( 'edit_pages' ) && YBY_Module_Registry::is_enabled( 'landing_pages' );
		$projects = 'edit-yby_project' === $screen->id && 'yby_project' === $screen->post_type &&
			current_user_can( 'edit_posts' ) && YBY_Module_Registry::is_enabled( 'project_studio' );
		if ( ! $landing && ! $projects ) { return; }
		$items = self::safe_items( 'yby-docs-os' );
		if ( ! $items ) { return; }
		$type = $landing ? 'landing_page' : 'yby_project';
		echo '<div class="andy-core-native-cpt-l3" data-andy-native-l3="' . esc_attr( $type ) . '">';
		echo '<nav class="andy-core-shell-v22__rail" aria-label="' . esc_attr__( '内容功能导航', 'yby-core' ) . '">';
		echo '<p class="andy-core-shell-v22__rail-title">内容管理</p>';
		self::render_links( $items, $landing ? '落地页' : '项目' );
		echo '</nav></div>';
	}
	/** Native HTML details offer collapsible groups, without DOM-moving JS. */
	private static function render_links( $items, $preferred = '' ) {
		$active_group = $preferred;
		if ( '' === $active_group ) {
			foreach ( $items as $item ) {
				if ( self::is_active( $item['url'] ?? '' ) ) {
					$active_group = (string) ( $item['group'] ?? '' ); break;
				}
			}
		}
		$current = '';
		$opened = false;
		foreach ( $items as $item ) {
			$title = (string) ( $item['title'] ?? '' );
			$url = (string) ( $item['url'] ?? '' );
			if ( '' === $title || '' === $url ) { continue; }
			$group = (string) ( $item['group'] ?? '' );
			if ( $group !== $current ) {
				if ( $opened ) { echo '</div></details>'; $opened = false; }
				if ( '' !== $group ) {
					echo '<details class="andy-core-shell-v22__section"';
					if ( $group === $active_group || ( '' === $active_group && '文档' === $group ) ) { echo ' open'; }
					echo '><summary class="andy-core-shell-v22__group">' . esc_html( $group ) . '</summary><div class="andy-core-shell-v22__section-links">';
					$opened = true;
				}
				$current = $group;
			}
			echo '<a class="andy-core-shell-v22__link" href="' . esc_url( $url ) . '"';
			if ( self::is_active( $url ) ) { echo ' aria-current="page"'; }
			echo '>' . esc_html( $title ) . '</a>';
		}
		if ( $opened ) { echo '</div></details>'; }
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
		$target_type = isset( $parts['post_type'] ) && is_scalar( $parts['post_type'] )
			? sanitize_key( $parts['post_type'] ) : '';
		if ( '' !== $target_type ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$path = wp_parse_url( $target, PHP_URL_PATH );
			return $screen && $screen->post_type === $target_type &&
				basename( (string) $path ) === ( $GLOBALS['pagenow'] ?? '' );
		}
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
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$native = $screen && class_exists( 'YBY_Module_Registry' ) && (
			( 'edit-landing_page' === $screen->id && current_user_can( 'edit_pages' ) &&
			  YBY_Module_Registry::is_enabled( 'landing_pages' ) ) ||
			( 'edit-yby_project' === $screen->id && current_user_can( 'edit_posts' ) &&
			  YBY_Module_Registry::is_enabled( 'project_studio' ) ) );
		if ( ! $native && ! self::safe_items( $page ) ) { return; }
		wp_enqueue_style(
			'andy-core-admin-shell-v22',
			YBY_CORE_PLUGIN_URL . 'assets/css/andy-core-admin-shell-v22.css',
			array(), YBY_CORE_VERSION
		);
	}
}
