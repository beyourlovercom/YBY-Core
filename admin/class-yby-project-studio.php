<?php
/**
 * Project Studio admin behavior.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project Studio admin controller.
 */
class YBY_Project_Studio {

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
	 * Return top-level menu slug.
	 *
	 * @return string
	 */
	public static function menu_slug() {
		return 'yby-os';
	}

	/**
	 * Return the dedicated Project Studio submenu slug.
	 *
	 * @return string
	 */
	public static function studio_page_slug() {
		return 'yby-project-studio';
	}

	/**
	 * Register Project Studio menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Andy Core', 'yby-core' ),
			__( 'Andy Core', 'yby-core' ),
			'manage_options',
			self::menu_slug(),
			array( $this, 'render_project_studio_page' ),
			'dashicons-portfolio',
			58
		);

		add_submenu_page(
			self::menu_slug(),
			__( 'Project Studio', 'yby-core' ),
			__( 'Project Studio', 'yby-core' ),
			'manage_options',
			self::studio_page_slug(),
			array( $this, 'render_project_studio_page' )
		);

		add_submenu_page(
			self::menu_slug(),
			__( 'Projects', 'yby-core' ),
			__( 'Projects', 'yby-core' ),
			'manage_options',
			'edit.php?post_type=' . YBY_Project_CPT::post_type()
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$post_type = '';

		if ( isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		} elseif ( isset( $_GET['post'] ) ) {
			$post_type = get_post_type( absint( $_GET['post'] ) );
		}

		$is_studio_page = false !== strpos( (string) $hook_suffix, self::menu_slug() );
		$is_project_cpt = YBY_Project_CPT::post_type() === $post_type;

		if ( ! $is_studio_page && ! $is_project_cpt ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			YBY_CORE_PLUGIN_URL . 'assets/css/yby-core-admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js',
			array(),
			$this->version,
			true
		);
	}

	/**
	 * Keep the Andy Core menu highlighted for the dedicated Studio route.
	 *
	 * @param string $parent_file Current parent menu file.
	 * @return string
	 */
	public function filter_parent_file( $parent_file ) {
		if ( self::studio_page_slug() === ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' ) ) {
			return self::menu_slug();
		}

		return $parent_file;
	}

	/**
	 * Keep Project Studio highlighted for its dedicated route.
	 *
	 * @param string $submenu_file Current submenu file.
	 * @return string
	 */
	public function filter_submenu_file( $submenu_file ) {
		if ( self::studio_page_slug() === ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' ) ) {
			return self::studio_page_slug();
		}

		return $submenu_file;
	}

	/**
	 * Render Project Studio page.
	 *
	 * @return void
	 */
	public function render_project_studio_page() {
		if ( ! $this->security->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}

		$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		$is_parent_request = self::menu_slug() === ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' );
		if ( $is_parent_request && ! isset( $_GET['view'] ) && ! $post_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=andy-core-leads' ) );
			exit;
		}

		if ( 'runtime' === $view && $post_id ) {
			$this->render_runtime_viewer( $post_id );
			return;
		}

		if ( 'overview' === $view && $post_id ) {
			$this->render_project_overview( $post_id );
			return;
		}

		$this->render_project_list();
	}

	/**
	 * Render Project List.
	 *
	 * @return void
	 */
	protected function render_project_list() {
		$projects = get_posts(
			array(
				'post_type'      => YBY_Project_CPT::post_type(),
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		include YBY_CORE_PLUGIN_DIR . 'admin/views/project-list.php';
	}

	/**
	 * Render Project Overview.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	protected function render_project_overview( $post_id ) {
		$post_id       = absint( $post_id );
		$post          = get_post( $post_id );

		if ( ! $post || YBY_Project_CPT::post_type() !== $post->post_type ) {
			wp_die( esc_html__( 'Project not found.', 'yby-core' ) );
		}

		$overview_data = YBY_Project_CPT::get_overview_data( $post_id );
		$runtime_map   = array(
			'project' => YBY_Project::get_project_by_post_id( $post_id ),
			'content' => YBY_Content::get_content_by_post_id( $post_id ),
			'template' => YBY_Project_Template::get_template_by_post_id( $post_id ),
		);

		include YBY_CORE_PLUGIN_DIR . 'admin/views/project-overview.php';
	}

	/**
	 * Render Runtime Viewer.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	protected function render_runtime_viewer( $post_id ) {
		$post_id      = absint( $post_id );
		$post         = get_post( $post_id );

		if ( ! $post || YBY_Project_CPT::post_type() !== $post->post_type ) {
			wp_die( esc_html__( 'Project not found.', 'yby-core' ) );
		}

		$overview     = YBY_Project_CPT::get_overview_data( $post_id );
		$project_data = YBY_Project::get_project_by_post_id( $post_id );

		$runtime_data = array(
			'YBYProject'    => $project_data,
			'YBYContent'    => YBY_Content::get_content_by_post_id( $post_id ),
			'YBYTemplate'   => YBY_Project_Template::get_template_by_post_id( $post_id ),
			'YBYTracking'   => array(
				'frontendConfig' => ( new YBY_Tracking() )->get_frontend_config(),
				'projectContext' => array(
					'trackingGroup'      => $project_data['trackingGroup'] ?? '',
					'ga4ContentGroup'    => $project_data['ga4ContentGroup'] ?? '',
					'adsConversionGroup' => $project_data['adsConversionGroup'] ?? '',
				),
			),
			'YBYCoreConfig' => YBY_Config::get_runtime_config(),
		);

		include YBY_CORE_PLUGIN_DIR . 'admin/views/runtime-viewer.php';
	}

	/**
	 * Build Project Studio page URL.
	 *
	 * @param string $view View name.
	 * @param int    $post_id Optional post ID.
	 * @return string
	 */
	public static function studio_url( $view, $post_id = 0 ) {
		$args = array(
			'page' => self::studio_page_slug(),
		);

		if ( ! empty( $view ) && 'list' !== $view ) {
			$args['view'] = $view;
		}

		if ( $post_id ) {
			$args['post_id'] = absint( $post_id );
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}
}
