<?php
/**
 * R13 reference FX quote gate. Data comes from independently retrieved,
 * validated central-bank reference observations. No network calls in checkout.
 * Sandbox-only adapter: NOT an authorization to enable live payment.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_R13_FX_Quote_Guard {
    public const MAX_RETRIEVAL_AGE = 21600; // six-hour cache for shopper quote
    public const MAX_OBSERVATION_AGE = 604800; // permits weekends & holidays
    public static function validate(array $input, int $now): array {
        if (($input['rate_source']??null)!==Andy_Commerce_Currency_Reference_FX_V1::SOURCE
            || ($input['base']??null)!=='USD'
            || !is_int($now)||$now<1
            || !is_int($input['retrieved_at_utc']??null)
            || $now-$input['retrieved_at_utc']<0
            || $now-$input['retrieved_at_utc']>self::MAX_RETRIEVAL_AGE)
            throw new InvalidArgumentException('Reference FX missing or stale fetch');
        $rates=$input['rate_micros']??null;$dates=$input['quote_dates']??null;
        if (!is_array($rates)||!is_array($dates)||count($rates)!==5
            || count($dates)!==4 || ($rates['USD']??null)!==1000000)
            throw new InvalidArgumentException('Incomplete USD reference FX');
        foreach(['EUR','GBP','AUD','CAD'] as $cur) {
            $m=$rates[$cur]??null;
            if(!is_int($m)||$m<100000||$m>10000000
                || !is_string($dates[$cur]??null)
                || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/D',$dates[$cur]))
                throw new InvalidArgumentException('Invalid reference FX currency or observation');
            $d=DateTimeImmutable::createFromFormat('!Y-m-d',$dates[$cur],new DateTimeZone('UTC'));
            if (!$d || $d->format('Y-m-d')!==$dates[$cur]
                || $now-$d->getTimestamp()>self::MAX_OBSERVATION_AGE
                || $d->getTimestamp()-$now>86400)
                throw new InvalidArgumentException('Reference FX observation date out of bounds');
        }
        return [
            'rate_source'=>$input['rate_source'],'base'=>'USD',
            'retrieved_at_utc'=>$input['retrieved_at_utc'],
            'quote_dates'=>$dates,'rate_micros'=>$rates,
            'snapshot_hash'=>hash('sha256',wp_json_encode([
                $input['rate_source'],$input['retrieved_at_utc'],$dates,$rates,
            ])),
            'is_settlement_rate'=>false,
        ];
    }
    public static function from_file(string $file, int $now): array {
        if (!is_file($file)||!is_readable($file)||filesize($file)>8192
            || filesize($file)<64) throw new InvalidArgumentException('Missing reference FX local snapshot');
        $body=file_get_contents($file);
        if(!is_string($body))throw new InvalidArgumentException('FX snapshot read failure');
        $d=json_decode($body,true,20);
        if(!is_array($d))throw new InvalidArgumentException('Invalid reference FX JSON');
        return self::validate($d,$now);
    }
    public static function selected_rate(string $currency): array {
        if(!in_array($currency,['USD','EUR','GBP','AUD','CAD'],true))
            throw new InvalidArgumentException('Unsupported requested currency');
        if(!defined('AICC_CURRENCY_R13_FX_SNAPSHOT_FILE')
            || !is_string(AICC_CURRENCY_R13_FX_SNAPSHOT_FILE)
            || !Andy_Commerce_Currency_Runtime_C1::allowed())
            throw new InvalidArgumentException('Reference FX runtime not isolated');
        $file=AICC_CURRENCY_R13_FX_SNAPSHOT_FILE;
        $validated=self::from_file($file,time());
        return [
            'currency'=>$currency,'rate_micros'=>$validated['rate_micros'][$currency],
            'rate_source'=>$validated['rate_source'],
            'retrieved_at_utc'=>$validated['retrieved_at_utc'],
            'quote_date'=>$currency==='USD'?'':$validated['quote_dates'][$currency],
            'snapshot_hash'=>$validated['snapshot_hash'],
            'is_settlement_rate'=>false,
        ];
    }
}
