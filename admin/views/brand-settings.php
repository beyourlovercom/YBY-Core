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
				<tr><th scope="row"><label for="yby-brand-presentation-name"><?php esc_html_e( 'Presentation Name', 'yby-core' ); ?></label></th><td><input id="yby-brand-presentation-name" name="yby_brand_os_options[presentation_name]" type="text" class="regular-text" value="<?php echo esc_attr( $options['presentation_name'] ); ?>"><p class="description"><?php esc_html_e( 'Display-only name; Site Profile remains the identity authority.', 'yby-core' ); ?></p></td></tr>
				<tr>
					<th scope="row"><label for="yby-brand-logo-default"><?php esc_html_e( 'Default Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-default" name="yby_brand_os_options[logo_default]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_default'] ); ?>"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-brand-logo-default"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button></td>
				</tr>
				<tr><th scope="row"><label for="yby-brand-cta-background"><?php esc_html_e( 'CTA Background', 'yby-core' ); ?></label></th><td><input id="yby-brand-cta-background" name="yby_brand_os_options[cta_background]" type="text" class="regular-text" value="<?php echo esc_attr( $options['cta_background'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-button-text"><?php esc_html_e( 'Button Text Color', 'yby-core' ); ?></label></th><td><input id="yby-brand-button-text" name="yby_brand_os_options[button_text_color]" type="text" class="regular-text" value="<?php echo esc_attr( $options['button_text_color'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-heading-font"><?php esc_html_e( 'Heading Font Stack', 'yby-core' ); ?></label></th><td><input id="yby-brand-heading-font" name="yby_brand_os_options[heading_font]" type="text" class="regular-text" value="<?php echo esc_attr( $options['heading_font'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-body-font"><?php esc_html_e( 'Body Font Stack', 'yby-core' ); ?></label></th><td><input id="yby-brand-body-font" name="yby_brand_os_options[body_font]" type="text" class="regular-text" value="<?php echo esc_attr( $options['body_font'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-radius"><?php esc_html_e( 'Border Radius', 'yby-core' ); ?></label></th><td><input id="yby-brand-radius" name="yby_brand_os_options[border_radius]" type="text" class="regular-text" value="<?php echo esc_attr( $options['border_radius'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-opacity"><?php esc_html_e( 'Glass Opacity', 'yby-core' ); ?></label></th><td><input id="yby-brand-opacity" name="yby_brand_os_options[glass_opacity]" type="number" min="0" max="1" step="0.01" value="<?php echo esc_attr( $options['glass_opacity'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="yby-brand-subscribe-image"><?php esc_html_e( 'Default Subscribe Image', 'yby-core' ); ?></label></th><td><input id="yby-brand-subscribe-image" name="yby_brand_os_options[subscribe_image]" type="url" class="regular-text" value="<?php echo esc_attr( $options['subscribe_image'] ); ?>"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-brand-subscribe-image"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button></td></tr>
				<tr>
					<th scope="row"><label for="yby-brand-logo-white"><?php esc_html_e( 'White Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-white" name="yby_brand_os_options[logo_white]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_white'] ); ?>"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-brand-logo-white"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-logo-black"><?php esc_html_e( 'Black Logo', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-logo-black" name="yby_brand_os_options[logo_black]" type="url" class="regular-text" value="<?php echo esc_attr( $options['logo_black'] ); ?>"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-brand-logo-black"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-favicon"><?php esc_html_e( 'Favicon', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-favicon" name="yby_brand_os_options[favicon]" type="url" class="regular-text" value="<?php echo esc_attr( $options['favicon'] ); ?>"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-brand-favicon"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button></td>
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

		<?php
		$preview_style = sprintf(
			'--yby-preview-primary:%1$s;--yby-preview-accent:%2$s;--yby-preview-cta:%3$s;--yby-preview-button-text:%4$s;--yby-preview-radius:%5$s;--yby-preview-opacity:%6$s;',
			esc_attr( $theme['primaryColor'] ?? '' ),
			esc_attr( $theme['accentColor'] ?? '' ),
			esc_attr( $theme['ctaBackground'] ?? '' ),
			esc_attr( $theme['buttonTextColor'] ?? '' ),
			esc_attr( $theme['borderRadius'] ?? '12px' ),
			esc_attr( $theme['glassOpacity'] ?? '0.82' )
		);
		?>
		<section class="yby-brand-preview" aria-labelledby="yby-brand-preview-title" style="<?php echo esc_attr( $preview_style ); ?>">
			<h2 id="yby-brand-preview-title"><?php esc_html_e( 'Brand Theme Preview', 'yby-core' ); ?></h2>
			<p class="description">
				<?php
				echo esc_html(
				sprintf(
					/* translators: 1: presentation name, 2: site display name. */
					__( 'Presentation: %1$s · Site display: %2$s', 'yby-core' ),
					$theme['presentationName'] ?? '',
					$theme['brandName'] ?? ''
				)
				);
				?>
			</p>
			<div style="background:var(--yby-preview-primary);border-radius:var(--yby-preview-radius);padding:20px;opacity:var(--yby-preview-opacity);max-width:640px;">
				<?php if ( ! empty( $theme['logoDefault'] ) ) : ?><img src="<?php echo esc_url( $theme['logoDefault'] ); ?>" alt="" style="background:#fff;max-width:180px;max-height:56px;padding:8px;border-radius:var(--yby-preview-radius);">
				<?php else : ?><strong style="color:#fff;"><?php echo esc_html( $theme['brandName'] ?? '' ); ?></strong><?php endif; ?>
				<div style="display:flex;gap:8px;align-items:center;margin-top:16px;flex-wrap:wrap;">
					<span style="color:#fff;font-family:<?php echo esc_attr( $theme['bodyFont'] ?? '' ); ?>;">Typography preview</span>
					<span style="background:var(--yby-preview-cta, var(--yby-preview-accent));border-radius:var(--yby-preview-radius);padding:8px 12px;color:var(--yby-preview-button-text);font-family:<?php echo esc_attr( $theme['headingFont'] ?? '' ); ?>;">CTA / Button</span>
				</div>
			</div>
			<p class="description">Primary: <?php echo esc_html( $theme['primaryColor'] ?? '' ); ?> · Accent: <?php echo esc_html( $theme['accentColor'] ?? '' ); ?> · CTA: <?php echo esc_html( $theme['ctaBackground'] ?? '' ); ?> · Button text: <?php echo esc_html( $theme['buttonTextColor'] ?? '' ); ?> · Radius: <?php echo esc_html( $theme['borderRadius'] ?? '' ); ?> · Glass opacity: <?php echo esc_html( $theme['glassOpacity'] ?? '' ); ?></p>
			<?php if ( ! empty( $theme['subscribeImage'] ) ) : ?><p><img src="<?php echo esc_url( $theme['subscribeImage'] ); ?>" alt="" style="max-width:240px;max-height:120px;border-radius:var(--yby-preview-radius);"></p><?php endif; ?>
		</section>

		<p class="submit">
			<button type="submit" name="yby_brand_submit" class="button button-primary"><?php esc_html_e( 'Save Brand Settings', 'yby-core' ); ?></button>
		</p>
	</form>
</div>
