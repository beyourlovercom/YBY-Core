<?php
/**
 * P9-6 isolated writer/read-only principal grant verifier.
 * Pure functions; no WordPress, no database connection and no mail sending.
 * This accepts one synthetic-only MySQL8 database with two distinct users.
 * Hostinger single-user hosting must never be treated as a PASS.
 */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P9_Grant_Preflight {
    private static function deny($reason) {
        return array('status'=>'blocked','reason'=>$reason,
            'can_provision'=>false,'can_write'=>false,
            'can_deploy'=>false,'can_send'=>false);
    }
    private static function exact_grants($grants,$schema,$user,$privileges) {
        if (!is_array($grants) || count($grants)<1 || count($grants)>4) {
            return false;
        }
        $usage=false;$scoped=false;
        foreach($grants as $line) {
            if (!is_string($line)||strlen($line)>800||
                preg_match('/[\\r\\n\\0]/',$line))return false;
            // Backticks are normal quoting on MySQL8 SHOW GRANTS only.
            $line=str_replace(chr(96),'',$line);
            if (!preg_match('/\\AGRANT ([A-Z, ]+) ON (\\*\\.\\*|[A-Za-z0-9_]+\\.\\*) TO ([A-Za-z0-9_]+)@localhost\\z/D',$line,$m)) {
                return false;
            }
            if ($m[3]!==$user)return false;
            $got=array_map('trim',explode(',',$m[1]));
            sort($got);
            if($m[2]==='*.*' && $got===array('USAGE')) {
                if($usage)return false;
                $usage=true;continue;
            }
            $want=$privileges;sort($want);
            if($m[2]===$schema.'.*' && $got===$want && !$scoped) {
                $scoped=true;continue;
            }
            return false;
        }
        return $usage && $scoped;
    }
    public static function verify($input) {
        $keys=array('synthetic_only','schema','writer_user','reader_user',
            'writer_current_user','reader_current_user','writer_grants',
            'reader_grants','network_disabled','wp_db_distinct',
            'p8_reader_unchanged');
        if(!is_array($input)||array_keys($input)!==$keys)return self::deny('input_contract');
        foreach(array('synthetic_only','network_disabled','wp_db_distinct',
            'p8_reader_unchanged') as $k) {
            if($input[$k]!==true)return self::deny('separation_guard');
        }
        foreach(array('schema','writer_user','reader_user',
            'writer_current_user','reader_current_user') as $k) {
            if(!is_string($input[$k])||strlen($input[$k])>64 ||
                !preg_match('/\\A[a-zA-Z][a-zA-Z0-9_]*(@localhost)?\\z/D',$input[$k])) {
                return self::deny('identity_shape');
            }
        }
        if($input['schema']!=='p9_mail_synthetic_uat'||
           $input['writer_user']===$input['reader_user'] ||
           $input['writer_current_user']!==$input['writer_user'].'@localhost'||
           $input['reader_current_user']!==$input['reader_user'].'@localhost') {
            return self::deny('identity_mismatch');
        }
        if(!self::exact_grants($input['writer_grants'],$input['schema'],
            $input['writer_user'],array('SELECT','INSERT','UPDATE'))){
            return self::deny('writer_privilege_drift');
        }
        if(!self::exact_grants($input['reader_grants'],$input['schema'],
            $input['reader_user'],array('SELECT'))) {
            return self::deny('reader_privilege_drift');
        }
        return array('status'=>'synthetic_lab_grants_verified',
            'scope'=>'p9_private_mysql8_lab_only',
            'two_distinct_identities'=>true,'writer_dml_scoped'=>true,
            'reader_select_only'=>true,'network_disabled'=>true,
            'contains_credentials'=>false,'real_dev_verified'=>false,
            'can_provision'=>false,'can_write'=>false,
            'can_deploy'=>false,'can_send'=>false);
    }
}
