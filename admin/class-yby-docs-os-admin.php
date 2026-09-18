<?php
/**
 * Docs OS admin shell.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Docs_OS_Admin {
	const PAGE_SLUG = 'yby-docs-os';

	public function add_admin_menu() {
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			__( 'Docs OS', 'yby-core' ),
			__( 'Docs OS', 'yby-core' ),
			'andy_core_settings_manage',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}
		$settings_url = add_query_arg(
			array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => 'modules' ),
			admin_url( 'admin.php' )
		);
		?>
		<div class="wrap yby-docs-os-shell">
			<h1><?php esc_html_e( 'Andy Core — Docs OS', 'yby-core' ); ?></h1>
			<p><?php esc_html_e( 'Docs OS module shell is active. Native Docs runtime is introduced in V160-3.', 'yby-core' ); ?></p>
			<table class="widefat striped" style="max-width:900px">
				<tbody>
					<tr><th><?php esc_html_e( 'Module', 'yby-core' ); ?></th><td>Docs OS V1</td></tr>
					<tr><th><?php esc_html_e( 'Runtime', 'yby-core' ); ?></th><td><?php esc_html_e( 'Shell only — BetterDocs runtime remains owner until migration gates pass.', 'yby-core' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Data retention', 'yby-core' ); ?></th><td><?php esc_html_e( 'Existing docs, taxonomy, metadata and media are retained when the module is disabled.', 'yby-core' ); ?></td></tr>
				</tbody>
			</table>
			<p><a class="button" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Back to Modules', 'yby-core' ); ?></a></p>
		</div>
		<?php
	}
}
