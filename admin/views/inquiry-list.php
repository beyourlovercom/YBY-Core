<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap yby-inquiry-admin">
	<h1><?php esc_html_e( 'Inquiry', 'yby-core' ); ?></h1>
	<form method="get" class="yby-inquiry-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( YBY_Inquiry_Admin::page_slug() ); ?>">
	<input type="search" name="s" minlength="2" placeholder="<?php esc_attr_e( 'Search Case ID, name, email, WhatsApp, company', 'yby-core' ); ?>" value="<?php echo esc_attr( $args['search'] ); ?>">
		<select name="status"><option value=""><?php esc_html_e( 'All statuses', 'yby-core' ); ?></option><?php foreach ( YBY_Lead_Management::STATUSES as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $args['status'], $status ); ?>><?php echo esc_html( $status ); ?></option><?php endforeach; ?></select>
		<select name="priority"><option value=""><?php esc_html_e( 'All priorities', 'yby-core' ); ?></option><?php foreach ( YBY_Lead_Management::PRIORITIES as $priority ) : ?><option value="<?php echo esc_attr( $priority ); ?>" <?php selected( $args['priority'], $priority ); ?>><?php echo esc_html( $priority ); ?></option><?php endforeach; ?></select>
		<select name="owner_user_id"><option value="0">All Owners</option><?php foreach ( YBY_Security::active_owner_ids() as $user_id ) : $user = get_user_by( 'id', $user_id ); ?><option value="<?php echo esc_attr( $user_id ); ?>" <?php selected( $args['owner_user_id'], $user_id ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select>
		<input name="country" type="text" placeholder="Country" value="<?php echo esc_attr( $args['country'] ); ?>">
		<input name="source_preset" type="text" placeholder="Preset" value="<?php echo esc_attr( $args['source_preset'] ); ?>">
		<input name="page_profile" type="text" placeholder="Profile" value="<?php echo esc_attr( $args['page_profile'] ); ?>">
		<input name="date_from" type="date" value="<?php echo esc_attr( $args['date_from'] ); ?>"><input name="date_to" type="date" value="<?php echo esc_attr( $args['date_to'] ); ?>">
		<select name="archived"><option value="">All records</option><option value="active" <?php selected( $args['archived'], 'active' ); ?>>Active</option><option value="archived" <?php selected( $args['archived'], 'archived' ); ?>>Archived</option></select>
		<select name="per_page"><?php foreach ( array( 30, 50, 100 ) as $size ) : ?><option value="<?php echo esc_attr( $size ); ?>" <?php selected( $data['per_page'], $size ); ?>><?php echo esc_html( $size ); ?></option><?php endforeach; ?></select>
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Search', 'yby-core' ); ?></button>
	</form>
	<table class="widefat striped"><thead><tr><th>Case ID</th><th><?php esc_html_e( 'Submitted', 'yby-core' ); ?></th><th><?php esc_html_e( 'Contact', 'yby-core' ); ?></th><th><?php esc_html_e( 'Company', 'yby-core' ); ?></th><th><?php esc_html_e( 'Country', 'yby-core' ); ?></th><th><?php esc_html_e( 'Source', 'yby-core' ); ?></th><th><?php esc_html_e( 'Status', 'yby-core' ); ?></th><th><?php esc_html_e( 'Owner', 'yby-core' ); ?></th><th><?php esc_html_e( 'Last follow-up', 'yby-core' ); ?></th><th><?php esc_html_e( 'Action', 'yby-core' ); ?></th></tr></thead><tbody>
	<?php foreach ( $data['items'] as $item ) : ?><tr>
		<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Inquiry_Admin::page_slug(), 'lead_id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item['case_id'] ); ?></a></td>
		<td><?php echo esc_html( $item['created_at'] ); ?></td><td><?php echo esc_html( $item['name'] ); ?></td><td><?php echo esc_html( $item['company'] ); ?></td><td><?php echo esc_html( $item['country'] ); ?></td><td><?php echo esc_html( $item['source_preset'] . ' / ' . $item['page_profile'] ); ?></td><td><?php echo esc_html( $item['status'] ?: 'new' ); ?></td><td><?php echo esc_html( YBY_Security::owner_display_name( $item['owner_user_id'] ?? 0, ! YBY_Security::is_active_owner( $item['owner_user_id'] ?? 0 ) ) ); ?></td><td><?php echo esc_html( $item['next_follow_up_at'] ?: '-' ); ?></td>
		<td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Inquiry_Admin::page_slug(), 'lead_id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'View', 'yby-core' ); ?></a></td>
	</tr><?php endforeach; ?></tbody></table>
	<p><?php echo esc_html( sprintf( __( '%d inquiries', 'yby-core' ), $data['total'] ) ); ?></p>
	<?php if ( $data['total'] > $data['per_page'] ) : ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $data['page'], 'total' => (int) ceil( $data['total'] / $data['per_page'] ), 'type' => 'plain' ) ) ); ?></div></div><?php endif; ?>
</div>
