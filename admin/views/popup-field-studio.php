<?php
/**
 * Inline Field Studio inside the existing Popup Inquiry settings form.
 * No new admin route, form handler, or site-specific plugin variant.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$studio_state = YBY_Popup_Field_Studio::get_state();
$studio_manager = new YBY_Inquiry_Manager();
$studio_fields = $studio_manager->get_field_manager()->get_fields();
$studio_presets = $studio_manager->get_preset_manager()->get_presets();
$studio_boot = array(
	'state' => $studio_state,
	'fields' => $studio_fields,
	'presets' => $studio_presets,
	'global_preset' => isset( $settings['inquiry_preset'] ) ? $settings['inquiry_preset'] : '',
	'last_preset' => sanitize_key( (string) get_option( 'yby_popup_studio_last_selected_preset', '' ) ),
	'site_name' => get_bloginfo( 'name' ),
);
?>
<div id="yby-popup-studio" class="yby-studio" aria-label="弹窗字段配置中心">
	<div class="yby-studio-head">
		<div>
			<div class="yby-studio-eyebrow">ANDY CORE · FIELD STUDIO V2</div>
			<h3>统一字段配置</h3>
			<p>直接在当前弹窗设置页管理：公共标准字段、本站扩展字段、电脑/手机独立显示与必填。不影响其它 WordPress 网站。</p>
		</div>
		<span class="yby-studio-site" id="yby-studio-site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	</div>
	<div class="yby-studio-toolbar">
		<label class="yby-studio-field yby-studio-select-preset"><span>当前编辑的业务表单预设</span><select id="yby-studio-preset" aria-label="当前编辑的业务表单预设"></select></label>
		<button type="button" class="button" id="yby-studio-clone">复制为本站预设</button>
		<button type="button" class="button button-primary" id="yby-studio-new-field">＋ 新增本站业务字段</button>
		<button type="button" class="button" id="yby-studio-activate">应用为 Global Popup</button>
	</div>
	<div class="yby-studio-note" id="yby-studio-note" role="status" aria-live="polite">原有预设默认只读。点击“复制为本站预设”后才会生成独立配置，不覆盖原表单。</div>
	<div class="yby-studio-preset-meta">
		<label class="yby-studio-field"><span>适用页面 Profile ID（逗号分隔）</span><input type="text" id="yby-studio-page-profiles" placeholder="irrigation_wholesale" maxlength="500"></label>
		<span class="yby-studio-profile-hint" id="yby-studio-profile-hint">预设与落地页通过 Page Profile 关联。空白代表全站适用，请谨慎选择。</span>
	</div>
	<div class="yby-studio-matrix">
		<div class="yby-studio-section-head">
			<div><h4>完整字段矩阵</h4><small id="yby-studio-count">加载中…</small></div>
			<span class="yby-studio-legend">启用及必填均可单独设置 · 点击「设置」编辑文字</span>
		</div>
		<div class="yby-studio-table-scroll">
			<table class="yby-studio-table">
				<thead><tr>
					<th rowspan="2">字段名称</th><th rowspan="2">来源 / 类型</th>
					<th colspan="2">电脑端</th><th colspan="2">手机端</th><th rowspan="2">字段设置</th>
				</tr><tr>
					<th>是否启用</th><th>是否必填</th><th>是否启用</th><th>是否必填</th>
				</tr></thead>
				<tbody id="yby-studio-tbody"></tbody>
			</table>
		</div>
		<div class="yby-studio-table-footer"><span>公共字段共享“类型定义”，但本站的配置独立保存。</span><span id="yby-studio-totals"></span></div>
	</div>
	<div class="yby-studio-bottom">
		<section class="yby-studio-panel" aria-label="字段编辑器">
			<div class="yby-studio-section-head">
				<div><h4 id="yby-studio-edit-title">字段属性</h4><small id="yby-studio-edit-desc"></small></div>
				<button type="button" class="button" id="yby-studio-remove-field">删除本站字段</button>
			</div>
			<div class="yby-studio-two-devices">
				<div class="yby-studio-device">
					<h5>▣ 电脑端</h5>
					<label class="yby-studio-field"><span>默认标题</span><input type="text" data-yby-studio-edit="desktop.label" maxlength="120"></label>
					<label class="yby-studio-field"><span>框中默认文字</span><input type="text" data-yby-studio-edit="desktop.placeholder" maxlength="180"></label>
				</div>
				<div class="yby-studio-device">
					<h5>▣ 手机端</h5>
					<label class="yby-studio-field"><span>默认标题</span><input type="text" data-yby-studio-edit="mobile.label" maxlength="120"></label>
					<label class="yby-studio-field"><span>框中默认文字</span><input type="text" data-yby-studio-edit="mobile.placeholder" maxlength="180"></label>
				</div>
			</div>
			<label class="yby-studio-field" id="yby-studio-options-wrap" hidden>
				<span>字段选项（每行一项，适用于下拉、单选、多选）</span>
				<textarea id="yby-studio-options" rows="4" placeholder="Under 1 ha&#10;1–5 ha&#10;5–20 ha"></textarea>
			</label>
			<p class="description">保存前可自由修改；关闭显示后，“必填”自动关闭。真实询盘仍交由原 Inquiry OS 引擎提交。</p>
		</section>
		<section class="yby-studio-panel yby-studio-preview-panel" aria-label="弹窗效果预览">
			<div class="yby-studio-section-head">
				<div><h4>弹窗效果预览</h4><small>仅测试字段与必填，不会发送询盘</small></div>
				<div class="yby-studio-devices">
					<button type="button" class="is-current" data-yby-studio-device="desktop">电脑端</button>
					<button type="button" data-yby-studio-device="mobile">手机端</button>
				</div>
			</div>
			<div class="yby-studio-preview-surface"><div id="yby-studio-preview"></div></div>
		</section>
	</div>
	<div class="yby-studio-actionline">
		<div class="yby-studio-status" id="yby-studio-status" role="status" aria-live="polite">新配置只有点击页面底部原有「保存」按钮后才会写入本站。</div>
		<button type="button" class="button" id="yby-studio-export">查看配置 JSON</button>
		<button type="button" class="button" id="yby-studio-reset">撤销未保存修改</button>
	</div>
	<pre id="yby-studio-json" class="yby-studio-json" hidden></pre>
	<div class="yby-studio-overlay" id="yby-studio-dialog" hidden>
		<div class="yby-studio-dialog" role="dialog" aria-modal="true" aria-labelledby="yby-studio-dialog-title">
			<h4 id="yby-studio-dialog-title">新增本站业务字段</h4>
			<p id="yby-studio-dialog-description">仅加入当前网站的字段库。</p>
			<label class="yby-studio-field" id="yby-studio-template-wrap"><span>字段模板</span><select id="yby-studio-template"><option value="custom">自定义字段</option><option value="farm">Farm Size · 灌溉面积</option><option value="bottle">Bottle Category · 瓶型分类</option><option value="film">Film Size · 膜材尺寸</option></select></label>
			<label class="yby-studio-field"><span>字段名称 / 预设名称</span><input id="yby-studio-new-title" type="text" maxlength="120"></label>
			<label class="yby-studio-field" id="yby-studio-type-wrap"><span>字段类型</span><select id="yby-studio-new-type">
				<option value="text">单行文本</option><option value="email">Email</option>
				<option value="tel">电话</option><option value="number">数字</option>
				<option value="textarea">多行文本</option><option value="select">下拉选择</option>
				<option value="radio">单选</option><option value="multiselect">多选</option>
				<option value="checkbox">勾选项</option>
			</select></label>
			<label class="yby-studio-field" id="yby-studio-new-options-wrap" hidden><span>选项（每行一项）</span><textarea id="yby-studio-new-options" rows="4"></textarea></label>
			<div class="yby-studio-dialog-actions"><button class="button" type="button" id="yby-studio-cancel">取消</button><button class="button button-primary" type="button" id="yby-studio-add-confirm">确定</button></div>
		</div>
	</div>
	<input type="hidden" name="yby_popup_field_studio_payload" id="yby-studio-payload" value="<?php echo esc_attr( wp_json_encode( $studio_state ) ); ?>">
	<input type="hidden" name="yby_popup_field_studio_active" id="yby-studio-selected" value="">
	<script type="application/json" id="yby-studio-bootstrap"><?php echo wp_json_encode( $studio_boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
</div>
