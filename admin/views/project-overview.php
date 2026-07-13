<?php
/**
 * Project overview page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $overview_data['project_name'] ? $overview_data['project_name'] : get_the_title( $post ) ); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'runtime', $post_id ) ); ?>"><?php esc_html_e( 'Runtime Viewer', 'yby-core' ); ?></a>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $post_id ) . '&action=edit' ) ); ?>"><?php esc_html_e( 'Edit Project', 'yby-core' ); ?></a>

	<p><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'list' ) ); ?>">&larr; <?php esc_html_e( 'Back to Project Studio', 'yby-core' ); ?></a></p>

	<h2><?php esc_html_e( 'Project Overview', 'yby-core' ); ?></h2>
	<table class="widefat striped">
		<tbody>
			<?php foreach ( YBY_Project_CPT::overview_fields() as $field_key => $field_config ) : ?>
				<tr>
					<th style="width: 220px;"><?php echo esc_html( $field_config['label'] ); ?></th>
					<td><?php echo esc_html( ! empty( $overview_data[ $field_key ] ) ? $overview_data[ $field_key ] : '-' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Runtime Compatibility Summary', 'yby-core' ); ?></h2>
	<table class="widefat striped">
		<tbody>
			<tr>
				<th style="width: 220px;"><?php esc_html_e( 'YBYProject', 'yby-core' ); ?></th>
				<td><?php echo esc_html( ! empty( $runtime_map['project']['projectId'] ) || ! empty( $runtime_map['project']['projectName'] ) ? __( 'Mapped', 'yby-core' ) : __( 'Fallback / Empty', 'yby-core' ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'YBYContent', 'yby-core' ); ?></th>
				<td><?php echo esc_html( ! empty( $runtime_map['content']['sections'] ) ? __( 'Mapped', 'yby-core' ) : __( 'Fallback / Empty', 'yby-core' ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'YBYTemplate', 'yby-core' ); ?></th>
				<td><?php echo esc_html( ! empty( $runtime_map['template']['pageMap'] ) ? __( 'Mapped', 'yby-core' ) : __( 'Fallback / Empty', 'yby-core' ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Project Specification', 'yby-core' ); ?></th>
				<td><?php esc_html_e( 'Project Studio reads approved fields only and does not redefine the specification.', 'yby-core' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>
