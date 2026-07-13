<?php
/**
 * Brand settings page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Brand', 'yby-core' ); ?></h1>
	<p><?php echo esc_html__( 'Manage approved YBY brand assets, system font stacks, colors, and brand document references.', 'yby-core' ); ?></p>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'yby_brand_os_save_settings', 'yby_brand_os_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-brand-logo-default"><?php esc_html_e( 'Default Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-default" name="yby_brand_os_options[logo_default]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_default'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-logo-white"><?php esc_html_e( 'White Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-white" name="yby_brand_os_options[logo_white]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_white'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-logo-black"><?php esc_html_e( 'Black Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-black" name="yby_brand_os_options[logo_black]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_black'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-favicon"><?php esc_html_e( 'Favicon', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-favicon" name="yby_brand_os_options[favicon]" type="url" class="regular-text" value="<?php echo esc_attr( $options['favicon'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-primary-color"><?php esc_html_e( 'Primary Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-primary-color" name="yby_brand_os_options[primary_color]" type="text" class="regular-text" value="<?php echo esc_attr( $options['primary_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-secondary-color"><?php esc_html_e( 'Secondary Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-secondary-color" name="yby_brand_os_options[secondary_color]" type="text" class="regular-text" value="<?php echo esc_attr( $options['secondary_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-accent-color"><?php esc_html_e( 'Accent Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-accent-color" name="yby_brand_os_options[accent_color]" type="text" class="regular-text" value="<?php echo esc_attr( $options['accent_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-font-primary"><?php esc_html_e( 'Primary Font Stack', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-brand-font-primary" name="yby_brand_os_options[font_primary]" type="text" class="regular-text" value="<?php echo esc_attr( $options['font_primary'] ); ?>">
						<p class="description"><?php esc_html_e( 'Use local or system font stacks only. Google Fonts are prohibited by default.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-font-secondary"><?php esc_html_e( 'Secondary Font Stack', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-font-secondary" name="yby_brand_os_options[font_secondary]" type="text" class="regular-text" value="<?php echo esc_attr( $options['font_secondary'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-document-url"><?php esc_html_e( 'Brand Document URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-document-url" name="yby_brand_os_options[brand_document_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['brand_document_url'] ); ?>"></td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" name="yby_brand_submit" class="button button-primary"><?php esc_html_e( 'Save Brand Settings', 'yby-core' ); ?></button>
		</p>
	</form>
</div>
