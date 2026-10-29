<?php
/** Independent 5-currency shipping source contract; never boots WordPress. */
declare(strict_types=1);
namespace Yay_Currency\Helpers {
    final class YayCurrencyHelper {
        public static string $currency = 'USD';
        public static function detect_current_currency(): array {
            return ['currency'=>self::$currency,'status'=>1];
        }
    }
}
namespace {
    define('ABSPATH', __DIR__);
    $GLOBALS['rate'] = 1.0;
    $GLOBALS['price_tax'] = 'no';
    function get_option($key,$default='') {
        if ($key === 'woocommerce_currency') return 'USD';
        if ($key === 'woocommerce_prices_include_tax') return $GLOBALS['price_tax'];
        return $default;
    }
    function wc_get_price_decimals() { return 2; }
    function has_filter($key) { return $key === 'yay_currency_convert_price' ? 10 : false; }
    function apply_filters($key,$v) {
        if ($key !== 'yay_currency_convert_price') throw new \RuntimeException('unknown filter');
        return number_format((float)$v * $GLOBALS['rate'],2,'.','');
    }
    final class FakeProduct {
        private string $usd;
        public function __construct(string $usd) { $this->usd=$usd; }
        public function get_price($context='view') {
            if ($context !== 'edit') throw new \RuntimeException('untrusted price context');
            return $this->usd;
        }
    }
    final class FakeCart {
        public array $items;
        public function __construct(array $items) { $this->items=$items; }
        public function get_cart(): array { return $this->items; }
    }
    final class FakeZone {
        public function __construct(private int $id=5) {}
        public function get_id(): int { return $this->id; }
    }
    final class WC_Shipping_Zones {
        public static function get_zone_matching_package(array $package) {
            return new FakeZone($package['zone_id'] ?? 5);
        }
    }
    final class Andy_Commerce_Site_Preset {
        public static function current(): array { return ['id'=>'byl']; }
    }
    final class Andy_Commerce_Shipping_Promotion_Policy {
        public static function is_runtime_active(): bool { return true; }
        public static function get_persisted_zone_effective_threshold(int $id): ?float {
            return [5=>69.0,0=>69.0,1=>0.0,4=>129.0,3=>169.0][$id] ?? null;
        }
        public static function get_persisted_settings(): array {
            return ['mode'=>'automatic','free_shipping_code'=>'BYL49'];
        }
    }
    $GLOBALS['cart']=null;
    function WC() {return (object)['cart'=>$GLOBALS['cart']];}
    require __DIR__ . '/../includes/class-andy-commerce-shipping-usd-source-v1.php';
    require __DIR__ . '/../includes/class-andy-commerce-shipping-promotion-runtime.php';
    $pass=0;
    $assert = static function($ok,$message) use (&$pass) {
        if (!$ok) {fwrite(STDERR,"FAIL $message\n");exit(1);}
        $pass++;
    };
    $cfg = ['USD'=>1.0,'EUR'=>0.897,'GBP'=>0.7505,'AUD'=>1.4187,'CAD'=>1.4];
    foreach ($cfg as $code=>$rate) {
        \Yay_Currency\Helpers\YayCurrencyHelper::$currency=$code;
        $GLOBALS['rate']=$rate;
        foreach ([60.0,75.0] as $source) {
            $products = $source===60.0 ? [60.0] : [60.0,15.0];
            $items = [];
            foreach ($products as $p) {
                $local = number_format($p*$rate,2,'.','');
                $items[]=['data'=>new FakeProduct(number_format($p,2,'.','')),
                    'quantity'=>1,'line_subtotal'=>$local,'line_total'=>$local];
            }
            $cart=new FakeCart($items);
            $GLOBALS['cart']=$cart;
            $usd=Andy_Commerce_Shipping_USD_Source_V1::subtotal($cart);
            $assert($usd===$source,"$code USD $source source read");
            $eligible=Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping(false,['zone_id'=>5],null);
            $assert($eligible===($source>=69),"$code USD $source eligibility");
        }
    }
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='EUR';
    $GLOBALS['rate']=0.897;
    $bad=new FakeCart([['data'=>new FakeProduct('60.00'),'quantity'=>1,'line_subtotal'=>'89.99']]);
    $GLOBALS['cart']=$bad;
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'unrecognized dynamic pricing invalidates source');
    $assert(Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping(true,['zone_id'=>5],null)===false,'fail-closed on unknown source');
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='JPY';
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'unsupported currency rejected');
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='USD';
    $GLOBALS['price_tax']='yes';
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'tax-included case rejected until audited');
    echo "PASS shipping USD source five currencies $pass assertions; 4 Dev failure regressions covered; no writes\n";
}
