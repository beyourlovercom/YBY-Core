<?php
/** Email hub view. */
$tabs = array(
	'floating_inquiry' => '悬浮询盘',
	'popup_inquiry' => '弹窗询盘', 'popup_subscribe' => '弹窗订阅', 'popup_lottery' => '弹窗抽奖',
	'shortcode_inquiry' => '短代码询盘', 'shortcode_subscribe' => '短代码订阅', 'bulk_marketing' => '营销群发',
);
$dock_theme = YBY_Brand_Profile::get_theme_config();
$dock_inquiry_background = $dock_settings['inquiry_background_color'] ?: ( sanitize_hex_color( $dock_theme['ctaBackground'] ?? '' ) ?: ( sanitize_hex_color( $dock_theme['primaryColor'] ?? '' ) ?: '#176b35' ) );
$dock_inquiry_text = $dock_settings['inquiry_text_color'] ?: ( sanitize_hex_color( $dock_theme['buttonTextColor'] ?? '' ) ?: '#ffffff' );
$dock_preview_settings = $dock_settings;
$dock_preview_settings['inquiry_background_color'] = $dock_inquiry_background;
$dock_preview_settings['inquiry_text_color'] = $dock_inquiry_text;
?>
<div class="wrap">
	<h1>Email</h1>
	<p>统一管理网站 Email 弹窗、短代码和后续营销能力。</p>
	<nav class="nav-tab-wrapper" aria-label="Email">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'yby-core-popups', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
</nav>
<?php if ( 'floating_inquiry' === $tab ) : ?>
	<div class="yby-floating-section">
		<h2>选择悬浮样式</h2>
		<p>两种悬浮询盘二选一。模式 A 保留现有轻量入口；模式 B 适合 WhatsApp 转化占比较高的站点。</p>
		<div class="yby-floating-mode-grid" data-yby-floating-mode-grid>
			<label class="yby-floating-mode-card <?php echo 'single' === $dock_settings['mode'] ? 'is-selected' : ''; ?>">
				<span class="yby-floating-mode-card__head"><input type="radio" form="yby-floating-inquiry-form" name="yby_inquiry_dock[mode]" value="single" <?php checked( $dock_settings['mode'], 'single' ); ?>><span class="yby-floating-mode-card__title">模式 A · 半透明单按钮</span></span>
				<span class="yby-floating-mode-card__desc">轻量悬浮，仅提供 Inquiry 入口。</span>
				<span class="yby-floating-mode-card__mock"><?php echo YBY_Global_Inquiry_Dock::render_markup( true, 'single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</label>
			<label class="yby-floating-mode-card <?php echo 'dual' === $dock_settings['mode'] ? 'is-selected' : ''; ?>">
				<span class="yby-floating-mode-card__badge">推荐</span>
				<span class="yby-floating-mode-card__head"><input type="radio" form="yby-floating-inquiry-form" name="yby_inquiry_dock[mode]" value="dual" <?php checked( $dock_settings['mode'], 'dual' ); ?>><span class="yby-floating-mode-card__title">模式 B · 底部双按钮</span></span>
				<span class="yby-floating-mode-card__desc">滑出首屏后固定显示 WhatsApp / Inquiry。</span>
				<span class="yby-floating-mode-card__mock"><?php echo YBY_Global_Inquiry_Dock::render_markup( true, 'dual' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</label>
		</div>
	</div>

	<div class="yby-floating-section">
		<h2>悬浮询盘设置</h2>
		<form id="yby-floating-inquiry-form" method="post">
			<?php wp_nonce_field( 'yby_inquiry_dock_save', 'yby_inquiry_dock_nonce' ); ?><input type="hidden" name="yby_inquiry_dock_submit" value="1">
			<table class="form-table"><tbody>
			<tr><th>启用悬浮询盘</th><td><label><input type="checkbox" name="yby_inquiry_dock[enabled]" value="1" <?php checked( $dock_settings['enabled'] ); ?>> 启用</label></td></tr>
			<tr><th>当前样式</th><td><strong><?php echo 'dual' === $dock_settings['mode'] ? '模式 B · 底部双按钮（推荐）' : '模式 A · 半透明单按钮'; ?></strong><p class="description">在上方样式卡片中选择，保存后生效。</p></td></tr>
			<tr><th>显示时机</th><td><select name="yby_inquiry_dock[display_timing]"><option value="after_first_screen" <?php selected( $dock_settings['display_timing'], 'after_first_screen' ); ?>>滑出首屏后显示（模式 B 推荐）</option><option value="immediate" <?php selected( $dock_settings['display_timing'], 'immediate' ); ?>>页面加载即显示</option></select><p class="description">模式 A 始终保持现有立即显示行为；该设置主要控制模式 B。</p></td></tr>
			<tr><th><label for="yby-dock-whatsapp-label">WhatsApp 按钮文案</label></th><td><input id="yby-dock-whatsapp-label" class="regular-text" name="yby_inquiry_dock[whatsapp_label]" value="<?php echo esc_attr( $dock_settings['whatsapp_label'] ); ?>"></td></tr>
			<tr><th><label for="yby-dock-label">Inquiry 按钮文案</label></th><td><input id="yby-dock-label" class="regular-text" name="yby_inquiry_dock[label]" value="<?php echo esc_attr( $dock_settings['label'] ); ?>"></td></tr>
			<tr><th>按钮颜色</th><td><div class="yby-floating-color-grid">
				<label>WhatsApp 背景色 <input type="color" data-yby-dock-color="--yby-dock-whatsapp-bg" name="yby_inquiry_dock[whatsapp_background_color]" value="<?php echo esc_attr( $dock_settings['whatsapp_background_color'] ); ?>"> <code data-yby-color-value><?php echo esc_html( strtoupper( $dock_settings['whatsapp_background_color'] ) ); ?></code></label>
				<label>WhatsApp 文字色 <input type="color" data-yby-dock-color="--yby-dock-whatsapp-text" name="yby_inquiry_dock[whatsapp_text_color]" value="<?php echo esc_attr( $dock_settings['whatsapp_text_color'] ); ?>"> <code data-yby-color-value><?php echo esc_html( strtoupper( $dock_settings['whatsapp_text_color'] ) ); ?></code></label>
				<label>Inquiry 背景色 <input type="color" data-yby-dock-color="--yby-dock-inquiry-bg" name="yby_inquiry_dock[inquiry_background_color]" value="<?php echo esc_attr( $dock_inquiry_background ); ?>"> <code data-yby-color-value><?php echo esc_html( strtoupper( $dock_inquiry_background ) ); ?></code></label>
				<label>Inquiry 文字色 <input type="color" data-yby-dock-color="--yby-dock-inquiry-text" name="yby_inquiry_dock[inquiry_text_color]" value="<?php echo esc_attr( $dock_inquiry_text ); ?>"> <code data-yby-color-value><?php echo esc_html( strtoupper( $dock_inquiry_text ) ); ?></code></label>
			</div><p class="description">点击色块直接选择颜色。WhatsApp 默认 #25D366；Inquiry 初始继承当前 Brand Theme，保存后成为当前站点的显式设置。</p></td></tr>
			<tr><th>单按钮位置</th><td><select name="yby_inquiry_dock[position]"><?php foreach ( array( 'bottom-right' => '右下', 'bottom-left' => '左下', 'bottom-center' => '底部居中' ) as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $dock_settings['position'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><p class="description">仅模式 A 使用；模式 B 固定底部居中。</p></td></tr>
			<tr><th>设备显示</th><td><label><input type="checkbox" name="yby_inquiry_dock[desktop]" value="1" <?php checked( $dock_settings['desktop'] ); ?>> 桌面端显示</label><br><label><input type="checkbox" name="yby_inquiry_dock[mobile]" value="1" <?php checked( $dock_settings['mobile'] ); ?>> 移动端显示</label></td></tr>
			<tr><th>Custom CSS</th><td><textarea class="large-text code" rows="9" name="yby_inquiry_dock[custom_css]" placeholder="例如：.yby-global-inquiry-dock { bottom: 24px; }"><?php echo esc_textarea( $dock_settings['custom_css'] ); ?></textarea><p class="description">仅作用于当前站点的悬浮询盘组件，优先于默认样式与 Brand Theme。</p></td></tr>
			</tbody></table>
			<p><button class="button button-primary" type="submit">保存设置</button></p>
		</form>
	</div>

	<div class="yby-floating-section">
		<h2>当前预览</h2>
		<h3>桌面端</h3><div class="yby-global-inquiry-dock-preview yby-global-inquiry-dock-preview--desktop" inert><?php echo YBY_Global_Inquiry_Dock::render_markup( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<h3>移动端</h3><div class="yby-global-inquiry-dock-preview yby-global-inquiry-dock-preview--mobile" inert><?php echo YBY_Global_Inquiry_Dock::render_markup( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'yby_inquiry_dock_preview', '1', home_url( '/' ) ) ); ?>">Preview / Test</a></p>
	</div>
	<style><?php echo YBY_Global_Inquiry_Dock::build_color_css( $dock_preview_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $dock_settings['custom_css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
	<script>
	document.addEventListener('change', function (event) {
		if (!event.target.matches('input[name="yby_inquiry_dock[mode]"]')) return;
		document.querySelectorAll('.yby-floating-mode-card').forEach(function (card) { card.classList.remove('is-selected'); });
		event.target.closest('.yby-floating-mode-card').classList.add('is-selected');
	});
	document.addEventListener('input', function (event) {
		if (!event.target.matches('[data-yby-dock-color]')) return;
		var cssVar = event.target.getAttribute('data-yby-dock-color');
		document.querySelectorAll('.yby-global-inquiry-dock').forEach(function (dock) { dock.style.setProperty(cssVar, event.target.value); });
		var code = event.target.parentElement.querySelector('[data-yby-color-value]');
		if (code) code.textContent = event.target.value.toUpperCase();
	});
	</script>
<?php elseif ( 'shortcode_inquiry' === $tab || 'shortcode_subscribe' === $tab ) :
	$shortcode_prefix = 'shortcode_inquiry' === $tab ? 'inquiry' : 'subscribe';
	$shortcode_key = $shortcode_prefix . '_shortcode_custom_css';
	$shortcode_root = 'inquiry' === $shortcode_prefix ? '.yby-shortcode-inquiry' : '.yby-shortcode-subscribe';
	$shortcode = 'inquiry' === $shortcode_prefix ? '[yby_inquiry]' : '[yby_subscribe]';
	$preview = 'inquiry' === $shortcode_prefix ? ( new YBY_Inquiry_Shortcodes( new YBY_Inquiry_Manager(), new YBY_Inquiry_Renderer() ) )->render_inline_shortcode() : ( new YBY_Subscribe_Shortcode() )->render();
?>
	<p><strong>短代码：</strong> <code><?php echo esc_html( $shortcode ); ?></code></p>
	<p>将此短代码放入页面或文章即可嵌入内容。短代码样式只作用于当前短代码根节点 <code><?php echo esc_html( $shortcode_root ); ?></code>，不会影响弹窗或其他组件。</p>
	<form method="post">
		<?php wp_nonce_field( 'yby_popup_save', 'yby_popup_nonce' ); ?>
		<table class="form-table"><tbody>
		<?php if ( 'subscribe' === $shortcode_prefix ) : ?><tr><th>订阅短代码内容</th><td>
			<?php foreach ( array( 'title' => '显示 Title', 'subtitle' => '显示 Subtitle', 'image' => '显示 Image' ) as $suffix => $label ) : $key = 'subscribe_shortcode_show_' . $suffix; ?>
				<input type="hidden" name="yby_popup_settings[<?php echo esc_attr( $key ); ?>]" value="0"><label><input type="checkbox" name="yby_popup_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ], 1 ); ?>> <?php echo esc_html( $label ); ?></label><br>
			<?php endforeach; ?>
		</td></tr><?php endif; ?>
			<tr><th>Custom CSS</th><td><textarea class="large-text code" rows="10" name="yby_popup_settings[<?php echo esc_attr( $shortcode_key ); ?>]"><?php echo esc_textarea( $settings[ $shortcode_key ] ); ?></textarea></td></tr>
		</tbody></table><p><button class="button button-primary" name="yby_popup_submit" value="1">保存</button></p>
	</form>
	<h2>当前嵌入效果</h2><div class="yby-admin-shortcode-preview" inert aria-label="Shortcode preview"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<style><?php echo $settings[ $shortcode_key ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<?php elseif ( 'popup_lottery' === $tab || 'bulk_marketing' === $tab ) : ?>
	<div class="notice notice-info inline"><p><?php echo 'popup_lottery' === $tab ? '后续设计' : '后续接入群发系统'; ?></p></div>
<?php else :
	$popup_prefix = 'popup_inquiry' === $tab ? 'inquiry' : 'subscribe';
	$popup_css_key = $popup_prefix . '_custom_css';
	$popup_root = 'inquiry' === $popup_prefix ? '.yby-global-popup--inquiry' : '.yby-global-popup--subscribe';
?>
	<p>Preview/Test 使用与生产环境相同的前端渲染器。弹窗 Custom CSS 只作用于当前弹窗根节点 <code><?php echo esc_html( $popup_root ); ?></code>，不会改变短代码样式。</p>
	<form method="post">
		<?php wp_nonce_field( 'yby_popup_save', 'yby_popup_nonce' ); ?>
		<table class="form-table"><tbody>
			<tr><th>标题</th><td><input class="regular-text" name="yby_popup_settings[<?php echo esc_attr( $popup_prefix ); ?>_title]" value="<?php echo esc_attr( $settings[ $popup_prefix . '_title' ] ); ?>" type="text"></td></tr>
			<tr><th>副标题</th><td><input class="regular-text" name="yby_popup_settings[<?php echo esc_attr( $popup_prefix ); ?>_subtitle]" value="<?php echo esc_attr( $settings[ $popup_prefix . '_subtitle' ] ); ?>" type="text"></td></tr>
			<tr><th>图片 URL</th><td><input id="yby-popup-<?php echo esc_attr( $popup_prefix ); ?>-image" class="regular-text yby-media-url" name="yby_popup_settings[<?php echo esc_attr( $popup_prefix ); ?>_image]" value="<?php echo esc_attr( $settings[ $popup_prefix . '_image' ] ); ?>" type="url" data-yby-media-target="yby-popup-<?php echo esc_attr( $popup_prefix ); ?>-image"> <button type="button" class="button yby-media-button" data-yby-media-target="yby-popup-<?php echo esc_attr( $popup_prefix ); ?>-image">从媒体库选择</button></td></tr>
			<tr><th>Custom CSS</th><td><textarea class="large-text code" rows="10" name="yby_popup_settings[<?php echo esc_attr( $popup_css_key ); ?>]"><?php echo esc_textarea( $settings[ $popup_css_key ] ); ?></textarea></td></tr>
		</tbody></table><p><button class="button button-primary" name="yby_popup_submit" value="1">保存</button></p>
	</form>
	<p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'yby_popup_preview' => $popup_prefix ), home_url( '/' ) ) ); ?>" target="_blank" rel="noopener">Preview/Test</a></p>
<?php endif; ?>
</div>
