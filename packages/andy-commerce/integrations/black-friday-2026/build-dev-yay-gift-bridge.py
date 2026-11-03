#!/usr/bin/env python3
"""Build a guarded, non-deployed BF2026 gift bridge for YayCurrency Pro.

Default is CHECK ONLY against the exact Dev source; no production operations.
Emits a candidate .php under ~/aicc-dev-staging after SHA validation.
The release owner must separately approve a Dev-only deploy after CI and
Commerce publisher integration. Never run against WWW.
"""
import argparse, hashlib, pathlib, sys

BASE_SHA="b08dc6993b533387eb2811c9d56ef5b216b65d0c04bd901b92ccd8a1587e147d"
DEV_SOURCE=pathlib.Path("/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026/includes/class-byl-bf2026-gifts.php")
OUT=pathlib.Path("/home/u247343654/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php")

def replace_block(s, start, stop, replacement):
    a=s.find(start)
    if a<0 or s.find(start,a+1)!=-1: raise RuntimeError("ambiguous anchor "+start[:45])
    b=s.find(stop,a+len(start))
    if b<0: raise RuntimeError("missing stop "+stop[:45])
    return s[:a]+replacement.rstrip()+"\n\n"+s[b:]

def build(source):
    s=replace_block(source,
        "    /**\n     * Deliberately USD-only until Commerce can supply",
        "    public static function is_gift( array $line ): bool {",
        """    /**
     * YayCurrency Pro is the sole currency engine. The Woo checkout code
     * can remain USD while Yay displays EUR/GBP/AUD/CAD.
     */
    private static function valid_currency(): bool {
        return class_exists('Andy_Commerce_Campaign_Yay_USD_Basis_V1')
            && null !== Andy_Commerce_Campaign_Yay_USD_Basis_V1::storefront_currency();
    }

    /** Local-display amount only; all Woo pricing starts from source USD. */
    private static function local_upgrade_price( int $usd_minor ): ?string {
        if ( ! self::valid_currency() ) { return null; }
        return Andy_Commerce_Campaign_Yay_USD_Basis_V1::display_usd_minor($usd_minor);
    }""")
    s=replace_block(s,
        "    /** Woo native line_total already includes cart coupon allocation",
        "    public static function reprice( $cart ): void {",
        """    /**
     * All campaign thresholds use USD source merchandise NET after native
     * coupons; never interpret converted Woo display totals as USD.
     * Excludes gifts, upgrade fees, tax and shipping, fail closed on unknown.
     */
    public static function eligibility( $cart ): array {
        if ( ! $cart instanceof WC_Cart || ! self::valid_currency() ) {
            return array( 'basis_minor' => 0, 'slots' => 0 );
        }
        $quote = Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote($cart);
        if ( ($quote['ok'] ?? false) !== true
             || ! is_int($quote['basis_minor'] ?? null)
             || ! is_int($quote['gift_slots'] ?? null) ) {
            return array('basis_minor' => 0, 'slots' => 0);
        }
        return array(
            'basis_minor' => $quote['basis_minor'],
            'slots' => $quote['gift_slots'],
        );
    }""")
    s=s.replace("            // Currency Runtime's Woo price getter converts source USD once.\n            // Writing local currency here would cause a second FX conversion.",
     "            // YayCurrency Pro converts source USD from the native Woo price getter.\n            // Writing local currency here would cause a second conversion.")
    old="""                $symbol = function_exists( 'get_woocommerce_currency_symbol' )
                    ? get_woocommerce_currency_symbol( get_woocommerce_currency() ) : '';
                $label = $upgrade > 0 && null !== $local
                    ? ' · Upgrade +' . $symbol . $local : ' · FREE';"""
    new="""                $selected = Andy_Commerce_Campaign_Yay_USD_Basis_V1::storefront_currency();
                $symbol = function_exists('get_woocommerce_currency_symbol')
                    ? get_woocommerce_currency_symbol($selected ?: 'USD') : '';
                $checkout_code = get_woocommerce_currency();
                $label = ' · FREE';
                if ($upgrade > 0 && null !== $local) {
                    $label = ( $selected !== $checkout_code )
                        ? ' · Upgrade +$' . number_format($upgrade/100,2,'.','')
                          . ' USD (approx. ' . $symbol . $local . ')'
                        : ' · Upgrade +' . $symbol . $local;
                }"""
    if s.count(old)!=1: raise RuntimeError("gift upgrade display anchor changed")
    s=s.replace(old,new)
    if "Andy_Commerce_Currency_Runtime_Dev_V1::convert(" in s or "Andy_Commerce_Campaign_USD_Basis_V1::quote(" in s:
        raise RuntimeError("legacy currency runtime call survived")
    return s

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument("--check",action="store_true")
    args=ap.parse_args()
    if not DEV_SOURCE.is_file(): raise RuntimeError("not Dev: no source file")
    b=DEV_SOURCE.read_bytes()
    checksum=hashlib.sha256(b).hexdigest()
    if checksum!=BASE_SHA: raise RuntimeError("unexpected Dev BF source sha="+checksum)
    text=b.decode("utf-8")
    if "Version: 0.1.0" in text:
        raise RuntimeError("wrong source file (main plugin not class)")
    patched=build(text)
    if args.check:
        print("BF_DEV_YAY_PATCH_CHECK_PASS original_sha="+checksum)
        print("BF_DEV_YAY_CANDIDATE_SHA="+hashlib.sha256(patched.encode()).hexdigest())
        return
    if not str(OUT).startswith("/home/u247343654/aicc-dev-staging/"):
        raise RuntimeError("not a staging output")
    OUT.parent.mkdir(parents=True,exist_ok=True)
    OUT.write_text(patched,encoding="utf-8")
    print("BF_DEV_YAY_CANDIDATE_STAGED="+str(OUT))
    print("BF_DEV_YAY_CANDIDATE_SHA="+hashlib.sha256(patched.encode()).hexdigest())
    print("NOT_DEPLOYED=YES")
if __name__=="__main__":
    try: main()
    except Exception as e:
        print("BF_DEV_YAY_PATCH_ABORT="+str(e),file=sys.stderr)
        raise SystemExit(1)
