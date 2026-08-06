<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap yby-inquiry-admin"><h1><?php echo esc_html( $lead['case_id'] ); ?></h1>
	<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
	<div class="yby-inquiry-columns"><section><h2><?php esc_html_e( 'Original Lead (read-only)', 'yby-core' ); ?></h2><dl>
	<?php foreach ( array( 'created_at','case_id','name','company','email','whatsapp','country','product_interest','quantity','project_details','source_component','source_page','source_preset','page_profile','source_url','utm_source','utm_medium','utm_campaign','utm_term','gclid','fbclid','custom_fields' ) as $field ) : ?><dt><?php echo esc_html( $field ); ?></dt><dd><?php echo nl2br( esc_html( (string) ( $lead[ $field ] ?? '' ) ) ); ?></dd><?php endforeach; ?></dl></section>
	<section><h2><?php esc_html_e( 'Follow-up Management', 'yby-core' ); ?></h2><form method="post"><?php wp_nonce_field( 'yby_inquiry_manage_' . $lead['id'], 'yby_inquiry_manage_nonce' ); ?><input type="hidden" name="yby_inquiry_manage_submit" value="1">
		<p><label>Status <select name="status"><?php foreach ( YBY_Lead_Management::STATUSES as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $lead['status'] ?: 'new', $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label></p>
		<p><label>Priority <select name="priority"><?php foreach ( YBY_Lead_Management::PRIORITIES as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $lead['priority'] ?: 'normal', $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label></p>
		<p><label>Owner User ID <input type="number" min="0" name="owner_user_id" value="<?php echo esc_attr( $lead['owner_user_id'] ?? 0 ); ?>"></label></p>
		<p><label>Next follow-up <input type="datetime-local" name="next_follow_up_at" value="<?php echo esc_attr( $lead['next_follow_up_at'] ?? '' ); ?>"></label></p>
		<p><label>Note<br><textarea name="note" rows="5"></textarea></label></p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save', 'yby-core' ); ?></button></form>
		<h2><?php esc_html_e( 'Activity Timeline', 'yby-core' ); ?></h2><?php foreach ( $lead['activities'] as $activity ) : ?><article><strong><?php echo esc_html( $activity['activity_type'] ); ?></strong> <time><?php echo esc_html( $activity['created_at'] ); ?></time><p><?php echo esc_html( $activity['content'] ); ?></p></article><?php endforeach; ?>
	</section></div>
</div>
