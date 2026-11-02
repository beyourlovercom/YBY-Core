<?php
/**
 * Source-only Black Friday landing preview. Never changes cart, prices or orders.
 * Product IDs must be reviewed and explicitly mapped; titles are NOT SKU proof.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class BYL_BF2026_Landing_Preview {
    public const SALE_NAMES = array(
        'Camera', 'Earpod', 'Angela', 'Eria', 'Joy Cup', 'Heart',
        'Wavey', 'SQUEEZE ME', 'ARIEL', 'Perfume', 'LUBRICANT 35ML',
        'TARA - APP VERSION', 'Devil Tongue', 'Blackfire',
        'Cloud Spirit', 'Cloud Ipo', 'Abe Rose', 'Anita', 'TALIA',
        'MAGIC CANE - GREEN', 'Leopard Duo', 'Orthus', 'Aura',
        'Pulse Stimulator',
    );
    public const TIERS = array(
        10 => array('Heart','Blackfire','Cloud Spirit','Cloud Ipo','MAGIC CANE - GREEN'),
        15 => array('Camera','Earpod','Joy Cup','SQUEEZE ME','Perfume'),
        20 => array('Angela','Eria','Wavey','ARIEL','TARA - APP VERSION'),
    );

    public static function register(): void {
        add_shortcode('byl_bf2026_landing', array(__CLASS__, 'render'));
    }

    private static function mapped_products(): array {
        // Owner-reviewed exact map, key=brief label, value=real Woo simple product ID.
        $ids = apply_filters('byl_bf2026_preview_product_map', array());
        if (!is_array($ids) || count($ids) > 24) { return array(); }
        $expected = array();
        // Dev-only product images; no promotion activation or warehouse approval.
        $host = function_exists('home_url') && function_exists('wp_parse_url')
            ? strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST)) : '';
        if (!$ids && $host === 'dev.beyourlover.com') {
            $file = dirname(__DIR__).'/config/dev-sale-candidates-20261102.json';
            $raw = is_file($file) && filesize($file) < 65536 ? file_get_contents($file) : false;
            $doc = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($doc) && ($doc['schema_version'] ?? null) === 1
                && ($doc['runtime_auto_load'] ?? true) === false && is_array($doc['items'] ?? null)
                && count($doc['items']) === 24) {
                foreach ($doc['items'] as $item) {
                    if (!is_array($item) || !is_string($item['label'] ?? null)
                        || !is_int($item['product_id'] ?? null) || $item['product_id'] <= 0
                        || !is_string($item['sku'] ?? null) || $item['sku'] === '') { continue; }
                    $ids[$item['label']] = $item['product_id'];
                    $expected[$item['label']] = $item['sku'];
                }
            }
        }
        $safe = array();
        foreach (self::SALE_NAMES as $name) {
            $id = $ids[$name] ?? null;
            if (!is_int($id) || $id <= 0 || !function_exists('wc_get_product')) { continue; }
            $product = wc_get_product($id);
            if (!$product || !$product->is_type('simple') || !$product->get_sku()) { continue; }
            if (isset($expected[$name]) && (string)$product->get_sku() !== $expected[$name]) { continue; }
            $safe[$name] = $product;
        }
        return $safe;
    }

    public static function render(): string {
        // Rendering is purely informational and works with Campaign master OFF.
        $css = plugins_url('assets/bf2026-landing.css', dirname(__DIR__) . '/black-friday-2026.php');
        wp_enqueue_style('byl-bf2026-landing-preview', $css, array(), '0.1.0');
        $mapped = self::mapped_products();
        ob_start();
        ?>
        <main class="byl-bf26" aria-label="Black Friday 2026 design preview">
            <section class="byl-bf26-hero">
                <p class="byl-bf26-eyebrow">BeYourLover &reg;</p>
                <h1>BLACK FRIDAY</h1>
                <p class="byl-bf26-off">UP TO 50% OFF</p>
                <div class="byl-bf26-promises">
                    <p>A FREE GIFT WITH EVERY ORDER</p>
                    <p>2 FREE GIFTS ON ORDERS $199+</p>
                </div>
                <p class="byl-bf26-date">NOV 23–30</p>
                <a class="byl-bf26-cta" href="#bf26-products">SHOP NOW</a>
            </section>
            <div class="byl-bf26-world">Free random gifts with eligible orders worldwide — subject to destination availability.</div>
            <section class="byl-bf26-section" id="bf26-products">
                <h2>UP TO 50% OFF</h2>
                <div class="byl-bf26-grid">
                    <?php foreach (self::SALE_NAMES as $name) :
                        $product = $mapped[$name] ?? null;
                        $href = $product ? get_permalink($product->get_id()) : '';
                        ?>
                        <article class="byl-bf26-product">
                            <div class="byl-bf26-art">
                                <?php if ($product && $product->get_image_id()) :
                                    echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail', false, array('loading'=>'lazy','alt'=>$name));
                                else : ?>
                                    <span aria-hidden="true">✦</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($href) : ?>
                                <a href="<?php echo esc_url($href); ?>"><?php echo esc_html($name); ?></a>
                            <?php else : ?>
                                <span><?php echo esc_html($name); ?></span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <a class="byl-bf26-cta byl-bf26-view" href="#bf26-products">View All Products</a>
            </section>
            <section class="byl-bf26-gifts" id="bf26-gifts">
                <div class="byl-bf26-gift-intro">
                    <h2>BLACK FRIDAY GIFT EVENT</h2>
                    <p>A Free Gift With Every Order</p>
                    <p>2 Free Gifts On Orders $199+</p>
                    <small>*Free gifts are randomly selected. While supplies last.</small>
                </div>
                <div class="byl-bf26-picker">
                    <p class="byl-bf26-us">U.S. SHIPPING ADDRESSES ONLY</p>
                    <h2>Pick Your Favorite</h2>
                    <p>Choose Your Favorite for an additional $10–$20.</p>
                    <p class="byl-bf26-rule">Unlock paid gift selection at $99 merchandise subtotal. Upgrade one gift slot; the other stays random at $199+.</p>
                    <div class="byl-bf26-tiers">
                        <?php foreach (self::TIERS as $dollars => $names) : ?>
                            <div class="byl-bf26-tier">
                                <h3>+$<?php echo esc_html((string)$dollars); ?> Gifts</h3>
                                <ul><?php foreach ($names as $name) : ?>
                                    <li><?php echo esc_html($name); ?></li>
                                <?php endforeach; ?></ul>
                                <span class="byl-bf26-soon">Select Gift +$<?php echo esc_html((string)$dollars); ?> — Preview Only</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <section class="byl-bf26-section byl-bf26-faq">
                <h2>FAQ</h2>
                <p>Promotion FAQs will be published after content approval.</p>
            </section>
        </main>
        <?php
        return (string) ob_get_clean();
    }
}