<?php
/**
 * Andy Core global inquiry popup and two-button dock.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YBY_Global_Popup_Dock {
	protected $inquiry_manager;
	protected $renderer;

	public function __construct( $inquiry_manager, $renderer ) {
		$this->inquiry_manager = $inquiry_manager;
		$this->renderer        = $renderer;
	}

	public function render() {
		if ( $this->is_excluded_context() ) {
			return;
		}

		$target_id = YBY_Inquiry_Shortcodes::get_compatible_modal_id();
		if ( '' === $target_id ) {
			echo $this->render_global_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$target_id = 'yby-global-inquiry-modal';
		}

		echo $this->render_dock( $target_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_admin_preview() {
		$markup = $this->render_global_modal( 'yby-admin-popup-preview' );
		?>
		<div class="wrap yby-core-popup-preview">
			<h1><?php esc_html_e( 'Popup Preview / Test', 'yby-core' ); ?></h1>
			<p><?php esc_html_e( 'This opens the real Andy Core popup shell in a non-submitting preview. No lead request is sent from this screen.', 'yby-core' ); ?></p>
			<button type="button" class="button button-primary" data-yby-admin-popup-open><?php esc_html_e( 'Open Popup Preview', 'yby-core' ); ?></button>
			<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<script>
		(function () {
			var button = document.querySelector('[data-yby-admin-popup-open]');
			var modal = document.getElementById('yby-admin-popup-preview');
			if (!button || !modal) return;
			button.addEventListener('click', function () { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); modal.classList.add('is-open'); });
			modal.addEventListener('click', function (event) { if (event.target.closest('[data-yby-modal-close]')) { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); modal.classList.remove('is-open'); } });
			modal.querySelector('form').addEventListener('submit', function (event) { event.preventDefault(); });
		})();
		</script>
		<?php
	}

	protected function render_global_modal( $id = 'yby-global-inquiry-modal' ) {
		$preset = $this->inquiry_manager->get_preset_manager()->get_preset( $this->get_global_preset_id() );
		if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) {
			return '';
		}
		$all_fields = $this->inquiry_manager->get_field_manager()->get_fields();
		$fields     = array();
		foreach ( (array) $preset['fields'] as $field_id ) {
			if ( isset( $all_fields[ $field_id ] ) && ! empty( $all_fields[ $field_id ]['enabled'] ) ) $fields[] = $all_fields[ $field_id ];
		}
		if ( empty( $fields ) ) {
			return '';
		}
		$image_id = class_exists( 'YBY_Config' ) ? YBY_Config::get_popup_image_id() : 0;
		$image    = $image_id && function_exists( 'wp_get_attachment_image_url' ) ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
		$settings = class_exists( 'YBY_Global_Popup' ) ? YBY_Global_Popup::settings() : array();
		$image    = ! empty( $settings['inquiry_image'] ) ? $settings['inquiry_image'] : $image;
		return $this->renderer->render_modal( $preset, $fields, array(
			'id'           => $id,
			'title'        => ! empty( $settings['inquiry_title'] ) ? $settings['inquiry_title'] : __( 'Tell us about your project', 'yby-core' ),
			'subtitle'     => isset( $settings['inquiry_subtitle'] ) ? $settings['inquiry_subtitle'] : '',
			'submit_label' => __( 'Request a Free Quote', 'yby-core' ),
			'image'        => is_string( $image ) ? esc_url_raw( $image ) : '',
			'class'        => array( 'yby-inquiry-modal--global', 'yby-global-popup--inquiry' ),
		) );
	}

	protected function render_dock( $target_id ) {
		$target_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $target_id );
		return '<nav class="yby-global-dock" data-yby-global-dock aria-label="' . esc_attr__( 'Contact options', 'yby-core' ) . '">' .
			'<a class="yby-global-dock__button yby-global-dock__button--quote" href="#' . esc_attr( $target_id ) . '" data-yby-inquiry-trigger data-yby-modal-open="' . esc_attr( $target_id ) . '" data-yby-source="site_global_free_quote">' . esc_html__( 'Free Quote', 'yby-core' ) . '</a>' .
			'<a class="yby-global-dock__button yby-global-dock__button--whatsapp" href="#" data-yby-whatsapp-link data-yby-source="site_global_whatsapp" aria-label="' . esc_attr__( 'Contact us on WhatsApp', 'yby-core' ) . '">' . esc_html__( 'WhatsApp', 'yby-core' ) . '</a>' .
			'</nav>';
	}

	protected function get_global_preset_id() {
		$default = 'irrigation_quick_inquiry';
		$presets = $this->inquiry_manager->get_preset_manager()->get_presets();
		if ( isset( $presets[ $default ] ) && ! empty( $presets[ $default ]['enabled'] ) ) {
			return $default;
		}
		foreach ( $presets as $id => $preset ) {
			if ( ! empty( $preset['enabled'] ) ) return $id;
		}
		return $default;
	}

	protected function is_excluded_context() {
		$excluded = is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || ( function_exists( 'is_customize_preview' ) && is_customize_preview() );
		$builder_keys = array( 'elementor-preview', 'fl_builder', 'bricks', 'vc_editable', 'et_fb', 'oxygen_iframe' );
		foreach ( $builder_keys as $key ) {
			if ( isset( $_GET[ $key ] ) || isset( $_POST[ $key ] ) ) $excluded = true;
		}
		return (bool) apply_filters( 'yby_global_ui_excluded', $excluded );
	}
}
