<?php
/**
 * Brand OS configuration and admin layer.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand OS controller.
 */
class YBY_Brand_OS {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Security helper.
	 *
	 * @var YBY_Security
	 */
	protected $security;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->security    = new YBY_Security();
	}

	/**
	 * Brand option key.
	 *
	 * @return string
	 */
	public static function option_key() {
		return 'yby_brand_os_options';
	}

	/**
	 * Admin page slug.
	 *
	 * @return string
	 */
	public static function page_slug() {
		return 'yby-brand-os';
	}

	/**
	 * Default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'logo_default'       => '',
			'logo_white'         => '',
			'logo_black'         => '',
			'favicon'            => '',
			'primary_color'      => '#0f766e',
			'secondary_color'    => '#111827',
			'accent_color'       => '#f59e0b',
			'font_primary'       => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'font_secondary'     => 'Georgia, "Times New Roman", serif',
			'brand_document_url' => '',
		);
	}

	/**
	 * Get options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options() {
		$options = get_option( self::option_key(), array() );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return wp_parse_args( $options, self::defaults() );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $options Raw options.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $options ) {
		$options  = is_array( $options ) ? $options : array();
		$defaults = self::defaults();

		return array(
			'logo_default'       => esc_url_raw( $options['logo_default'] ?? $defaults['logo_default'] ),
			'logo_white'         => esc_url_raw( $options['logo_white'] ?? $defaults['logo_white'] ),
			'logo_black'         => esc_url_raw( $options['logo_black'] ?? $defaults['logo_black'] ),
			'favicon'            => esc_url_raw( $options['favicon'] ?? $defaults['favicon'] ),
			'primary_color'      => sanitize_hex_color( $options['primary_color'] ?? $defaults['primary_color'] ) ?: $defaults['primary_color'],
			'secondary_color'    => sanitize_hex_color( $options['secondary_color'] ?? $defaults['secondary_color'] ) ?: $defaults['secondary_color'],
			'accent_color'       => sanitize_hex_color( $options['accent_color'] ?? $defaults['accent_color'] ) ?: $defaults['accent_color'],
			'font_primary'       => self::sanitize_font_stack( $options['font_primary'] ?? $defaults['font_primary'] ),
			'font_secondary'     => self::sanitize_font_stack( $options['font_secondary'] ?? $defaults['font_secondary'] ),
			'brand_document_url' => esc_url_raw( $options['brand_document_url'] ?? $defaults['brand_document_url'] ),
		);
	}

	/**
	 * Sanitize font stack without allowing Google Fonts URLs.
	 *
	 * @param string $value Raw font stack.
	 * @return string
	 */
	protected static function sanitize_font_stack( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( false !== stripos( $value, 'fonts.googleapis.com' ) || false !== stripos( $value, '@import' ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Get one option.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$options = self::get_options();

		return $options[ $key ] ?? null;
	}

	/**
	 * Get default logo URL.
	 *
	 * @return string
	 */
	public static function get_logo_default() {
		return (string) self::get( 'logo_default' );
	}

	/**
	 * Get white logo URL.
	 *
	 * @return string
	 */
	public static function get_logo_white() {
		return (string) self::get( 'logo_white' );
	}

	/**
	 * Get black logo URL.
	 *
	 * @return string
	 */
	public static function get_logo_black() {
		return (string) self::get( 'logo_black' );
	}

	/**
	 * Get runtime-safe brand config.
	 *
	 * @return array<string, string>
	 */
	public static function get_brand_config() {
		return array(
			'logoDefault'      => self::get_logo_default(),
			'logoWhite'        => (string) self::get( 'logo_white' ),
			'logoBlack'        => (string) self::get( 'logo_black' ),
			'favicon'          => (string) self::get( 'favicon' ),
			'primaryColor'     => (string) self::get( 'primary_color' ),
			'secondaryColor'   => (string) self::get( 'secondary_color' ),
			'accentColor'      => (string) self::get( 'accent_color' ),
			'fontPrimary'      => (string) self::get( 'font_primary' ),
			'fontSecondary'    => (string) self::get( 'font_secondary' ),
			'brandDocumentUrl' => (string) self::get( 'brand_document_url' ),
		);
	}

	/**
	 * Register admin submenu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			__( 'Brand', 'yby-core' ),
			__( 'Brand', 'yby-core' ),
			'manage_options',
			self::page_slug(),
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue shared admin assets on this page.
	 *
	 * @param string $hook_suffix Admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( YBY_Project_Studio::menu_slug() . '_page_' . self::page_slug() !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			YBY_CORE_PLUGIN_URL . 'assets/css/yby-core-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! $this->security->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}

		$notice = '';

		if ( isset( $_POST['yby_brand_submit'] ) ) {
			check_admin_referer( 'yby_brand_os_save_settings', 'yby_brand_os_nonce' );

			$raw_options = wp_unslash( $_POST['yby_brand_os_options'] ?? array() );
			$options     = self::sanitize( is_array( $raw_options ) ? $raw_options : array() );

			update_option( self::option_key(), $options );
			$notice = __( 'Brand settings saved.', 'yby-core' );
		}

		$options = self::get_options();

		include YBY_CORE_PLUGIN_DIR . 'admin/views/brand-settings.php';
	}
}
