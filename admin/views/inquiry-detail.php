<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap yby-inquiry-admin">
	<h1><?php echo esc_html( $lead['case_id'] ); ?></h1>
	<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
	<?php if ( ! empty( $error ) ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
	<div class="yby-inquiry-columns">
		<section><h2><?php esc_html_e( 'Original Lead (read-only)', 'yby-core' ); ?></h2><dl>
		<?php foreach ( array( 'created_at','case_id','name','company','email','whatsapp','country','product_interest','quantity','project_details','source_component','source_page','source_preset','page_profile','source_url','utm_source','utm_medium','utm_campaign','utm_term','gclid','fbclid','custom_fields' ) as $field ) : ?><dt><?php echo esc_html( $field ); ?></dt><dd><?php echo nl2br( esc_html( (string) ( $lead[ $field ] ?? '' ) ) ); ?></dd><?php endforeach; ?>
		</dl></section>
		<section><h2><?php esc_html_e( 'Follow-up Management', 'yby-core' ); ?></h2><?php if ( YBY_Security::can_manage_leads() ) : ?><form method="post" class="yby-inquiry-detail-form">
		<?php wp_nonce_field( 'yby_inquiry_manage_' . $lead['id'], 'yby_inquiry_manage_nonce' ); ?><input type="hidden" name="yby_inquiry_manage_submit" value="1">
		<label><span>Status</span><select name="status"><?php foreach ( YBY_Lead_Management::STATUSES as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $lead['status'] ?: 'new', $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label>
		<label><span>Priority</span><select name="priority"><?php foreach ( YBY_Lead_Management::PRIORITIES as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $lead['priority'] ?: 'normal', $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label>
		<label><span>Owner</span><select name="owner_user_id"><?php foreach ( YBY_Security::owner_dropdown_options( $lead['owner_user_id'] ?? 0 ) as $owner ) : ?><option value="<?php echo esc_attr( $owner['id'] ); ?>" <?php selected( $lead['owner_user_id'] ?? 0, $owner['id'] ); ?>><?php echo esc_html( $owner['label'] ); ?></option><?php endforeach; ?></select></label>
		<label><span>Next follow-up</span><input type="datetime-local" name="next_follow_up_at" value="<?php echo esc_attr( YBY_Lead_Management::format_datetime_local( $lead['next_follow_up_at'] ?? '' ) ); ?>"></label>
		<label class="yby-inquiry-note"><span>Note</span><textarea name="note" rows="5"></textarea></label>
		<div class="yby-inquiry-actions"><?php if ( ! empty( $lead['archived_at'] ) ) : ?><button class="button" name="restore" value="1" type="submit">Restore</button><?php else : ?><button class="button" name="archived" value="1" type="submit">Archive</button><?php endif; ?><button class="button button-primary" type="submit">Save</button></div>
		</form><?php else : ?><dl><dt>Status</dt><dd><?php echo esc_html( $lead['status'] ?: 'new' ); ?></dd><dt>Priority</dt><dd><?php echo esc_html( $lead['priority'] ?: 'normal' ); ?></dd><dt>Owner</dt><dd><?php echo esc_html( YBY_Security::owner_display_name( $lead['owner_user_id'] ?? 0, ! YBY_Security::is_active_owner( $lead['owner_user_id'] ?? 0 ) ) ); ?></dd><dt>Next follow-up</dt><dd><?php echo esc_html( $lead['next_follow_up_at'] ?: '-' ); ?></dd></dl><?php endif; ?><h2><?php esc_html_e( 'Activity Timeline', 'yby-core' ); ?></h2><?php foreach ( $lead['activities'] as $activity ) : ?><article><strong><?php echo esc_html( $activity['activity_type'] ); ?></strong> <time><?php echo esc_html( $activity['created_at'] ); ?></time><p><?php echo esc_html( $activity['content'] ); ?></p></article><?php endforeach; ?></section>
	</div>
</div>
