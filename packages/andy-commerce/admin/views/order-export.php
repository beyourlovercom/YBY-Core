<?php /** Commerce / Woo Order Export. @package Andy_Commerce */ ?>
<div class="wrap yby-commerce-page">
<h1>Andy Commerce</h1>
<nav class="nav-tab-wrapper">
<a class="nav-tab <?php echo 'order-export' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'order-export' ), admin_url( 'admin.php' ) ) ); ?>">Order Export</a>
<a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => Andy_Commerce_Admin::PAGE_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) ); ?>">Settings</a>
</nav>
<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
<?php if ( 'settings' === $tab ) : ?>
<form method="post">
<?php wp_nonce_field( 'yby_woo_export_settings_save', 'yby_woo_export_settings_nonce' ); ?>
<input type="hidden" name="yby_woo_export_settings_submit" value="1">
<table class="form-table"><tbody>
<tr><th><label for="yby-export-default-preset">Default preset</label></th><td><select id="yby-export-default-preset" name="yby_woo_export_settings[default_preset]"><?php foreach ( $presets as $id => $preset ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( $settings['default_preset'], $id ); ?>><?php echo esc_html( $preset['label'] ); ?></option><?php endforeach; ?></select></td></tr>
<tr><th><label for="yby-export-batch-size">Batch size</label></th><td><input id="yby-export-batch-size" type="number" min="25" max="500" name="yby_woo_export_settings[batch_size]" value="<?php echo esc_attr( $settings['batch_size'] ); ?>"></td></tr>
<tr><th>BOM</th><td><label><input type="checkbox" name="yby_woo_export_settings[bom]" value="1" <?php checked( $settings['bom'] ); ?>> Excel-friendly UTF-8 BOM</label></td></tr>
<tr><th>Audit</th><td><label><input type="checkbox" name="yby_woo_export_settings[audit_enabled]" value="1" <?php checked( $settings['audit_enabled'] ); ?>> Store bounded non-PII export audit metadata</label></td></tr>
</tbody></table><?php submit_button( 'Save Settings' ); ?>
</form>
<?php else : ?>
<?php if ( ! $module_enabled ) : ?>
<div class="notice notice-warning"><p>Woo Order Export 模块当前关闭。请先在 Andy Core → Settings → 模块 中启用。</p></div>
<?php else : ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" value="yby_woo_order_export">
<?php wp_nonce_field( 'yby_woo_order_export', 'yby_woo_order_export_nonce' ); ?>
<table class="form-table"><tbody>
<tr><th><label for="yby-export-preset">Preset</label></th><td><select id="yby-export-preset" name="preset"><?php foreach ( $presets as $id => $preset ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( $settings['default_preset'], $id ); ?>><?php echo esc_html( $preset['label'] ); ?></option><?php endforeach; ?></select></td></tr>
<tr><th>Date</th><td><input type="date" name="date_from"> — <input type="date" name="date_to"></td></tr>
<tr><th>Order status</th><td><select name="statuses[]" multiple size="6"><?php foreach ( $order_statuses as $status => $label ) : ?><option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
<tr><th><label for="yby-export-order-ids">Order IDs</label></th><td><input id="yby-export-order-ids" type="text" class="regular-text" name="order_ids" placeholder="123, 456, 789"></td></tr>
<tr><th><label for="yby-export-customer-email">Customer Email</label></th><td><input id="yby-export-customer-email" type="email" class="regular-text" name="customer_email"></td></tr>
<tr><th><label for="yby-export-currency">Currency</label></th><td><input id="yby-export-currency" type="text" maxlength="3" name="currency" placeholder="USD"></td></tr>
<tr><th><label for="yby-export-payment">Payment Method</label></th><td><input id="yby-export-payment" type="text" class="regular-text" name="payment_method" placeholder="paypal / stripe"></td></tr>
</tbody></table>
<?php submit_button( 'Export CSV', 'primary', 'submit', false ); ?>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
