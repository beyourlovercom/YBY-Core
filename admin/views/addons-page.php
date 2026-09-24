<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap yby-addons-page">
<h1><?php esc_html_e( 'Andy Core', 'yby-core' ); ?></h1>
<h2 class="nav-tab-wrapper">
<?php foreach ( YBY_Admin::settings_tabs() as $key => $label ) : ?>
<a class="nav-tab <?php echo 'addons' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
<?php endforeach; ?>
</h2>
<div class="yby-modules-hero"><div><span class="yby-modules-kicker">Andy Core Addon Registry V<?php echo esc_html( YBY_Addon_Registry::VERSION ); ?></span><h2>Addons</h2><p>Andy Core 仅管理 Addon 的注册、依赖与兼容状态；业务能力由各 Addon 自己提供。</p></div><span class="yby-status-badge yby-status-ready">Core <?php echo esc_html( YBY_CORE_VERSION ); ?></span></div>
<table class="widefat fixed striped yby-modules-table">
<thead><tr><th>Addon</th><th>Version</th><th>Requires Core</th><th>Plugin</th><th>Status</th><th>Settings</th></tr></thead>
<tbody>
<?php if ( empty( $addons ) ) : ?>
<tr><td colspan="6"><span class="description">当前没有检测到已注册的 Andy Addon。Core-only 运行正常。</span></td></tr>
<?php else : foreach ( $addons as $id => $addon ) : $addon_status = $statuses[ $id ]; ?>
<tr>
<td><strong><?php echo esc_html( $addon['name'] ); ?></strong><code><?php echo esc_html( $id ); ?></code><p class="description"><?php echo esc_html( $addon['description'] ); ?></p></td>
<td><?php echo esc_html( $addon['version'] ?: '—' ); ?></td>
<td><?php echo esc_html( $addon['requires_core'] ?: '—' ); ?></td>
<td><code><?php echo esc_html( $addon['plugin_file'] ?: 'runtime' ); ?></code></td>
<td><span class="yby-status-badge <?php echo 'ready' === $addon_status['state'] ? 'yby-status-ready' : 'yby-status-neutral'; ?>"><?php echo esc_html( $addon_status['state'] ); ?></span><?php if ( ! empty( $addon_status['missing_dependencies'] ) ) : ?><p class="description"><?php echo esc_html( implode( ', ', $addon_status['missing_dependencies'] ) ); ?></p><?php endif; ?></td>
<td><?php if ( ! empty( $addon['settings_url'] ) && 'ready' === $addon_status['state'] ) : ?><a class="button button-small" href="<?php echo esc_url( $addon['settings_url'] ); ?>">设置</a><?php else : ?>—<?php endif; ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
<p class="description">此页面不负责安装、删除或自动启停插件；Addon 生命周期变更仍需明确授权。</p>
</div>
