<?php
/**
 * R10-H2 manual-over-auto coupon priority planner. SOURCE/SHADOW ONLY.
 *
 * Never registered as a WooCommerce hook; never reads a request/session,
 * touches an order/cart, or writes WordPress options. Caller must verify
 * provenance at the server-side hook where it originated, not in the browser.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10H2_Manual_Priority_Shadow {
    private const ORDINARY = ['percent', 'fixed_cart', 'fixed_product'];
    private const PROTECTED = ['store_credit', 'bogo', 'gift'];
    private const AUTO_PROVIDERS = ['woocommerce', 'webtoffee', 'storeapps'];

    private static function coupon_code($code): ?string {
        if (!is_string($code) || strlen($code) > 80) return null;
        $code = strtolower(trim($code));
        // A narrow input contract for the SOURCE-only candidate; unknown
        // vendor codes deliberately fail closed instead of guessing.
        return preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $code) ? $code : null;
    }

    private static function classify($row): ?array {
        if (!is_array($row)) return null;
        $code = self::coupon_code($row['code'] ?? null);
        $type = $row['type'] ?? null;
        if ($code === null || !is_string($type)
            || !array_key_exists('free_shipping', $row)
            || !is_bool($row['free_shipping'])) return null;

        // Woo's free-shipping entitlement must not be taken away by a
        // single-ordinary-coupon policy, even with fixed_cart metadata.
        if ($row['free_shipping']) return ['code'=>$code, 'kind'=>'protected'];

        if (in_array($type, self::ORDINARY, true)) {
            $amount = $row['amount'] ?? null;
            if (!(is_int($amount) || is_string($amount))
                || !preg_match('/^\d{1,9}(?:\.\d{1,4})?$/D', (string)$amount)) return null;
            // Zero-value normal coupons are not an ordinary positive reward.
            return ['code'=>$code,
                    'kind'=>(float)$amount>0.0 ? 'ordinary' : 'protected'];
        }
        if (in_array($type, self::PROTECTED, true)
            && ($row['protected_verified'] ?? false) === true) {
            return ['code'=>$code, 'kind'=>'protected'];
        }
        return null; // Unknown or unverified vendor reward: do not mutate.
    }

    private static function origin($row): ?string {
        if (!is_array($row) || ($row['origin_verified'] ?? false) !== true) return null;
        $origin = $row['origin'] ?? null;
        $evidence = $row['origin_evidence'] ?? null;
        if ($origin === 'manual' && $evidence === 'trusted_server_customer_submission') {
            return 'manual';
        }
        if ($origin === 'auto' && $evidence === 'trusted_vendor_auto_apply'
            && in_array($row['vendor'] ?? null, self::AUTO_PROVIDERS, true)) {
            return 'auto';
        }
        return null;
    }

    private static function fingerprint(array $applied): string {
        // Ordered and complete snapshot. Future Woo adapters must re-read
        // and compare against the same trusted canonical applied coupon data.
        return hash('sha256', json_encode($applied, JSON_UNESCAPED_SLASHES));
    }

    private static function outcome(string $action, string $reason,
        array $remove = [], ?string $snapshot = null): array {
        return [
            'action'=>$action,
            'reason'=>$reason,
            'remove_codes'=>$remove,
            'expected_snapshot'=>$snapshot,
            'writes_performed'=>0,
        ];
    }

    /**
     * $incoming/$applied are trusted, server-derived coupon descriptions,
     * NOT client-submitted JSON. No effects, only a prospective decision.
     * 'replace_auto' is NOT permission to remove a Woo coupon at this stage.
     */
    public static function plan($incoming, $applied, $prior_vendor_valid): array {
        if ($prior_vendor_valid !== true) return self::outcome('deny','vendor_veto');
        $next = self::classify($incoming);
        if ($next===null || !is_array($applied) || count($applied)>50) {
            return self::outcome('deny','unverified_coupon_snapshot');
        }
        // Validate entire snapshot first: partial/provenance-free lists
        // must not be trusted for *any* replace or second ordinary discount.
        $ordinary=[];
        $seen=[];
        foreach ($applied as $row) {
            $c=self::classify($row);
            if ($c===null || isset($seen[$c['code']])) {
                return self::outcome('deny','unverified_coupon_snapshot');
            }
            $seen[$c['code']]=true;
            if ($c['kind']==='ordinary') {
                $src=self::origin($row);
                if ($src===null) return self::outcome('deny','unverified_origin');
                $ordinary[]=['code'=>$c['code'],'origin'=>$src];
            }
        }
        if (count($ordinary)>1) return self::outcome('deny','multiple_ordinary_existing');

        // Non-ordinary entitlements (free shipping / verified credits / BOGO
        // / gifts / zero-value) are NEVER removed or replaced by this policy.
        if ($next['kind']==='protected') return self::outcome('keep','protected_reward');
        $incomingOrigin=self::origin($incoming);
        if ($incomingOrigin===null) return self::outcome('deny','unverified_origin');

        if (!$ordinary) return self::outcome('keep','ordinary_first');
        $prior=$ordinary[0];
        if ($prior['code']===$next['code']) return self::outcome('keep','idempotent_same_code');
        if ($incomingOrigin==='manual' && $prior['origin']==='auto') {
            return self::outcome('replace_auto','manual_priority_verified',
                [$prior['code']], self::fingerprint($applied));
        }
        return self::outcome('deny','one_ordinary_code_limit');
    }

    /** Pure optimistic-concurrency recheck for a future atomic adapter. */
    public static function snapshot_matches($expected, $applied): bool {
        return is_string($expected) && strlen($expected)===64
            && is_array($applied) && count($applied)<=50
            && hash_equals($expected, self::fingerprint($applied));
    }
}
