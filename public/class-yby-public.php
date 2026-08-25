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
			$this->asset_version( 'public/assets/css/yby-core-public.css' )
		);

		wp_enqueue_style(
			'yby-inquiry-components',
			YBY_CORE_PLUGIN_URL . 'public/css/yby-inquiry-components.css',
			array(),
			$this->asset_version( 'public/css/yby-inquiry-components.css' )
		);

		wp_add_inline_style(
			'yby-inquiry-components',
			$this->build_brand_profile_css()
		);

		wp_enqueue_script(
			'yby-lead-sdk',
			YBY_CORE_PLUGIN_URL . 'public/js/yby-lead-sdk.js',
			array(),
			$this->asset_version( 'public/js/yby-lead-sdk.js' ),
			true
		);

		wp_enqueue_script(
			$this->plugin_name . '-public',
			YBY_CORE_PLUGIN_URL . 'public/assets/js/yby-core-public.js',
			array( 'yby-lead-sdk' ),
			$this->asset_version( 'public/assets/js/yby-core-public.js' ),
			true
		);

		wp_enqueue_script(
			'yby-inquiry-components',
			YBY_CORE_PLUGIN_URL . 'public/js/yby-inquiry-components.js',
			array( 'yby-lead-sdk', $this->plugin_name . '-public' ),
			$this->asset_version( 'public/js/yby-inquiry-components.js' ),
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

	/**
	 * Build governed inline CSS variables for inquiry modals.
	 *
	 * @return string
	 */
	protected function build_brand_profile_css() {
		$profile = YBY_Brand_Profile::get_profile();
		$theme   = YBY_Brand_Profile::get_theme_config();

		$variables = array(
			'--yby-inquiry-primary'       => $profile['brand_primary_color'],
			'--yby-inquiry-primary-text'  => $profile['brand_primary_text_color'],
			'--yby-inquiry-secondary'     => $profile['brand_secondary_color'],
			'--yby-inquiry-surface'       => $profile['brand_surface_color'],
			'--yby-inquiry-text'          => $profile['brand_text_color'],
			'--yby-inquiry-muted'         => $profile['brand_muted_text_color'],
			'--yby-inquiry-border'        => $profile['brand_border_color'],
			'--yby-theme-primary'         => $theme['primaryColor'],
			'--yby-theme-accent'          => $theme['accentColor'],
			'--yby-theme-cta-background'  => $theme['ctaBackground'] ?: $theme['primaryColor'],
			'--yby-theme-button-text'     => $theme['buttonTextColor'],
			'--yby-theme-heading-font'    => $theme['headingFont'] ?: 'inherit',
			'--yby-theme-body-font'       => $theme['bodyFont'] ?: 'inherit',
			'--yby-theme-border-radius'   => $theme['borderRadius'],
			'--yby-theme-glass-opacity'   => $theme['glassOpacity'],
		);
		$declarations = array();

		foreach ( $variables as $name => $value ) {
			$declarations[] = $name . ':' . $value;
		}

		return ':root,.yby-inquiry-modal{' . implode( ';', $declarations ) . ';}';
	}

	/**
	 * Build a cache-busting asset version for public runtime files.
	 *
	 * @param string $relative_path Plugin-relative asset path.
	 * @return string
	 */
	protected function asset_version( $relative_path ) {
		$path = YBY_CORE_PLUGIN_DIR . ltrim( (string) $relative_path, '/\\' );

		if ( is_readable( $path ) ) {
			$mtime = filemtime( $path );

			if ( false !== $mtime ) {
				return $this->version . '.' . $mtime;
			}
		}

		return $this->version;
	}
}
