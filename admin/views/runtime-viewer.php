<?php
/**
 * Runtime viewer page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Runtime Viewer', 'yby-core' ); ?></h1>
	<p><?php echo esc_html( $overview['project_name'] ? $overview['project_name'] : get_the_title( $post ) ); ?></p>
	<p><a href="<?php echo esc_url( YBY_Project_Studio::studio_url( 'overview', $post_id ) ); ?>">&larr; <?php esc_html_e( 'Back to Project Overview', 'yby-core' ); ?></a></p>

	<?php foreach ( $runtime_data as $runtime_name => $runtime_value ) : ?>
		<h2><?php echo esc_html( $runtime_name ); ?></h2>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:16px;overflow:auto;"><?php echo esc_html( wp_json_encode( $runtime_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
	<?php endforeach; ?>
</div>
