<?php
/**
 * Social Login provider overview and Google settings.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_url = admin_url( 'admin.php?page=' . YBY_Social_Login_Admin::page_slug() );
?>
<div class="wrap">
	<?php if ( '' !== $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'google' !== $provider ) : ?>
		<h1><?php esc_html_e( 'Social Login', 'yby-core' ); ?></h1>
		<p><?php esc_html_e( 'Configure sign-in providers for this site. Authentication flows are introduced separately after their administration foundation is approved.', 'yby-core' ); ?></p>

		<div class="card">
			<h2><?php esc_html_e( 'Google', 'yby-core' ); ?></h2>
			<p><strong><?php echo esc_html( YBY_Social_Login::provider_status( 'google' ) ); ?></strong></p>
			<p><a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'provider', 'google', $page_url ) ); ?>"><?php esc_html_e( 'Settings', 'yby-core' ); ?></a></p>
		</div>

		<?php
		$planned_providers = array(
			'Facebook' => 'v1.6.0',
			'X'        => 'v1.7.0',
			'TikTok'   => 'v1.8.0',
		);
		foreach ( $planned_providers as $name => $version ) :
			?>
			<div class="card">
				<h2><?php echo esc_html( $name ); ?></h2>
				<p><strong><?php esc_html_e( 'Coming Soon', 'yby-core' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: %s: planned plugin version. */
						esc_html__( 'Planned for %s', 'yby-core' ),
						esc_html( $version )
					);
					?>
				</p>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<h1><?php esc_html_e( 'Google Login', 'yby-core' ); ?></h1>
		<p><a href="<?php echo esc_url( $page_url ); ?>">&larr; <?php esc_html_e( 'Back to Social Login', 'yby-core' ); ?></a></p>

		<form method="post" action="<?php echo esc_url( add_query_arg( 'provider', 'google', $page_url ) ); ?>">
			<?php wp_nonce_field( 'yby_social_login_save_google', 'yby_social_login_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Google Login', 'yby-core' ); ?></th>
					<td><label><input type="checkbox" name="yby_social_login_options[google][enabled]" value="1" <?php checked( $google_options['enabled'] ); ?>> <?php esc_html_e( 'Enabled', 'yby-core' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-client-id"><?php esc_html_e( 'Client ID', 'yby-core' ); ?></label></th>
					<td><input id="yby-google-client-id" class="regular-text" type="text" maxlength="255" name="yby_social_login_options[google][client_id]" value="<?php echo esc_attr( $google_options['client_id'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Select account on each login', 'yby-core' ); ?></th>
					<td>
						<label><input type="checkbox" name="yby_social_login_options[google][select_account]" value="1" <?php checked( $google_options['select_account'] ); ?>> <?php esc_html_e( 'Select account on each login', 'yby-core' ); ?></label>
						<p class="description"><?php esc_html_e( 'Allow users to choose which Google account to use.', 'yby-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-username-prefix"><?php esc_html_e( 'Username prefix', 'yby-core' ); ?></label></th>
					<td><input id="yby-google-username-prefix" type="text" maxlength="32" name="yby_social_login_options[google][username_prefix]" value="<?php echo esc_attr( $google_options['username_prefix'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-fallback-prefix"><?php esc_html_e( 'Fallback username prefix', 'yby-core' ); ?></label></th>
					<td><input id="yby-google-fallback-prefix" type="text" maxlength="32" name="yby_social_login_options[google][fallback_prefix]" value="<?php echo esc_attr( $google_options['fallback_prefix'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-image-size"><?php esc_html_e( 'Profile image size', 'yby-core' ); ?></label></th>
					<td>
						<select id="yby-google-image-size" name="yby_social_login_options[google][profile_image_size]">
							<?php foreach ( array( 'small', 'default', 'medium', 'large', 'extra_large', 'original' ) as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $google_options['profile_image_size'], $size ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $size ) ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-default-role"><?php esc_html_e( 'Default role for new users', 'yby-core' ); ?></label></th>
					<td>
						<select id="yby-google-default-role" name="yby_social_login_options[google][default_role]">
							<?php foreach ( $allowed_roles as $role_key ) : ?>
								<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $google_options['default_role'], $role_key ); ?>><?php echo esc_html( translate_user_role( $roles[ $role_key ]['name'] ?? ucfirst( $role_key ) ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Disable Google login for selected roles', 'yby-core' ); ?></th>
					<td>
						<?php foreach ( $roles as $role_key => $role_data ) : ?>
							<label>
								<input type="checkbox" name="yby_social_login_options[google][disabled_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $google_options['disabled_roles'], true ) ); ?>>
								<?php echo esc_html( translate_user_role( $role_data['name'] ?? $role_key ) ); ?>
							</label><br>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="yby-google-redirect"><?php esc_html_e( 'Redirect after login', 'yby-core' ); ?></label></th>
					<td>
						<input id="yby-google-redirect" class="regular-text" type="text" name="yby_social_login_options[google][redirect_url]" value="<?php echo esc_attr( $google_options['redirect_url'] ); ?>">
						<p class="description"><?php esc_html_e( 'Use a relative path or a URL on this site. Leave empty to use the homepage.', 'yby-core' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Changes', 'yby-core' ), 'primary', 'yby_social_login_submit' ); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Implementation information', 'yby-core' ); ?></h2>
		<?php
		$home_parts = wp_parse_url( home_url( '/' ) );
		$origin     = isset( $home_parts['scheme'], $home_parts['host'] ) ? $home_parts['scheme'] . '://' . $home_parts['host'] . ( isset( $home_parts['port'] ) ? ':' . absint( $home_parts['port'] ) : '' ) : home_url( '/' );
		?>
		<p><strong><?php esc_html_e( 'Authorized JavaScript Origin:', 'yby-core' ); ?></strong> <code><?php echo esc_html( $origin ); ?></code></p>
		<p><strong><?php esc_html_e( 'Planned Login Endpoint:', 'yby-core' ); ?></strong> <code><?php echo esc_html( rest_url( 'yby/v1/auth/google' ) ); ?></code></p>
		<p><strong><?php esc_html_e( 'Shortcode:', 'yby-core' ); ?></strong> <code>[yby_social_login provider="google"]</code></p>
		<p class="description"><?php esc_html_e( 'The endpoint and shortcode are planned for the Google authentication implementation phase.', 'yby-core' ); ?></p>
	<?php endif; ?>
</div>
