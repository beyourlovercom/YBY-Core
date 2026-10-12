<?php
/** OFF-only Marketing campaign drafts list. Only rendered on gated Dev admin. */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) { wp_die('Insufficient permissions.'); }
$drafts = isset($drafts) && is_array($drafts) ? $drafts : [];
$types=['spend_save'=>'满减','bogo'=>'买赠','bundle'=>'捆绑组合','tiered'=>'多买多折','free_gift'=>'满赠','free_shipping'=>'满额免邮'];
?>
<div class="wrap m3-list" style="max-width:1120px;font-family:-apple-system,BlinkMacSystemFont,'PingFang SC',sans-serif;color:#161a17">
    <h2>营销活动管理 <span style="font-size:13px;color:#69736c">· Dev 草稿</span></h2>
    <?php if(isset($_GET['m3_result']) && $_GET['m3_result']==='archived'): ?>
      <div class="notice notice-success inline"><p>草稿已归档，活动始终保持 OFF。</p></div>
    <?php endif; ?>
    <div class="notice notice-info inline"><p><strong>当前所有营销活动均未启用。</strong> 可以创建、保存、编辑和归档草稿；暂不能真正发布或发放优惠。现有 BYL49 不变。</p></div>
    <p><a class="button button-primary" style="background:#006241;border-color:#006241;padding:3px 17px" href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m3_edit'=>'new'],admin_url('admin.php'))); ?>">+ 创建活动草稿</a></p>
    <div style="overflow-x:auto;background:white;border:1px solid #dfe3e0;border-radius:10px">
        <table class="widefat striped" style="min-width:650px;border:none">
           <thead><tr><th>活动名称</th><th>优惠类型</th><th>活动规则</th><th>状态</th><th>版本</th><th>操作</th></tr></thead>
           <tbody>
           <?php foreach($drafts as $draft): ?>
             <?php if(($draft['status']??'')!=='draft'||!empty($draft['active'])) continue; ?>
             <tr>
               <td><strong><?php echo esc_html((string)($draft['name']??'')); ?></strong>
                   <div style="font-size:11px;color:#65716a"><?php echo esc_html((string)($draft['campaign_id']??'')); ?></div>
               </td>
               <td><?php echo esc_html($types[$draft['campaign_type']??'']??(string)($draft['campaign_type']??'')); ?></td>
               <td><?php echo esc_html((string)($draft['summary']??'仅草稿')); ?></td>
               <td><strong>OFF · 草稿</strong></td>
               <td><?php echo esc_html((string)($draft['revision']??'')); ?></td>
               <td><a href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m3_edit'=>(string)($draft['campaign_id']??'')],admin_url('admin.php'))); ?>">编辑规则</a></td>
             </tr>
           <?php endforeach; ?>
           <?php if(!$drafts): ?><tr><td colspan="6">尚无活动草稿。点击上方「创建活动草稿」。</td></tr><?php endif; ?>
           </tbody>
        </table>
    </div>
    <p class="description">优惠规则显示为运营可读文字；底层规则以版本化草稿保存。所有活动保持 OFF，无法发布。</p>
</div>
