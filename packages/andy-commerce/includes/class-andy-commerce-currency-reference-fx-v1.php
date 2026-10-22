<?php
/**
 * Currency OS M2: bounded reference-FX feed. Only invoked explicitly
 * on Dev admin preview, never on cart/checkout/payment or website traffic.
 * Public central-bank reference rates are NOT merchant settlement rates.
 */
declare(strict_types=1);
if ( ! defined('ABSPATH') ) { exit; }

final class Andy_Commerce_Currency_Reference_FX_V1 {
    public const SOURCE = 'FRANKFURTER_REFERENCE';
    public const ENDPOINT = 'https://api.frankfurter.dev/v2/rates?base=USD&quotes=EUR,GBP,AUD,CAD';
    private const QUOTES = ['EUR','GBP','AUD','CAD'];
    private const MAX_AGE_SECONDS = 7 * 86400;

    public static function parse(array $rows, int $retrievedAt): array {
        if ($retrievedAt < 1 || count($rows) < 4 || count($rows) > 20) {
            throw new InvalidArgumentException('Invalid reference FX observations');
        }
        $rates = ['USD'=>1000000];
        $dates = [];
        foreach ($rows as $row) {
            if ( ! is_array($row)
                || ($row['base'] ?? null) !== 'USD'
                || ! in_array($row['quote'] ?? null, self::QUOTES, true)
                || ! isset($row['date'],$row['rate'])
                || ! is_string($row['date'])
                || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $row['date'])
                || (! is_float($row['rate']) && ! is_int($row['rate']))
                || ! is_finite((float)$row['rate'])
                || (float)$row['rate'] <= 0.1 || (float)$row['rate'] >= 10.0) {
                throw new InvalidArgumentException('Invalid reference FX currency/rate row');
            }
            $code = $row['quote'];
            if (isset($rates[$code])) {
                throw new InvalidArgumentException('Duplicate reference currency');
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d',$row['date'],new DateTimeZone('UTC'));
            if ( ! $date || $date->format('Y-m-d') !== $row['date'] ) {
                throw new InvalidArgumentException('Invalid reference FX date');
            }
            $secondsAgo = $retrievedAt - $date->getTimestamp();
            if ($secondsAgo > self::MAX_AGE_SECONDS || $secondsAgo < -86400) {
                throw new InvalidArgumentException('Stale or future reference FX date');
            }
            $micro = (int)round(((float)$row['rate']) * 1000000, 0, PHP_ROUND_HALF_UP);
            if ($micro < 100000 || $micro > 10000000) {
                throw new InvalidArgumentException('Reference FX micro rate outside safe bounds');
            }
            $rates[$code] = $micro;
            $dates[$code] = $row['date'];
        }
        foreach(self::QUOTES as $required) {
            if (!isset($rates[$required])) {
                throw new InvalidArgumentException('Incomplete five-currency FX snapshot');
            }
        }
        return [
            'base'=>'USD',
            'rate_source'=>self::SOURCE,
            'retrieved_at_utc'=>$retrievedAt,
            'quote_dates'=>$dates,
            'rate_micros'=>$rates,
            'mode'=>'DEV_READONLY_REFERENCE_NOT_PAYMENT',
        ];
    }

    public static function fetch_readonly(int $retrievedAt): array {
        if (!function_exists('wp_safe_remote_get')
            || !function_exists('wp_remote_retrieve_response_code')
            || !function_exists('wp_remote_retrieve_body')
            || !function_exists('is_wp_error')) {
            throw new RuntimeException('WordPress HTTP client unavailable');
        }
        $response = wp_safe_remote_get(self::ENDPOINT, [
            'timeout'=>8,
            'redirection'=>0,
            'limit_response_size'=>16384,
            'headers'=>['Accept'=>'application/json'],
        ]);
        if (is_wp_error($response)
            || wp_remote_retrieve_response_code($response) !== 200) {
            throw new RuntimeException('Reference FX provider request failed');
        }
        $raw = wp_remote_retrieve_body($response);
        if (!is_string($raw) || strlen($raw) > 16384 || $raw === '') {
            throw new RuntimeException('Reference FX response exceeds allowed size');
        }
        $data = json_decode($raw, true, 20);
        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException('Unexpected reference FX response shape');
        }
        return self::parse($data,$retrievedAt);
    }
}
