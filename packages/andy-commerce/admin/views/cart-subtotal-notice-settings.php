<?php
/** Andy Commerce > Settings > Cart: small storefront copy setting. */
defined('ABSPATH') || exit;
$cart_notice = Andy_Commerce_Cart_Notice_Settings::get();
?>
<hr style="margin:30px 0 18px">
<h2>Cart — Subtotal Reminder</h2>
<p class="description">This is the small shipping, duties and taxes reminder directly above <strong>Subtotal</strong> in the Dev Cart Drawer. It only changes display text, not actual Woo shipping rates, duties or checkout calculations.</p>
<form method="post">
  <?php wp_nonce_field('andy_commerce_cart_notice_save','andy_cart_notice_nonce'); ?>
  <input type="hidden" name="andy_cart_notice_settings_submit" value="1">
  <table class="form-table" role="presentation"><tbody>
    <tr>
      <th scope="row">Show reminder</th>
      <td><label><input type="checkbox" name="andy_cart_notice_settings[enabled]" value="1" <?php checked($cart_notice['enabled']); ?>> Enabled</label></td>
    </tr>
    <tr>
      <th scope="row"><label for="andy-commerce-subtotal-reminder-text">Reminder text</label></th>
      <td>
        <textarea id="andy-commerce-subtotal-reminder-text" class="large-text" rows="3" maxlength="<?php echo esc_attr((string)Andy_Commerce_Cart_Notice_Settings::MAX_LENGTH); ?>" name="andy_cart_notice_settings[text]"><?php echo esc_textarea($cart_notice['text']); ?></textarea>
        <p class="description">Plain text only, maximum 240 characters. Changes are visible after refreshing the Dev Cart. No promotions, coupons, Woo pricing or order settings are changed.</p>
      </td>
    </tr>
  </tbody></table>
  <?php submit_button('Save Cart Reminder','primary','andy_cart_notice_save',false); ?>
  <button type="submit" class="button" name="andy_cart_notice_restore_default" value="1" onclick="return confirm('Restore the default reminder text and enable it?');">Restore default</button>
</form>
<p><a href="<?php echo esc_url(home_url('/?byl_cart_drawer_preview=1')); ?>" target="_blank" rel="noopener noreferrer">Open Dev Cart Drawer preview ↗</a></p>
