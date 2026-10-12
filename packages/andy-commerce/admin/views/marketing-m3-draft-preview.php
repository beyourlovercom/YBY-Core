<?php
/** Marketing M3 operator list V2 — Dev-only. Business records remain OFF. */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) { wp_die('Insufficient permissions.'); }
$m3_result=is_array($m3_result??null)?$m3_result:['items'=>[],'total'=>0,'page'=>1,'per_page'=>25];
$m3_counts=is_array($m3_counts??null)?$m3_counts:['draft'=>0,'archived'=>0];
$m3_view=($m3_view??'all')==='trash'?'trash':'all';
$m3_search=(string)($m3_search??'');
$rows=$m3_result['items'];
$archived=$m3_view==='trash';
$types=['spend_save'=>'Spend & Save','bogo'=>'BOGO · 买赠','bundle'=>'Bundle · 组合','tiered'=>'Tiered · 阶梯','free_gift'=>'Free Gift · 满赠','free_shipping'=>'Free Shipping · 免邮'];
$list_url=static function(array $args=[]):string {
  return add_query_arg(array_merge(['page'=>'andy-commerce','tab'=>'marketing-promotions'],$args),admin_url('admin.php'));
};
$nonce_action='andy_commerce_m3_draft_write';
?>
<style>
.m3-v2{--m3-green:#006241;--m3-line:#e2e7e5;--m3-ink:#202a27;--m3-muted:#62716c;max-width:1480px;margin:20px 18px 30px 0;color:var(--m3-ink);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif}
.m3-v2 *{box-sizing:border-box}
.m3-v2 .m3-panel{background:#fff;border:1px solid #e2e8e8;border-radius:15px;box-shadow:0 4px 20px rgba(35,55,49,.06);padding:28px 30px}
.m3-v2 .m3-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.m3-v2 .m3-main-title{font-size:23px;line-height:1.25;font-weight:750;margin:0}
.m3-v2 .m3-muted{font-size:12px;color:var(--m3-muted)}
.m3-v2 .m3-add{background:var(--m3-green);color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:5px;border-radius:5px;padding:11px 17px;font-weight:650}
.m3-v2 .m3-add:hover,.m3-v2 .m3-add:focus{background:#004f34;color:white}
.m3-v2 .m3-head-main{display:flex;align-items:center;gap:17px;flex-wrap:wrap}
.m3-v2 .m3-head-tools{display:flex;align-items:center;gap:9px}
.m3-v2 .m3-search{display:flex;align-items:center;border:1px solid #d7e0df;border-radius:50px;height:39px;max-width:270px;min-width:195px;background:#fff}
.m3-v2 .m3-search input{border:0!important;box-shadow:none!important;background:transparent!important;min-width:0;width:100%;padding:7px 13px}
.m3-v2 .m3-search button{border:0;background:transparent;color:#667972;cursor:pointer;margin-right:5px}
.m3-v2 .m3-gear{border:0;background:transparent;cursor:pointer;color:#293a34;padding:7px}
.m3-v2 .m3-tabs{display:flex;gap:8px;margin:21px 0 20px;align-items:center;flex-wrap:wrap}
.m3-v2 .m3-tab{padding:8px 15px;border-radius:23px;border:1px solid #ecf0ee;text-decoration:none;color:#57645e;font-weight:600;background:#fff}
.m3-v2 .m3-tab.is-active{color:var(--m3-green);border-color:#b8d9c9;background:#eef8f3}
.m3-v2 .m3-notice{border-left:3px solid var(--m3-green);background:#f6faf8;border-radius:3px;color:#435f50;padding:9px 14px;margin:16px 0;font-size:12px}
.m3-v2 .m3-list-settings{padding:13px 15px;background:#f7faf8;border:1px solid var(--m3-line);border-radius:8px;margin:14px 0}
.m3-v2 .m3-list-settings label{display:inline-flex;gap:5px;align-items:center;margin-right:13px}
.m3-v2 .m3-table-wrap{overflow-x:auto}
.m3-v2 table{width:100%;border-collapse:separate;border-spacing:0 8px;table-layout:fixed;min-width:930px}
.m3-v2 th{text-align:left;color:#596860;font-weight:650;font-size:12px;padding:7px 11px 11px}
.m3-v2 td{font-size:13px;padding:14px 11px;vertical-align:middle;background:white;border-top:1px solid #e5e9e7;border-bottom:1px solid #e5e9e7}
.m3-v2 tr td:first-child{border-left:1px solid #e5e9e7;border-radius:8px 0 0 8px}
.m3-v2 tr td:last-child{border-right:1px solid #e5e9e7;border-radius:0 8px 8px 0}
.m3-v2 .m3-name{font-size:13px;color:#26332e;font-weight:700;line-height:1.35;text-decoration:none}
.m3-v2 .m3-name:hover{color:var(--m3-green)}
.m3-v2 .m3-description{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;color:#485650}
.m3-v2 .m3-status{display:inline-block;font-weight:750;font-size:10px;letter-spacing:.02em;padding:5px 7px;border-radius:4px;background:#fff2c9;color:#8c6510}
.m3-v2 .m3-status.is-archived{background:#ecefed;color:#687470}
.m3-v2 .m3-date{font-size:12px;color:#5c6b64}
.m3-v2 .m3-row-actions{display:flex;gap:12px;align-items:center;flex-wrap:nowrap}
.m3-v2 .m3-icon-button{display:inline-flex;align-items:center;justify-content:center;border:0;background:transparent;color:#30483f;padding:6px;cursor:pointer;text-decoration:none;border-radius:5px}
.m3-v2 .m3-icon-button:hover{background:#edf5ef;color:var(--m3-green)}
.m3-v2 .m3-icon-button:disabled{opacity:.38;cursor:not-allowed}
.m3-v2 .m3-inline-form{display:inline;margin:0}
.m3-v2 .m3-switch{display:inline-block;position:relative;width:36px;height:20px;border-radius:25px;background:#c8cfcb;opacity:.65;cursor:not-allowed}
.m3-v2 .m3-switch:before{content:"";position:absolute;width:14px;height:14px;border-radius:50%;background:#fff;top:3px;left:3px;box-shadow:0 1px 2px #999}
.m3-v2 .m3-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:17px;flex-wrap:wrap;color:#68746f;font-size:12px}
.m3-v2 .m3-pages a{display:inline-block;text-decoration:none;border:1px solid var(--m3-line);border-radius:5px;padding:7px 10px;margin-left:5px}
.m3-v2 .m3-bulk{display:flex;align-items:center;gap:10px;margin:5px 0 4px}
.m3-v2 .m3-bulk button{font-size:12px;border:1px solid #d0d8d3;border-radius:5px;background:#fff;color:#32433b;padding:5px 12px;cursor:pointer}
.m3-v2 .m3-bulk button:disabled{opacity:.4;cursor:not-allowed}
.m3-v2 .m3-empty{text-align:center;padding:35px;color:#68746f}
.m3-v2 [data-m3-col-hidden="true"]{display:none!important}
.m3-v2 .m3-row-check,.m3-v2 #m3-check-all{accent-color:var(--m3-green)}
@media(max-width:850px){
 .m3-v2{margin:10px 10px 24px 0}
 .m3-v2 .m3-panel{padding:16px 14px;border-radius:12px}
 .m3-v2 .m3-main-title{font-size:19px}
 .m3-v2 .m3-head-main,.m3-v2 .m3-head-tools{width:100%}
 .m3-v2 .m3-search{flex:1;max-width:none}
 .m3-v2 .m3-table-wrap{overflow:visible}
 .m3-v2 table{display:block;min-width:0;border-spacing:0}
 .m3-v2 thead{display:none}
 .m3-v2 tbody{display:grid;gap:12px}
 .m3-v2 tr{display:grid;grid-template-columns:1fr 1fr;gap:8px 10px;border:1px solid var(--m3-line);border-radius:11px;padding:15px;background:#fff;min-width:0}
 .m3-v2 td{display:block;padding:0!important;border:0!important;border-radius:0!important;min-width:0}
 .m3-v2 td[data-col="select"]{display:none}
 .m3-v2 td[data-col="title"],.m3-v2 td[data-col="description"],.m3-v2 td[data-col="actions"]{grid-column:1/-1}
 .m3-v2 td[data-col="title"]{font-size:15px}
 .m3-v2 td[data-col="description"]{margin:3px 0 4px}
 .m3-v2 td[data-col="status"],.m3-v2 td[data-col="usage"],.m3-v2 td[data-col="schedule"],.m3-v2 td[data-col="toggle"]{font-size:12px}
 .m3-v2 td[data-col="usage"]:before{content:"使用次数";display:block;color:#7a8680;font-size:11px}
 .m3-v2 td[data-col="schedule"]:before{content:"计划时间";display:block;color:#7a8680;font-size:11px}
 .m3-v2 td[data-col="actions"]{margin-top:8px;border-top:1px solid var(--m3-line)!important;padding-top:9px!important}
 .m3-v2 .m3-row-actions{gap:20px}
 .m3-v2 .m3-description{-webkit-line-clamp:3}
 .m3-v2 .m3-bulk{display:none}
}
</style>
<div class="wrap m3-v2" id="m3-campaign-list">
  <div class="m3-panel">
    <div class="m3-head">
      <div class="m3-head-main">
        <h2 class="m3-main-title">Marketing offers</h2>
        <a class="m3-add" href="<?php echo esc_url($list_url(['m3_edit'=>'new'])); ?>">+ Add new</a>
      </div>
      <div class="m3-head-tools">
        <form class="m3-search" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" role="search">
          <input type="hidden" name="page" value="andy-commerce">
          <input type="hidden" name="tab" value="marketing-promotions">
          <input type="hidden" name="m3_view" value="<?php echo esc_attr($m3_view); ?>">
          <input aria-label="搜索活动" type="search" name="m3_q" maxlength="100" placeholder="Search / 搜索活动" value="<?php echo esc_attr($m3_search); ?>">
          <button type="submit" aria-label="搜索"><span class="dashicons dashicons-search"></span></button>
        </form>
        <button type="button" class="m3-gear" aria-label="显示设置" aria-expanded="false" aria-controls="m3-col-settings" id="m3-settings-button" title="列显示设置"><span class="dashicons dashicons-admin-generic"></span></button>
      </div>
    </div>
    <div class="m3-tabs" role="navigation" aria-label="Campaign status">
      <a class="m3-tab <?php echo $archived?'':'is-active'; ?>" <?php if(!$archived): ?>aria-current="page"<?php endif; ?> href="<?php echo esc_url($list_url(['m3_view'=>'all'])); ?>">All (<?php echo esc_html((string)($m3_counts['draft']??0)); ?>)</a>
      <a class="m3-tab <?php echo $archived?'is-active':''; ?>" <?php if($archived): ?>aria-current="page"<?php endif; ?> href="<?php echo esc_url($list_url(['m3_view'=>'trash'])); ?>">Trash (<?php echo esc_html((string)($m3_counts['archived']??0)); ?>)</a>
      <span class="m3-muted">Dev draft management · 全部优惠 OFF</span>
    </div>
    <?php if(isset($_GET['m3_result']) && in_array($_GET['m3_result'],['archived','bulk_archived'],true)): ?>
      <div class="notice notice-success inline"><p>已移入 Trash，历史记录保留，未开启任何优惠。</p></div>
    <?php endif; ?>
    <div class="m3-list-settings" id="m3-col-settings" hidden>
       <strong>显示列：</strong>
       <?php foreach(['description'=>'Description','usage'=>'Conversion','schedule'=>'Schedule'] as $field=>$label): ?>
         <label><input type="checkbox" data-m3-column="<?php echo esc_attr($field); ?>" checked> <?php echo esc_html($label); ?></label>
       <?php endforeach; ?>
       <span class="m3-muted">仅影响当前页面布局</span>
    </div>
    <div class="m3-notice">所有优惠 OFF · 复制仅生成新草稿 · 删除仅移入 Trash · 转化次数尚未接入真实运行时统计 · BYL49 保持不变</div>
    <form id="m3-bulk-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
       <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
       <input type="hidden" name="draft_action" value="bulk_archive">
       <?php wp_nonce_field($nonce_action); ?>
    </form>
    <?php if(!$archived): ?>
    <div class="m3-bulk"><button id="m3-bulk-archive" type="submit" form="m3-bulk-form" disabled>将所选活动移入 Trash</button><span id="m3-selected-count" class="m3-muted">尚未选择</span></div>
    <?php endif; ?>
    <div class="m3-table-wrap">
      <table aria-label="Marketing campaigns">
        <colgroup><col style="width:3%"><col style="width:17%"><col style="width:22%"><col style="width:10%"><col style="width:9%"><col style="width:14%"><col style="width:17%"><col style="width:8%"></colgroup>
        <thead><tr>
          <th scope="col"><input type="checkbox" id="m3-check-all" <?php echo $archived?'disabled':''; ?> aria-label="选择本页活动"></th>
          <th scope="col">Campaign</th><th scope="col" data-col="description">Description</th>
          <th scope="col">Status</th><th scope="col" data-col="usage">Conversion</th>
          <th scope="col" data-col="schedule">Schedule</th><th scope="col">Actions</th><th scope="col">Enabled</th>
        </tr></thead>
        <tbody>
          <?php foreach($rows as $draft):
             $id=(string)($draft['campaign_id']??'');
             $revision=(int)($draft['revision']??0);
             $isTrash=($draft['status']??'')==='archived';
             $start=(string)($draft['start_utc']??'');
             $end=(string)($draft['end_utc']??'');
             $range=($start!==''||$end!=='') ? (($start!==''?substr($start,0,10):'—').' → '.($end!==''?substr($end,0,10):'—')):'—';
          ?>
          <tr>
            <td data-col="select"><?php if(!$isTrash): ?><input class="m3-row-check" type="checkbox" form="m3-bulk-form" name="campaign_versions[<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr((string)$revision); ?>" aria-label="选择 <?php echo esc_attr((string)($draft['name']??$id)); ?>"><?php endif; ?></td>
            <td data-col="title">
              <?php if(!$isTrash): ?>
              <a class="m3-name" href="<?php echo esc_url($list_url(['m3_edit'=>$id])); ?>"><?php echo esc_html((string)($draft['name']??'')); ?></a>
              <?php else: ?><strong class="m3-name"><?php echo esc_html((string)($draft['name']??'')); ?></strong><?php endif; ?>
              <div class="m3-muted"><?php echo esc_html($types[$draft['campaign_type']??'']??(string)($draft['campaign_type']??'')); ?></div>
            </td>
            <td data-col="description"><span class="m3-description" title="<?php echo esc_attr((string)($draft['description']??'')); ?>"><?php echo esc_html((string)($draft['summary']??'仅草稿')); ?><?php if(!empty($draft['description'])): ?><span class="m3-muted"> · <?php echo esc_html((string)$draft['description']); ?></span><?php endif; ?></span></td>
            <td data-col="status"><span class="m3-status <?php echo $isTrash?'is-archived':''; ?>"><?php echo $isTrash?'ARCHIVED':'DRAFT'; ?></span></td>
            <td data-col="usage" title="尚未接入实际优惠使用统计">— <span class="m3-muted">未接入</span></td>
            <td data-col="schedule"><span class="m3-date"><?php echo esc_html($range); ?></span><?php if($range!=='—'): ?><div class="m3-muted">仅草稿计划 · UTC</div><?php endif; ?></td>
            <td data-col="actions"><div class="m3-row-actions">
              <?php if(!$isTrash): ?>
              <a class="m3-icon-button" title="编辑活动" aria-label="编辑 <?php echo esc_attr((string)$draft['name']); ?>" href="<?php echo esc_url($list_url(['m3_edit'=>$id])); ?>"><span class="dashicons dashicons-edit"></span></a>
              <form class="m3-inline-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
                <input type="hidden" name="draft_action" value="duplicate">
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr($id); ?>">
                <input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>">
                <?php wp_nonce_field($nonce_action); ?>
                <button type="submit" class="m3-icon-button" title="复制为新草稿" aria-label="复制 <?php echo esc_attr((string)$draft['name']); ?>"><span class="dashicons dashicons-admin-page"></span></button>
              </form>
              <form class="m3-inline-form m3-archive-single" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
                <input type="hidden" name="draft_action" value="archive">
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr($id); ?>">
                <input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>">
                <?php wp_nonce_field($nonce_action); ?>
                <button type="submit" class="m3-icon-button" title="移入 Trash（可保留审计记录）" aria-label="归档 <?php echo esc_attr((string)$draft['name']); ?>"><span class="dashicons dashicons-trash"></span></button>
              </form>
              <?php else: ?>
              <span class="m3-muted">已归档 · 保留记录</span>
              <?php endif; ?>
            </div></td>
            <td data-col="toggle"><span class="m3-switch" role="switch" aria-checked="false" aria-disabled="true" aria-label="活动未启用，当前无法开启" title="Owner 发布门禁未通过，暂不支持启用"></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$rows): ?><tr><td colspan="8" class="m3-empty"><?php echo $archived?'Trash 中暂无归档活动。':($m3_search!==''?'没有找到匹配的活动。':'还没有活动草稿，点击 Add new 创建。'); ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="m3-footer">
       <div>共 <?php echo esc_html((string)$m3_result['total']); ?> 条 · 第 <?php echo esc_html((string)$m3_result['page']); ?> 页 · 每页 25 条 <span class="m3-muted">· 使用次数待运行时接入</span></div>
       <div class="m3-pages">
       <?php if($m3_result['page']>1): ?><a href="<?php echo esc_url($list_url(['m3_view'=>$m3_view,'m3_q'=>$m3_search,'m3_page'=>$m3_result['page']-1])); ?>">← 上一页</a><?php endif; ?>
       <?php if($m3_result['total']>$m3_result['page']*$m3_result['per_page']): ?><a href="<?php echo esc_url($list_url(['m3_view'=>$m3_view,'m3_q'=>$m3_search,'m3_page'=>$m3_result['page']+1])); ?>">下一页 →</a><?php endif; ?>
       </div>
    </div>
  </div>
</div>
<script>
(function(){
 const root=document.getElementById('m3-campaign-list');if(!root)return;
 const all=root.querySelector('#m3-check-all'),checks=Array.from(root.querySelectorAll('.m3-row-check'));
 const bulk=root.querySelector('#m3-bulk-archive'),selected=root.querySelector('#m3-selected-count');
 function sync(){const n=checks.filter(x=>x.checked).length;
   if(bulk)bulk.disabled=n===0;
   if(selected)selected.textContent=n?'已选择 '+n+' 条':'尚未选择';
   if(all){all.checked=n>0&&n===checks.length;all.indeterminate=n>0&&n<checks.length;}
 }
 if(all)all.addEventListener('change',()=>{checks.forEach(x=>x.checked=all.checked);sync();});
 checks.forEach(x=>x.addEventListener('change',sync));
 const bulkForm=root.querySelector('#m3-bulk-form');
 if(bulkForm)bulkForm.addEventListener('submit',e=>{
   const n=checks.filter(x=>x.checked).length;
   if(!n || !confirm('确认将 '+n+' 条草稿移入 Trash？不会永久删除记录。'))e.preventDefault();
 });
 root.querySelectorAll('.m3-archive-single').forEach(f=>f.addEventListener('submit',e=>{
   if(!confirm('确认将该活动移入 Trash？原有审计记录会保留。'))e.preventDefault();
 }));
 const gear=root.querySelector('#m3-settings-button'),panel=root.querySelector('#m3-col-settings');
 if(gear&&panel)gear.addEventListener('click',()=>{panel.hidden=!panel.hidden;gear.setAttribute('aria-expanded',String(!panel.hidden));});
 root.querySelectorAll('[data-m3-column]').forEach(input=>input.addEventListener('change',()=>{
    root.querySelectorAll('[data-col="'+input.dataset.m3Column+'"]').forEach(el=>{
      if(input.checked)el.removeAttribute('data-m3-col-hidden');else el.setAttribute('data-m3-col-hidden','true');
    });
 }));
 sync();
})();
</script>
