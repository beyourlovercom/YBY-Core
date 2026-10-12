<?php
/**
 * Andy Commerce Marketing M3 — IN-MEMORY, OFFLINE, DRAFT-ONLY sandbox.
 *
 * No WordPress bootstrap, DB persistence, options, HTTP endpoints, hooks,
 * scheduling, publishing or WooCommerce runtime integration.
 *
 * This is a pure validation/workflow prototype for a future, separately
 * authorized Andy Commerce Marketing admin. A real WP controller MUST verify
 * current_user_can(), a nonce, ownership and optimistic locking server-side.
 * Passing sandbox guard booleans here does NOT provide production security.
 *
 * IMPORTANT: Not loaded by andy-commerce.php / class-andy-commerce.php.
 */
declare(strict_types=1);

final class Andy_Commerce_Marketing_Draft_Sandbox {
    private const BASIS = 'post_discount_merchandise_subtotal_excluding_tax_and_shipping';
    private const TYPES = ['bogo','bundle','spend_save','tiered','free_gift','free_shipping'];
    private const FIELDS = [
        'schema_version','id','name','type','currency','status','active','priority',
        'countries','start_utc','end_utc','stack_with_coupon','rule','notes'
    ];

    private array $drafts = [];
    private array $events = [];

    public function create(array $raw, array $guard): array {
        self::gate($guard);
        $draft = self::validate($raw);
        $id = $draft['id'];
        if (isset($this->drafts[$id])) {
            throw new DomainException('Campaign ID already exists');
        }
        $draft['revision'] = 1;
        $draft['archived'] = false;
        $this->drafts[$id] = $draft;
        $this->audit('create', $draft, $guard);
        return $draft;
    }

    public function revise(string $id, int $expectedRevision, array $raw, array $guard): array {
        self::gate($guard);
        $this->requireDraft($id, $expectedRevision);
        $draft = self::validate($raw);
        if ($draft['id'] !== $id || $draft['type'] !== $this->drafts[$id]['type']) {
            throw new DomainException('Campaign ID and type are immutable');
        }
        $draft['revision'] = $expectedRevision + 1;
        $draft['archived'] = false;
        $this->drafts[$id] = $draft;
        $this->audit('revise', $draft, $guard);
        return $draft;
    }

    public function archive(string $id, int $expectedRevision, array $guard): array {
        self::gate($guard);
        $this->requireDraft($id, $expectedRevision);
        $draft = $this->drafts[$id];
        $draft['archived'] = true;
        $draft['revision']++;
        $this->drafts[$id] = $draft;
        $this->audit('archive', $draft, $guard);
        return $draft;
    }

    public function find(string $id): ?array { return $this->drafts[$id] ?? null; }
    public function listDrafts(): array {
        return array_values(array_filter($this->drafts, static fn(array $item): bool => !$item['archived']));
    }
    // Archived records are available only through explicit ID lookup; they are
    // not displayed as actionable draft campaigns.
    public function auditEvents(): array { return $this->events; }

    private function requireDraft(string $id, int $revision): void {
        if (!isset($this->drafts[$id]) || $this->drafts[$id]['archived']) {
            throw new DomainException('Draft missing or archived');
        }
        if ($revision < 1 || $revision !== $this->drafts[$id]['revision']) {
            throw new DomainException('Stale revision; no overwrite');
        }
    }

    private function audit(string $action, array $draft, array $guard): void {
        // Bounded internal-only synthetic event, no customer or payment data.
        $this->events[] = [
            'event' => $action, 'id' => $draft['id'], 'revision' => $draft['revision'],
            'actor_id' => $guard['actor_id'], 'at_unix' => $guard['now_unix']
        ];
    }

    private static function gate(array $guard): void {
        if (($guard['sandbox'] ?? false) !== true
            || ($guard['manage_woocommerce_checked'] ?? false) !== true
            || ($guard['nonce_checked'] ?? false) !== true
            || !is_int($guard['actor_id'] ?? null) || $guard['actor_id'] < 1
            || !is_int($guard['now_unix'] ?? null) || $guard['now_unix'] < 1) {
            throw new DomainException('Draft sandbox requires verified synthetic guard');
        }
    }

    public static function validate(array $raw): array {
        foreach ($raw as $key => $_) {
            if (!in_array($key, self::FIELDS, true)) {
                throw new InvalidArgumentException('Unknown campaign field');
            }
        }
        if (($raw['schema_version'] ?? null) !== 1
            || ($raw['status'] ?? null) !== 'draft'
            || ($raw['active'] ?? null) !== false) {
            throw new InvalidArgumentException('Only version 1 inactive draft campaigns are supported');
        }
        $id = $raw['id'] ?? null;
        $name = $raw['name'] ?? null;
        $type = $raw['type'] ?? null;
        $currency = $raw['currency'] ?? null;
        $priority = $raw['priority'] ?? 0;
        if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9_-]{2,59}$/D', $id)
            || !is_string($name) || strlen($name) < 2 || strlen($name) > 120
            || !preg_match('//u', $name) || trim($name) === ''
            || preg_match('/[\x00-\x1f\x7f]/', $name)
            || !is_string($type) || !in_array($type, self::TYPES, true)
            || !is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)
            || !is_int($priority) || $priority < -1000 || $priority > 1000) {
            throw new InvalidArgumentException('Invalid campaign identity or ranking');
        }
        $countries = $raw['countries'] ?? [];
        if (!is_array($countries) || count($countries) > 30) {
            throw new InvalidArgumentException('Invalid country selection');
        }
        foreach ($countries as $country) {
            if (!is_string($country) || !preg_match('/^[A-Z]{2}$/D', $country)) {
                throw new InvalidArgumentException('Invalid country');
            }
        }
        if (count(array_unique($countries)) !== count($countries)) {
            throw new InvalidArgumentException('Duplicate country');
        }
        $start = self::dateUtc($raw['start_utc'] ?? null);
        $end = self::dateUtc($raw['end_utc'] ?? null);
        if ($start !== null && $end !== null && strcmp($start,$end) >= 0) {
            throw new InvalidArgumentException('End date must be after start date');
        }
        $stack = $raw['stack_with_coupon'] ?? false;
        if (!is_bool($stack)) { throw new InvalidArgumentException('Invalid stack setting'); }
        $notes = $raw['notes'] ?? '';
        if (!is_string($notes) || strlen($notes) > 1000
            || !preg_match('//u', $notes) || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $notes)) {
            throw new InvalidArgumentException('Invalid notes');
        }
        $rule = self::rule($type, $raw['rule'] ?? null);
        return [
            'schema_version' => 1, 'id' => $id, 'name' => trim($name),
            'type' => $type, 'currency' => $currency,
            'status' => 'draft', 'active' => false, 'priority' => $priority,
            'countries' => $countries, 'start_utc' => $start, 'end_utc' => $end,
            'stack_with_coupon' => $stack, 'rule' => $rule, 'notes' => $notes,
            'eligibility_basis' => self::BASIS,
            'requires_publish_owner_gate' => true,
            'requires_legacy_shipping_migration' => $type === 'free_shipping',
            'runtime_enabled' => false,
        ];
    }

    private static function dateUtc($date): ?string {
        if ($date === null) { return null; }
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $date)) {
            throw new InvalidArgumentException('UTC ISO timestamp required');
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $date, new DateTimeZone('UTC'));
        if (!$parsed || $parsed->format('Y-m-d\TH:i:s\Z') !== $date) {
            throw new InvalidArgumentException('Invalid calendar timestamp');
        }
        return $date;
    }

    private static function positive($number, int $max = 1000000000000): int {
        if (!is_int($number) || $number < 1 || $number > $max) {
            throw new InvalidArgumentException('Expected bounded positive integer');
        }
        return $number;
    }
    private static function percent($number): int {
        return self::positive($number,10000);
    }
    private static function productIds($raw): array {
        if (!is_array($raw) || count($raw) < 1 || count($raw) > 100) {
            throw new InvalidArgumentException('Invalid product selector');
        }
        $ids = [];
        foreach ($raw as $id) {
            $id = self::positive($id,2147483647);
            if (isset($ids[$id])) { throw new InvalidArgumentException('Duplicate SKU selector'); }
            $ids[$id] = true;
        }
        return array_keys($ids);
    }
    private static function allowedKeys(array $r,array $fields): void {
        foreach ($r as $key=>$_) {
            if (!in_array($key,$fields,true)) {
                throw new InvalidArgumentException('Unexpected promotion rule field');
            }
        }
    }
    private static function amountOrPercent(array $r): array {
        $fixed = array_key_exists('discount_minor',$r);
        $bps = array_key_exists('discount_bps',$r);
        if ($fixed === $bps) { throw new InvalidArgumentException('Choose fixed or percent award'); }
        return $fixed
            ? ['discount_minor'=>self::positive($r['discount_minor'])]
            : ['discount_bps'=>self::percent($r['discount_bps'])];
    }

    private static function rule(string $type, $rule): array {
        if (!is_array($rule)) { throw new InvalidArgumentException('Campaign rule must be structured'); }
        return match($type) {
            'bogo' => self::bogo($rule),
            'bundle' => self::bundle($rule),
            'spend_save' => self::spendSave($rule),
            'tiered' => self::tiered($rule),
            'free_gift' => self::gift($rule),
            'free_shipping' => self::shipping($rule),
        };
    }
    private static function bogo(array $r): array {
        self::allowedKeys($r,['buy_product_ids','get_product_ids','buy_qty','get_qty','reward_bps']);
        $buy = self::productIds($r['buy_product_ids'] ?? null);
        $get = self::productIds($r['get_product_ids'] ?? null);
        $n = self::positive($r['buy_qty'] ?? null,100);
        $m = self::positive($r['get_qty'] ?? null,100);
        $bps = self::percent($r['reward_bps'] ?? 10000);
        $overlap = array_intersect($buy,$get);
        $sortedBuy=$buy;$sortedGet=$get;sort($sortedBuy);sort($sortedGet);
        if ($overlap && $sortedBuy !== $sortedGet) {
            throw new InvalidArgumentException('Partially overlapping BOGO pools are unsafe');
        }
        return ['buy_product_ids'=>$buy,'get_product_ids'=>$get,'buy_qty'=>$n,'get_qty'=>$m,'reward_bps'=>$bps];
    }
    private static function bundle(array $r): array {
        self::allowedKeys($r,['requires','bundle_price_minor','discount_bps']);
        if (!is_array($r['requires'] ?? null) || count($r['requires']) < 2
            || count($r['requires']) > 25) {
            throw new InvalidArgumentException('Bundle requires 2–25 distinct products');
        }
        $items=[];$seen=[];
        foreach($r['requires'] as $line) {
            if (!is_array($line)) { throw new InvalidArgumentException('Bad bundle component'); }
            self::allowedKeys($line,['product_id','quantity']);
            $id=self::positive($line['product_id']??null,2147483647);
            $qty=self::positive($line['quantity']??null,100);
            if(isset($seen[$id])) {throw new InvalidArgumentException('Repeated bundle component');}
            $seen[$id]=true;$items[]=['product_id'=>$id,'quantity'=>$qty];
        }
        $price = array_key_exists('bundle_price_minor',$r);
        $pct = array_key_exists('discount_bps',$r);
        if ($price === $pct) { throw new InvalidArgumentException('Choose bundle price or percent'); }
        if ($price) {
            $v=$r['bundle_price_minor'];
            if (!is_int($v)||$v<0||$v>1000000000000) {throw new InvalidArgumentException('Invalid bundle price');}
            return ['requires'=>$items,'bundle_price_minor'=>$v];
        }
        return ['requires'=>$items,'discount_bps'=>self::percent($r['discount_bps'])];
    }
    private static function spendSave(array $r): array {
        self::allowedKeys($r,['threshold_minor','discount_minor','discount_bps','product_ids']);
        $out=['threshold_minor'=>self::positive($r['threshold_minor']??null)];
        if (array_key_exists('product_ids',$r)) {$out['product_ids']=self::productIds($r['product_ids']);}
        return $out + self::amountOrPercent($r);
    }
    private static function tiered(array $r): array {
        self::allowedKeys($r,['product_ids','tiers']);
        if (!is_array($r['tiers']??null) || !$r['tiers'] || count($r['tiers'])>20) {
            throw new InvalidArgumentException('Invalid quantity tiers');
        }
        $tiers=[];$previous=0;
        foreach($r['tiers'] as $tier) {
            if(!is_array($tier)) {throw new InvalidArgumentException('Invalid quantity tier');}
            self::allowedKeys($tier,['min_qty','discount_minor','discount_bps']);
            $qty=self::positive($tier['min_qty']??null,5000);
            if($qty<=$previous) {throw new InvalidArgumentException('Tiers must be strictly ascending');}
            $tiers[]=['min_qty'=>$qty]+self::amountOrPercent($tier);
            $previous=$qty;
        }
        $out=['tiers'=>$tiers];
        if (array_key_exists('product_ids',$r)) {$out['product_ids']=self::productIds($r['product_ids']);}
        return $out;
    }
    private static function gift(array $r): array {
        self::allowedKeys($r,['threshold_minor','reward_product_id','max_gift_qty']);
        return [
            'threshold_minor'=>self::positive($r['threshold_minor']??null),
            'reward_product_id'=>self::positive($r['reward_product_id']??null,2147483647),
            'max_gift_qty'=>self::positive($r['max_gift_qty']??1,5)
        ];
    }
    private static function shipping(array $r): array {
        self::allowedKeys($r,['threshold_minor','shipping_method_id']);
        $method=$r['shipping_method_id']??null;
        if (!is_string($method) || !preg_match('/^free_shipping:[1-9][0-9]{0,8}$/D',$method)) {
            throw new InvalidArgumentException('Valid Woo Free Shipping method reference required');
        }
        return ['threshold_minor'=>self::positive($r['threshold_minor']??null),
                'shipping_method_id'=>$method];
    }
}
