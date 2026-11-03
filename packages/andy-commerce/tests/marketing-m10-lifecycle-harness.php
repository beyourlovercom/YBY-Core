<?php
/** Virtual lifecycle only: revision, transitions, trace, bounded history. */
declare(strict_types=1);
define('ABSPATH','/tmp/m10-lifecycle-tests/');
require_once dirname(__DIR__).'/includes/class-andy-commerce-m10-lifecycle.php';
$count=0;$ok=static function(bool $v,string $why)use(&$count):void{
 if(!$v)throw new RuntimeException('M10 lifecycle FAIL '.$why);
 $count++;
};
$draft=['id'=>'sample','active'=>false,'status'=>'draft','revision'=>1];
$next=['review','scheduled','paused','scheduled','ended','archived'];
foreach($next as $state){
 $before=$draft['lifecycle']??'draft';
 $rev=$draft['revision'];
 $draft=Andy_Commerce_M10_Lifecycle::transition($draft,$state,$rev,
    '2026-10-23T07:00:00+00:00','user_7');
 $ok($draft['lifecycle']===$state&&$draft['revision']===$rev+1,$before.' to '.$state);
 $ok($draft['status']==='draft'&&$draft['active']===false,'never actually activated');
 $ok(end($draft['lifecycle_history'])['runtime_applied']===false,'trace is shadow only');
}
$fail=static function(array $x,string $target,int $rev)use($ok):void{
 try{
  Andy_Commerce_M10_Lifecycle::transition($x,$target,$rev,'2026-10-23T07:00:00+00:00','user_7');
  $ok(false,'invalid transition accepted');
 }catch(InvalidArgumentException $e){$ok(true,'invalid transition rejected');}
};
$fail($draft,'review',$draft['revision']);
$original=['active'=>false,'status'=>'draft','revision'=>1];
$fail($original,'scheduled',1);
$fail($original,'review',0);
$bad=$original;$bad['active']=true;$fail($bad,'review',1);
$bad=$original;$bad['status']='active';$fail($bad,'review',1);
$bad=$original;$bad['lifecycle']='unknown';$fail($bad,'review',1);
$bad=$original;$bad['lifecycle_history']=array_fill(0,31,[]);
$fail($bad,'review',1);
$ok(Andy_Commerce_M10_Lifecycle::allowed('archived')===[],'archive terminal');
echo "M10_LIFECYCLE_PASS=$count; REVISION_GUARD=YES; RUNTIME_APPLIED=0\n";
