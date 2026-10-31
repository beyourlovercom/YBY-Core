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
            if ($context === 'edit') return $this->usd;
            if ($context !== 'view') throw new \RuntimeException('unexpected price context');
            return number_format((float)$this->usd * $GLOBALS['rate'], 2, '.', '');
        }
    }
    final class FakeCart {
        public array $items;
        public function __construct(array $items) { $this->items=$items; }
        public function get_cart(): array { return $this->items; }
        public function get_displayed_subtotal(): float {
            return array_sum(array_map(static fn($line)=>(float)$line['line_subtotal'], $this->items));
        }
        public function get_applied_coupons(): array { return []; }
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
        public static function normalize_code(string $code): string { return strtolower($code); }
    }
    $GLOBALS['cart']=null;
    final class FakeShipping {
        public function get_packages(): array { return [['zone_id'=>5]]; }
    }
    final class FakeWC {
        public function __get($name) {
            return $name === 'cart' ? $GLOBALS['cart'] : null;
        }
        public function shipping() { return new FakeShipping(); }
    }
    function WC() {return new FakeWC();}
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
            // Native rate callbacks occur before final Woo cart line totals
            // are guaranteed. The earlier source failed closed on this state.
            $pendingItems = array_map(static function($item) {
                unset($item['line_subtotal']);
                return $item;
            }, $items);
            $completeCart=$cart;
            $pendingCart=new FakeCart($pendingItems);
            $GLOBALS['cart']=$pendingCart;
            $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($pendingCart)===null,
                "$code USD $source strict missing subtotal rejection");
            $assert(Andy_Commerce_Shipping_USD_Source_V1::shipping_subtotal($pendingCart)===$source,
                "$code USD $source native quote source");
            $assert(Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping(false,['zone_id'=>5],null)===($source>=69),
                "$code USD $source native rate phase");
            $GLOBALS['cart']=$completeCart;
            $fields=Andy_Commerce_Shipping_Promotion_Runtime::include_pricing_in_shipping_hash(
                ['subtotal','total','rates','package_id'], ['contents'=>[]]);
            $assert(!in_array('subtotal',$fields,true)&&!in_array('total',$fields,true)
                &&in_array('rates',$fields,true), "$code USD $source cache price signature");

            $presentation=Andy_Commerce_Shipping_Promotion_Runtime::get_cart_presentation_state();
            $assert($presentation['active']===true,"$code USD $source presentation active");
            $assert($presentation['eligible']===($source>=69),"$code USD $source presentation eligibility");
            $assert($presentation['basis_currency']==='USD',"$code USD $source canonical basis");
            $assert($presentation['display_currency']===$code,"$code USD $source visible currency");
            $assert($presentation['subtotal_usd']===$source,"$code USD $source canonical source subtotal");
            $assert(abs($presentation['remaining_usd']-max(0,69-$source))<0.001,"$code USD $source canonical remaining");
            $expectedThreshold=(float)number_format(69*$rate,2,'.','');
            $assert(abs($presentation['threshold']-$expectedThreshold)<0.011,"$code USD $source local threshold");
            $expectedRemain=(float)number_format(max(0,69-$source)*$rate,2,'.','');
            $assert(abs($presentation['remaining']-$expectedRemain)<0.011,"$code USD $source local remaining");
        }
    }
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='EUR';
    $GLOBALS['rate']=0.897;
    $bad=new FakeCart([['data'=>new FakeProduct('60.00'),'quantity'=>1,'line_subtotal'=>'89.99']]);
    $GLOBALS['cart']=$bad;
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'unrecognized dynamic pricing invalidates source');
    $assert(Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping(true,['zone_id'=>5],null)===false,'fail-closed on unknown source');
    $actualBad = new FakeCart([['data'=>new FakeProduct('60.00'),'quantity'=>1,'line_subtotal'=>'89.99']]);
    $GLOBALS['cart']=$actualBad;
    $assert(Andy_Commerce_Shipping_USD_Source_V1::shipping_subtotal($actualBad)===60.0,
        'rate phase uses verified current unit not stale committed line');
    $wrongViewProduct=new class {
        public function get_price($context='view') {
            return $context === 'edit' ? '75.00' : '200.00';
        }
    };
    $wrongView=new FakeCart([['data'=>$wrongViewProduct,'quantity'=>1]]);
    $GLOBALS['cart']=$wrongView;
    $assert(Andy_Commerce_Shipping_USD_Source_V1::shipping_subtotal($wrongView)===null,
        'unverified dynamic view price must reject native shipping');
    $assert(Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping(true,['zone_id'=>5],null)===false,
        'invalid dynamic cart cannot receive free rate');
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='JPY';
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'unsupported currency rejected');
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency='USD';
    $GLOBALS['price_tax']='yes';
    $assert(Andy_Commerce_Shipping_USD_Source_V1::subtotal($bad)===null,'tax-included case rejected until audited');
    echo "PASS shipping USD source five currencies $pass assertions; 4 Dev failure regressions covered; no writes\n";
}
