<?php
/** Pure virtual campaign lifecycle; no active Woo promotion state. */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
final class Andy_Commerce_M10_Lifecycle {
    private const NEXT=[
        'draft'=>['review','archived'],
        'review'=>['draft','scheduled','archived'],
        'scheduled'=>['paused','ended','archived'],
        'paused'=>['scheduled','ended','archived'],
        'ended'=>['archived'],
        'archived'=>[]
    ];
    public static function allowed(string $state):array {
        return self::NEXT[$state]??[];
    }
    public static function transition(array $row,string $next,int $revision,string $utc,string $actor):array {
        $current=$row['lifecycle']??'draft';
        if(($row['active']??null)!==false||($row['status']??null)!=='draft'
            ||!is_string($current)||!isset(self::NEXT[$current])
            ||!in_array($next,self::NEXT[$current],true)
            ||!is_int($row['revision']??null)||$row['revision']!==$revision
            ||!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/D',$utc)
            ||$actor===''||strlen($actor)>64) {
            throw new InvalidArgumentException('Invalid virtual lifecycle transition');
        }
        $history=$row['lifecycle_history']??[];
        if(!is_array($history)||count($history)>30)throw new InvalidArgumentException('History capacity exceeded');
        $history[]=['from'=>$current,'to'=>$next,'at_utc'=>$utc,'actor'=>$actor,
            'mode'=>'dev_shadow_only','runtime_applied'=>false];
        $row['lifecycle']=$next;$row['lifecycle_history']=$history;
        $row['revision']++;$row['active']=false;$row['status']='draft';
        return $row;
    }
}
