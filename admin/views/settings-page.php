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

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row" colspan="2"><h2><?php esc_html_e( 'Email', 'yby-core' ); ?></h2></th>
				</tr>
				<tr>
					<th scope="row" colspan="2"><h3><?php esc_html_e( 'Lead Notification', 'yby-core' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="yby-lead-notification-primary-recipient-email"><?php esc_html_e( 'Primary Recipient Email', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-lead-notification-primary-recipient-email" name="yby_lead_notification_primary_recipient_email" type="email" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_primary_recipient_email() ); ?>">
					</td>
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
					<th scope="row"><label for="yby-whatsapp-number"><?php esc_html_e( 'WhatsApp Number', 'yby-core' ); ?></label></th>
					<td><input id="yby-whatsapp-number" name="yby_core_options[whatsapp_number]" type="text" class="regular-text" value="<?php echo esc_attr( $options['whatsapp_number'] ); ?>"></td>
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
					<th scope="row"><label for="yby-support-email"><?php esc_html_e( 'Support Email', 'yby-core' ); ?></label></th>
					<td><input id="yby-support-email" name="yby_core_options[support_email]" type="email" class="regular-text" value="<?php echo esc_attr( $options['support_email'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-crm-webhook-url"><?php esc_html_e( 'CRM Webhook URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-crm-webhook-url" name="yby_core_options[crm_webhook_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['crm_webhook_url'] ); ?>"></td>
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
					<th scope="row"><label for="yby-thank-you-url"><?php esc_html_e( 'Thank You URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-thank-you-url" name="yby_core_options[thank_you_url]" type="text" class="regular-text" value="<?php echo esc_attr( $options['thank_you_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-return-page-url"><?php esc_html_e( 'Return Page URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-return-page-url" name="yby_core_options[return_page_url]" type="text" class="regular-text" value="<?php echo esc_attr( $options['return_page_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-website-url"><?php esc_html_e( 'Website URL', 'yby-core' ); ?></label></th>
					<td><input id="yby-website-url" name="yby_core_options[website_url]" type="url" class="regular-text" value="<?php echo esc_attr( $options['website_url'] ); ?>"></td>
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
