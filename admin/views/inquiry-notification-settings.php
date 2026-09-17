<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<h1>Andy Core</h1>
	<h2 class="nav-tab-wrapper">
		<?php foreach ( YBY_Admin::settings_tabs() as $key => $label ) : ?>
			<a class="nav-tab <?php echo 'inquiry-notification' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</h2>
	<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
	<form method="post">
		<?php wp_nonce_field( 'yby_inquiry_notification_save', 'yby_inquiry_notification_nonce' ); ?>
		<input type="hidden" name="yby_inquiry_notification_submit" value="1">
		<h2>WhatsApp</h2>
		<p class="description">统一管理网站 WhatsApp 号码与默认消息模板；悬浮询盘等入口继续复用同一 Runtime。</p>
		<table class="form-table" role="presentation"><tbody>
		<tr><th scope="row"><label for="yby-whatsapp-number">WhatsApp Number</label></th><td><input id="yby-whatsapp-number" name="yby_core_options[whatsapp_number]" type="text" class="regular-text" value="<?php echo esc_attr( $options['whatsapp_number'] ); ?>"></td></tr>
		<tr><th scope="row"><label for="yby-whatsapp-message-template">WhatsApp Message Template</label></th><td>
			<textarea id="yby-whatsapp-message-template" name="yby_core_options[whatsapp_message_template]" class="large-text code" rows="8"><?php echo esc_textarea( $options['whatsapp_message_template'] ); ?></textarea>
			<p class="description">Supported placeholders: {brand_name}, {case_id}, {country}, {crop}, {farm_size}, {water_source}, {recommended_system}, {estimated_range}. Leave empty to use the shared portable default.</p>
		</td></tr>
		</tbody></table>
		<h2>Email Notification</h2>
		<table class="form-table" role="presentation"><tbody>
		<tr><th scope="row"><label for="yby-lead-notification-primary-recipient-email">Primary Recipient Email</label></th><td><input id="yby-lead-notification-primary-recipient-email" name="yby_lead_notification_primary_recipient_email" type="email" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_primary_recipient_email() ); ?>"></td></tr>
		<tr><th scope="row"><label for="yby-lead-notification-cc-recipient-emails">CC Recipient Emails</label></th><td><input id="yby-lead-notification-cc-recipient-emails" name="yby_lead_notification_cc_recipient_emails" type="text" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_cc_recipient_emails() ); ?>"><p class="description">Comma-separated email addresses.</p></td></tr>
		<tr><th scope="row"><label for="yby-lead-notification-bcc-recipient-emails">BCC Recipient Emails</label></th><td><input id="yby-lead-notification-bcc-recipient-emails" name="yby_lead_notification_bcc_recipient_emails" type="text" class="regular-text" value="<?php echo esc_attr( YBY_Config::get_lead_notification_bcc_recipient_emails() ); ?>"><p class="description">Reserved for archive / ERP / AI.</p></td></tr>
		<tr><th scope="row"><label for="yby-lead-notification-reply-to-policy">Reply-To Policy</label></th><td><select id="yby-lead-notification-reply-to-policy" name="yby_lead_notification_reply_to_policy">
			<option value="auto" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'auto' ); ?>>Auto</option>
			<option value="customer_email_only" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'customer_email_only' ); ?>>Customer Email Only</option>
			<option value="disabled" <?php selected( YBY_Config::get_lead_notification_reply_to_policy(), 'disabled' ); ?>>Disabled</option>
		</select></td></tr>
		<tr><th scope="row"><label for="yby-lead-notification-subject-template">Subject Template</label></th><td><input id="yby-lead-notification-subject-template" name="yby_core_options[lead_notification_subject_template]" type="text" class="regular-text" value="<?php echo esc_attr( $options['lead_notification_subject_template'] ); ?>"><p class="description">Use placeholders like {case_id}, {name}, {country}, {crop}, {farm_size}, {water_source}, {recommended_system}, {project_id}, {product_interest}, and {source_component}.</p></td></tr>
		<tr><th scope="row"><label for="yby-inquiry-email-title">Inquiry Email Title</label></th><td><input id="yby-inquiry-email-title" name="yby_core_options[inquiry_email_title]" type="text" class="regular-text" value="<?php echo esc_attr( $options['inquiry_email_title'] ); ?>"></td></tr>
		<tr><th scope="row"><label for="yby-email-company-name">Legacy Email Company Name Override</label></th><td><input id="yby-email-company-name" name="yby_core_options[email_company_name]" type="text" class="regular-text" value="<?php echo esc_attr( $options['email_company_name'] ); ?>"><p class="description">Optional backward-compatible override. Leave blank to use the Site Brand Name.</p></td></tr>
		<tr><th scope="row"><label for="yby-email-company-website">Legacy Email Company Website Override</label></th><td><input id="yby-email-company-website" name="yby_core_options[email_company_website]" type="url" class="regular-text" value="<?php echo esc_attr( $options['email_company_website'] ); ?>"><p class="description">Optional backward-compatible override. Leave blank to use the Canonical Website URL.</p></td></tr>
		</tbody></table>
		<p class="submit"><button type="submit" class="button button-primary">保存询盘通知设置</button></p>
	</form>
</div>
