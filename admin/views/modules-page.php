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
<div class="yby-modules-hero"><div><span class="yby-modules-kicker">Andy Core Modular Runtime V1</span><h2>模块</h2><p>按网站选择需要的能力。关闭模块不会删除已有内容、配置或数据库数据。</p></div><span class="yby-status-badge yby-status-ready">Registry v<?php echo esc_html( YBY_Module_Registry::VERSION ); ?></span></div>
<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
<h2 class="yby-modules-section-title">Foundation <small>始终开启，不可关闭</small></h2>
<div class="yby-modules-grid yby-modules-grid--foundation">
<?php foreach ( $foundation as $id => $label ) : ?><article class="yby-module-card is-foundation"><div class="yby-module-card__top"><span class="dashicons dashicons-lock"></span><span class="yby-status-badge yby-status-ready">Always On</span></div><h3><?php echo esc_html( $label ); ?></h3><p>Andy Core 基础设施。</p></article><?php endforeach; ?>
</div>
<form method="post">
<?php wp_nonce_field( 'yby_core_modules_save', 'yby_core_modules_nonce' ); ?>
<input type="hidden" name="yby_core_modules_submit" value="1">
<h2 class="yby-modules-section-title">Feature Modules <small>按当前网站启用</small></h2>
<div class="yby-modules-grid">
<?php foreach ( $modules as $id => $module ) : $is_planned = 'planned' === $module['status']; $settings_url = YBY_Module_Registry::settings_url( $id ); ?>
<article class="yby-module-card <?php echo $is_planned ? 'is-planned' : ''; ?>">
<div class="yby-module-card__top"><span class="yby-module-id"><?php echo esc_html( $id ); ?></span><label class="yby-module-switch"><input type="checkbox" name="yby_core_modules[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $enabled, true ) ); ?> <?php disabled( $is_planned ); ?>><span class="yby-module-switch__track"></span><span class="screen-reader-text"><?php echo esc_html( $module['label'] ); ?></span></label></div>
<h3><?php echo esc_html( $module['label'] ); ?></h3><p><?php echo esc_html( $module['description'] ); ?></p>
<div class="yby-module-card__footer"><span class="yby-status-badge <?php echo $is_planned ? 'yby-status-neutral' : ( in_array( $id, $enabled, true ) ? 'yby-status-ready' : 'yby-status-disabled' ); ?>"><?php echo esc_html( $is_planned ? 'Docs OS V1 待接入' : ( in_array( $id, $enabled, true ) ? '已开启' : '已关闭' ) ); ?></span><?php if ( $settings_url && ! $is_planned ) : ?><a href="<?php echo esc_url( $settings_url ); ?>">设置 →</a><?php endif; ?></div>
</article>
<?php endforeach; ?>
</div>
<p class="submit"><button type="submit" class="button button-primary button-large">保存模块设置</button></p>
<p class="description">M1/M2 仅建立 Registry 与配置中心；现有 Runtime Gate 将在 M3 接入。保存开关不会删除任何模块数据。</p>
</form>
</div>
