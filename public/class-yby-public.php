<?php
/**
 * Public-facing behavior.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public controller.
 */
class YBY_Public {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue public assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			$this->plugin_name . '-public',
			YBY_CORE_PLUGIN_URL . 'public/assets/css/yby-core-public.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'yby-lead-sdk',
			YBY_CORE_PLUGIN_URL . 'public/js/yby-lead-sdk.js',
			array(),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->plugin_name . '-public',
			YBY_CORE_PLUGIN_URL . 'public/assets/js/yby-core-public.js',
			array( 'yby-lead-sdk' ),
			$this->version,
			true
		);

		$project      = YBY_Project::get_current_project();
		$page_profile = YBY_Page_Profile::get_current_profile();
		$content      = YBY_Content::get_current_content();
		$template     = YBY_Project_Template::get_current_template();

		$data = array(
			'config'      => YBY_Config::get_runtime_config(),
			'leadSession' => ( new YBY_Lead_Session() )->get_frontend_config(),
			'tracking'    => ( new YBY_Tracking() )->get_frontend_config(),
			'project'     => $project,
			'pageProfile' => $page_profile,
			'content'     => $content,
			'template'    => $template,
		);

		wp_add_inline_script(
			$this->plugin_name . '-public',
			'window.YBYCoreConfig = Object.assign({}, window.YBYCoreConfig || {}, ' . wp_json_encode( $data['config'] ) . ');'
			. 'window.YBYCoreData = window.YBYCoreData || ' . wp_json_encode( $data ) . ';'
			. 'window.YBYProject = window.YBYProject || ' . wp_json_encode( $project ) . ';'
			. 'window.YBYPageProfile = window.YBYPageProfile || ' . wp_json_encode( $page_profile ) . ';'
			. 'window.YBYContent = window.YBYContent || ' . wp_json_encode( $content ) . ';'
			. 'window.YBYTemplate = window.YBYTemplate || ' . wp_json_encode( $template ) . ';',
			'before'
		);
	}
}
