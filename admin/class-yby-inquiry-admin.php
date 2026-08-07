<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Inquiry_Admin {
	protected $plugin_name;
	protected $version;
	protected $page_hook = '';
	protected static $registered_page_hook = '';

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public static function page_slug() {
		return 'andy-core-leads';
	}

	public function add_admin_menu() {
		$this->page_hook = add_submenu_page( YBY_Project_Studio::menu_slug(), __( 'Inquiry', 'yby-core' ), __( 'Inquiry', 'yby-core' ), 'andy_core_leads_view', self::page_slug(), array( $this, 'render_page' ) );
		self::$registered_page_hook = $this->page_hook;
		global $submenu;
		$parent_slug = YBY_Project_Studio::menu_slug();
		if ( isset( $submenu[ $parent_slug ] ) ) {
			foreach ( $submenu[ $parent_slug ] as $index => $item ) {
				if ( YBY_Project_Studio::menu_slug() === $item[2] ) {
					unset( $submenu[ $parent_slug ][ $index ] );
				}
			}
			$submenu[ $parent_slug ] = array_values( $submenu[ $parent_slug ] );
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

	/**
	 * Return the admin hook captured during the real menu registration.
	 *
	 * @return string
	 */
	public static function registered_page_hook() {
		return self::$registered_page_hook;
	}

	public function enqueue_assets( $hook_suffix ) {
		$is_inquiry_request = self::page_slug() === ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' );
		if ( $this->page_hook !== $hook_suffix && ! $is_inquiry_request ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name . '-inquiry', YBY_CORE_PLUGIN_URL . 'assets/css/yby-inquiry-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-inquiry', YBY_CORE_PLUGIN_URL . 'assets/js/yby-inquiry-admin.js', array(), $this->version, true );
	}

	public function render_page() {
		if ( ! YBY_Security::can_view_leads() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}
		$lead_id = absint( $_GET['lead_id'] ?? 0 );
		if ( $lead_id ) {
			if ( ! YBY_Security::can_view_lead( $lead_id ) ) { wp_die( esc_html__( 'You do not have permission to access this inquiry.', 'yby-core' ) ); }
			$this->render_detail( $lead_id );
			return;
		}
		$args = array(
			'page' => absint( $_GET['paged'] ?? 1 ),
			'per_page' => absint( $_GET['per_page'] ?? YBY_Security::inquiry_settings()['leads_per_page'] ),
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
		$error = '';
		if ( isset( $_POST['yby_inquiry_manage_submit'] ) ) {
			if ( ! YBY_Security::can_manage_leads() ) {
				wp_die( esc_html__( 'You do not have permission to manage inquiries.', 'yby-core' ) );
			}
			check_admin_referer( 'yby_inquiry_manage_' . $lead_id, 'yby_inquiry_manage_nonce' );
			$status = sanitize_key( wp_unslash( $_POST['status'] ?? 'new' ) );
			$priority = sanitize_key( wp_unslash( $_POST['priority'] ?? 'normal' ) );
			$owner_id = absint( $_POST['owner_user_id'] ?? 0 );
			$mysql_date = YBY_Lead_Management::normalize_datetime( sanitize_text_field( wp_unslash( $_POST['next_follow_up_at'] ?? '' ) ) );
			if ( ! in_array( $status, YBY_Lead_Management::STATUSES, true ) ) { $error = __( 'Invalid status.', 'yby-core' ); }
			elseif ( ! in_array( $priority, YBY_Lead_Management::PRIORITIES, true ) ) { $error = __( 'Invalid priority.', 'yby-core' ); }
			elseif ( false === $mysql_date ) { $error = __( 'Invalid follow-up date.', 'yby-core' ); }
			elseif ( ! YBY_Security::can_assign_leads() && $owner_id !== absint( $lead['owner_user_id'] ?? 0 ) ) { $error = __( 'You cannot assign this inquiry.', 'yby-core' ); }
			elseif ( ( ! empty( $_POST['archived'] ) || ! empty( $_POST['restore'] ) ) && ! YBY_Security::can_archive_leads() ) { $error = __( 'You cannot archive or restore this inquiry.', 'yby-core' ); }
			else { $result = YBY_Lead_Management::save( $lead_id, array( 'status' => $status, 'priority' => $priority, 'owner_user_id' => $owner_id, 'next_follow_up_at' => $mysql_date, 'note' => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ), 'archived' => ! empty( $_POST['archived'] ), 'restore' => ! empty( $_POST['restore'] ) ), get_current_user_id() ); if ( ! $result['success'] ) { $error = $result['message']; } }
			if ( '' === $error ) {
				$notice = __( 'Inquiry management saved.', 'yby-core' );
				$lead = YBY_Lead_Management::get_detail( $lead_id );
			}
		}
		include YBY_CORE_PLUGIN_DIR . 'admin/views/inquiry-detail.php';
	}
}
