<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap yby-inquiry-admin">
	<h1><?php esc_html_e( 'Inquiry', 'yby-core' ); ?></h1>
	<form method="get" class="yby-inquiry-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( YBY_Inquiry_Admin::page_slug() ); ?>">
		<input type="search" name="s" minlength="2" placeholder="<?php esc_attr_e( 'Search Case ID, name, email, WhatsApp, company', 'yby-core' ); ?>" value="<?php echo esc_attr( $args['search'] ); ?>">
		<select name="status"><option value=""><?php esc_html_e( 'All statuses', 'yby-core' ); ?></option><?php foreach ( YBY_Lead_Management::STATUSES as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $args['status'], $status ); ?>><?php echo esc_html( $status ); ?></option><?php endforeach; ?></select>
		<select name="priority"><option value=""><?php esc_html_e( 'All priorities', 'yby-core' ); ?></option><?php foreach ( YBY_Lead_Management::PRIORITIES as $priority ) : ?><option value="<?php echo esc_attr( $priority ); ?>" <?php selected( $args['priority'], $priority ); ?>><?php echo esc_html( $priority ); ?></option><?php endforeach; ?></select>
		<select name="per_page"><?php foreach ( array( 30, 50, 100 ) as $size ) : ?><option value="<?php echo esc_attr( $size ); ?>" <?php selected( $data['per_page'], $size ); ?>><?php echo esc_html( $size ); ?></option><?php endforeach; ?></select>
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Search', 'yby-core' ); ?></button>
	</form>
	<table class="widefat striped"><thead><tr><th>Case ID</th><th><?php esc_html_e( 'Submitted', 'yby-core' ); ?></th><th><?php esc_html_e( 'Contact', 'yby-core' ); ?></th><th><?php esc_html_e( 'Company', 'yby-core' ); ?></th><th><?php esc_html_e( 'Country', 'yby-core' ); ?></th><th><?php esc_html_e( 'Source', 'yby-core' ); ?></th><th><?php esc_html_e( 'Status', 'yby-core' ); ?></th><th><?php esc_html_e( 'Action', 'yby-core' ); ?></th></tr></thead><tbody>
	<?php foreach ( $data['items'] as $item ) : ?><tr>
		<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Inquiry_Admin::page_slug(), 'lead_id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item['case_id'] ); ?></a></td>
		<td><?php echo esc_html( $item['created_at'] ); ?></td><td><?php echo esc_html( $item['name'] ); ?></td><td><?php echo esc_html( $item['company'] ); ?></td><td><?php echo esc_html( $item['country'] ); ?></td><td><?php echo esc_html( $item['source_preset'] . ' / ' . $item['page_profile'] ); ?></td><td><?php echo esc_html( $item['status'] ?: 'new' ); ?></td>
		<td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Inquiry_Admin::page_slug(), 'lead_id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'View', 'yby-core' ); ?></a></td>
	</tr><?php endforeach; ?></tbody></table>
	<p><?php echo esc_html( sprintf( __( '%d inquiries', 'yby-core' ), $data['total'] ) ); ?></p>
</div>
