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
			$this->plugin_name . '-public',
			YBY_CORE_PLUGIN_URL . 'public/assets/js/yby-core-public.js',
			array(),
			$this->version,
			true
		);

		$data = array(
			'config'      => YBY_Config::get_options(),
			'leadSession' => ( new YBY_Lead_Session() )->get_frontend_config(),
			'tracking'    => ( new YBY_Tracking() )->get_frontend_config(),
			'thankYouUrl' => home_url( '/lp/thank-you-irrigation-solution/' ),
		);

		wp_add_inline_script(
			$this->plugin_name . '-public',
			'window.YBYCoreData = window.YBYCoreData || ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}
}
