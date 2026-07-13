<?php
/**
 * Project list page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Project Studio', 'yby-core' ); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . YBY_Project_CPT::post_type() ) ); ?>"><?php esc_html_e( 'Add Project', 'yby-core' ); ?></a>

	<p><?php esc_html_e( 'Project Studio manages existing runtime and visualizes Project configuration through YBY Core.', 'yby-core' ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Project Name', 'yby-core' ); ?></th>
				<th><?php esc_html_e( 'Country', 'yby-core' ); ?></th>
				<th><?php esc_html_e( 'Product Interest', 'yby-core' ); ?></th>
				<th><?php esc_html_e( 'Status', 'yby-core' ); ?></th>
				<th><?php esc_html_e( 'Tracking Group', 'yby-core' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'yby-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $projects ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No YBY Projects found yet.', 'yby-core' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $projects as $project_post ) : ?>
					<?php
					$overview = YBY_Project_CPT::get_overview_data( $project_post->ID );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'overview', $project_post->ID ) ); ?>"><?php echo esc_html( $overview['project_name'] ? $overview['project_name'] : $project_post->post_title ); ?></a></strong>
							<div class="row-actions">
								<span><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'overview', $project_post->ID ) ); ?>"><?php esc_html_e( 'Overview', 'yby-core' ); ?></a> | </span>
								<span><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'runtime', $project_post->ID ) ); ?>"><?php esc_html_e( 'Runtime Viewer', 'yby-core' ); ?></a> | </span>
								<span><a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $project_post->ID ) . '&action=edit' ) ); ?>"><?php esc_html_e( 'Edit Post', 'yby-core' ); ?></a></span>
							</div>
						</td>
						<td><?php echo esc_html( $overview['country'] ? $overview['country'] : '-' ); ?></td>
						<td><?php echo esc_html( $overview['product_interest'] ? $overview['product_interest'] : '-' ); ?></td>
						<td><?php echo esc_html( $overview['project_status'] ? $overview['project_status'] : $project_post->post_status ); ?></td>
						<td><?php echo esc_html( $overview['tracking_group'] ? $overview['tracking_group'] : '-' ); ?></td>
						<td><?php echo esc_html( get_the_modified_date( 'Y-m-d H:i', $project_post ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
