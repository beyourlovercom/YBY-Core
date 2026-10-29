#!/usr/bin/env python3
"""CI-only synthetic source fixture for the exact Dev BF2026 patch transformer."""
import importlib.util
import os
import pathlib
import tempfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
BUILDER = ROOT / "integrations" / "black-friday-2026" / "build-dev-yay-gift-bridge.py"
spec = importlib.util.spec_from_file_location("bf_yay_builder", BUILDER)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

BEFORE = """<?php
if (!defined('ABSPATH')) exit;
final class BYL_BF2026_Gifts {
    public const ID = 'byl-black-friday-2026';
    public const CART_FLAG = 'andy_campaign_pack_id';
    /**
     * Deliberately USD-only until Commerce can supply an audited post-coupon
     * base-USD entitlement. FX display totals alone cannot prove the $199
     * threshold: Dev GBP rate 0.749160 rounds USD198.99 and 199.00 to the
     * same local penny. Never infer gift entitlements from inverse FX.
     */
    private static function valid_currency(): bool {
        return get_woocommerce_currency()==='USD';
    }

    private static function local_upgrade_price( int $usd_minor ): ?string {
        return number_format($usd_minor/100,2,'.','');
    }

    public static function is_gift( array $line ): bool {
        return ($line[self::CART_FLAG] ?? null) === self::ID
            && ($line['_byl_bf2026_gift'] ?? null) === true;
    }

    /** Woo native line_total already includes cart coupon allocation, excludes tax and shipping. */
    public static function eligibility( $cart ): array {
        if ( ! $cart instanceof WC_Cart || ! self::valid_currency() ) {
            return array( 'basis_minor' => 0, 'slots' => 0 );
        }
        $basis = 0;
        foreach($cart->get_cart() as $line){
            if(self::is_gift($line))continue;
            $basis += (int) round((float)$line['line_total'] * 100);
        }
        return array('basis_minor'=>$basis,
            'slots'=>$basis <= 0 ? 0 : ($basis >= 19900 ? 2 : 1));
    }

    public static function reprice( $cart ): void {
        // Currency Runtime's Woo price getter converts source USD once.
        // Writing local currency here would cause a second FX conversion.
    }

    public static function render(): string {
                $symbol = function_exists( 'get_woocommerce_currency_symbol' )
                    ? get_woocommerce_currency_symbol( get_woocommerce_currency() ) : '';
                $label = $upgrade > 0 && null !== $local
                    ? ' · Upgrade +' . $symbol . $local : ' · FREE';
        return $label;
    }
}
"""

patched = module.build(BEFORE)
assert "Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote($cart)" in patched
assert "Andy_Commerce_Campaign_USD_Basis_V1::quote(" not in patched
assert "Andy_Commerce_Currency_Runtime_Dev_V1::convert(" not in patched
assert "eligibility( $cart )" in patched
assert "approx." in patched
output = pathlib.Path(os.environ.get("RUNNER_TEMP", tempfile.gettempdir())) / "bf2026-yay-ci-candidate.php"
output.write_text(patched, encoding="utf-8")
print("BF2026_YAY_PATCH_FIXTURE_CONTRACT_PASS=1")
print("BF2026_YAY_PATCH_OUTPUT="+str(output))
