<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Inquiry_Admin {
	protected $plugin_name;
	protected $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public static function page_slug() {
		return 'andy-core-leads';
	}

	public function add_admin_menu() {
		add_submenu_page( YBY_Project_Studio::menu_slug(), __( 'Inquiry', 'yby-core' ), __( 'Inquiry', 'yby-core' ), 'manage_options', self::page_slug(), array( $this, 'render_page' ) );
		global $submenu;
		$parent_slug = YBY_Project_Studio::menu_slug();
		if ( isset( $submenu[ $parent_slug ] ) ) {
			foreach ( $submenu[ $parent_slug ] as $index => $item ) {
				if ( self::page_slug() === $item[2] ) {
					$inquiry = $item;
					unset( $submenu[ $parent_slug ][ $index ] );
					array_unshift( $submenu[ $parent_slug ], $inquiry );
					break;
				}
			}
		}
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( YBY_Project_Studio::menu_slug() . '_page_' . self::page_slug() !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name . '-inquiry', YBY_CORE_PLUGIN_URL . 'assets/css/yby-inquiry-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-inquiry', YBY_CORE_PLUGIN_URL . 'assets/js/yby-inquiry-admin.js', array(), $this->version, true );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}
		$lead_id = absint( $_GET['lead_id'] ?? 0 );
		if ( $lead_id ) {
			$this->render_detail( $lead_id );
			return;
		}
		$args = array(
			'page' => absint( $_GET['paged'] ?? 1 ),
			'per_page' => absint( $_GET['per_page'] ?? 30 ),
			'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'status' => sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ),
			'priority' => sanitize_key( wp_unslash( $_GET['priority'] ?? '' ) ),
			'owner_user_id' => absint( $_GET['owner_user_id'] ?? 0 ),
			'country' => sanitize_text_field( wp_unslash( $_GET['country'] ?? '' ) ),
			'source_preset' => sanitize_key( wp_unslash( $_GET['source_preset'] ?? '' ) ),
			'page_profile' => sanitize_key( wp_unslash( $_GET['page_profile'] ?? '' ) ),
			'date_from' => sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) ),
			'date_to' => sanitize_text_field( wp_unslash( $_GET['date_to'] ?? '' ) ),
			'archived' => sanitize_key( wp_unslash( $_GET['archived'] ?? '' ) ),
		);
		$data = YBY_Lead_Management::list_leads( $args );
		include YBY_CORE_PLUGIN_DIR . 'admin/views/inquiry-list.php';
	}

	protected function render_detail( $lead_id ) {
		$lead = YBY_Lead_Management::get_detail( $lead_id );
		if ( ! $lead ) { wp_die( esc_html__( 'Inquiry not found.', 'yby-core' ) ); }
		$notice = '';
		if ( isset( $_POST['yby_inquiry_manage_submit'] ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage inquiries.', 'yby-core' ) );
			}
			check_admin_referer( 'yby_inquiry_manage_' . $lead_id, 'yby_inquiry_manage_nonce' );
			$status = sanitize_key( wp_unslash( $_POST['status'] ?? 'new' ) );
			$priority = sanitize_key( wp_unslash( $_POST['priority'] ?? 'normal' ) );
			if ( in_array( $status, YBY_Lead_Management::STATUSES, true ) && in_array( $priority, YBY_Lead_Management::PRIORITIES, true ) ) {
				YBY_Lead_Management::save( $lead_id, array( 'status' => $status, 'priority' => $priority, 'owner_user_id' => absint( $_POST['owner_user_id'] ?? 0 ), 'next_follow_up_at' => sanitize_text_field( wp_unslash( $_POST['next_follow_up_at'] ?? '' ) ), 'note' => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ), 'archived' => ! empty( $_POST['archived'] ), 'restore' => ! empty( $_POST['restore'] ) ), get_current_user_id() );
				$notice = __( 'Inquiry management saved.', 'yby-core' );
				$lead = YBY_Lead_Management::get_detail( $lead_id );
			}
		}
		include YBY_CORE_PLUGIN_DIR . 'admin/views/inquiry-detail.php';
	}
}
