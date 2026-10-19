<?php
/**
 * Scoped WordPress plugin update notices and update-Cron controls.
 * Does not mutate update transients or disable Andy Core's signed updater.
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Update_Control {
	const OPTION = 'yby_core_update_control_v1';
	const NONCE = 'yby_core_update_control_save';
	private $updater;

	public function __construct( $updater ) { $this->updater = $updater; }

	public static function settings() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) { $saved = array(); }
		return array(
			'hide_plugin_notices' => ! empty( $saved['hide_plugin_notices'] ),
			'block_plugin_auto' => ! empty( $saved['block_plugin_auto'] ),
			'prune_update_cron' => ! empty( $saved['prune_update_cron'] ),
		);
	}

	public function register_hooks( $loader ) {
		$loader->add_filter( 'wp_get_update_data', $this, 'hide_plugin_update_counts', 1000, 2 );
		$loader->add_action( 'admin_head', $this, 'hide_plugin_update_rows', 1000, 0 );
		$loader->add_filter( 'auto_update_plugin', $this, 'guard_plugin_auto_updates', 2000, 2 );
		$loader->add_filter( 'pre_schedule_event', $this, 'prevent_update_cron_reschedule', 1000, 3 );
		$loader->add_action( 'init', $this, 'prune_known_update_crons', 20, 0 );
		$loader->add_action( 'admin_init', $this, 'handle_settings_save', 15, 0 );
	}

	/** Suppress plugin counts but keep WordPress core, theme and translation alerts. */
	public function hide_plugin_update_counts( $data, $titles = array() ) {
		if ( ! self::settings()['hide_plugin_notices'] || ! is_array( $data ) || ! isset( $data['counts'] ) || ! is_array( $data['counts'] ) ) {
			return $data;
		}
		$data['counts']['plugins'] = 0;
		$data['counts']['total'] = array_sum( array_map( 'intval', array_intersect_key( $data['counts'], array_flip( array( 'plugins', 'themes', 'wordpress', 'translations' ) ) ) ) );
		if ( is_array( $titles ) ) {
			unset( $titles['plugins'] );
			$data['title'] = esc_attr( implode( ', ', $titles ) );
		}
		return $data;
	}

	/** Hide native plugin update rows without intercepting update checks. */
	public function hide_plugin_update_rows() {
		if ( ! self::settings()['hide_plugin_notices'] ) { return; }
		echo '<style id="yby-update-control-plugin-notices">'
			. '.plugins tr.plugin-update-tr,.plugins .update-message,'
			. '#update-plugins,.plugin-update-count,'
			. 'body.plugins-php .plugin-update-tr,body.update-core-php form#upgrade-plugins'
			. '{display:none!important}</style>';
	}

	/** No other plugin auto-upgrades; preserve only explicitly authorized signed Andy updates. */
	public function guard_plugin_auto_updates( $update, $item ) {
		if ( ! self::settings()['block_plugin_auto'] ) { return $update; }
		if ( is_object( $item ) && ( $item->plugin ?? '' ) === plugin_basename( YBY_CORE_PLUGIN_FILE ) ) {
			return $this->updater->signed_background_auto_enabled();
		}
		return false;
	}

	public function blocked_cron_hooks() {
		if ( ! self::settings()['prune_update_cron'] ) { return array(); }
		$blocked = array( 'wp_update_themes' );
		if ( ! $this->updater->signed_background_auto_enabled() ) {
			$blocked[] = 'wp_update_plugins';
			$blocked[] = 'wp_maybe_auto_update';
		}
		// wp_version_check is always retained. Cron-driven core version checks
		// can trigger WordPress's eligible minor/security background updates.
		return $blocked;
	}

	public function prune_known_update_crons() {
		foreach ( $this->blocked_cron_hooks() as $hook ) {
			if ( wp_next_scheduled( $hook ) ) { wp_clear_scheduled_hook( $hook ); }
		}
	}

	public function prevent_update_cron_reschedule( $pre, $event, $wp_error = false ) {
		if ( null !== $pre || ! is_object( $event ) || ! isset( $event->hook ) ) { return $pre; }
		return in_array( $event->hook, $this->blocked_cron_hooks(), true ) ? false : $pre;
	}

	public function handle_settings_save() {
		if ( ! is_admin() || ! isset( $_POST['yby_core_update_control_save'] ) ) { return; }
		if ( ! current_user_can( 'andy_core_settings_manage' ) || ! ( new YBY_Security() )->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to manage update settings.', 'yby-core' ) );
		}
		check_admin_referer( self::NONCE, 'yby_core_update_control_nonce' );
		$requested = array(
			'hide_plugin_notices' => ! empty( $_POST['hide_plugin_notices'] ),
			'block_plugin_auto' => ! empty( $_POST['block_plugin_auto'] ),
			'prune_update_cron' => ! empty( $_POST['prune_update_cron'] ),
		);
		$old = self::settings();
		update_option( self::OPTION, $requested, false );
		if ( ! $requested['prune_update_cron'] && $old['prune_update_cron'] && function_exists( 'wp_schedule_update_checks' ) ) {
			wp_schedule_update_checks();
		}
		if ( $requested['prune_update_cron'] ) { $this->prune_known_update_crons(); }
		wp_safe_redirect( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => 'updates', 'yby_update_control_saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Read-only Cron inspection for the existing WordPress admin page.
	 * This never executes, reschedules, or deletes an event.
	 * Policy-blocked update hooks should be absent after cleanup.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function cron_diagnostic_rows() {
		$blocked = $this->blocked_cron_hooks();
		$rows = array();
		foreach ( array( 'wp_version_check', 'wp_update_plugins', 'wp_update_themes', 'wp_maybe_auto_update' ) as $hook ) {
			$next = wp_next_scheduled( $hook );
			$scheduled = false !== $next;
			$is_blocked = in_array( $hook, $blocked, true );
			$expected = $is_blocked ? 'absent' : ( 'wp_version_check' === $hook ? 'present' : 'unrestricted' );
			$pass = 'absent' === $expected ? ! $scheduled : ( 'present' === $expected ? $scheduled : null );
			$rows[] = array(
				'hook' => $hook,
				'next' => $scheduled ? (int) $next : null,
				'expected' => $expected,
				'pass' => $pass,
			);
		}
		return $rows;
	}

	/** Administrator-visible snapshot; does not run WP-Cron or test execution. */
	public function render_cron_diagnostics() {
		echo '<h3>' . esc_html__( 'Cron 状态（只读）', 'yby-core' ) . '</h3>';
		echo '<p>' . esc_html__( '只检查四项 WordPress 更新任务的计划状态；不执行命令、不触发升级，也不修改任务。', 'yby-core' ) . '</p>';
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr>';
		foreach ( array( 'Cron Hook', '检查结果', '下次计划运行', '规则' ) as $heading ) {
			echo '<th scope="col">' . esc_html( $heading ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $this->cron_diagnostic_rows() as $row ) {
			$state = null === $row['pass'] ? '未限制' : ( $row['pass'] ? '符合预期' : '需要复核' );
			$next_label = null === $row['next'] ? '未计划' : wp_date( 'Y-m-d H:i:s T', $row['next'] );
			$policy = 'absent' === $row['expected'] ? '应删除' : ( 'present' === $row['expected'] ? '应保留' : '由 WordPress / 主机管理' );
			echo '<tr><td><code>' . esc_html( $row['hook'] ) . '</code></td>'
				. '<td><strong>' . esc_html( $state ) . '</strong></td>'
				. '<td>' . esc_html( $next_label ) . '</td>'
				. '<td>' . esc_html( $policy ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( '此表仅证明 WordPress 计划记录的当前快照。不能单凭它证明 Cron 已执行、更新签名验证通过或系统安全更新已完成。', 'yby-core' ) . '</p>';
	}

	public function render_settings() {
		if ( ! current_user_can( 'andy_core_settings_manage' ) || ! ( new YBY_Security() )->can_manage_settings() ) { return; }
		$settings = self::settings();
		echo '<hr><h2>' . esc_html__( 'Update Control', 'yby-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'Optional site controls (off by default). Update information and signed verification remain intact.', 'yby-core' ) . '</p>';
		if ( isset( $_GET['yby_update_control_saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Update Control settings saved.', 'yby-core' ) . '</p></div>';
		}
		echo '<form method="post">';
		wp_nonce_field( self::NONCE, 'yby_core_update_control_nonce' );
		foreach ( array(
			'hide_plugin_notices' => __( 'Hide plugin update notices and badges across WordPress admin', 'yby-core' ),
			'block_plugin_auto' => __( 'Block automatic updates for other plugins; retain signed Andy Core opt-in', 'yby-core' ),
			'prune_update_cron' => __( 'Delete and block re-creation of unnecessary WordPress update Cron events', 'yby-core' ),
		) as $key => $label ) {
			echo '<p><label><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( $settings[ $key ], true, false ) . '> ' . esc_html( $label ) . '</label></p>';
		}
		echo '<p>' . esc_html__( 'Safety: wp_version_check and all non-update business Cron tasks are preserved. Signed Andy Core auto-update retains its own required WordPress discovery and runner events. Disabling this control restores native update-check schedules.', 'yby-core' ) . '</p>';
		echo '<p><button class="button button-primary" type="submit" name="yby_core_update_control_save" value="1">' . esc_html__( 'Save Update Control', 'yby-core' ) . '</button></p></form>';
		$this->render_cron_diagnostics();
	}
}
