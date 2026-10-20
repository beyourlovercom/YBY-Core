<?php
/**
 * Orders UI V1: lightweight native WooCommerce HPOS / legacy presentation.
 *
 * All order queries and write actions remain with WooCommerce. Preferences are
 * stored per WordPress user only on explicit Save, never on mode switches.
 *
 * @package Andy_Commerce
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Orders_UI {
	const META_KEY = 'andy_commerce_orders_ui_v1';
	const NONCE_ACTION = 'andy_commerce_orders_ui_v1';

	public static function boot() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_andy_commerce_orders_ui_save', array( __CLASS__, 'save' ) );
	}

	public static function on_orders_screen( $screen ) {
		return is_object( $screen )
			&& isset( $screen->id )
			&& in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order' ), true );
	}

	public static function sanitize_preferences( $input ) {
		if ( ! is_array( $input ) ) {
			return false;
		}
		$layout = isset( $input['layout'] ) && is_string( $input['layout'] )
			? $input['layout'] : '';
		if ( ! in_array( $layout, array( 'standard', 'compact', 'cards' ), true ) ) {
			return false;
		}
		$columns = isset( $input['columns'] ) ? $input['columns'] : array();
		if ( ! is_array( $columns ) || count( $columns ) > 40 ) {
			return false;
		}
		$ordered = array();
		foreach ( $columns as $raw ) {
			if ( ! is_string( $raw ) || strlen( $raw ) > 64 ) {
				return false;
			}
			$key = sanitize_key( $raw );
			if ( '' === $key || $key !== $raw || 'cb' === $key ) {
				return false;
			}
			if ( ! in_array( $key, $ordered, true ) ) {
				$ordered[] = $key;
			}
		}
		return array( 'layout' => $layout, 'columns' => $ordered );
	}

	public static function enqueue() {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! self::on_orders_screen( get_current_screen() ) ) {
			return;
		}
		$raw = get_user_meta( get_current_user_id(), self::META_KEY, true );
		$preferences = self::sanitize_preferences( $raw );
		if ( false === $preferences ) {
			$preferences = array( 'layout' => 'standard', 'columns' => array() );
		}
		$css = 'assets/css/orders-ui-v1.css';
		$js = 'assets/js/orders-ui-v1.js';
		wp_enqueue_style( 'andy-commerce-orders-ui-v1', ANDY_COMMERCE_PLUGIN_URL . $css, array(), ANDY_COMMERCE_VERSION );
		wp_enqueue_script( 'andy-commerce-orders-ui-v1', ANDY_COMMERCE_PLUGIN_URL . $js, array(), ANDY_COMMERCE_VERSION, true );
		wp_localize_script(
			'andy-commerce-orders-ui-v1',
			'AndyCommerceOrdersUIV1',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'preferences' => $preferences,
				'strings'     => array(
					'title'      => '订单布局',
					'standard'   => '标准',
					'compact'    => '紧凑',
					'cards'      => '卡片',
					'columns'    => '列排序',
					'save'       => '保存我的布局',
					'saving'     => '保存中…',
					'saved'      => '已保存',
					'failed'     => '保存失败，请重试',
					'reset'      => '恢复默认',
					'moveUp'     => '上移',
					'moveDown'   => '下移',
					'visibility' => '显示或隐藏列，请使用页面右上角的「显示选项」。',
				),
			)
		);
	}

	public static function save() {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$raw = array(
			'layout'  => isset( $_POST['layout'] ) && is_string( $_POST['layout'] )
				? wp_unslash( $_POST['layout'] ) : '',
			'columns' => isset( $_POST['columns'] ) && is_array( $_POST['columns'] )
				? wp_unslash( $_POST['columns'] ) : array(),
		);
		$preferences = self::sanitize_preferences( $raw );
		if ( false === $preferences ) {
			wp_send_json_error( array( 'message' => 'invalid preferences' ), 400 );
		}
		update_user_meta( get_current_user_id(), self::META_KEY, $preferences );
		wp_send_json_success( array( 'preferences' => $preferences ) );
	}
}
