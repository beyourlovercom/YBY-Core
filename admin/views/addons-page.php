<?php
/** Addon manager view. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap yby-modules-page">
<h1><?php esc_html_e( 'Andy Core', 'yby-core' ); ?></h1>
<h2 class="nav-tab-wrapper">
<?php foreach ( YBY_Admin::settings_tabs() as $key => $label ) : ?>
<a class="nav-tab <?php echo 'addons' === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => YBY_Helpers::admin_page_slug(), 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
<?php endforeach; ?>
</h2>
<div class="yby-modules-hero"><div><span class="yby-modules-kicker">Andy Core Addon Runtime V1</span><h2>Addon 管理</h2><p>这里仅显示已加载并向 Andy Core 注册的 Addon。Core 负责兼容性与依赖状态，不承载 Addon 的业务逻辑。</p></div><span class="yby-status-badge yby-status-ready">Registry v<?php echo esc_html( YBY_Addon_Registry::VERSION ); ?></span></div>
<div class="yby-modules-table-wrap"><table class="widefat fixed striped yby-modules-table"><thead><tr>
<th class="col-module">Addon</th><th class="col-desc">说明</th><th class="col-type">版本</th><th class="col-status">安装</th><th class="col-status">状态</th><th class="col-status">Core 兼容</th><th class="col-status">依赖</th><th class="col-action">进入</th>
</tr></thead><tbody>
<?php if ( empty( $addons ) ) : ?>
<tr><td colspan="8"><span class="description">当前没有已安装的 Andy Core Addon。Core-only / B2B 运行时保持干净。</span></td></tr>
<?php else : foreach ( $addons as $id => $addon ) : $addon_status = YBY_Addon_Registry::status( $id ); ?>
<tr>
<td><strong><?php echo esc_html( $addon['name'] ); ?></strong><code><?php echo esc_html( $id ); ?></code></td>
<td><?php echo esc_html( $addon['description'] ); ?></td>
<td><?php echo esc_html( $addon['version'] ?: '—' ); ?></td>
<td><?php echo $addon_status['installed'] ? 'PASS' : 'FAIL'; ?></td>
<td><span class="yby-status-badge <?php echo 'ready' === $addon_status['state'] ? 'yby-status-ready' : ( 'inactive' === $addon_status['state'] ? 'yby-status-neutral' : 'yby-status-disabled' ); ?>"><?php echo esc_html( 'ready' === $addon_status['state'] ? 'Ready' : ( 'inactive' === $addon_status['state'] ? 'Inactive' : 'Blocked' ) ); ?></span></td>
<td><?php echo $addon_status['compatible'] ? 'PASS' : 'FAIL'; ?></td>
<td><?php echo $addon_status['requirements_met'] ? 'PASS' : 'FAIL'; ?></td>
<td><?php if ( ! empty( $addon['admin_url'] ) ) : ?><a class="yby-module-enter" href="<?php echo esc_url( $addon['admin_url'] ); ?>">进入 →</a><?php else : ?>—<?php endif; ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
</div>
