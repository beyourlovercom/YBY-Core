<?php
/** Native WordPress admin content for Login OS V1 (separate from Social Login). */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap yby-login-os-settings">
	<?php if ( '' !== $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<nav class="nav-tab-wrapper" aria-label="后台登录设置">
		<?php foreach ( array(
			'general' => '基础设置',
			'appearance' => '视觉与品牌',
			'url' => '登录 URL',
			'safety' => '兼容与安全',
		) as $key => $label ) : ?>
			<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( 'tab', $key, $page_url ) ); ?>"
				<?php if ( $tab === $key ) : ?>aria-current="page"<?php endif; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php if ( 'general' === $tab ) : ?>
		<h2>后台登录</h2>
		<p>本模块只管理 WordPress 管理员登录入口及外观，不替换原生用户名、密码、认证 Cookie 或权限校验。</p>
		<table class="widefat striped"><tbody>
			<tr><th scope="row">登录页面视觉</th><td><?php echo $options['enabled'] ? '已启用' : '关闭（默认）'; ?></td></tr>
			<tr><th scope="row">自定义登录别名</th><td><?php echo YBY_Login_OS::alias_active() ? '已启用' : '关闭／被兼容性检查禁用'; ?></td></tr>
			<tr><th scope="row">Google 登录</th><td>由原有「社交登录」页面独立管理；本模块不会修改其配置。</td></tr>
			<tr><th scope="row">WooCommerce 顾客账户</th><td>由 Andy Commerce 管理；/my-account/ 保持原样。</td></tr>
		</tbody></table>
		<p><a class="button" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener noreferrer">预览登录页</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=yby-social-login' ) ); ?>">返回社交登录</a></p>
	<?php elseif ( 'appearance' === $tab ) : ?>
		<h2>视觉与品牌</h2>
		<p class="description">主题无关的独立登录 CSS。启用前不改变登录页；留空项从现有 Brand OS 读取。</p>
		<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', $tab, $page_url ) ); ?>">
			<?php wp_nonce_field( 'yby_login_os_save_appearance', 'yby_login_os_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">启用登录页美化</th><td>
					<input type="hidden" name="yby_login_os[enabled]" value="0">
					<label><input type="checkbox" name="yby_login_os[enabled]" value="1" <?php checked( $options['enabled'] ); ?>> 启用 Andy Core 品牌样式</label>
					<p class="description">只改变外观，不改变登录行为。</p></td></tr>
				<tr><th scope="row"><label for="yby-login-os-logo">Logo URL</label></th><td>
					<input class="regular-text" id="yby-login-os-logo" type="url" name="yby_login_os[logo_url]"
						value="<?php echo esc_attr( $options['logo_url'] ); ?>" placeholder="继承 Andy Core 品牌 Logo">
					<p class="description">留空继承 Brand OS 默认 Logo。</p></td></tr>
				<tr><th scope="row"><label for="yby-login-os-accent">品牌色</label></th><td>
					<input id="yby-login-os-accent" type="text" name="yby_login_os[accent_color]" maxlength="7"
						pattern="#[0-9a-fA-F]{6}" value="<?php echo esc_attr( $options['accent_color'] ); ?>"
						placeholder="继承现有品牌颜色">
					<p class="description">填写十六进制颜色，例如 #9B3749。</p></td></tr>
				<tr><th scope="row"><label for="yby-login-os-bg">背景颜色</label></th><td>
					<input id="yby-login-os-bg" type="color" name="yby_login_os[background_color]"
						value="<?php echo esc_attr( $options['background_color'] ); ?>"></td></tr>
			</table>
			<?php submit_button( '保存视觉设置', 'primary', 'yby_login_os_submit' ); ?>
		</form>
	<?php elseif ( 'url' === $tab ) : ?>
		<h2>登录 URL</h2>
		<p class="description">V1 安全别名：提供新的后台登录入口，始终保留原始 wp-login.php 作为管理员恢复入口。</p>
		<?php if ( is_multisite() ) : ?>
			<div class="notice notice-warning inline"><p>Multisite 站点暂不支持 Login OS V1 自定义 URL；请继续使用原生登录地址。</p></div>
		<?php endif; ?>
		<?php if ( $conflicts ) : ?>
			<div class="notice notice-warning inline"><p>发现其他登录 URL 插件：<?php echo esc_html( implode( '、', $conflicts ) ); ?>。不能同时接管登录地址。</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', $tab, $page_url ) ); ?>">
			<?php wp_nonce_field( 'yby_login_os_save_url', 'yby_login_os_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">启用登录别名</th><td>
					<input type="hidden" name="yby_login_os[url_enabled]" value="0">
					<label><input type="checkbox" name="yby_login_os[url_enabled]" value="1" <?php checked( $options['url_enabled'] ); ?> <?php disabled( ! empty( $conflicts ) || is_multisite() ); ?>> 启用新的登录地址</label></td></tr>
				<tr><th scope="row"><label for="yby-login-os-slug">自定义路径</label></th><td>
					<code><?php echo esc_html( trailingslashit( home_url( '/' ) ) ); ?></code>
					<input id="yby-login-os-slug" name="yby_login_os[slug]" type="text"
						pattern="[a-z0-9][a-z0-9-]{2,49}" maxlength="50" required
						value="<?php echo esc_attr( $options['slug'] ); ?>"> <code>/</code>
					<p class="description">仅使用小写英文字母、数字、连字符；不得与已存在的页面冲突。</p></td></tr>
			</table>
			<?php submit_button( '保存登录 URL', 'primary', 'yby_login_os_submit' ); ?>
		</form>
		<p><strong>自定义登录入口：</strong> <code><?php echo esc_html( YBY_Login_OS::alias_url() ); ?></code></p>
		<p><strong>原生恢复入口：</strong> <code><?php echo esc_html( site_url( 'wp-login.php', 'https' ) ); ?></code></p>
	<?php elseif ( 'safety' === $tab ) : ?>
		<h2>兼容与安全</h2>
		<table class="widefat striped"><tbody>
			<tr><th scope="row">WordPress 认证</th><td>原生流程；未修改用户、角色、密码或 Cookies</td></tr>
			<tr><th scope="row">Google 登录</th><td>保留现有 REST 端点、One Tap 与社交登录配置</td></tr>
			<tr><th scope="row">顾客登录</th><td>不覆盖 WooCommerce /my-account/</td></tr>
			<tr><th scope="row">登录 URL 插件冲突</th><td><?php echo esc_html( $conflicts ? implode( '、', $conflicts ) : '未检测到已知插件冲突' ); ?></td></tr>
			<tr><th scope="row">应急恢复</th><td>WordPress 原生 wp-login.php 始终可用；停用 Core 后亦可恢复</td></tr>
			<tr><th scope="row">安全边界</th><td>隐藏地址并不代替限流、双因素验证或 WAF 防护</td></tr>
		</tbody></table>
	<?php endif; ?>
</div>
