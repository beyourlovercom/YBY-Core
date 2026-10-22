<?php
/**
 * Dev-only Andy Commerce Currency OS M1 read-only shadow preview.
 * Never load outside dev.beyourlover.com or perform Woo/payment mutations.
 */
if ( ! defined('ABSPATH') ) { exit; }
if ( ! current_user_can('manage_woocommerce')
    || parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com'
    || ! class_exists('Andy_Commerce_Currency_OS_V1') ) {
    echo '<div class="notice notice-error"><p>Currency OS preview is available on authorized Dev only.</p></div>';
    return;
}
$currency = isset($_GET['currency']) ? sanitize_key(wp_unslash($_GET['currency'])) : 'usd';
$currency = strtoupper($currency);
if ( ! in_array($currency, Andy_Commerce_Currency_OS_V1::SUPPORTED, true) ) {
    $currency = 'USD';
}
$fixtures = ['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000];

$fxMode = isset($_GET['fx_source']) ? sanitize_key(wp_unslash($_GET['fx_source'])) : 'synthetic';
$fxMode = $fxMode === 'reference' ? 'reference' : 'synthetic';
$referenceDate = '';
$referenceError = '';
$referenceSnapshot = null;
if ($fxMode === 'reference') {
    // Explicit, credentialed administrator GET on Dev only; never silent
    // requests from cart, checkout, frontend traffic or background CRON.
    try {
        $referenceSnapshot = Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly(time());
        $fixtures = $referenceSnapshot['rate_micros'];
        $referenceDate = $currency==='USD' ? 'USD base' : ($referenceSnapshot['quote_dates'][$currency] ?? '');
    } catch (Throwable $error) {
        $referenceError = 'Reference FX unavailable or stale; no quote produced. Source error remains private.';
    }
}
$ratesLabel = [
    'USD'=>'1.00', 'EUR'=>'0.90', 'GBP'=>'0.78', 'AUD'=>'1.50', 'CAD'=>'1.35'
];
if ($referenceError !== '') {
    echo '<div class="notice notice-error"><p>' . esc_html($referenceError) . '</p></div>';
    return;
}
$example = [
    'currency'=>$currency,
    'rate_micros'=>$fixtures[$currency],
    'rate_source'=>$referenceSnapshot ? $referenceSnapshot['rate_source'] : 'DEV_SYNTHETIC',
    'rate_timestamp'=>$referenceSnapshot ? $referenceSnapshot['retrieved_at_utc'] : 1792600000,
    'threshold_usd_minor'=>9900,
    'shipping_usd_minor'=>1000,
    'tax_usd_minor'=>700,
    'lines'=>[
        ['qty'=>1,'unit_usd_minor'=>12000,'discount_usd_minor'=>2500,
         'eligible_for_promotion'=>true,'free_gift'=>false],
    ],
];
try {
    $quote = Andy_Commerce_Currency_OS_V1::quote($example);
} catch (Throwable $error) {
    echo '<div class="notice notice-error"><p>Currency OS mock evaluation is unavailable. No shipping or payment affected.</p></div>';
    return;
}
$symbols = ['USD'=>'$','EUR'=>'€','GBP'=>'£','AUD'=>'A$','CAD'=>'C$'];
$money = static function(int $minor, string $code) use ($symbols): string {
    return $symbols[$code] . number_format($minor / 100, 2, '.', ',');
};
?>
<div class="wrap" style="max-width:1100px">
    <h1>Currency OS <span style="font-size:14px;font-weight:400;color:#777">· Dev Preview V1</span></h1>
    <p><strong>Shadow-only / No Checkout hooks / No Woo writes / No payments.</strong>
       This is a synthetic FX preview. It does not replace actual Woo tax, shipping, gateway or settlement logic.</p>
    <form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="get" style="display:flex;align-items:center;gap:10px;margin:18px 0">
        <input type="hidden" name="page" value="andy-commerce"/>
        <input type="hidden" name="tab" value="currency-os"/>
        <label for="byl-currency-os-demo">Customer currency</label>
        <select id="byl-currency-os-demo" name="currency">
            <?php foreach (Andy_Commerce_Currency_OS_V1::SUPPORTED as $code): ?>
              <option value="<?php echo esc_attr($code); ?>" <?php selected($code,$currency); ?>>
                  <?php echo esc_html($code); ?>
              </option>
            <?php endforeach; ?>
        </select>
        <label for="byl-currency-fx-mode">FX source</label>
        <select id="byl-currency-fx-mode" name="fx_source">
            <option value="synthetic" <?php selected('synthetic',$fxMode); ?>>Synthetic example</option>
            <option value="reference" <?php selected('reference',$fxMode); ?>>Daily reference (read-only)</option>
        </select>
        <button class="button button-primary" type="submit">Preview exchange</button>
        <span>1 USD = <?php echo esc_html($referenceSnapshot
            ? number_format($fixtures[$currency]/1000000,6,'.','')
            : $ratesLabel[$currency]); ?> <?php echo esc_html($currency); ?>
            (<?php echo $referenceSnapshot ? 'reference only, observed '.esc_html($referenceDate) : 'sample only'; ?>)</span>
    </form>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:18px">
        <div class="postbox" style="padding:18px;margin:0">
            <h2 style="margin-top:0">Business ledger · USD</h2>
            <p>Product gross: <strong><?php echo esc_html($money($quote['gross_usd_minor'],'USD')); ?></strong></p>
            <p>Discount: <strong>−<?php echo esc_html($money($quote['discount_usd_minor'],'USD')); ?></strong></p>
            <p>Promotion-eligible net: <strong><?php echo esc_html($money($quote['eligible_subtotal_usd_minor'],'USD')); ?></strong></p>
            <p>Example marketing threshold (simulation only): <strong>$99.00</strong></p>
            <p><strong><?php echo $quote['promotion_eligible'] ? 'Eligible' : 'Not eligible'; ?></strong>
                (shipping and tax do not unlock USD campaign)</p>
            <p>USD reporting total: <strong><?php echo esc_html($money($quote['accounting_total_usd_minor'],'USD')); ?></strong></p>
        </div>
        <div class="postbox" style="padding:18px;margin:0">
            <h2 style="margin-top:0">Customer-facing · <?php echo esc_html($currency); ?></h2>
            <p>Product gross: <strong><?php echo esc_html($money($quote['gross_local_minor'],$currency)); ?></strong></p>
            <p>Discount: <strong>−<?php echo esc_html($money($quote['discount_local_minor'],$currency)); ?></strong></p>
            <p>Discounted merchandise: <strong><?php echo esc_html($money($quote['merchandise_net_local_minor'],$currency)); ?></strong></p>
            <p>Illustrative shipping: <strong><?php echo esc_html($money($quote['shipping_local_minor'],$currency)); ?></strong></p>
            <p>Illustrative tax: <strong><?php echo esc_html($money($quote['tax_local_minor'],$currency)); ?></strong></p>
            <p>Total: <strong style="font-size:21px"><?php echo esc_html($money($quote['payable_local_minor'],$currency)); ?></strong></p>
        </div>
    </div>
    <p class="description"><strong>Not for real orders or payment capture.</strong>
    Synthetic rates are illustrative. Daily reference observations are for
    independent FX reconciliation, not Stripe/WooPayments/PayPal settlement.
    No exchange rates are saved or applied to cart prices.
    <?php if ($referenceSnapshot): ?>
      FX source <?php echo esc_html($referenceSnapshot['rate_source']); ?>,
      fetched <?php echo esc_html(gmdate('Y-m-d H:i', $referenceSnapshot['retrieved_at_utc'])); ?> UTC.
    <?php endif; ?>
    </p>
    <p class="description"><strong>Do not use these sample rates or numbers for real orders.</strong>
    Current $69 regional/free-shipping contracts and special $30/parcel or $30/pillow-item
    rules remain unchanged. M1 only establishes the USD-ledger and FX quote contracts.
    Stripe/WooPayments/PayPal, Woo tax classes, countries, refunds and historical order FX
    require separate Dev runtime verification before enabling the Currency OS engine.</p>
    <p><strong>Actual multiple marketing thresholds belong to Marketing OS, not this single-ledger example.</strong> <a href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m9_preview'=>'1'],admin_url('admin.php'))); ?>">Open multi-rule USD preview</a></p>
</div>
