<?php
/**
 * Settings page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'YBY Core', 'yby-core' ); ?></h1>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'yby_core_save_settings', 'yby_core_nonce' ); ?>

		<h2><?php esc_html_e( 'Site Identity', 'yby-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-site-brand-key"><?php esc_html_e( 'Site Brand Key', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-site-brand-key" name="yby_core_options[site_brand_key]" type="text" class="regular-text" value="<?php echo esc_attr( $options['site_brand_key'] ); ?>">
						<p class="description"><?php esc_html_e( 'Stable machine identifier stored in the Lead brand column.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-site-brand-name"><?php esc_html_e( 'Site Brand Name', 'yby-core' ); ?></label></th>
					<td><input id="yby-site-brand-name" name="yby_core_options[site_brand_name]" type="text" class="regular-text" value="<?php echo esc_attr( $options['site_brand_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-case-id-brand-code"><?php esc_html_e( 'Case ID Brand Code', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-case-id-brand-code" name="yby_core_options[case_id_brand_code]" type="text" class="regular-text" value="<?php echo esc_attr( $options['case_id_brand_code'] ); ?>">
						<p class="description"><?php esc_html_e( 'Used by server-generated Case IDs such as YBY-CORE-YYYYMMDD-XXXXXX.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-website-url"><?php esc_html_e( 'Canonical Website URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-website-url" name="yby_core_options[website_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['website_url'] ); ?>"></td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Brand Presentation', 'yby-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-brand-primary-color"><?php esc_html_e( 'Primary Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-primary-color" name="yby_core_options[brand_primary_color]" type="color" value="<?php echo esc_attr( $options['brand_primary_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-primary-text-color"><?php esc_html_e( 'Primary Text Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-primary-text-color" name="yby_core_options[brand_primary_text_color]" type="color" value="<?php echo esc_attr( $options['brand_primary_text_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-secondary-color"><?php esc_html_e( 'Secondary Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-secondary-color" name="yby_core_options[brand_secondary_color]" type="color" value="<?php echo esc_attr( $options['brand_secondary_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-surface-color"><?php esc_html_e( 'Surface Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-surface-color" name="yby_core_options[brand_surface_color]" type="color" value="<?php echo esc_attr( $options['brand_surface_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-text-color"><?php esc_html_e( 'Text Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-text-color" name="yby_core_options[brand_text_color]" type="color" value="<?php echo esc_attr( $options['brand_text_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-muted-text-color"><?php esc_html_e( 'Muted Text Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-muted-text-color" name="yby_core_options[brand_muted_text_color]" type="color" value="<?php echo esc_attr( $options['brand_muted_text_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-brand-border-color"><?php esc_html_e( 'Border Color', 'yby-core' ); ?></label></th>
					<td><input id="yby-brand-border-color" name="yby_core_options[brand_border_color]" type="color" value="<?php echo esc_attr( $options['brand_border_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-logo-url"><?php esc_html_e( 'Default Logo', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-email-logo-url" name="yby_core_options[email_logo_url]" type="url" class="regular-text yby-media-url" value="<?php echo esc_attr( $options['email_logo_url'] ); ?>" data-yby-media-target="yby-email-logo-url">
						<button type="button" class="button yby-media-button" data-yby-media-target="yby-email-logo-url"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-reverse-logo-url"><?php esc_html_e( 'Reverse / Light Logo', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-email-reverse-logo-url" name="yby_core_options[email_reverse_logo_url]" type="url" class="regular-text yby-media-url" value="<?php echo esc_attr( $options['email_reverse_logo_url'] ); ?>" data-yby-media-target="yby-email-reverse-logo-url">
						<button type="button" class="button yby-media-button" data-yby-media-target="yby-email-reverse-logo-url"><?php esc_html_e( 'Select from Media Library', 'yby-core' ); ?></button>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-company-phone"><?php esc_html_e( 'Company Phone', 'yby-core' ); ?></label></th>
					<td><input id="yby-email-company-phone" name="yby_core_options[email_company_phone]" type="text" class="regular-text" value="<?php echo esc_attr( $options['email_company_phone'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-company-whatsapp"><?php esc_html_e( 'Company WhatsApp', 'yby-core' ); ?></label></th>
					<td><input id="yby-email-company-whatsapp" name="yby_core_options[email_company_whatsapp]" type="text" class="regular-text" value="<?php echo esc_attr( $options['email_company_whatsapp'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-support-email"><?php esc_html_e( 'Support Email', 'yby-core' ); ?></label></th>
					<td><input id="yby-support-email" name="yby_core_options[support_email]" type="email" class="regular-text" value="<?php echo esc_attr( $options['support_email'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-footer-copyright"><?php esc_html_e( 'Footer Copyright', 'yby-core' ); ?></label></th>
					<td><input id="yby-email-footer-copyright" name="yby_core_options[email_footer_copyright]" type="text" class="regular-text" value="<?php echo esc_attr( $options['email_footer_copyright'] ); ?>"></td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Inquiry Experience', 'yby-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-thank-you-url"><?php esc_html_e( 'Thank You URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-thank-you-url" name="yby_core_options[thank_you_url]" type="text" class="regular-text" value="<?php echo esc_attr( $options['thank_you_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-return-page-url"><?php esc_html_e( 'Return Page URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-return-page-url" name="yby_core_options[return_page_url]" type="text" class="regular-text" value="<?php echo esc_attr( $options['return_page_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-catalog-url"><?php esc_html_e( 'Catalog URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-catalog-url" name="yby_core_options[catalog_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['catalog_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-youtube-video-id"><?php esc_html_e( 'YouTube Video ID', 'yby-core' ); ?></label></th>
					<td><input id="yby-youtube-video-id" name="yby_core_options[youtube_video_id]" type="text" class="regular-text" value="<?php echo esc_attr( $options['youtube_video_id'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-default-country"><?php esc_html_e( 'Default Country', 'yby-core' ); ?></label></th>
					<td><input id="yby-default-country" name="yby_core_options[default_country]" type="text" class="regular-text" value="<?php echo esc_attr( $options['default_country'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-default-product-interest"><?php esc_html_e( 'Default Product Interest', 'yby-core' ); ?></label></th>
					<td><input id="yby-default-product-interest" name="yby_core_options[default_product_interest]" type="text" class="regular-text" value="<?php echo esc_attr( $options['default_product_interest'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-whatsapp-number"><?php esc_html_e( 'WhatsApp Number', 'yby-core' ); ?></label></th>
					<td><input id="yby-whatsapp-number" name="yby_core_options[whatsapp_number]" type="text" class="regular-text" value="<?php echo esc_attr( $options['whatsapp_number'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-whatsapp-message-template"><?php esc_html_e( 'WhatsApp Message Template', 'yby-core' ); ?></label></th>
					<td>
						<textarea id="yby-whatsapp-message-template" name="yby_core_options[whatsapp_message_template]" class="large-text code" rows="8"><?php echo esc_textarea( $options['whatsapp_message_template'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Supported placeholders: {brand_name}, {case_id}, {country}, {crop}, {farm_size}, {water_source}, {recommended_system}, {estimated_range}. Leave empty to use the shared portable default.', 'yby-core' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Email Notification', 'yby-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-lead-notification-primary-recipient-email"><?php esc_html_e( 'Primary Recipient Email', 'yby-core' ); ?></label></th>
					<td><input id="yby-lead-notification-primary-recipient-email" name="yby_lead_notification_primary_recipient_email" type="email" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_primary_recipient_email() ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-lead-notification-cc-recipient-emails"><?php esc_html_e( 'CC Recipient Emails', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-lead-notification-cc-recipient-emails" name="yby_lead_notification_cc_recipient_emails" type="text" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_cc_recipient_emails() ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated email addresses.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-lead-notification-bcc-recipient-emails"><?php esc_html_e( 'BCC Recipient Emails', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-lead-notification-bcc-recipient-emails" name="yby_lead_notification_bcc_recipient_emails" type="text" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_bcc_recipient_emails() ); ?>">
						<p class="description"><?php esc_html_e( 'Reserved for archive / ERP / AI.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-lead-notification-reply-to-policy"><?php esc_html_e( 'Reply-To Policy', 'yby-core' ); ?></label></th>
					<td>
						<select id="yby-lead-notification-reply-to-policy" name="yby_lead_notification_reply_to_policy">
							<option value="auto" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'auto' ); ?>><?php esc_html_e( 'Auto', 'yby-core' ); ?></option>
							<option value="customer_email_only" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'customer_email_only' ); ?>><?php esc_html_e( 'Customer Email Only', 'yby-core' ); ?></option>
							<option value="disabled" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'yby-core' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-lead-notification-subject-template"><?php esc_html_e( 'Subject Template', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-lead-notification-subject-template" name="yby_core_options[lead_notification_subject_template]" type="text" class="regular-text" value="<?php echo esc_attr( $options['lead_notification_subject_template'] ); ?>">
						<p class="description"><?php esc_html_e( 'Use placeholders like {case_id}, {name}, {country}, {crop}, {farm_size}, {water_source}, {recommended_system}, {project_id}, {product_interest}, and {source_component}.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-inquiry-email-title"><?php esc_html_e( 'Inquiry Email Title', 'yby-core' ); ?></label></th>
					<td><input id="yby-inquiry-email-title" name="yby_core_options[inquiry_email_title]" type="text" class="regular-text" value="<?php echo esc_attr( $options['inquiry_email_title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-company-name"><?php esc_html_e( 'Legacy Email Company Name Override', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-email-company-name" name="yby_core_options[email_company_name]" type="text" class="regular-text" value="<?php echo esc_attr( $options['email_company_name'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional backward-compatible override. Leave blank to use the Site Brand Name.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-email-company-website"><?php esc_html_e( 'Legacy Email Company Website Override', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-email-company-website" name="yby_core_options[email_company_website]" type="url" class="regular-text" value="<?php echo esc_attr( $options['email_company_website'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional backward-compatible override. Leave blank to use the Canonical Website URL.', 'yby-core' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Core Runtime', 'yby-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="yby-crm-webhook-url"><?php esc_html_e( 'CRM Webhook URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-crm-webhook-url" name="yby_core_options[crm_webhook_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['crm_webhook_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Tracking', 'yby-core' ); ?></th>
					<td><label><input name="yby_core_options[enable_tracking]" type="checkbox" value="1" <?php checked( ! empty( $options['enable_tracking'] ) ); ?>> <?php esc_html_e( 'Enabled', 'yby-core' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Case ID', 'yby-core' ); ?></th>
					<td><label><input name="yby_core_options[enable_case_id]" type="checkbox" value="1" <?php checked( ! empty( $options['enable_case_id'] ) ); ?>> <?php esc_html_e( 'Enabled', 'yby-core' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable CRM Webhook', 'yby-core' ); ?></th>
					<td><label><input name="yby_core_options[enable_crm_webhook]" type="checkbox" value="1" <?php checked( ! empty( $options['enable_crm_webhook'] ) ); ?>> <?php esc_html_e( 'Enabled', 'yby-core' ); ?></label></td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" name="yby_core_submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'yby-core' ); ?></button>
		</p>
	</form>
</div>
