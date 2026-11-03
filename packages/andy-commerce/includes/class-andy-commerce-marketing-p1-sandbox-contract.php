<?php
/**
 * M-P1: explicit, version-bound Marketing sandbox activation contract.
 *
 * This class never publishes a campaign, registers Woo hooks, or changes M7's
 * OFF-only drafts. #213 owns bootstrap, admin approval and the only Dev ZIP.
 * This contract fails closed anywhere but the disposable Woo sandbox.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

require_once __DIR__.'/class-andy-commerce-m7-draft-studio.php';

final class Andy_Commerce_Marketing_P1_Sandbox_Contract {
    public const APPROVAL_OPTION = 'andy_commerce_marketing_p1_approved_snapshot';
    public const SWITCH_OPTION = 'andy_commerce_marketing_p1_sandbox_enabled';

    /**
     * Transitional two-engine safety veto. This legacy Dev-only executor may
     * never calculate a new promotion while Andy Campaign master is ON.
     * Do not delete read-only drafts, previews, or existing M15/M16 evidence.
     */
    public static function legacy_runtime_allowed(): bool {
        return function_exists( 'get_option' )
            && get_option( 'andy_campaign_lite_master', 'no' ) !== 'yes';
    }
    private const LOCAL_URL = 'http://127.0.0.1:18973';
    private const LOCAL_DB = 'aicc_commerce_p5_20261023';

    public static function isolated(): bool {
        return (defined('AICC_MARKETING_RUNTIME_SANDBOX')
                && AICC_MARKETING_RUNTIME_SANDBOX === true
                && self::local_origin())
            || self::approved_dev_owner();
    }

    /** Dev-only UAT requires HTTPS, explicit wp-config opt-in, a logged-in
     * Woo administrator and two independent OFF-by-default WordPress switches.
     * No guest checkout or production URL may activate Marketing from this.
     */
    private static function dev_identity(): bool {
        return defined('AICC_MARKETING_DEV_UAT')
            && AICC_MARKETING_DEV_UAT === true
            && function_exists('home_url')
            && rtrim(home_url(), '/') === 'https://dev.beyourlover.com'
            && function_exists('is_ssl') && is_ssl()
            && function_exists('wp_get_environment_type')
            && in_array(wp_get_environment_type(), ['staging','development'], true);
    }

    /** Runtime checks the buyer's active currency, not just the store base. */
    private static function dev_origin(): bool {
        return self::dev_identity()
            && function_exists('get_woocommerce_currency')
            && get_woocommerce_currency() === 'USD'
            && function_exists('wc_get_price_decimals')
            && wc_get_price_decimals() === 2;
    }

    /** An authorized administrator may PREPARE while viewing EUR/GBP.
     * The site's BASE currency must remain USD; actual promotion runtime
     * continues to require USD in dev_origin().
     */
    public static function dev_admin_setup(): bool {
        return self::dev_identity()
            && function_exists('get_option')
            && get_option('woocommerce_currency','') === 'USD'
            && function_exists('wc_get_price_decimals')
            && wc_get_price_decimals() === 2
            && function_exists('is_user_logged_in') && is_user_logged_in()
            && function_exists('current_user_can') && current_user_can('manage_woocommerce');
    }

    public static function approved_dev_owner(): bool {
        return self::dev_origin() && self::dev_admin_setup()
            && get_option('andy_commerce_marketing_dev_uat_enabled','no') === 'yes';
    }

    /**
     * Site identity remains checkable even after the opt-in flag is removed,
     * so M-P2 can clean a previously inserted session coupon or gift.
     * This never authorizes applying a new promotion by itself.
     */
    public static function disposable_origin(): bool {
        return self::local_origin() || self::approved_dev_owner();
    }

    private static function local_origin(): bool {
        return defined('DB_NAME') && DB_NAME === self::LOCAL_DB
            && function_exists('home_url') && home_url() === self::LOCAL_URL
            && function_exists('wp_get_environment_type')
            && wp_get_environment_type() === 'local'
            && defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL === true
            && defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true
            && function_exists('get_woocommerce_currency')
            && get_woocommerce_currency() === 'USD'
            && function_exists('wc_get_price_decimals')
            && wc_get_price_decimals() === 2;
    }

    private static function valid_draft(array $draft): bool {
        $type=$draft['type']??null;
        if (($draft['active']??null)!==false || ($draft['status']??null)!=='draft'
            || ($draft['lifecycle']??null)!=='scheduled'
            || !is_string($draft['id']??null)
            || !preg_match('/^[A-Za-z0-9_-]{8,64}$/D',$draft['id'])
            || !is_int($draft['revision']??null) || $draft['revision']<1
            || !in_array($type,['spend_save','free_gift','bogo','bundles','tiered'],true)
            || ($draft['currency']??null)!=='USD'
            || !is_int($draft['priority']??null)
            || $draft['priority']<0 || $draft['priority']>1000
            || !is_array($draft['rule']??null)
            || !is_array($draft['policy']??null)
            || !is_array($draft['countries']??null)
            || !$draft['countries'] || count($draft['countries'])>30
            || count(array_unique($draft['countries']))!==count($draft['countries'])) return false;
        foreach ($draft['countries'] as $country) {
            if(!is_string($country)||!preg_match('/^[A-Z]{2}$/D',$country)) return false;
        }
        try {
            $policy=Andy_Commerce_M10_Policy_Fields::normalize([
                'start_date'=>$draft['policy']['start_date']??'',
                'end_date'=>$draft['policy']['end_date']??'',
                'product_scope'=>$draft['policy']['product_scope']??'all',
                'product_ids'=>implode(',',(array)($draft['policy']['product_ids']??[])),
                'stack_policy'=>$draft['policy']['stack_policy']??'best_single',
            ]);
        } catch (Throwable $e) { return false; }
        if ($policy['product_scope']!=='all' && !$policy['product_ids']) return false;
        // Business amounts and rule validity are checked AGAIN by M10 on the
        // actual verified Woo cart. Do not add a second promotion engine.
        return true;
    }

    private static function canonical(array $draft): array {
        return [
            'id'=>$draft['id'], 'revision'=>$draft['revision'],
            'name'=>$draft['name']??'', 'type'=>$draft['type'],
            'rule'=>$draft['rule'], 'policy'=>$draft['policy'],
            'countries'=>array_values($draft['countries']),
            'priority'=>$draft['priority'], 'currency'=>'USD',
            'lifecycle'=>'scheduled','status'=>'draft','active'=>false,
        ];
    }

    /**
     * Pure preparation: an authorized sandbox operator stores this snapshot
     * separately, then explicitly flips SWITCH_OPTION. No state writes here.
     */
    public static function prepare(array $inactiveDraft): array {
        if (!self::isolated() && !self::dev_admin_setup())
            throw new RuntimeException('M_P1_UAT_OPERATOR_ONLY');
        if (!self::valid_draft($inactiveDraft))
            throw new InvalidArgumentException('M_P1_INVALID_OFF_SCHEDULED_DRAFT');
        $draft=self::canonical($inactiveDraft);
        $fingerprint=hash('sha256',json_encode($draft,JSON_THROW_ON_ERROR));
        return ['schema'=>1,'draft_id'=>$draft['id'],
            'source_revision'=>$draft['revision'],
            'fingerprint'=>$fingerprint,'draft'=>$draft];
    }

    /**
     * Only one selected, exactly revision-matched M7 OFF draft is allowed.
     * A modification, deletion, malformed snapshot or absent opt-in stops it.
     */
    public static function current(): ?array {
        if (!self::legacy_runtime_allowed()
            || !self::isolated()
            || !function_exists('get_option')
            || get_option(self::SWITCH_OPTION,'no')!=='yes') return null;
        $stored=get_option(self::APPROVAL_OPTION,null);
        if (!is_array($stored)||($stored['schema']??null)!==1
            || !is_array($stored['draft']??null)
            || !is_string($stored['fingerprint']??null)
            || !is_string($stored['draft_id']??null)
            || !is_int($stored['source_revision']??null)
            || !self::valid_draft($stored['draft'])) return null;
        $canonical=self::canonical($stored['draft']);
        try {
            $digest=hash('sha256',json_encode($canonical,JSON_THROW_ON_ERROR));
        } catch (Throwable $e) { return null; } // malformed stored input fails closed
        if (($canonical['id']??null)!==$stored['draft_id']
            || ($canonical['revision']??null)!==$stored['source_revision']
            || !hash_equals($digest,$stored['fingerprint'])) return null;
        if (!class_exists('Andy_Commerce_M7_Draft_Studio')) return null;
        $rows=Andy_Commerce_M7_Draft_Studio::rows();
        $latest=$rows[$stored['draft_id']]??null;
        if (!is_array($latest)||!self::valid_draft($latest)
            || self::canonical($latest)!==$canonical) return null;
        return $canonical; // Still OFF for existing M10 preview API.
    }
}
