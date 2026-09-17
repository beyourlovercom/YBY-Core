<?php
/** Module manager view. @package YBY_Core */
?>
<div class="wrap yby-modules-page">
<h1><?php esc_html_e( 'Andy Core', 'yby-core' ); ?></h1>
<h2 class="nav-tab-wrapper">
<?php foreach ( YBY_Admin::settings_tabs() as $key => $label ) : ?>
<a class="nav-tab <?php echo 'modules' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
<?php endforeach; ?>
</h2>
<div class="yby-modules-hero"><div><span class="yby-modules-kicker">Andy Core Modular Runtime V1</span><h2>模块管理</h2><p>集中管理当前网站启用的 Andy Core 能力。关闭模块不会删除已有内容、配置或数据库数据。</p></div><span class="yby-status-badge yby-status-ready">Registry v<?php echo esc_html( YBY_Module_Registry::VERSION ); ?></span></div>
<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
<form method="post">
<?php wp_nonce_field( 'yby_core_modules_save', 'yby_core_modules_nonce' ); ?>
<input type="hidden" name="yby_core_modules_submit" value="1">
<div class="yby-modules-table-wrap"><table class="widefat fixed striped yby-modules-table"><thead><tr>
<th class="col-module">模块</th><th class="col-desc">功能说明</th><th class="col-type">类型</th><th class="col-status">运行状态</th><th class="col-toggle">启用</th><th class="col-action">设置</th><th class="col-action">进入</th>
</tr></thead><tbody><?php foreach ( $foundation as $id => $label ) : ?>
<tr class="is-foundation"><td><strong><?php echo esc_html( $label ); ?></strong><code><?php echo esc_html( $id ); ?></code></td><td>Andy Core 基础设施。</td><td><span class="yby-module-type">Foundation</span></td><td><span class="yby-status-badge yby-status-ready">Always On</span></td><td><span class="dashicons dashicons-lock" aria-hidden="true"></span></td><td>—</td><td>—</td></tr>
<?php endforeach; ?>
<?php foreach ( $modules as $id => $module ) : $is_planned = 'planned' === $module['status']; $is_enabled = in_array( $id, $enabled, true ); $settings_url = YBY_Module_Registry::settings_url( $id ); ?>
<tr class="<?php echo $is_planned ? 'is-planned' : ''; ?>">
<td><strong><?php echo esc_html( $module['label'] ); ?></strong><code><?php echo esc_html( $id ); ?></code></td>
<td><?php echo esc_html( $module['description'] ); ?></td>
<td><span class="yby-module-type">Feature</span></td>
<td><span class="yby-status-badge <?php echo $is_planned ? 'yby-status-neutral' : ( $is_enabled ? 'yby-status-ready' : 'yby-status-disabled' ); ?>"><?php echo esc_html( $is_planned ? 'Planned' : ( $is_enabled ? '已开启' : '已关闭' ) ); ?></span></td>
<td><label class="yby-module-switch"><input type="checkbox" name="yby_core_modules[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $is_enabled ); ?> <?php disabled( $is_planned ); ?>><span class="yby-module-switch__track"></span><span class="screen-reader-text"><?php echo esc_html( $module['label'] ); ?></span></label></td>
<td><?php if ( $settings_url && ! $is_planned ) : ?><a class="button button-small" href="<?php echo esc_url( $settings_url ); ?>">设置</a><?php else : ?>—<?php endif; ?></td>
<td><?php if ( $settings_url && ! $is_planned ) : ?><a class="yby-module-enter" href="<?php echo esc_url( $settings_url ); ?>">进入 →</a><?php else : ?><span class="description">待接入</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div><div class="yby-modules-footer"><p class="submit"><button type="submit" class="button button-primary">保存模块设置</button></p><p class="description">模块关闭后仅停止对应菜单、Hooks、REST、Assets 与 Runtime；已有数据保持不变。</p></div>
</form>
</div>