<?php
/**
 * Creator Dashboard V1: narrow WWW read-only AffiliateWP snapshots.
 * A dedicated secret in wp-config is required. Never reuse ERP HMAC or AffiliateWP admin keys.
 * No WordPress users, referrals, payouts, or configuration are modified by this route.
 */
defined('ABSPATH') || exit;

final class YBY_Creator_Readonly_History {
    const ROUTE = '/creator/history';
    const LIMIT_REFERRALS = 12;
    const LIMIT_PAYOUTS = 4;

    public static function register_routes(): void {
        register_rest_route('andy-core/v1', self::ROUTE, [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => [__CLASS__, 'authorize'],
        ]);
    }

    private static function deny(string $message = 'Creator history authorization denied.', int $status = 401): WP_Error {
        return new WP_Error('byl_creator_readonly_denied', $message, ['status'=>$status]);
    }

    private static function page($value): int {
        if (!(is_string($value) || is_int($value)) || !preg_match('/^[0-9]{1,5}$/D', (string)$value)) { return 0; }
        $number = (int)$value;
        return $number >= 1 && $number <= 1000 ? $number : 0;
    }

    public static function authorize($request) {
        if (!defined('BYL_CREATOR_REMOTE_API_ENABLED') || BYL_CREATOR_REMOTE_API_ENABLED !== true ||
            !defined('BYL_CREATOR_REMOTE_KEY_ID') || !defined('BYL_CREATOR_REMOTE_SIGN_KEY') ||
            !is_string(BYL_CREATOR_REMOTE_SIGN_KEY) || strlen(BYL_CREATOR_REMOTE_SIGN_KEY) < 32 ||
            !is_string(BYL_CREATOR_REMOTE_KEY_ID) || !preg_match('/^[A-Za-z0-9._-]{8,64}$/D', BYL_CREATOR_REMOTE_KEY_ID) ||
            !is_ssl() || $request->get_method() !== 'GET') { return self::deny(); }

        $key = (string)$request->get_header('X-BYL-Creator-Key');
        $ts = (string)$request->get_header('X-BYL-Creator-Time');
        $nonce = (string)$request->get_header('X-BYL-Creator-Nonce');
        $signature = (string)$request->get_header('X-BYL-Creator-Signature');
        if (!hash_equals(BYL_CREATOR_REMOTE_KEY_ID, $key) ||
            !preg_match('/^[0-9]{10}$/D', $ts) || abs(time() - (int)$ts) > 120 ||
            !preg_match('/^[a-f0-9]{32}$/D', $nonce) ||
            !preg_match('/^[a-f0-9]{64}$/D', $signature)) { return self::deny(); }

        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if ($path !== '/wp-json/andy-core/v1/creator/history') { return self::deny(); }
        $query = wp_parse_url($uri, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') { return self::deny(); }
        parse_str($query, $params);
        if (!is_array($params) || count($params) !== 5 ||
            array_keys($params) !== ['dev_user_id','affiliate_id','wp_user_id','ref_page','pay_page']) { return self::deny(); }
        if (!is_string($params['dev_user_id']) || !ctype_digit($params['dev_user_id']) || (int)$params['dev_user_id'] < 1 || (int)$params['dev_user_id'] > 2147483647 || !is_string($params['affiliate_id']) || !ctype_digit($params['affiliate_id']) || (int)$params['affiliate_id'] < 1 || (int)$params['affiliate_id'] > 2147483647 || !is_string($params['wp_user_id']) || !ctype_digit($params['wp_user_id']) || (int)$params['wp_user_id'] < 1 || (int)$params['wp_user_id'] > 2147483647 ||
            self::page($params['ref_page']) === 0 || self::page($params['pay_page']) === 0) { return self::deny(); }
        // The authenticated Dev server supplies both exact IDs, but the provider
        // independently verifies ownership before returning anything.
        $canonical = "GET\n".$uri."\n".$ts."\n".$nonce."\n".$key;
        $expected = hash_hmac('sha256', $canonical, BYL_CREATOR_REMOTE_SIGN_KEY);
        if (!hash_equals($expected, $signature)) { return self::deny(); }
        $reuse = 'byl_creator_nonce_'.hash('sha256', $key.'|'.$nonce);
        if (get_transient($reuse)) { return self::deny('Duplicate request.', 409); }
        set_transient($reuse, '1', 180);
        return true;
    }

    private static function owned_records($records, int $affiliate_id, int $limit, array $fields) {
        if (!is_array($records)) { return new WP_Error('byl_creator_provider_unavailable','Provider records unavailable.', ['status'=>503]); }
        $out=[];
        foreach ($records as $record) {
            if (!is_object($record) || !isset($record->affiliate_id) || (int)$record->affiliate_id !== $affiliate_id) {
                return new WP_Error('byl_creator_source_mismatch','Provider ownership mismatch.', ['status'=>503]);
            }
            $row=['affiliate_id'=>$affiliate_id];
            foreach ($fields as $field) {
                $value = $record->$field ?? null;
                if (is_scalar($value) && strlen((string)$value) <= 100) { $row[$field]=$value; }
            }
            $out[]=$row;
        }
        return [
            'rows'=>array_slice($out,0,$limit),
            'more'=>count($out)>$limit,
        ];
    }

    public static function handle($request) {
        if (!function_exists('affwp_get_affiliate_id') || !function_exists('affwp_get_affiliate_status') ||
            !function_exists('affiliate_wp') || !function_exists('affwp_get_currency')) {
            return self::deny('AffiliateWP provider unavailable.',503);
        }
        $params=$request->get_params();
        $www_user_id=(int)$params['wp_user_id'];
        $affiliate_id=(int)$params['affiliate_id'];
        $dev_user_id=(int)$params['dev_user_id'];
        // Dedicated key is permitted to read only independently allowlisted
        // Dev-account ? WWW-account ? affiliate triples.
        if (!defined('BYL_CREATOR_REMOTE_BINDINGS') || !is_array(BYL_CREATOR_REMOTE_BINDINGS)) {
            return self::deny('No approved account bindings.', 403);
        }
        $approved = BYL_CREATOR_REMOTE_BINDINGS[$dev_user_id] ?? null;
        if (!is_array($approved) || (int)($approved['wp_user_id'] ?? 0) !== $www_user_id ||
            (int)($approved['affiliate_id'] ?? 0) !== $affiliate_id) {
            return self::deny('Creator mapping is not approved.', 403);
        }
        $user=get_userdata($www_user_id);
        if (!$user || !(int)$user->ID || (int)affwp_get_affiliate_id($www_user_id)!==$affiliate_id ||
            affwp_get_affiliate_status($affiliate_id)!=='active') {
            return self::deny('Creator identity not approved.',403);
        }
        $engine=affiliate_wp();
        if (!is_object($engine) || !isset($engine->affiliates,$engine->referrals) ||
            !method_exists($engine->affiliates,'get_affiliate') ||
            !method_exists($engine->referrals,'get_referrals')) { return self::deny('AffiliateWP provider unavailable.',503); }
        $payout_provider = isset($engine->payouts) ? $engine->payouts : ($engine->affiliates->payouts ?? null);
        if (!is_object($payout_provider) || !method_exists($payout_provider,'get_payouts')) { return self::deny('AffiliateWP payout provider unavailable.',503); }
        $account=$engine->affiliates->get_affiliate($affiliate_id);
        if (!is_object($account) || (isset($account->user_id) && (int)$account->user_id!==$www_user_id)) {
            return self::deny('Affiliate identity mismatch.',403);
        }

        $ref_limit=self::LIMIT_REFERRALS; $pay_limit=self::LIMIT_PAYOUTS;
        $ref_rows=$engine->referrals->get_referrals([
            'affiliate_id'=>$affiliate_id,'number'=>$ref_limit+1,
            'offset'=>((int)$params['ref_page']-1)*$ref_limit,'orderby'=>'date','order'=>'DESC',
        ]);
        $pay_rows=$payout_provider->get_payouts([
            'affiliate_id'=>$affiliate_id,'number'=>$pay_limit+1,
            'offset'=>((int)$params['pay_page']-1)*$pay_limit,'orderby'=>'date','order'=>'DESC',
        ]);
        $refs=self::owned_records($ref_rows,$affiliate_id,$ref_limit,['referral_id','date','amount','status','currency']);
        $pays=self::owned_records($pay_rows,$affiliate_id,$pay_limit,['date','amount','status','currency']);
        if (is_wp_error($refs)) { return $refs; }
        if (is_wp_error($pays)) { return $pays; }

        $totals=[];
        foreach (['earnings','unpaid_earnings','visits','referrals'] as $field) {
            $val=$account->$field ?? null;
            $totals[$field]=is_numeric($val) && (float)$val>=0 ? $val : null;
        }
        $currency=(string)affwp_get_currency();
        if (!preg_match('/^[A-Z]{3}$/D',$currency)) { return self::deny('Currency source unavailable.',503); }
        $url='';
        if (function_exists('affwp_get_affiliate_referral_url')) {
            $candidate=affwp_get_affiliate_referral_url(['affiliate_id'=>$affiliate_id,'base_url'=>home_url('/')]);
            if (is_string($candidate) && wp_http_validate_url($candidate) &&
                wp_parse_url($candidate,PHP_URL_HOST) === wp_parse_url(home_url('/'),PHP_URL_HOST)) { $url=$candidate; }
        }
        nocache_headers();
        return new WP_REST_Response([
            'ok'=>true,
            'data'=>[
                'affiliate_id'=>$affiliate_id,'wp_user_id'=>$www_user_id,'status'=>'active',
                'account'=>$totals,'currency'=>$currency,
                'referrals'=>$refs['rows'],'payouts'=>$pays['rows'],
                'more_referrals'=>$refs['more'],'more_payouts'=>$pays['more'],
                'referral_url'=>$url,
            ],
        ],200);
    }
}
